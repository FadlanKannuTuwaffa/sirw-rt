<?php

$defaultLexical = 'meilisearch';

$hasAlgolia = env('ALGOLIA_APP_ID') && env('ALGOLIA_API_KEY');
$hasBonsai = env('ELASTICSEARCH_URL');

if ($hasAlgolia) {
    $defaultLexical = 'algolia';
}

if ($hasBonsai) {
    $defaultLexical = $hasAlgolia ? 'algolia,bonsai' : 'bonsai';
}

$lexicalDrivers = array_filter(array_map(
    'trim',
    explode(',', env('RAG_LEXICAL_DRIVERS', $defaultLexical))
));

if ($lexicalDrivers === []) {
    $lexicalDrivers = ['meilisearch'];
}

$lexicalDrivers = array_values(array_unique(array_map(static function (string $driver): string {
    return strtolower($driver);
}, $lexicalDrivers)));

$rerankerProviders = [];
$commonProviderConfig = [
    'top_n' => (int) env('RAG_RERANKER_TOP_N', 16),
    'timeout_ms' => (int) env('RAG_RERANKER_TIMEOUT_MS', 10000),
    'retries' => (int) env('RAG_RERANKER_RETRIES', 1),
    'backoff_ms' => (int) env('RAG_RERANKER_BACKOFF_MS', 500),
    'failover_codes' => array_values(array_filter(array_map('intval', explode(
        ',',
        (string) env('RAG_RERANKER_FAILOVER_CODES', '429,500,502,503,504')
    )))),
    'breaker_open_secs' => (int) env('RAG_RERANKER_BREAKER_OPEN_SECS', 30),
];

if (env('RAG_RERANKER_JINA_ENDPOINT') && env('RAG_RERANKER_JINA_API_KEY')) {
    $rerankerProviders[] = array_merge($commonProviderConfig, [
        'driver' => 'jina',
        'endpoint' => env('RAG_RERANKER_JINA_ENDPOINT'),
        'api_key' => env('RAG_RERANKER_JINA_API_KEY'),
        'model' => env('RAG_RERANKER_JINA_MODEL', 'jina-reranker-v1'),
    ]);
}

if (env('RAG_RERANKER_COHERE_API_KEY')) {
    $rerankerProviders[] = array_merge($commonProviderConfig, [
        'driver' => 'cohere',
        'endpoint' => env('RAG_RERANKER_COHERE_ENDPOINT', 'https://api.cohere.ai/v1/rerank'),
        'api_key' => env('RAG_RERANKER_COHERE_API_KEY'),
        'model' => env('RAG_RERANKER_COHERE_MODEL', 'rerank-english-v2.0'),
    ]);
}

return [
    'enabled' => (bool) env('RAG_HYBRID_ENABLED', true),
    'confidence_threshold' => (float) env('RAG_CONFIDENCE_THRESHOLD', 0.4),
    'max_sources' => (int) env('RAG_MAX_SOURCES', 3),
    'lexical_drivers' => $lexicalDrivers,
    'mmr' => [
        'k' => (int) env('RAG_MMR_K', 6),
        'lambda' => (float) env('RAG_MMR_LAMBDA', 0.65),
    ],
    'meilisearch' => [
        'host' => env('RAG_MEILI_HOST'),
        'key' => env('RAG_MEILI_KEY'),
        'index' => env('RAG_MEILI_INDEX', 'rt_kb'),
        'search_limit' => (int) env('RAG_MEILI_TOP_K', 12),
    ],
    'algolia' => [
        'app_id' => env('ALGOLIA_APP_ID'),
        'api_key' => env('ALGOLIA_API_KEY'),
        'index' => env('RAG_ALGOLIA_INDEX', 'rt_kb'),
        'search_limit' => (int) env('RAG_ALGOLIA_TOP_K', 12),
    ],
    'bonsai' => [
        'url' => env('ELASTICSEARCH_URL'),
        'index' => env('ELASTICSEARCH_INDEX', 'rt_kb'),
        'search_limit' => (int) env('RAG_BONSAI_TOP_K', 12),
    ],
    'qdrant' => [
        'host' => env('RAG_QDRANT_HOST'),
        'key' => env('RAG_QDRANT_KEY'),
        'collection' => env('RAG_QDRANT_COLLECTION', 'rt_kb'),
        'search_limit' => (int) env('RAG_QDRANT_TOP_K', 12),
    ],
    'reranker' => [
        'providers' => $rerankerProviders,
        'model' => env('RAG_RERANKER_MODEL', 'cross-encoder/ms-marco-MiniLM-L-6-v2'),
        'endpoint' => env('RAG_RERANKER_ENDPOINT'),
        'api_key' => env('RAG_RERANKER_API_KEY', env('HUGGINGFACE_API_KEY')),
        'timeout' => (int) env('RAG_RERANKER_TIMEOUT', 10),
    ],
];

