<?php

namespace App\Services\Assistant;

use App\Services\Assistant\Exceptions\LLMClientException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HuggingFaceClient implements LLMClient
{
    /**
     * @var array<int, string>
     */
    private array $baseUrls = [
        'https://router.huggingface.co/v1',
        'https://router.huggingface.co/hf-inference/v1',
    ];

    private string $model;

    public function __construct()
    {
        $this->model = config('services.huggingface.model', 'microsoft/Phi-3-mini-4k-instruct');
        $configuredEndpoints = config('services.huggingface.endpoints', []);

        if (is_array($configuredEndpoints) && $configuredEndpoints !== []) {
            $this->baseUrls = array_values($configuredEndpoints);
        }
    }

    public function chat(array $messages, array $tools = []): array
    {
        $lastException = null;
        $fullMessages = $this->ensureSystemPrompt($messages);

        foreach ($this->baseUrls as $index => $baseUrl) {
            $baseUrl = rtrim($baseUrl, '/');
            $endpoint = "{$baseUrl}/chat/completions";

            try {
                Log::info('HuggingFace request', [
                    'model' => $this->model,
                    'endpoint' => $endpoint,
                    'messages_count' => count($fullMessages),
                ]);

                $response = Http::timeout(60)
                    ->withHeaders($this->headers())
                    ->post($endpoint, [
                        'model' => $this->model,
                        'messages' => $this->normalizeMessages($fullMessages),
                        'temperature' => 0.2,
                        'max_tokens' => 1000,
                    ]);

                Log::info('HuggingFace response', [
                    'status' => $response->status(),
                    'endpoint' => $endpoint,
                ]);

                if ($response->failed()) {
                    Log::warning('HuggingFace request failed', [
                        'status' => $response->status(),
                        'endpoint' => $endpoint,
                        'body' => $response->body(),
                    ]);

                    throw LLMClientException::fromService(
                        'HuggingFace',
                        'API request failed: ' . $response->body(),
                        $response->status()
                    );
                }

                $data = $response->json();

                if (isset($data['error'])) {
                    throw LLMClientException::fromService('HuggingFace', $data['error']);
                }

                $choice = $data['choices'][0]['message']['content'] ?? null;

                if ($choice === null || trim((string) $choice) === '') {
                    throw LLMClientException::fromService('HuggingFace', 'Empty response');
                }

                return [
                    'content' => trim((string) $choice),
                    'provider' => 'HuggingFace',
                ];
            } catch (LLMClientException $e) {
                $lastException = $e;

                // Coba endpoint berikutnya jika masih tersedia
                if ($index < count($this->baseUrls) - 1) {
                    continue;
                }

                throw $e;
            } catch (\Exception $e) {
                $lastException = LLMClientException::fromService('HuggingFace', $e->getMessage());

                if ($index < count($this->baseUrls) - 1) {
                    continue;
                }

                throw $lastException;
            }
        }

        throw $lastException ?? LLMClientException::fromService('HuggingFace', 'All endpoints failed');
    }

    /**
     * @param array<int, array<string, mixed>> $messages
     * @return array<int, array<string, mixed>>
     */
    private function ensureSystemPrompt(array $messages): array
    {
        foreach ($messages as $message) {
            if (($message['role'] ?? '') === 'system') {
                return $messages;
            }
        }

        array_unshift($messages, [
            'role' => 'system',
            'content' => SystemPrompt::get(),
        ]);

        return $messages;
    }

    /**
     * @param array<int, array<string, mixed>> $messages
     * @return array<int, array<string, string>>
     */
    private function normalizeMessages(array $messages): array
    {
        $normalized = [];

        foreach ($messages as $message) {
            $role = (string) ($message['role'] ?? 'user');
            $content = $message['content'] ?? '';

            if (is_array($content)) {
                $content = collect($content)
                    ->pluck('text')
                    ->filter()
                    ->implode("\n");
            }

            $normalized[] = [
                'role' => $role,
                'content' => is_string($content) ? $content : json_encode($content, JSON_UNESCAPED_UNICODE),
            ];
        }

        return $normalized;
    }

    public function supportsStreaming(): bool
    {
        return false;
    }

    public function stream(array $messages, array $tools, callable $onEvent): array
    {
        $response = $this->chat($messages, $tools);

        if (isset($response['content'])) {
            $onEvent('token', (string) $response['content']);
        }

        return $response + ['provider' => 'HuggingFace'];
    }

    public function embed(string $text): ?array
    {
        return null;
    }



    private function headers(): array
    {
        $apiKey = config('services.huggingface.api_key');

        if (!$apiKey) {
            throw new \RuntimeException('HUGGINGFACE_API_KEY is not configured.');
        }

        return [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ];
    }
}
