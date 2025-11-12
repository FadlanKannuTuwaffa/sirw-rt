<?php

namespace App\Services\Assistant\Retrieval;

use App\Models\AssistantKbDocumentWeight;
use App\Services\Assistant\Embeddings\SimpleEmbeddingGenerator;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class HybridRetriever
{
    private Client $http;
    private MiniLmReranker $reranker;
    private SimpleEmbeddingGenerator $embedder;
    private array $config;
    private bool $indexesEnsured = false;
    private array $lexicalDrivers = [];
    private ?array $bonsaiEndpoint = null;

    public function __construct(
        ?Client $http = null,
        ?MiniLmReranker $reranker = null,
        ?SimpleEmbeddingGenerator $embedder = null
    ) {
        $this->http = $http ?? new Client(['timeout' => 8]);
        $this->reranker = $reranker ?? new MiniLmReranker($embedder);
        $this->embedder = $embedder ?? new SimpleEmbeddingGenerator();
        $this->config = config('rag', []);
        $configuredDrivers = $this->config['lexical_drivers'] ?? ['meilisearch'];
        $this->lexicalDrivers = array_values(array_filter($configuredDrivers, function (string $driver): bool {
            return $this->lexicalDriverConfigured($driver);
        }));
        $this->bonsaiEndpoint = $this->parseBonsaiUrl();
    }

    public function isConfigured(): bool
    {
        return $this->lexicalDrivers !== []
            && !empty($this->config['qdrant']['host'] ?? null);
    }

    /**
     * @return array{success:bool, confidence?:float, documents?:array<int, array<string, mixed>>, reason?:string, suggested_titles?:array<int,string>}
     */
    public function search(string $query, ?int $limit = null): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'reason' => 'not_configured'];
        }

        $limit = $limit ?? (int) ($this->config['max_sources'] ?? 3);
        $lexical = [];

        foreach ($this->lexicalDrivers as $driver) {
            $lexical = array_merge($lexical, $this->searchLexical($driver, $query));
        }

        $vector = $this->searchQdrant($query);

        if ($lexical === [] && $vector === []) {
            return ['success' => false, 'reason' => 'no_result'];
        }

        $candidates = $this->mergeCandidates($lexical, $vector);
        if ($candidates === []) {
            return ['success' => false, 'reason' => 'no_result'];
        }

        $selected = $this->mmr(
            $candidates,
            (int) Arr::get($this->config, 'mmr.k', 6),
            (float) Arr::get($this->config, 'mmr.lambda', 0.65)
        );

        if ($selected === []) {
            return ['success' => false, 'reason' => 'no_result'];
        }

        $reranked = $this->reranker->rerank($query, $selected);

        if ($reranked === []) {
            return ['success' => false, 'reason' => 'no_result'];
        }

        $top = array_slice($reranked, 0, $limit);
        $confidence = (float) ($top[0]['score'] ?? 0.0);
        $threshold = (float) ($this->config['confidence_threshold'] ?? 0.4);

        if ($confidence < $threshold) {
            return [
                'success' => false,
                'reason' => 'low_confidence',
                'confidence' => $confidence,
                'documents' => $top,
                'suggested_titles' => array_values(array_unique(array_map(
                    fn ($doc) => $doc['title'] ?? 'Dokumen',
                    $top
                ))),
            ];
        }

        return [
            'success' => true,
            'confidence' => $confidence,
            'documents' => $top,
        ];
    }

    /**
     * @param  array<string, mixed>  $document
     */
    public function indexDocument(array $document, ?array $embedding = null): void
    {
        if (!$this->isConfigured()) {
            return;
        }

        $this->ensureIndexes();

        $payload = [
            'id' => $document['id'],
            'article_id' => $document['article_id'] ?? null,
            'title' => $document['title'] ?? 'Dokumen',
            'content' => $document['content'] ?? '',
            'snippet' => Str::limit($document['content'] ?? '', 260),
        ];

        foreach ($this->lexicalDrivers as $driver) {
            $this->indexLexical($driver, $payload);
        }

        $this->indexQdrant($payload, $embedding);
    }

    public function flushIndexes(): void
    {
        if (!$this->isConfigured()) {
            return;
        }

        foreach ($this->lexicalDrivers as $driver) {
            $this->flushLexical($driver);
        }

        $this->deleteQdrantPoints();
        $this->indexesEnsured = false;
    }

    public function deleteDocument(int|string $id): void
    {
        if (!$this->isConfigured()) {
            return;
        }

        foreach ($this->lexicalDrivers as $driver) {
            $this->deleteLexicalDocument($driver, $id);
        }

        $this->deleteQdrantPoint($id);
    }

    private function searchLexical(string $driver, string $query): array
    {
        return match ($driver) {
            'algolia' => $this->searchAlgolia($query),
            'bonsai' => $this->searchBonsai($query),
            default => $this->searchMeilisearch($query),
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function searchMeilisearch(string $query): array
    {
        $host = rtrim((string) Arr::get($this->config, 'meilisearch.host'), '/');
        if ($host === '') {
            return [];
        }

        $index = Arr::get($this->config, 'meilisearch.index', 'rt_kb');
        $limit = (int) Arr::get($this->config, 'meilisearch.search_limit', 12);

        try {
            $response = $this->http->post("{$host}/indexes/{$index}/search", [
                'headers' => $this->meiliHeaders(),
                'json' => [
                    'q' => $query,
                    'limit' => $limit,
                    'attributesToRetrieve' => ['id', 'title', 'content', 'article_id'],
                ],
            ]);

            $data = json_decode((string) $response->getBody(), true);

            if (!isset($data['hits'])) {
                return [];
            }

            return array_map(function ($hit) {
                return [
                    'id' => $hit['id'] ?? null,
                    'title' => $hit['title'] ?? 'Dokumen',
                    'content' => $hit['content'] ?? '',
                    'article_id' => $hit['article_id'] ?? null,
                    'source' => 'meilisearch',
                    'lexical_score' => (float) ($hit['_rankingScore'] ?? 0),
                ];
            }, $data['hits']);
        } catch (\Throwable $e) {
            Log::warning('HybridRetriever Meilisearch error', [
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function searchAlgolia(string $query): array
    {
        $appId = Arr::get($this->config, 'algolia.app_id');
        $apiKey = Arr::get($this->config, 'algolia.api_key');
        $index = Arr::get($this->config, 'algolia.index', 'rt_kb');
        $limit = (int) Arr::get($this->config, 'algolia.search_limit', 12);

        if (!$appId || !$apiKey) {
            return [];
        }

        $endpoint = sprintf('https://%s-dsn.algolia.net/1/indexes/%s/query', $appId, urlencode($index));

        try {
            $response = $this->http->post($endpoint, [
                'headers' => [
                    'X-Algolia-API-Key' => $apiKey,
                    'X-Algolia-Application-Id' => $appId,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'query' => $query,
                    'hitsPerPage' => $limit,
                    'attributesToRetrieve' => ['title', 'content', 'article_id'],
                ],
            ]);

            $data = json_decode((string) $response->getBody(), true);
            $hits = $data['hits'] ?? [];

            return array_map(function (array $hit): array {
                $ranking = (float) Arr::get($hit, '_rankingInfo.rankingScore', 0);

                return [
                    'id' => $hit['objectID'] ?? null,
                    'title' => $hit['title'] ?? 'Dokumen',
                    'content' => $hit['content'] ?? '',
                    'article_id' => $hit['article_id'] ?? null,
                    'source' => 'algolia',
                    'lexical_score' => $ranking,
                ];
            }, $hits);
        } catch (\Throwable $e) {
            Log::warning('HybridRetriever Algolia error', [
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function searchBonsai(string $query): array
    {
        if ($this->bonsaiEndpoint === null) {
            return [];
        }

        $index = Arr::get($this->config, 'bonsai.index', 'rt_kb');
        $limit = (int) Arr::get($this->config, 'bonsai.search_limit', 12);
        $url = "{$this->bonsaiEndpoint['base']}/{$index}/_search";

        try {
            $response = $this->http->post($url, [
                'auth' => $this->bonsaiEndpoint['auth'],
                'json' => [
                    'size' => $limit,
                    '_source' => ['title', 'content', 'article_id'],
                    'query' => [
                        'multi_match' => [
                            'query' => $query,
                            'type' => 'best_fields',
                            'fields' => ['title^2', 'content'],
                        ],
                    ],
                ],
            ]);

            $data = json_decode((string) $response->getBody(), true);
            $hits = $data['hits']['hits'] ?? [];

            return array_map(function (array $hit): array {
                return [
                    'id' => $hit['_id'] ?? null,
                    'title' => Arr::get($hit, '_source.title', 'Dokumen'),
                    'content' => Arr::get($hit, '_source.content', ''),
                    'article_id' => Arr::get($hit, '_source.article_id'),
                    'source' => 'bonsai',
                    'lexical_score' => (float) ($hit['_score'] ?? 0.0),
                ];
            }, $hits);
        } catch (\Throwable $e) {
            Log::warning('HybridRetriever Bonsai error', [
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function searchQdrant(string $query): array
    {
        $host = rtrim((string) Arr::get($this->config, 'qdrant.host'), '/');
        $limit = (int) Arr::get($this->config, 'qdrant.search_limit', 12);

        if ($host === '') {
            return [];
        }

        try {
            $response = $this->http->post("{$host}/collections/" . Arr::get($this->config, 'qdrant.collection', 'rt_kb') . "/points/search", [
                'headers' => $this->qdrantHeaders(),
                'json' => [
                    'vector' => $this->embedder->generate($query),
                    'with_payload' => true,
                    'with_vector' => false,
                    'limit' => $limit,
                ],
            ]);

            $data = json_decode((string) $response->getBody(), true);

            if (!isset($data['result'])) {
                return [];
            }

            return array_map(function ($result) {
                $payload = $result['payload'] ?? [];

                return [
                    'id' => $result['id'] ?? null,
                    'title' => $payload['title'] ?? 'Dokumen',
                    'content' => $payload['content'] ?? '',
                    'article_id' => $payload['article_id'] ?? null,
                    'source' => 'qdrant',
                    'vector_score' => (float) ($result['score'] ?? 0.0),
                    'embedding' => null,
                ];
            }, $data['result']);
        } catch (\Throwable $e) {
            Log::warning('HybridRetriever Qdrant error', [
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    private function mergeCandidates(array $lexical, array $vector): array
    {
        $candidates = [];

        foreach (array_merge($lexical, $vector) as $doc) {
            $id = $doc['id'] ?? null;
            if ($id === null) {
                continue;
            }

            if (!isset($candidates[$id])) {
                $candidates[$id] = [
                    'id' => $id,
                    'title' => $doc['title'] ?? 'Dokumen',
                    'content' => $doc['content'] ?? '',
                    'article_id' => $doc['article_id'] ?? null,
                    'source' => $doc['source'] ?? 'unknown',
                    'lexical_score' => 0.0,
                    'vector_score' => 0.0,
                    'embedding' => $doc['embedding'] ?? null,
                ];
            }

            $candidates[$id]['lexical_score'] = max(
                $candidates[$id]['lexical_score'],
                (float) ($doc['lexical_score'] ?? 0.0)
            );
            $candidates[$id]['vector_score'] = max(
                $candidates[$id]['vector_score'],
                (float) ($doc['vector_score'] ?? 0.0)
            );
        }

        return array_values(array_map(function ($candidate) {
            $candidate['base_score'] = 0.5 * $candidate['vector_score'] + 0.5 * $candidate['lexical_score'];
            $candidate['weight'] = $this->documentWeightFor($candidate['id']);
            $candidate['base_score'] *= $candidate['weight'];
            $candidate['snippet'] = Str::limit($candidate['content'], 260);

            return $candidate;
        }, $candidates));
    }

    private function documentWeightFor(int|string|null $documentId): float
    {
        if ($documentId === null || !Schema::hasTable('assistant_kb_document_weights')) {
            return 1.0;
        }

        $weights = Cache::remember('assistant_kb_document_weights', 600, function () {
            return AssistantKbDocumentWeight::query()
                ->pluck('weight', 'document_id')
                ->map(fn ($weight) => (float) $weight)
                ->toArray();
        });

        return max(0.1, min(2.0, (float) ($weights[(string) $documentId] ?? 1.0)));
    }

    /**
     * @param  array<int, array<string, mixed>>  $candidates
     * @return array<int, array<string, mixed>>
     */
    private function mmr(array $candidates, int $k, float $lambda): array
    {
        $selected = [];
        $k = max(1, $k);

        while (count($selected) < $k && $candidates !== []) {
            $bestIndex = null;
            $bestScore = -INF;

            foreach ($candidates as $index => $candidate) {
                $redundancy = 0.0;

                foreach ($selected as $chosen) {
                    $redundancy = max($redundancy, $this->embedder->cosine(
                        $candidate['embedding'] ?? $this->embedder->generate($candidate['content']),
                        $chosen['embedding'] ?? $this->embedder->generate($chosen['content'])
                    ));
                }

                $mmrScore = $lambda * ($candidate['base_score'] ?? 0.0)
                    - (1 - $lambda) * $redundancy;

                if ($mmrScore > $bestScore) {
                    $bestScore = $mmrScore;
                    $bestIndex = $index;
                }
            }

            if ($bestIndex === null) {
                break;
            }

            $selected[] = $candidates[$bestIndex];
            unset($candidates[$bestIndex]);
        }

        return $selected;
    }

    private function ensureIndexes(): void
    {
        if ($this->indexesEnsured) {
            return;
        }

        if (in_array('meilisearch', $this->lexicalDrivers, true)) {
            $this->ensureMeilisearchIndex();
        }

        $this->ensureQdrantCollection();
        $this->indexesEnsured = true;
    }

    private function ensureMeilisearchIndex(): void
    {
        $host = rtrim((string) Arr::get($this->config, 'meilisearch.host'), '/');
        if ($host === '') {
            return;
        }

        $index = Arr::get($this->config, 'meilisearch.index', 'rt_kb');

        try {
            $this->http->post("{$host}/indexes", [
                'headers' => $this->meiliHeaders(),
                'json' => [
                    'uid' => $index,
                    'primaryKey' => 'id',
                ],
            ]);
        } catch (\Throwable $e) {
            // Index likely exists already.
        }
    }

    private function ensureQdrantCollection(): void
    {
        $host = rtrim((string) Arr::get($this->config, 'qdrant.host'), '/');
        if ($host === '') {
            return;
        }

        $collection = Arr::get($this->config, 'qdrant.collection', 'rt_kb');

        try {
            $this->http->put("{$host}/collections/{$collection}", [
                'headers' => $this->qdrantHeaders(),
                'json' => [
                    'vectors' => [
                        'size' => $this->embedder->dimensions(),
                        'distance' => 'Cosine',
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            // Collection might already exist; ignore.
        }
    }

    private function indexLexical(string $driver, array $document): void
    {
        match ($driver) {
            'algolia' => $this->indexAlgolia($document),
            'bonsai' => $this->indexBonsai($document),
            default => $this->indexMeilisearch($document),
        };
    }

    private function flushLexical(string $driver): void
    {
        match ($driver) {
            'algolia' => $this->deleteAlgoliaIndex(),
            'bonsai' => $this->deleteBonsaiIndex(),
            default => $this->deleteMeilisearchDocuments(),
        };
    }

    private function deleteLexicalDocument(string $driver, int|string $id): void
    {
        match ($driver) {
            'algolia' => $this->deleteAlgoliaDocument($id),
            'bonsai' => $this->deleteBonsaiDocument($id),
            default => $this->deleteMeiliDocument($id),
        };
    }

    private function indexMeilisearch(array $document): void
    {
        try {
            $host = rtrim((string) Arr::get($this->config, 'meilisearch.host'), '/');
            if ($host === '') {
                return;
            }

            $index = Arr::get($this->config, 'meilisearch.index', 'rt_kb');

            $this->http->post("{$host}/indexes/{$index}/documents", [
                'headers' => $this->meiliHeaders(),
                'json' => [$document],
            ]);
        } catch (\Throwable $e) {
            Log::warning('HybridRetriever unable to index Meilisearch document', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function indexAlgolia(array $document): void
    {
        $appId = Arr::get($this->config, 'algolia.app_id');
        $apiKey = Arr::get($this->config, 'algolia.api_key');
        $index = Arr::get($this->config, 'algolia.index', 'rt_kb');

        if (!$appId || !$apiKey) {
            return;
        }

        $endpoint = sprintf('https://%s.algolia.net/1/indexes/%s/%s', $appId, urlencode($index), urlencode((string) $document['id']));

        try {
            $this->http->put($endpoint, [
                'headers' => [
                    'X-Algolia-API-Key' => $apiKey,
                    'X-Algolia-Application-Id' => $appId,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'objectID' => (string) $document['id'],
                    'title' => $document['title'],
                    'content' => $document['content'],
                    'article_id' => $document['article_id'],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('HybridRetriever unable to index Algolia document', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function indexBonsai(array $document): void
    {
        if ($this->bonsaiEndpoint === null) {
            return;
        }

        $index = Arr::get($this->config, 'bonsai.index', 'rt_kb');
        $url = "{$this->bonsaiEndpoint['base']}/{$index}/_doc/" . urlencode((string) $document['id']);

        try {
            $this->http->put($url, [
                'auth' => $this->bonsaiEndpoint['auth'],
                'json' => [
                    'title' => $document['title'],
                    'content' => $document['content'],
                    'article_id' => $document['article_id'],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('HybridRetriever unable to index Bonsai document', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function indexQdrant(array $document, ?array $embedding = null): void
    {
        try {
            $host = rtrim((string) Arr::get($this->config, 'qdrant.host'), '/');
            if ($host === '') {
                return;
            }

            $collection = Arr::get($this->config, 'qdrant.collection', 'rt_kb');
            $vector = $embedding ?? $this->embedder->generate($document['content'] ?? '');

            $this->http->put("{$host}/collections/{$collection}/points", [
                'headers' => $this->qdrantHeaders(),
                'json' => [
                    'points' => [[
                        'id' => $document['id'],
                        'vector' => array_values($vector),
                        'payload' => [
                            'title' => $document['title'],
                            'content' => $document['content'],
                            'article_id' => $document['article_id'],
                            'embedding' => $vector,
                        ],
                    ]],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('HybridRetriever unable to index Qdrant point', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function deleteMeiliDocument(int|string $id): void
    {
        try {
            $host = rtrim((string) Arr::get($this->config, 'meilisearch.host'), '/');
            if ($host === '') {
                return;
            }

            $index = Arr::get($this->config, 'meilisearch.index', 'rt_kb');

            $this->http->delete("{$host}/indexes/{$index}/documents/{$id}", [
                'headers' => $this->meiliHeaders(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('HybridRetriever unable to delete Meilisearch document', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function deleteAlgoliaDocument(int|string $id): void
    {
        $appId = Arr::get($this->config, 'algolia.app_id');
        $apiKey = Arr::get($this->config, 'algolia.api_key');
        if (!$appId || !$apiKey) {
            return;
        }

        $index = Arr::get($this->config, 'algolia.index', 'rt_kb');
        $endpoint = sprintf('https://%s.algolia.net/1/indexes/%s/%s', $appId, urlencode($index), urlencode((string) $id));

        try {
            $this->http->delete($endpoint, [
                'headers' => [
                    'X-Algolia-API-Key' => $apiKey,
                    'X-Algolia-Application-Id' => $appId,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('HybridRetriever unable to delete Algolia document', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function deleteBonsaiDocument(int|string $id): void
    {
        if ($this->bonsaiEndpoint === null) {
            return;
        }

        $index = Arr::get($this->config, 'bonsai.index', 'rt_kb');
        $url = "{$this->bonsaiEndpoint['base']}/{$index}/_doc/" . urlencode((string) $id);

        try {
            $this->http->delete($url, [
                'auth' => $this->bonsaiEndpoint['auth'],
            ]);
        } catch (\Throwable $e) {
            Log::warning('HybridRetriever unable to delete Bonsai document', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function deleteAlgoliaIndex(): void
    {
        $appId = Arr::get($this->config, 'algolia.app_id');
        $apiKey = Arr::get($this->config, 'algolia.api_key');
        if (!$appId || !$apiKey) {
            return;
        }

        $index = Arr::get($this->config, 'algolia.index', 'rt_kb');
        $endpoint = sprintf('https://%s.algolia.net/1/indexes/%s', $appId, urlencode($index));

        try {
            $this->http->delete($endpoint, [
                'headers' => [
                    'X-Algolia-API-Key' => $apiKey,
                    'X-Algolia-Application-Id' => $appId,
                ],
            ]);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    private function deleteBonsaiIndex(): void
    {
        if ($this->bonsaiEndpoint === null) {
            return;
        }

        $index = Arr::get($this->config, 'bonsai.index', 'rt_kb');
        $url = "{$this->bonsaiEndpoint['base']}/{$index}";

        try {
            $this->http->delete($url, [
                'auth' => $this->bonsaiEndpoint['auth'],
            ]);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    private function deleteMeilisearchDocuments(): void
    {
        try {
            $host = rtrim((string) Arr::get($this->config, 'meilisearch.host'), '/');
            if ($host === '') {
                return;
            }

            $index = Arr::get($this->config, 'meilisearch.index', 'rt_kb');
            $this->http->delete("{$host}/indexes/{$index}", [
                'headers' => $this->meiliHeaders(),
            ]);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    private function deleteQdrantPoint(int|string $id): void
    {
        try {
            $host = rtrim((string) Arr::get($this->config, 'qdrant.host'), '/');
            if ($host === '') {
                return;
            }

            $collection = Arr::get($this->config, 'qdrant.collection', 'rt_kb');

            $this->http->post("{$host}/collections/{$collection}/points/delete", [
                'headers' => $this->qdrantHeaders(),
                'json' => [
                    'points' => [(int) $id],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('HybridRetriever unable to delete Qdrant point', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function deleteQdrantPoints(): void
    {
        try {
            $host = rtrim((string) Arr::get($this->config, 'qdrant.host'), '/');
            if ($host === '') {
                return;
            }

            $collection = Arr::get($this->config, 'qdrant.collection', 'rt_kb');
            $this->http->delete("{$host}/collections/{$collection}", [
                'headers' => $this->qdrantHeaders(),
            ]);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    private function lexicalDriverConfigured(string $driver): bool
    {
        return match (strtolower($driver)) {
            'algolia' => !empty($this->config['algolia']['app_id'] ?? null)
                && !empty($this->config['algolia']['api_key'] ?? null),
            'bonsai' => !empty($this->config['bonsai']['url'] ?? null),
            default => !empty($this->config['meilisearch']['host'] ?? null),
        };
    }

    private function meiliHeaders(): array
    {
        $headers = ['Content-Type' => 'application/json'];
        $key = Arr::get($this->config, 'meilisearch.key');
        if ($key) {
            $headers['Authorization'] = 'Bearer ' . $key;
        }

        return $headers;
    }

    private function qdrantHeaders(): array
    {
        $headers = ['Content-Type' => 'application/json'];
        $key = Arr::get($this->config, 'qdrant.key');
        if ($key) {
            $headers['api-key'] = $key;
        }

        return $headers;
    }

    private function parseBonsaiUrl(): ?array
    {
        $url = Arr::get($this->config, 'bonsai.url');
        if (!$url) {
            return null;
        }

        $parts = parse_url($url);
        if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $base = $parts['scheme'] . '://' . $parts['host'];
        if (!empty($parts['port'])) {
            $base .= ':' . $parts['port'];
        }

        $user = $parts['user'] ?? null;
        $pass = $parts['pass'] ?? null;

        return [
            'base' => $base,
            'auth' => [$user ?? '', $pass ?? ''],
        ];
    }
}
