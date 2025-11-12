<?php

namespace App\Services\Assistant;

use Illuminate\Support\Str;

class QueryClassifier
{
    /**
     * Lexicon yang digunakan untuk menilai intent. Setiap intent memiliki daftar kata kunci,
     * frasa, dan booster yang membantu menghitung skor akhir.
     *
     * @var array<string, array<string, array<int, string>>>|
     *     array<string, array<string, array<string, float>>>
     */
    private array $intentMatrix = [
        'tagihan' => [
            'keywords' => ['tagihan', 'iuran', 'tunggakan', 'hutang', 'bayar'],
            'phrases' => ['tagihan bulan', 'belum bayar', 'total tagihan'],
            'boosters' => ['bulan ini', 'bln ini', 'hari ini', 'sisa', 'berapa', 'total']
        ],
        'pembayaran' => [
            'keywords' => ['pembayaran', 'bayar', 'riwayat', 'dibayar', 'pelunasan'],
            'phrases' => ['sudah bayar', 'riwayat bayar', 'apa saja yang sudah saya bayar'],
            'boosters' => ['bulan ini', 'minggu ini', 'transaksi', 'sudah', 'status']
        ],
        'agenda' => [
            'keywords' => ['agenda', 'acara', 'kegiatan', 'rapat', 'event', 'jadwal'],
            'phrases' => ['agenda minggu', 'agenda bulan', 'kegiatan besok', 'ada acara'],
            'boosters' => ['minggu', 'bulan', 'besok', 'hari ini', 'jam', 'kapan']
        ],
        'warga' => [
            'keywords' => ['warga', 'penduduk', 'resident', 'anggota'],
            'phrases' => ['berapa total warga', 'jumlah warga', 'total penduduk'],
            'boosters' => ['jumlah', 'total', 'berapa', 'data', 'rekap']
        ],
        'cari_warga' => [
            'keywords' => ['cari', 'temukan', 'search', 'find'],
            'phrases' => ['cari warga', 'ada warga', 'siapa saja'],
            'boosters' => ['nama', 'alamat', 'kontak', 'telpon', 'no hp', 'dimana', 'lihat']
        ],
        'kontak' => [
            'keywords' => ['kontak', 'hubungi', 'nomor', 'ketua', 'sekretaris', 'bendahara'],
            'phrases' => ['kontak ketua', 'nomor ketua', 'hubungi pengurus'],
            'boosters' => ['siapa', 'bagaimana cara', 'dihubungi', 'telepon', 'whatsapp']
        ],
        'surat' => [
            'keywords' => ['surat', 'pengantar', 'domisili', 'skck', 'rt rw'],
            'phrases' => ['cara mengurus surat', 'urusan surat', 'surat domisili'],
            'boosters' => ['bagaimana', 'proses', 'syarat', 'dokumen', 'urus']
        ],
        'fasilitas' => [
            'keywords' => ['fasilitas', 'balai', 'aula', 'lapangan', 'sampah', 'keamanan', 'ronda'],
            'phrases' => ['jadwal ronda', 'pengambilan sampah', 'pinjam aula'],
            'boosters' => ['jadwal', 'kapan', 'bagaimana', 'aturan', 'sewa']
        ],
        'keuangan' => [
            'keywords' => ['rekap', 'keuangan', 'laporan', 'kas', 'neraca'],
            'phrases' => ['rekap keuangan', 'laporan keuangan', 'rekap bulan lalu'],
            'boosters' => ['bulan lalu', 'bulan ini', 'download', 'ekspor', 'laporan']
        ],
        'bantuan' => [
            'keywords' => ['bantuan', 'tolong', 'help', 'bisa apa', 'panduan'],
            'phrases' => ['kamu bisa apa', 'butuh bantuan', 'bisa bantu'],
            'boosters' => ['jelasin', 'contoh', 'panduan', 'gimana cara']
        ],
    ];

    private array $tokenAliases = [
        'tagian' => 'tagihan',
        'tagihanku' => 'tagihan',
        'tagihannya' => 'tagihan',
        'tagihanku?' => 'tagihan',
        'iuranku' => 'iuran',
        'bills' => 'tagihan',
        'bill' => 'tagihan',
        'riwayat' => 'riwayat',
        'riwayatnya' => 'riwayat',
        'bayaran' => 'bayar',
        'dibayar' => 'bayar',
        'pembayaranku' => 'pembayaran',
        'agendae' => 'agenda',
        'agendanya' => 'agenda',
        'event' => 'event',
        'events' => 'event',
        'schedule' => 'jadwal',
        'jadwalnya' => 'jadwal',
        'warganya' => 'warga',
        'residen' => 'resident',
        'resident' => 'resident',
        'residents' => 'resident',
        'kontaknya' => 'kontak',
        'nomornya' => 'nomor',
        'telepon' => 'telpon',
        'no' => 'nomor',
        'hp' => 'telpon',
        'whatsapp' => 'telpon',
        'wa' => 'telpon',
        'domisili' => 'domisili',
        'skck' => 'skck',
        'suratnya' => 'surat',
        'ronda' => 'ronda',
        'sampah' => 'sampah',
        'rekapnya' => 'rekap',
        'laporannya' => 'laporan',
        'tolong' => 'tolong',
        'bisa' => 'bisa',
        'panduan' => 'panduan',
    ];

    private array $smallTalkTriggers = [
        'halo', 'hai', 'hi', 'hello', 'hey', 'hola',
        'apa kabar', 'gimana kabar', 'kabar', 'ok', 'oke',
        'terima kasih', 'makasih', 'thanks',
        'siapa kamu', 'kamu siapa',
        'selamat pagi', 'selamat siang', 'selamat sore', 'selamat malam',
    ];

    private array $reasoningTriggers = [
        'kenapa', 'mengapa', 'jelaskan', 'bedanya', 'perbedaan', 'bandingkan',
        'bagaimana jika', 'apa yang terjadi', 'saran', 'rekomendasi',
        'analisis', 'evaluasi', 'menurut kamu', 'pendapatmu',
    ];

    private array $multiIntentConnectors = [' dan ', ' serta ', ' lalu ', ' kemudian ', ','];

    private array $stopWords = [
        'apa', 'yang', 'itu', 'ada', 'nggak', 'gak', 'dong', 'dong', 'deh', 'nih', 'sih',
        'tolong', 'bisa', 'mau', 'aku', 'saya', 'gue', 'gua', 'aku', 'anda', 'lagi', 'dong',
        'tanyakan', 'tanya', 'pun', 'ya', 'kah', 'gimana', 'gmn', 'mo', 'mohon', 'please'
    ];

    private array $negations = ['tidak', 'tak', 'nggak', 'gak', 'ga', 'bukan'];

    public function classify(string $message): array
    {
        $normalized = Str::of($message)->lower()->squish()->value();

        if ($normalized === '') {
            return ['type' => 'small_talk', 'confidence' => 1.0, 'reason' => 'empty'];
        }

        if ($this->isSmallTalk($normalized)) {
            return ['type' => 'small_talk', 'confidence' => 0.95, 'reason' => 'small_talk_trigger'];
        }

        if ($this->requiresReasoning($normalized)) {
            return ['type' => 'complex', 'confidence' => 0.9, 'reason' => 'reasoning_trigger'];
        }

        $tokens = $this->tokenize($normalized);
        $scores = $this->scoreIntents($normalized, $tokens);

        if ($scores !== []) {
            arsort($scores);
            $topIntent = array_key_first($scores);
            $topScore = $scores[$topIntent];

            $selected = $this->selectRelevantIntents($scores, $topScore, $normalized, $tokens);

            if ($selected !== []) {
                $type = $this->hasComplexStructure($normalized) ? 'complex' : 'simple';
                $confidence = $this->confidenceFromScore($topScore, count($tokens));

                return [
                    'type' => $type,
                    'category' => $topIntent,
                    'intents' => array_keys($selected),
                    'scores' => $selected,
                    'confidence' => $confidence,
                    'reason' => count($selected) > 1 ? 'multi_intent_match' : 'single_intent_match',
                ];
            }
        }

        if (Str::length($normalized) < 60 && !$this->hasComplexStructure($normalized)) {
            return [
                'type' => 'simple',
                'category' => 'general',
                'intents' => [],
                'confidence' => 0.55,
                'reason' => 'short_sentence_fallback',
            ];
        }

        return ['type' => 'complex', 'confidence' => 0.65, 'reason' => 'unmatched'];
    }

    public function isSmallTalk(string $message): bool
    {
        foreach ($this->smallTalkTriggers as $trigger) {
            if (str_contains($trigger, ' ')) {
                if (str_contains($message, $trigger)) {
                    return true;
                }
                continue;
            }

            $pattern = '/\b' . preg_quote($trigger, '/') . '\b/u';
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        return false;
    }

    private function requiresReasoning(string $message): bool
    {
        foreach ($this->reasoningTriggers as $trigger) {
            if (str_contains($message, $trigger)) {
                return true;
            }
        }

        return false;
    }

    private function tokenize(string $message): array
    {
        $rawTokens = preg_split('/[^a-z0-9]+/u', $message) ?: [];
        $tokens = [];

        foreach ($rawTokens as $token) {
            if ($token === '') {
                continue;
            }

            $normalized = $this->normalizeToken($token);

            if (in_array($normalized, $this->stopWords, true)) {
                continue;
            }

            $tokens[] = $normalized;
        }

        return $tokens;
    }

    private function normalizeToken(string $token): string
    {
        $token = trim($token);

        if ($token === '') {
            return $token;
        }

        if (isset($this->tokenAliases[$token])) {
            return $this->tokenAliases[$token];
        }

        foreach (['ku', 'mu', 'nya'] as $suffix) {
            if (Str::endsWith($token, $suffix) && Str::length($token) > Str::length($suffix) + 2) {
                $base = Str::substr($token, 0, -Str::length($suffix));
                if (isset($this->tokenAliases[$base])) {
                    return $this->tokenAliases[$base];
                }

                return $base;
            }
        }

        return $token;
    }

    private function scoreIntents(string $message, array $tokens): array
    {
        $scores = [];
        $tokenCounts = array_count_values($tokens);

        foreach ($this->intentMatrix as $intent => $definition) {
            $score = 0.0;

            foreach ($definition['keywords'] ?? [] as $keyword) {
                $score += $this->scoreKeyword($keyword, $tokenCounts);
            }

            foreach ($definition['phrases'] ?? [] as $phrase) {
                if (str_contains($message, $phrase)) {
                    $score += 1.4;
                }
            }

            foreach ($definition['boosters'] ?? [] as $booster) {
                if (str_contains($message, $booster)) {
                    $score += 0.6;
                }
            }

            if ($score > 0) {
                $scores[$intent] = round($score, 3);
            }
        }

        return $scores;
    }

    private function scoreKeyword(string $keyword, array $tokenCounts): float
    {
        if (isset($tokenCounts[$keyword])) {
            return 1.2 + ($tokenCounts[$keyword] - 1) * 0.2;
        }

        foreach ($tokenCounts as $token => $count) {
            if ($this->fuzzyMatch($token, $keyword)) {
                return 0.8 + ($count - 1) * 0.15;
            }
        }

        return 0.0;
    }

    private function selectRelevantIntents(array $scores, float $topScore, string $message, array $tokens): array
    {
        $selected = [];
        $threshold = max(0.7, $topScore * 0.5);

        foreach ($scores as $intent => $score) {
            if ($score >= $threshold || $this->intentHasDirectHit($intent, $tokens)) {
                $selected[$intent] = $score;
            }
        }

        if (count($selected) < 2 && $this->hasMultiIntentCue($message)) {
            $ranked = $scores;
            arsort($ranked);
            foreach ($ranked as $intent => $score) {
                if (isset($selected[$intent])) {
                    continue;
                }

                if (
                    $this->intentHasDirectHit($intent, $tokens)
                    || $score >= max(0.55, $topScore * 0.35)
                ) {
                    $selected[$intent] = $score;
                }

                if (count($selected) >= 3) {
                    break;
                }
            }
        }

        return $selected;
    }

    private function confidenceFromScore(float $score, int $tokenCount): float
    {
        $lengthFactor = max(1, min(4, $tokenCount / 4));
        $confidence = 0.45 + ($score / (2.5 + $lengthFactor));

        return max(0.4, min(0.99, $confidence));
    }

    private function intentHasDirectHit(string $intent, array $tokens): bool
    {
        $definition = $this->intentMatrix[$intent] ?? null;

        if ($definition === null) {
            return false;
        }

        foreach ($definition['keywords'] ?? [] as $keyword) {
            if (in_array($keyword, $tokens, true)) {
                return true;
            }
        }

        return false;
    }

    private function fuzzyMatch(string $token, string $keyword): bool
    {
        if ($token === $keyword) {
            return true;
        }

        if (Str::startsWith($token, $keyword) || Str::startsWith($keyword, $token)) {
            return true;
        }

        similar_text($token, $keyword, $percent);

        return $percent >= 82;
    }

    private function hasMultiIntentCue(string $message): bool
    {
        foreach ($this->multiIntentConnectors as $connector) {
            if (str_contains($message, $connector)) {
                return true;
            }
        }

        return false;
    }

    private function hasComplexStructure(string $message): bool
    {
        $patterns = [
            '/\b(jika|kalau|apabila|seandainya)\b/u',
            '/\b(atau|dan|serta)\b.*\b(atau|dan|serta)\b/u',
            '/\?.*\?/u',
            '/\b(kalau misal|andaikan)\b/u',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        return false;
    }
}
