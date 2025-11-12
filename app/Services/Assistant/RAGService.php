<?php

namespace App\Services\Assistant;

use App\Models\KbArticle;
use App\Models\KbChunk;
use App\Services\Assistant\Embeddings\SimpleEmbeddingGenerator;
use App\Services\Assistant\Retrieval\HybridRetriever;
use App\Services\Assistant\Retrieval\QueryRefiner;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RAGService
{
    public const CACHE_PREFIX = 'rag_hybrid_';

    private const MIN_CONFIDENCE = 0.32;
    private const MMR_K = 5;
    private const RERANK_TOP_K = 3;
    private const MAX_SOURCES = 3;
    private const CACHE_SECONDS = 900;
    private const MAX_SNIPPET_LENGTH = 260;

    private SimpleEmbeddingGenerator $embedder;
    private ?HybridRetriever $hybridRetriever;
    private bool $hybridEnabled;
    private QueryRefiner $queryRefiner;

    public function __construct(
        ?SimpleEmbeddingGenerator $embedder = null,
        ?HybridRetriever $hybridRetriever = null,
        ?QueryRefiner $queryRefiner = null
    ) {
        $this->embedder = $embedder ?? new SimpleEmbeddingGenerator();
        $this->hybridRetriever = $hybridRetriever;
        $this->hybridEnabled = (bool) config('rag.enabled', true);
        $this->queryRefiner = $queryRefiner ?? new QueryRefiner();
        $this->ensureDefaultKnowledgeBase();
    }

    public function search(string $query): array
    {
        if (!Schema::hasTable('kb_articles') || !Schema::hasTable('kb_chunks')) {
            return $this->fallback();
        }

        $normalized = Str::of($query)->squish()->value();

        if ($normalized === '') {
            return $this->fallback();
        }

        $refined = $this->queryRefiner->refine($normalized);
        $primaryQuery = $refined['primary'] ?? $normalized;
        $queryVariants = array_filter(array_merge([$primaryQuery], $refined['variants'] ?? [], $refined['keywords'] ?? []));
        $queryTokens = $this->tokensFromVariants($queryVariants);
        $keywordTokens = $this->tokensFromVariants($refined['keywords'] ?? []);

        if ($queryTokens === []) {
            return $this->fallback();
        }

        if ($this->shouldUseHybrid()) {
            $hybridResult = $this->hybridRetriever?->search($primaryQuery, self::MAX_SOURCES);

            if (is_array($hybridResult)) {
                if (($hybridResult['success'] ?? false) === true) {
                    return $this->formatHybridSuccess(
                        $hybridResult['documents'] ?? [],
                        (float) ($hybridResult['confidence'] ?? 0.0)
                    );
                }

                if (($hybridResult['reason'] ?? null) === 'low_confidence') {
                    return $this->formatHybridLowConfidence(
                        $hybridResult['documents'] ?? [],
                        $normalized,
                        (float) ($hybridResult['confidence'] ?? 0.0),
                        $hybridResult['suggested_titles'] ?? []
                    );
                }
            }
        }

        $cacheKey = self::CACHE_PREFIX . md5(Str::lower(implode('|', $queryVariants)));

        return Cache::remember($cacheKey, self::CACHE_SECONDS, function () use ($primaryQuery, $queryTokens, $keywordTokens, $refined, $normalized) {
            $chunks = KbChunk::with('article')->get();

            if ($chunks->isEmpty()) {
                return $this->fallback();
            }
            $queryVector = $this->embedder->generate($primaryQuery);

            $documents = [];
            foreach ($chunks as $chunk) {
                $tokens = $this->tokenize($chunk->chunk_text);
                $documents[] = [
                    'chunk' => $chunk,
                    'tokens' => $tokens,
                    'length' => max(count($tokens), 1),
                    'embedding' => $this->embeddingForChunk($chunk),
                ];
            }

            $lexicalScores = $this->bm25Scores($queryTokens, $documents);
            $vectorScores = $this->vectorScores($queryVector, $documents);

            if ($lexicalScores === [] && $vectorScores === []) {
                return $this->fallback();
            }

            $lexMax = $this->maxValue($lexicalScores);

            $candidates = [];
            foreach ($documents as $doc) {
                $chunk = $doc['chunk'];
                $chunkId = $chunk->id;
                $lexicalRaw = $lexicalScores[$chunkId] ?? 0.0;
                $vectorRaw = $vectorScores[$chunkId] ?? 0.0;

                $lexicalNorm = $lexMax > 0 ? $lexicalRaw / $lexMax : 0.0;
                $vectorNorm = ($vectorRaw + 1) / 2;
                $vectorNorm = max(0.0, min(1.0, $vectorNorm));

                $coverage = $this->coverageScore($queryTokens, $doc['tokens']);
                $position = $this->positionBoost($queryTokens, $doc['tokens']);

                $baseScore = 0.55 * $vectorNorm + 0.3 * $lexicalNorm + 0.1 * $coverage + 0.05 * $position;

                $candidates[] = [
                    'chunk' => $chunk,
                    'embedding' => $doc['embedding'],
                    'vector_raw' => $vectorRaw,
                    'vector_norm' => $vectorNorm,
                    'lexical_raw' => $lexicalRaw,
                    'lexical_norm' => $lexicalNorm,
                    'coverage' => $coverage,
                    'position' => $position,
                    'base_score' => $baseScore,
                    'tokens' => $doc['tokens'],
                ];
            }

            $mmrSelected = $this->mmr($candidates, self::MMR_K);

            if ($mmrSelected === []) {
                return $this->fallback();
            }

            $reranked = $this->rerank($mmrSelected, $queryTokens, $refined['focus'] ?? null, $keywordTokens);
            $top = array_slice($reranked, 0, self::MAX_SOURCES);

            if ($top === []) {
                return $this->fallback();
            }

            $confidence = $top[0]['rerank_score'] ?? 0.0;

            if ($confidence < self::MIN_CONFIDENCE) {
                return $this->lowConfidence($top, $normalized);
            }

            $sources = $this->buildSources($top);

            return [
                'success' => true,
                'answer' => $this->formatAnswer($top),
                'confidence' => round($confidence, 3),
                'source' => $this->formatSources($top),
                'sources' => $sources,
                'chunks' => $this->formatChunks($top),
            ];
        });
    }

    public function ingest(string $title, string $content): void
    {
        DB::transaction(function () use ($title, $content) {
            $article = KbArticle::updateOrCreate(
                ['title' => $title],
                ['body' => $content]
            );

            $article->chunks()->delete();

            foreach ($this->chunkContent($content) as $chunkText) {
                $embedding = $this->embedder->generate($chunkText);
                $chunk = $article->chunks()->create([
                    'chunk_text' => $chunkText,
                    'embedding' => $embedding,
                ]);

                if ($chunk && $this->shouldUseHybrid()) {
                    $this->hybridRetriever?->indexDocument([
                        'id' => $chunk->id,
                        'article_id' => $article->id,
                        'title' => $article->title,
                        'content' => $chunkText,
                    ], $embedding);
                }
            }
        });
    }

    private function embeddingForChunk(KbChunk $chunk): array
    {
        $embedding = $chunk->embedding;

        if (!is_array($embedding) || count($embedding) !== $this->embedder->dimensions()) {
            $embedding = $this->embedder->generate($chunk->chunk_text);
            $chunk->embedding = $embedding;
            $chunk->save();
        }

        return $embedding;
    }

    private function formatAnswer(iterable $top): string
    {
        return collect($top)
            ->values()
            ->take(self::MAX_SOURCES)
            ->map(function (array $item, int $index) {
                $chunk = $item['chunk'];
                $title = $chunk->article->title;
                $label = $index + 1;
                $snippet = Str::limit($chunk->chunk_text, self::MAX_SNIPPET_LENGTH);

                return "{$label}. {$snippet}\n(Sumber: {$title})";
            })
            ->implode("\n\n");
    }

    private function formatSources(iterable $top): string
    {
        return collect($top)
            ->map(fn (array $item) => $item['chunk']->article->title)
            ->unique()
            ->take(self::MAX_SOURCES)
            ->implode(', ');
    }

    /**
     * @param  array<int, array<string, mixed>>  $documents
     */
    private function formatHybridAnswer(array $documents): string
    {
        $lines = [];

        foreach (array_slice($documents, 0, self::MAX_SOURCES) as $index => $doc) {
            $snippet = Str::limit((string) ($doc['content'] ?? ''), self::MAX_SNIPPET_LENGTH);
            $title = $doc['title'] ?? 'Dokumen';
            $lines[] = ($index + 1) . '. ' . $snippet . "\n(Sumber: {$title})";
        }

        return implode("\n\n", $lines);
    }

    /**
     * @param  array<int, array<string, mixed>>  $documents
     */
    private function formatHybridSourceString(array $documents): string
    {
        return collect($documents)
            ->pluck('title')
            ->filter()
            ->unique()
            ->take(self::MAX_SOURCES)
            ->implode(', ');
    }

    /**
     * @param  array<int, array<string, mixed>>  $documents
     * @return array<int, array<string, mixed>>
     */
    private function buildHybridSources(array $documents): array
    {
        return collect($documents)
            ->values()
            ->take(self::MAX_SOURCES)
            ->map(function (array $doc, int $index) {
                return [
                    'index' => $index + 1,
                    'title' => $doc['title'] ?? 'Dokumen',
                    'document_id' => $doc['id'] ?? null,
                    'snippet' => Str::limit((string) ($doc['content'] ?? ''), self::MAX_SNIPPET_LENGTH),
                    'score' => round((float) ($doc['score'] ?? 0.0), 3),
                ];
            })
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $documents
     * @return array<int, array<string, mixed>>
     */
    private function formatHybridChunks(array $documents): array
    {
        return collect($documents)
            ->take(self::MAX_SOURCES)
            ->map(fn (array $doc) => [
                'title' => $doc['title'] ?? 'Dokumen',
                'excerpt' => Str::limit((string) ($doc['content'] ?? ''), self::MAX_SNIPPET_LENGTH),
                'score' => round((float) ($doc['score'] ?? 0.0), 3),
            ])
            ->all();
    }

    private function formatChunks(iterable $top): array
    {
        return collect($top)
            ->map(fn (array $item) => [
                'title' => $item['chunk']->article->title,
                'excerpt' => Str::limit($item['chunk']->chunk_text, self::MAX_SNIPPET_LENGTH),
                'score' => round($item['rerank_score'] ?? $item['score'] ?? 0, 3),
            ])
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $documents
     */
    private function formatHybridSuccess(array $documents, float $confidence): array
    {
        if ($documents === []) {
            return $this->fallback();
        }

        $sources = $this->buildHybridSources($documents);

        return [
            'success' => true,
            'answer' => $this->formatHybridAnswer($documents),
            'confidence' => round($confidence, 3),
            'source' => $this->formatHybridSourceString($documents),
            'sources' => $sources,
            'chunks' => $this->formatHybridChunks($documents),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $documents
     * @param  array<int, string>  $suggestedTitles
     */
    private function formatHybridLowConfidence(
        array $documents,
        string $query,
        float $confidence,
        array $suggestedTitles
    ): array {
        $sources = $this->buildHybridSources($documents);
        $titles = $suggestedTitles !== [] ? $suggestedTitles : array_column($sources, 'title');

        return [
            'success' => false,
            'reason' => 'low_confidence',
            'query' => $query,
            'confidence' => $confidence,
            'sources' => $sources,
            'suggested_titles' => array_values(array_filter($titles)),
        ];
    }

    private function buildSources(iterable $top): array
    {
        return collect($top)
            ->values()
            ->take(self::MAX_SOURCES)
            ->map(function (array $item, int $index) {
                return [
                    'index' => $index + 1,
                    'title' => $item['chunk']->article->title,
                    'snippet' => Str::limit($item['chunk']->chunk_text, self::MAX_SNIPPET_LENGTH),
                    'score' => round($item['rerank_score'] ?? 0, 3),
                ];
            })
            ->all();
    }

    private function tokenize(string $text): array
    {
        $normalized = Str::of($text)->lower()->squish()->value();
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', $normalized) ?: [];

        return array_values(array_filter($tokens, fn ($token) => $token !== ''));
    }

    /**
     * @param  array<int,string>  $queries
     * @return array<int,string>
     */
    private function tokensFromVariants(array $queries): array
    {
        $tokens = [];

        foreach ($queries as $variant) {
            if (!is_string($variant) || trim($variant) === '') {
                continue;
            }

            $tokens = array_merge($tokens, $this->tokenize($variant));
        }

        return array_values(array_unique($tokens));
    }

    /**
     * @param  array<int, string>  $queryTokens
     * @param  array<int, array<string, mixed>>  $documents
     * @return array<int, float>
     */
    private function bm25Scores(array $queryTokens, array $documents): array
    {
        if ($documents === [] || $queryTokens === []) {
            return [];
        }

        $docCount = count($documents);
        $avgDocLen = array_sum(array_column($documents, 'length')) / max($docCount, 1);
        $k1 = 1.5;
        $b = 0.75;
        $queryTermFreq = array_count_values($queryTokens);
        $docFreq = [];

        foreach ($documents as $doc) {
            $tokens = array_unique($doc['tokens']);
            foreach ($tokens as $token) {
                $docFreq[$token] = ($docFreq[$token] ?? 0) + 1;
            }
        }

        $scores = [];

        foreach ($documents as $doc) {
            $score = 0.0;
            $tokenCounts = array_count_values($doc['tokens']);
            $docLen = $doc['length'];

            foreach ($queryTermFreq as $token => $freq) {
                if (!isset($tokenCounts[$token])) {
                    continue;
                }

                $df = $docFreq[$token] ?? 0;
                if ($df === 0) {
                    continue;
                }

                $idf = log(($docCount - $df + 0.5) / ($df + 0.5) + 1);
                $tf = $tokenCounts[$token];
                $numerator = $tf * ($k1 + 1);
                $denominator = $tf + $k1 * (1 - $b + $b * ($docLen / $avgDocLen));
                $score += $idf * ($numerator / max($denominator, 0.0001));
            }

            $scores[$doc['chunk']->id] = $score;
        }

        return $scores;
    }

    /**
     * @param  array<string, float>  $queryVector
     * @param  array<int, array<string, mixed>>  $documents
     * @return array<int, float>
     */
    private function vectorScores(array $queryVector, array $documents): array
    {
        $scores = [];

        foreach ($documents as $doc) {
            $scores[$doc['chunk']->id] = $this->embedder->cosine($queryVector, $doc['embedding']);
        }

        return $scores;
    }

    private function coverageScore(array $queryTokens, array $docTokens): float
    {
        if ($queryTokens === []) {
            return 0.0;
        }

        $docTokenSet = array_fill_keys($docTokens, true);
        $matches = 0;

        foreach (array_unique($queryTokens) as $token) {
            if (isset($docTokenSet[$token])) {
                $matches++;
            }
        }

        return $matches / max(count(array_unique($queryTokens)), 1);
    }

    private function positionBoost(array $queryTokens, array $docTokens): float
    {
        $positions = [];
        $docCount = count($docTokens);

        foreach ($queryTokens as $token) {
            $position = array_search($token, $docTokens, true);
            if ($position !== false) {
                $positions[] = $position;
            }
        }

        if ($positions === []) {
            return 0.0;
        }

        $firstPosition = min($positions);

        if ($docCount === 0) {
            return 0.0;
        }

        return 1 - min($firstPosition / $docCount, 1);
    }

    private function mmr(array $candidates, int $k, float $lambda = 0.65): array
    {
        $selected = [];

        if ($candidates === []) {
            return $selected;
        }

        while (count($selected) < $k && $candidates !== []) {
            $bestIndex = null;
            $bestScore = -INF;

            foreach ($candidates as $index => $candidate) {
                $redundancy = 0.0;

                foreach ($selected as $chosen) {
                    $redundancy = max($redundancy, $this->pairwiseSimilarity($candidate['embedding'], $chosen['embedding']));
                }

                $mmrScore = $lambda * $candidate['base_score'] - (1 - $lambda) * $redundancy;

                if ($mmrScore > $bestScore) {
                    $bestScore = $mmrScore;
                    $bestIndex = $index;
                }
            }

            if ($bestIndex === null) {
                break;
            }

            $candidate = $candidates[$bestIndex];
            $candidate['mmr_score'] = $bestScore;
            $selected[] = $candidate;
            unset($candidates[$bestIndex]);
        }

        return $selected;
    }

    private function pairwiseSimilarity(array $a, array $b): float
    {
        return $this->embedder->cosine($a, $b);
    }

    private function rerank(array $candidates, array $queryTokens, ?string $focus, array $keywordTokens): array
    {
        return collect($candidates)
            ->map(function (array $candidate) use ($queryTokens, $focus, $keywordTokens) {
                $coverage = $candidate['coverage'];
                $position = $candidate['position'];
                $lexical = $candidate['lexical_norm'];
                $vector = $candidate['vector_norm'];
                $keywordDensity = $this->coverageScore($queryTokens, $candidate['tokens']);
                $keywordBoost = $keywordTokens !== [] ? $this->coverageScore($keywordTokens, $candidate['tokens']) : 0.0;
                $focusBoost = $this->questionFocusBoost($focus, $candidate['chunk']->chunk_text);

                $score = 0.45 * $vector
                    + 0.25 * $lexical
                    + 0.12 * $coverage
                    + 0.08 * max($position, $keywordDensity)
                    + 0.05 * $keywordBoost
                    + 0.05 * $focusBoost;

                $candidate['rerank_score'] = round($score, 6);

                return $candidate;
            })
            ->sortByDesc('rerank_score')
            ->values()
            ->all();
    }

    private function questionFocusBoost(?string $focus, string $content): float
    {
        if ($focus === null) {
            return 0.0;
        }

        $normalized = Str::of($content)->lower()->value();

        return match ($focus) {
            'time' => $this->containsAny($normalized, ['jam', 'pukul', 'tanggal', 'hari', 'waktu']) ? 1.0 : 0.0,
            'person' => $this->containsAny($normalized, ['ketua', 'sekretaris', 'bendahara', 'pak', 'ibu', 'narahubung', 'kontak']) ? 1.0 : 0.0,
            'location' => $this->containsAny($normalized, ['jalan', 'jl', 'komplek', 'lokasi', 'alamat']) ? 1.0 : 0.0,
            'procedure' => preg_match('/\d+\./', $content) || $this->containsAny($normalized, ['langkah', 'prosedur', 'step'])
                ? 1.0
                : 0.0,
            default => 0.0,
        };
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && Str::contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function lowConfidence(array $top, string $query): array
    {
        $sources = $this->buildSources($top);

        return [
            'success' => false,
            'reason' => 'low_confidence',
            'query' => $query,
            'confidence' => 0.0,
            'sources' => $sources,
            'suggested_titles' => array_values(array_unique(array_map(
                fn (array $source) => $source['title'],
                $sources
            ))),
        ];
    }

    private function maxValue(array $scores): float
    {
        if ($scores === []) {
            return 0.0;
        }

        return (float) max($scores);
    }

    private function chunkContent(string $content): array
    {
        $plain = $this->normalizeContent($content);
        $sentences = preg_split('/(?<=[.!?])\s+/u', $plain) ?: [$plain];

        $chunks = [];
        $buffer = '';

        foreach ($sentences as $sentence) {
            if (Str::length($buffer . ' ' . $sentence) > 400) {
                $chunks[] = trim($buffer);
                $buffer = $sentence;
            } else {
                $buffer = trim($buffer . ' ' . $sentence);
            }
        }

        if ($buffer !== '') {
            $chunks[] = $buffer;
        }

        return $chunks === [] ? [$plain] : $chunks;
    }

    private function normalizeContent(string $content): string
    {
        return Str::of($content)
            ->replace(["\r\n", "\r"], "\n")
            ->replaceMatches('/\n{2,}/', "\n\n")
            ->replaceMatches('/[#*_`>-]+/', '')
            ->replace("\t", ' ')
            ->squish()
            ->value();
    }

    private function shouldUseHybrid(): bool
    {
        return $this->hybridEnabled
            && $this->hybridRetriever !== null
            && $this->hybridRetriever->isConfigured();
    }

    private function ensureDefaultKnowledgeBase(): void
    {
        if (!Schema::hasTable('kb_articles') || !Schema::hasTable('kb_chunks')) {
            return;
        }

        if (KbArticle::count() > 0) {
            return;
        }

        foreach ($this->defaultDocuments() as $document) {
            $this->ingest($document['title'], $document['content']);
        }
    }

    /**
     * @return array<int, array{title:string, content:string}>
     */
    private function defaultDocuments(): array
    {
        return [
            [
                'title' => 'Prosedur Surat Pindah',
                'content' => "Untuk mengurus surat pindah:\n1. Siapkan KTP asli dan fotokopi.\n2. Siapkan Kartu Keluarga asli dan fotokopi.\n3. Mintalah surat pengantar dari RT/RW.\n4. Datang ke kelurahan dengan dokumen lengkap.\nProses biasanya selesai dalam 1-3 hari kerja.",
            ],
            [
                'title' => 'Permohonan Surat Pengantar',
                'content' => "Langkah membuat surat pengantar RT:\n1. Datang ke sekretariat RT dengan membawa KTP.\n2. Isi formulir permohonan sesuai keperluan (SKCK, domisili, dll).\n3. Jelaskan kebutuhan surat kepada pengurus.\n4. Proses biasanya selesai dalam 1-2 hari dan tidak dipungut biaya.",
            ],
            [
                'title' => 'Iuran Wajib RT',
                'content' => "Iuran wajib di lingkungan RT mencakup:\n- Iuran kebersihan untuk layanan sampah.\n- Iuran keamanan untuk ronda atau satpam.\n- Iuran kas RT untuk kegiatan operasional.\nBesaran dan jadwal pembayaran dapat dilihat pada menu Tagihan atau ditanyakan ke bendahara.",
            ],
            [
                'title' => 'Jadwal Ronda dan Keamanan',
                'content' => "Jadwal ronda warga:\n- Daftar anggota ronda tersedia di menu Agenda dan papan pengumuman.\n- Setiap warga dijadwalkan bergiliran.\n- Bila berhalangan, hubungi koordinator keamanan untuk tukar jadwal.\nPastikan mencatat giliran ronda agar keamanan lingkungan tetap terjaga.",
            ],
            [
                'title' => 'Jadwal Pengambilan Sampah',
                'content' => "Pengambilan sampah lingkungan RT:\n- Dilakukan 2-3 kali per minggu tergantung cluster.\n- Pisahkan sampah organik dan anorganik sebelum dibuang.\n- Tempatkan sampah di depan rumah maksimal pukul 07.00.\n- Informasi perubahan jadwal diumumkan melalui dashboard atau grup RT.",
            ],
            [
                'title' => 'Peminjaman Fasilitas Umum',
                'content' => "Untuk meminjam fasilitas RT (balai, aula, lapangan):\n1. Ajukan permintaan minimal 3 hari sebelum acara.\n2. Hubungi admin RT atau isi formulir peminjaman.\n3. Bayar biaya sewa (jika ada) sesuai ketentuan.\n4. Pastikan fasilitas dikembalikan dalam kondisi bersih dan rapi.",
            ],
        ];
    }

    private function fallback(): array
    {
        return [
            'success' => false,
            'answer' => 'Maaf, aku belum menemukan informasi soal itu. Coba kontak pengurus atau cek dokumen RT ya!',
            'confidence' => 0,
        ];
    }
}
