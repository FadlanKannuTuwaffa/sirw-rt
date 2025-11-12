<?php

namespace App\Services\Assistant;

use App\Services\Assistant\Support\GeminiKeyManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MLIntentClassifier
{
    private array $intentExamples = [
        'bills' => [
            'tagihan bulan ini apa',
            'berapa tunggakan saya',
            'iuran yang belum dibayar',
            'what bills do I owe',
        ],
        'payments' => [
            'pembayaran yang sudah lunas',
            'riwayat bayar bulan ini',
            'transaksi yang sudah selesai',
            'payment history this month',
        ],
        'agenda' => [
            'agenda minggu depan',
            'acara apa besok',
            'kegiatan bulan ini',
            'upcoming events',
        ],
        'residents' => [
            'jumlah warga',
            'cari warga bernama',
            'kontak pengurus',
            'resident directory',
        ],
        'finance' => [
            'rekap keuangan',
            'laporan kas',
            'financial report',
        ],
        'knowledge_base' => [
            'prosedur surat domisili',
            'cara mengurus izin',
            'syarat pendaftaran',
            'how to request letter',
        ],
    ];

    private ?string $embeddingProvider = null;
    private ?string $fallbackProvider = null;
    private ?array $lastScores = null;
    private ?string $lastScoresHash = null;

    public function __construct()
    {
        $this->embeddingProvider = config('copilot.embedding_provider', 'gemini');
        $this->fallbackProvider = config('copilot.embedding_fallback', 'cohere');
    }

    public function classify(string $message, array $context = []): array
    {
        $cacheKey = 'ml_intent:' . md5($message);
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Fallback ke rule-based jika embedding tidak tersedia
        if (!$this->hasEmbeddingSupport()) {
            return $this->fallbackClassify($message, $context);
        }

        $messageEmbedding = $this->embed($message);
        
        if ($messageEmbedding === null) {
            // Coba fallback provider
            $messageEmbedding = $this->embed($message, true);
            
            if ($messageEmbedding === null) {
                return $this->fallbackClassify($message, $context);
            }
        }

        $scores = [];
        
        foreach ($this->intentExamples as $intent => $examples) {
            $intentScore = 0;
            
            foreach ($examples as $example) {
                $exampleEmbedding = $this->embed($example);
                
                if ($exampleEmbedding === null) {
                    $exampleEmbedding = $this->embed($example, true);
                }
                
                if ($exampleEmbedding !== null) {
                    $similarity = $this->cosineSimilarity($messageEmbedding, $exampleEmbedding);
                    $intentScore = max($intentScore, $similarity);
                }
            }
            
            $scores[$intent] = $intentScore;
        }

        arsort($scores);
        
        $topIntent = array_key_first($scores);
        $topScore = $scores[$topIntent];
        
        $result = [
            'intent' => $topScore > 0.65 ? $topIntent : null,
            'confidence' => $topScore,
            'all_scores' => $scores,
            'method' => 'ml',
        ];

        Cache::put($cacheKey, $result, now()->addMinutes(10));
        $this->lastScores = $scores;
        $this->lastScoresHash = md5($message);
        
        return $result;
    }

    /**
     * @return array<string,float>
     */
    public function lastScores(string $message): array
    {
        if ($this->lastScores !== null && $this->lastScoresHash === md5($message)) {
            return $this->lastScores;
        }

        $result = $this->classify($message);
        return $result['all_scores'] ?? [];
    }

    private function embed(string $text, bool $useFallback = false): ?array
    {
        $provider = $useFallback ? $this->fallbackProvider : $this->embeddingProvider;
        $cacheKey = 'embedding:' . $provider . ':' . md5($text);
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $embedding = match ($provider) {
                'gemini' => $this->embedGemini($text),
                'cohere' => $this->embedCohere($text),
                default => null,
            };

            if ($embedding !== null) {
                Cache::put($cacheKey, $embedding, now()->addHours(24));
                return $embedding;
            }
        } catch (\Throwable) {
            // Silent fail
        }

        return null;
    }

    private function embedGemini(string $text): ?array
    {
        $apiKey = GeminiKeyManager::getNextKey() ?? config('services.gemini.api_key');
        
        if (!$apiKey) {
            return null;
        }

        $response = Http::timeout(10)
            ->post("https://generativelanguage.googleapis.com/v1beta/models/text-embedding-004:embedContent?key={$apiKey}", [
                'model' => 'models/text-embedding-004',
                'content' => [
                    'parts' => [
                        ['text' => $text]
                    ]
                ],
            ]);

        if ($response->successful()) {
            return $response->json('embedding.values');
        }

        return null;
    }

    private function embedCohere(string $text): ?array
    {
        $apiKey = config('services.cohere.key');
        
        if (!$apiKey) {
            return null;
        }

        $response = Http::withToken($apiKey)
            ->timeout(10)
            ->post('https://api.cohere.ai/v1/embed', [
                'texts' => [$text],
                'model' => 'embed-multilingual-light-v3.0',
                'input_type' => 'search_query',
            ]);

        if ($response->successful()) {
            $embeddings = $response->json('embeddings');
            return $embeddings[0] ?? null;
        }

        return null;
    }

    private function cosineSimilarity(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            return 0.0;
        }

        $dotProduct = 0;
        $magnitudeA = 0;
        $magnitudeB = 0;

        for ($i = 0; $i < count($a); $i++) {
            $dotProduct += $a[$i] * $b[$i];
            $magnitudeA += $a[$i] * $a[$i];
            $magnitudeB += $b[$i] * $b[$i];
        }

        $magnitude = sqrt($magnitudeA) * sqrt($magnitudeB);

        return $magnitude > 0 ? $dotProduct / $magnitude : 0.0;
    }

    private function hasEmbeddingSupport(): bool
    {
        $primarySupport = match ($this->embeddingProvider) {
            'gemini' => config('services.gemini.key') !== null,
            'cohere' => config('services.cohere.key') !== null,
            default => false,
        };

        if ($primarySupport) {
            return true;
        }

        // Check fallback
        return match ($this->fallbackProvider) {
            'gemini' => config('services.gemini.key') !== null,
            'cohere' => config('services.cohere.key') !== null,
            default => false,
        };
    }

    private function fallbackClassify(string $message, array $context): array
    {
        // Rule-based fallback
        $normalized = Str::of($message)->lower()->squish()->value();
        
        $patterns = [
            'bills' => ['tagihan', 'tunggakan', 'iuran', 'bill', 'belum bayar'],
            'payments' => ['pembayaran', 'bayar', 'lunas', 'riwayat', 'payment'],
            'agenda' => ['agenda', 'acara', 'kegiatan', 'event', 'jadwal'],
            'residents' => ['warga', 'resident', 'jumlah', 'cari', 'kontak'],
            'finance' => ['rekap', 'keuangan', 'laporan', 'kas'],
            'knowledge_base' => ['prosedur', 'cara', 'syarat', 'dokumen', 'surat'],
        ];

        foreach ($patterns as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (Str::contains($normalized, $keyword)) {
                    return [
                        'intent' => $intent,
                        'confidence' => 0.7,
                        'all_scores' => [$intent => 0.7],
                        'method' => 'rule',
                    ];
                }
            }
        }

        return [
            'intent' => null,
            'confidence' => 0.0,
            'all_scores' => [],
            'method' => 'rule',
        ];
    }
}
