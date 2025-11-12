<?php

namespace App\Services\Assistant;

use App\Services\Assistant\Exceptions\LLMClientException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LangDBClient implements LLMClient
{
    private string $apiKey;
    private string $endpoint;
    private string $model;

    public function __construct()
    {
        $this->apiKey = (string) config('services.langdb.api_key', '');
        $this->endpoint = (string) config('services.langdb.endpoint', 'https://api.us-east-1.langdb.ai/v1/chat/completions');
        $allowed = config('services.langdb.allowed_models', ['deepinfra/llama-3.1-8b-instruct']);
        $configuredModel = (string) config('services.langdb.model', 'deepinfra/llama-3.1-8b-instruct');

        if (!in_array($configuredModel, $allowed, true)) {
            throw new \RuntimeException(sprintf(
                'LangDB model "%s" is not in the allowed list: %s',
                $configuredModel,
                implode(', ', $allowed)
            ));
        }

        $this->model = $configuredModel;
    }

    public function chat(array $messages, array $tools = []): array
    {
        $fullMessages = $this->ensureSystemPrompt($messages);

        $payload = [
            'model' => $this->model,
            'messages' => $fullMessages,
            'temperature' => 0.8,
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
                Log::error('LangDB API failed', ['status' => $status, 'body' => $body]);

                if ($status === 429 || str_contains((string) $body, 'rate_limit')) {
                    throw LLMClientException::fromService('LangDB', 'Rate limit reached', $status);
                }

                throw LLMClientException::fromService('LangDB', 'API request failed', $status);
            }

            $data = $response->json();
            $choice = $data['choices'][0] ?? null;

            if ($choice === null) {
                throw LLMClientException::fromService('LangDB', 'No choices returned');
            }

            $message = $choice['message'] ?? [];

            if (!empty($message['tool_calls'])) {
                return [
                    'tool_calls' => $message['tool_calls'],
                    'provider' => 'LangDB',
                ];
            }

            $content = $message['content'] ?? '';

            if (trim((string) $content) === '') {
                throw LLMClientException::fromService('LangDB', 'Empty response content');
            }

            return [
                'content' => $content,
                'provider' => 'LangDB',
            ];
        } catch (LLMClientException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            Log::error('LangDB exception', ['error' => $exception->getMessage()]);
            throw LLMClientException::fromService('LangDB', $exception->getMessage());
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

        return $response + ['provider' => 'LangDB'];
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
