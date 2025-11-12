<?php

namespace App\Services\Assistant;

use App\Services\Assistant\Exceptions\LLMClientException;
use Illuminate\Support\Facades\Http;

class GroqClient implements LLMClient
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.groq.api_key');
        $this->model = config('services.groq.model', 'llama-3.3-70b-versatile');
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

        if (!empty($tools)) {
            $payload['tools'] = $tools;
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.groq.com/openai/v1/chat/completions', $payload);

            if ($response->failed()) {
                $status = $response->status();
                $body = $response->body();

                \Log::error('Groq API failed', ['status' => $status, 'body' => $body]);

                if ($status === 429 || str_contains((string) $body, 'rate_limit')) {
                    throw LLMClientException::fromService('Groq', 'Rate limit reached', $status);
                }

                throw LLMClientException::fromService('Groq', 'API request failed', $status);
            }

            $data = $response->json();
            $choice = $data['choices'][0] ?? null;

            if (!$choice) {
                throw LLMClientException::fromService('Groq', 'No choices returned');
            }

            $message = $choice['message'] ?? [];

            if (isset($message['tool_calls']) && !empty($message['tool_calls'])) {
                return [
                    'tool_calls' => $message['tool_calls'],
                    'provider' => 'Groq',
                ];
            }

            $content = $message['content'] ?? '';

            if (trim((string) $content) === '') {
                throw LLMClientException::fromService('Groq', 'Empty response content');
            }

            return [
                'content' => $content,
                'provider' => 'Groq',
            ];
        } catch (LLMClientException $exception) {
            throw $exception;
        } catch (\Exception $e) {
            \Log::error('Groq exception', ['error' => $e->getMessage()]);
            throw LLMClientException::fromService('Groq', $e->getMessage());
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

        return $response + ['provider' => 'Groq'];
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
