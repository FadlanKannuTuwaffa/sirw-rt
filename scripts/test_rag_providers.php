<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

loadEnvFile(dirname(__DIR__) . '/.env');

$results = [
    'algolia' => testAlgolia(),
    'bonsai' => testBonsai(),
    'qdrant' => testQdrant(),
    'jina_reranker' => testJinaReranker(),
    'cohere_reranker' => testCohereReranker(),
];

echo json_encode($results, JSON_PRETTY_PRINT) . PHP_EOL;

function testAlgolia(): array
{
    $appId = envValue('ALGOLIA_APP_ID');
    $apiKey = envValue('ALGOLIA_API_KEY');
    $index = envValue('RAG_ALGOLIA_INDEX', 'rt_kb');

    if (!$appId || !$apiKey) {
        return skipped('App ID or API key missing');
    }

    $endpoint = sprintf('https://%s-dsn.algolia.net/1/indexes/%s/query', $appId, urlencode($index));
    $payload = json_encode(['query' => '', 'hitsPerPage' => 1]);
    $headers = [
        'X-Algolia-API-Key: ' . $apiKey,
        'X-Algolia-Application-Id: ' . $appId,
        'Content-Type: application/json',
    ];

    return sendRequest('POST', $endpoint, $headers, $payload);
}

function testBonsai(): array
{
    $url = envValue('ELASTICSEARCH_URL');
    if (!$url) {
        return skipped('ELASTICSEARCH_URL missing');
    }

    $parts = parse_url($url);
    if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
        return failed('Invalid Bonsai URL');
    }

    $base = $parts['scheme'] . '://' . $parts['host'];
    if (!empty($parts['port'])) {
        $base .= ':' . $parts['port'];
    }

    $endpoint = $base . '/_cluster/health';
    $headers = ['Accept: application/json'];
    $auth = (isset($parts['user'], $parts['pass'])) ? sprintf('%s:%s', $parts['user'], $parts['pass']) : null;

    return sendRequest('GET', $endpoint, $headers, null, $auth);
}

function testQdrant(): array
{
    $host = rtrim((string) envValue('RAG_QDRANT_HOST'), '/');
    $key = envValue('RAG_QDRANT_KEY');
    if ($host === '') {
        return skipped('RAG_QDRANT_HOST missing');
    }

    $headers = ['Content-Type: application/json'];
    if ($key) {
        $headers[] = 'api-key: ' . $key;
    }

    return sendRequest('GET', $host . '/collections', $headers);
}

function testJinaReranker(): array
{
    $endpoint = envValue('RAG_RERANKER_JINA_ENDPOINT');
    $apiKey = envValue('RAG_RERANKER_JINA_API_KEY');
    $model = envValue('RAG_RERANKER_JINA_MODEL', 'jina-reranker-v1');

    if (!$endpoint || !$apiKey) {
        return skipped('Jina endpoint or API key missing');
    }

    $payload = json_encode([
        'model' => $model,
        'query' => 'tes sambungan',
        'documents' => [['text' => 'ini hanya uji coba sambungan reranker']],
        'top_n' => 1,
    ]);

    $headers = [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
    ];

    return sendRequest('POST', $endpoint, $headers, $payload);
}

function testCohereReranker(): array
{
    $endpoint = envValue('RAG_RERANKER_COHERE_ENDPOINT', 'https://api.cohere.ai/v1/rerank');
    $apiKey = envValue('RAG_RERANKER_COHERE_API_KEY');
    $model = envValue('RAG_RERANKER_COHERE_MODEL', 'rerank-english-v2.0');

    if (!$apiKey) {
        return skipped('Cohere API key missing');
    }

    $payload = json_encode([
        'query' => 'tes sambungan',
        'documents' => ['ini hanya uji coba reranker cohere'],
        'top_n' => 1,
        'model' => $model,
    ]);

    $headers = [
        'Authorization: Bearer ' . $apiKey,
        'Cohere-Version: 2022-12-06',
        'Content-Type: application/json',
    ];

    return sendRequest('POST', $endpoint, $headers, $payload);
}

function envValue(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return $value;
}

function sendRequest(string $method, string $url, array $headers, ?string $body = null, ?string $basicAuth = null): array
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }

    if ($basicAuth !== null) {
        curl_setopt($ch, CURLOPT_USERPWD, $basicAuth);
    }

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return failed($error ?: 'Unknown cURL error');
    }

    $isSuccess = $status >= 200 && $status < 300;

    return [
        'status' => $isSuccess ? 'ok' : 'error',
        'http_code' => $status,
        'message' => summarizeBody($response),
    ];
}

function summarizeBody(string $body): string
{
    $trimmed = trim($body);
    if ($trimmed === '') {
        return 'empty response';
    }

    $json = json_decode($trimmed, true);
    if (is_array($json)) {
        return substr(json_encode(array_slice($json, 0, 3)), 0, 200);
    }

    return substr($trimmed, 0, 200);
}

function skipped(string $reason): array
{
    return ['status' => 'skipped', 'message' => $reason];
}

function failed(string $reason): array
{
    return ['status' => 'error', 'message' => $reason];
}

function loadEnvFile(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

