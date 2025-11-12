<?php

namespace App\Services\Assistant;

use App\Services\Assistant\Exceptions\LLMClientException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MistralClient implements LLMClient
{
    private string $apiKey;
    private string $endpoint;
    private string $model;

    public function __construct()
    {
        $this->apiKey = (string) config('services.mistral.api_key', '');
        $this->endpoint = (string) config('services.mistral.endpoint', 'https://api.mistral.ai/v1/chat/completions');
        $this->model = (string) config('services.mistral.model', 'mistral-large-latest');
    }

    public function chat(array $messages, array $tools = []): array
    {
        $fullMessages = $this->ensureSystemPrompt($messages);

        $payload = [
            'model' => $this->model,
            'messages' => $fullMessages,
            'temperature' => 0.7,
            'max_tokens' => 500,
        ];

        if ($tools !== []) {
            $payload['tools'] = $tools;
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->endpoint, $payload);

            if ($response->failed()) {
                $status = $response->status();
                $body = $response->body();
                Log::error('Mistral API failed', ['status' => $status, 'body' => $body]);

                if ($status === 429 || str_contains((string) $body, 'rate limit')) {
                    throw LLMClientException::fromService('Mistral', 'Rate limit reached', $status);
                }

                throw LLMClientException::fromService('Mistral', 'API request failed', $status);
            }

            $data = $response->json();
            $choice = $data['choices'][0] ?? null;

            if ($choice === null) {
                throw LLMClientException::fromService('Mistral', 'No choices returned');
            }

            $message = $choice['message'] ?? [];

            if (!empty($message['tool_calls'])) {
                return [
                    'tool_calls' => $message['tool_calls'],
                    'provider' => 'Mistral',
                ];
            }

            $content = $message['content'] ?? '';

            if (trim((string) $content) === '') {
                throw LLMClientException::fromService('Mistral', 'Empty response content');
            }

            return [
                'content' => $content,
                'provider' => 'Mistral',
            ];
        } catch (LLMClientException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            Log::error('Mistral exception', ['error' => $exception->getMessage()]);
            throw LLMClientException::fromService('Mistral', $exception->getMessage());
        }
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

        return $response + ['provider' => 'Mistral'];
    }

    public function embed(string $text): ?array
    {
        return null;
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
}
