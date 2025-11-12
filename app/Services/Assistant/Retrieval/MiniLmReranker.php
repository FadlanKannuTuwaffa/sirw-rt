<?php

namespace App\Services\Assistant\Retrieval;

use App\Services\Assistant\Embeddings\SimpleEmbeddingGenerator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MiniLmReranker
{
    private array $providers = [];
    private array $providerBreakers = [];
    private ?string $endpoint;
    private ?string $apiKey;
    private string $model;
    private int $timeout;
    private SimpleEmbeddingGenerator $embedder;

    public function __construct(?SimpleEmbeddingGenerator $embedder = null)
    {
        $config = config('rag.reranker', []);
        $this->providers = $config['providers'] ?? [];
        $this->endpoint = $config['endpoint'] ?? null;
        $this->apiKey = $config['api_key'] ?? null;
        $this->model = $config['model'] ?? 'cross-encoder/ms-marco-MiniLM-L-6-v2';
        $this->timeout = (int) ($config['timeout'] ?? 10);
        $this->embedder = $embedder ?? new SimpleEmbeddingGenerator();
    }

    /**
     * @param  array<int, array<string, mixed>>  $documents
     * @return array<int, array<string, mixed>>
     */
    public function rerank(string $query, array $documents): array
    {
        if ($documents === []) {
            return [];
        }

        foreach ($this->providers as $index => $provider) {
            $result = $this->rerankViaProvider($provider, $query, $documents, $index);
            if ($result !== null && $result !== []) {
                return $result;
            }
        }

        if ($this->canUseRemote()) {
            $ranked = $this->rerankViaFallbackApi($query, $documents);
            if ($ranked !== null) {
                return $ranked;
            }
        }

        return $this->rerankLocally($query, $documents);
    }

    private function canUseRemote(): bool
    {
        return $this->endpoint !== null && $this->apiKey !== null;
    }

    private function rerankViaProvider(array $provider, string $query, array $documents, int $providerIndex): ?array
    {
        $driver = Str::lower($provider['driver'] ?? '');
        if ($driver === '') {
            return null;
        }

        $breakerKey = $driver . ':' . $providerIndex;
        $now = microtime(true);
        if (isset($this->providerBreakers[$breakerKey]) && $this->providerBreakers[$breakerKey] > $now) {
            return null;
        }

        $attempts = 0;
        $maxAttempts = max(1, (int) ($provider['retries'] ?? 0)) + 1;
        $backoff = max(0, (int) ($provider['backoff_ms'] ?? 0));
        $timeoutSeconds = max(1, (int) ($provider['timeout_ms'] ?? 10000)) / 1000;

        while ($attempts < $maxAttempts) {
            $attempts++;
            try {
                return match ($driver) {
                    'jina' => $this->rerankWithJina($provider, $query, $documents, $timeoutSeconds),
                    'cohere' => $this->rerankWithCohere($provider, $query, $documents, $timeoutSeconds),
                    default => null,
                };
            } catch (\Throwable $e) {
                if ($attempts >= $maxAttempts) {
                    $this->openBreaker($breakerKey, (int) ($provider['breaker_open_secs'] ?? 30));
                    Log::warning('Reranker provider exhausted', [
                        'driver' => $driver,
                        'message' => $e->getMessage(),
                    ]);
                    return null;
                }

                if ($backoff > 0) {
                    usleep($backoff * 1000);
                }
            }
        }

        return null;
    }

    private function rerankWithJina(array $provider, string $query, array $documents, float $timeout): array
    {
        $endpoint = $provider['endpoint'] ?? null;
        $apiKey = $provider['api_key'] ?? null;
        if (!$endpoint || !$apiKey) {
            return [];
        }

        $topN = min(count($documents), max(1, (int) ($provider['top_n'] ?? count($documents))));
        $payload = [
            'model' => $provider['model'] ?? 'jina-reranker-v1',
            'query' => $query,
            'documents' => array_map(fn ($doc) => [
                'text' => $this->documentText($doc),
            ], $documents),
            'top_n' => $topN,
        ];

        $response = Http::timeout($timeout)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post($endpoint, $payload);

        $this->guardProviderResponse($response, $provider);

        $body = $response->json();
        $results = $body['data'] ?? $body['results'] ?? $body['output'] ?? [];

        return $this->normalizeProviderResults($results, $documents);
    }

    private function rerankWithCohere(array $provider, string $query, array $documents, float $timeout): array
    {
        $endpoint = $provider['endpoint'] ?? 'https://api.cohere.ai/v1/rerank';
        $apiKey = $provider['api_key'] ?? null;
        if (!$apiKey) {
            return [];
        }

        $topN = min(count($documents), max(1, (int) ($provider['top_n'] ?? count($documents))));

        $response = Http::timeout($timeout)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Cohere-Version' => '2022-12-06',
                'Content-Type' => 'application/json',
            ])
            ->post($endpoint, [
                'query' => $query,
                'documents' => array_map(fn ($doc) => $this->documentText($doc), $documents),
                'top_n' => $topN,
                'model' => $provider['model'] ?? 'rerank-english-v2.0',
            ]);

        $this->guardProviderResponse($response, $provider);

        $body = $response->json();
        $results = $body['results'] ?? [];

        return $this->normalizeProviderResults($results, $documents);
    }

    private function rerankViaFallbackApi(string $query, array $documents): ?array
    {
        try {
            $endpoint = $this->endpoint ?: ('https://api-inference.huggingface.co/models/' . $this->model);

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint, [
                    'inputs' => array_map(fn ($doc) => [
                        'text' => $query,
                        'text_pair' => $this->documentText($doc),
                    ], $documents),
                ]);

            if ($response->failed()) {
                Log::warning('MiniLm reranker API failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();
            if (!is_array($data)) {
                return null;
            }

            $scores = [];
            foreach ($data as $index => $classification) {
                if (is_array($classification) && isset($classification[0]['score'])) {
                    $scores[$index] = (float) $classification[0]['score'];
                } elseif (isset($classification['score'])) {
                    $scores[$index] = (float) $classification['score'];
                } else {
                    $scores[$index] = 0.0;
                }
            }

            foreach ($documents as $index => &$doc) {
                $doc['score'] = $scores[$index] ?? 0.0;
            }

            unset($doc);

            usort($documents, static fn ($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

            return $documents;
        } catch (\Throwable $e) {
            Log::error('MiniLm reranker fallback exception', [
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $documents
     * @return array<int, array<string, mixed>>
     */
    private function rerankLocally(string $query, array $documents): array
    {
        $queryVector = $this->embedder->generate($query);
        $scored = [];

        foreach ($documents as $doc) {
            $content = strip_tags((string) ($doc['content'] ?? $doc['snippet'] ?? ''));
            $vector = $doc['embedding'] ?? $this->embedder->generate($content);
            $score = $this->embedder->cosine($queryVector, $vector);
            $doc['score'] = round(0.5 * $score + 0.5 * ((float) ($doc['base_score'] ?? 0)), 6);
            $scored[] = $doc;
        }

        usort($scored, static fn ($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

        return $scored;
    }

    private function documentText(array $doc): string
    {
        $text = (string) ($doc['content'] ?? $doc['snippet'] ?? '');
        if ($text === '') {
            $text = (string) ($doc['title'] ?? '');
        }

        return mb_substr(strip_tags($text), 0, 4000);
    }

    private function normalizeProviderResults(array $results, array $documents): array
    {
        if ($results === []) {
            return [];
        }

        $scores = [];
        $texts = array_map(fn ($doc) => $this->documentText($doc), $documents);

        foreach ($results as $rank => $result) {
            $index = Arr::get($result, 'index');
            if ($index === null) {
                $index = Arr::get($result, 'document.index');
            }

            if ($index === null && isset($result['document']['text'])) {
                $index = array_search($result['document']['text'], $texts, true);
            }

            if ($index === null) {
                $index = $rank;
            }

            $score = (float) ($result['score'] ?? $result['relevance'] ?? $result['similarity'] ?? (1.0 - ($rank / max(count($results), 1))));
            $scores[(int) $index] = max($scores[(int) $index] ?? 0, $score);
        }

        if ($scores === []) {
            return [];
        }

        arsort($scores, SORT_NUMERIC);

        $ranked = [];
        foreach (array_keys($scores) as $idx) {
            if (!isset($documents[$idx])) {
                continue;
            }
            $doc = $documents[$idx];
            $doc['score'] = $scores[$idx];
            $ranked[] = $doc;
        }

        return $ranked;
    }

    private function guardProviderResponse($response, array $provider): void
    {
        $failoverCodes = array_map('intval', $provider['failover_codes'] ?? []);
        if ($response->failed()) {
            if (in_array($response->status(), $failoverCodes, true)) {
                throw new \RuntimeException('Provider returned failover status: ' . $response->status());
            }

            Log::warning('Reranker provider failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('Provider error');
        }
    }

    private function openBreaker(string $key, int $seconds): void
    {
        $this->providerBreakers[$key] = microtime(true) + max(1, $seconds);
    }
}

