<?php

namespace App\Services\Assistant;

use App\Services\Assistant\Exceptions\LLMClientException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CohereClient implements LLMClient
{
    private string $apiKey;
    private string $endpoint;
    private string $model;

    public function __construct()
    {
        $this->apiKey = (string) config('services.cohere.api_key', '');
        $this->endpoint = (string) config('services.cohere.endpoint', 'https://api.cohere.com/v1/chat');
        $this->model = (string) config('services.cohere.model', 'command-r-plus');
    }

    public function chat(array $messages, array $tools = []): array
    {
        $fullMessages = $this->ensureSystemPrompt($messages);
        $preamble = null;
        $chatHistory = [];
        $currentMessage = null;

        $count = count($fullMessages);
        foreach ($fullMessages as $index => $message) {
            $role = strtolower((string) ($message['role'] ?? 'user'));
            $content = trim((string) ($message['content'] ?? ''));

            if ($role === 'system' && $preamble === null) {
                $preamble = $content;
                continue;
            }

            $isLast = $index === $count - 1;

            if ($isLast && $role === 'user') {
                $currentMessage = $content;
                continue;
            }

            if ($content === '') {
                continue;
            }

            $chatHistory[] = [
                'role' => $role === 'assistant' ? 'CHATBOT' : 'USER',
                'message' => $content,
            ];
        }

        if ($currentMessage === null) {
            foreach (array_reverse($fullMessages) as $message) {
                if (($message['role'] ?? '') === 'user') {
                    $currentMessage = trim((string) ($message['content'] ?? ''));
                    break;
                }
            }
        }

        if ($currentMessage === null || $currentMessage === '') {
            throw LLMClientException::fromService('Cohere', 'No user message provided');
        }

        $payload = [
            'model' => $this->model,
            'message' => $currentMessage,
            'chat_history' => $chatHistory,
            'temperature' => 0.7,
            'max_tokens' => 500,
        ];

        if ($preamble !== null && $preamble !== '') {
            $payload['preamble'] = $preamble;
        }

        if ($tools !== []) {
            $cohereTools = [];

            foreach ($tools as $tool) {
                $function = $tool['function'] ?? null;

                if (!is_array($function)) {
                    continue;
                }

                $name = $function['name'] ?? null;

                if (!is_string($name) || trim($name) === '') {
                    continue;
                }

                $schema = $function['parameters'] ?? [
                    'type' => 'object',
                    'properties' => new \stdClass(),
                ];

                if (is_array($schema)) {
                    if (!isset($schema['type'])) {
                        $schema['type'] = 'object';
                    }

                    if (isset($schema['properties']) && is_array($schema['properties']) && $schema['properties'] === []) {
                        $schema['properties'] = new \stdClass();
                    }
                }

                $cohereTools[] = [
                    'name' => $name,
                    'description' => $function['description'] ?? '',
                    'input_schema' => $schema,
                ];
            }

            if ($cohereTools !== []) {
                $payload['tools'] = $cohereTools;
            }
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Cohere-Version' => '2022-12-06',
                ])
                ->post($this->endpoint, $payload);

            if ($response->failed()) {
                $status = $response->status();
                $body = $response->body();
                Log::error('Cohere API failed', ['status' => $status, 'body' => $body]);

                if ($status === 429 || str_contains((string) $body, 'rate limit')) {
                    throw LLMClientException::fromService('Cohere', 'Rate limit reached', $status);
                }

                throw LLMClientException::fromService('Cohere', 'API request failed', $status);
            }

            $data = $response->json();

            $toolCalls = $data['tool_calls'] ?? $data['toolCalls'] ?? null;

            if (is_array($toolCalls) && $toolCalls !== []) {
                return [
                    'tool_calls' => $toolCalls,
                    'provider' => 'Cohere',
                ];
            }

            $content = $data['text'] ?? $data['response'] ?? null;

            if (is_array($content)) {
                $content = implode("\n", array_map('strval', $content));
            }

            $content = (string) $content;

            if (trim($content) === '') {
                throw LLMClientException::fromService('Cohere', 'Empty response content');
            }

            return [
                'content' => $content,
                'provider' => 'Cohere',
            ];
        } catch (LLMClientException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            Log::error('Cohere exception', ['error' => $exception->getMessage()]);
            throw LLMClientException::fromService('Cohere', $exception->getMessage());
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

        return $response + ['provider' => 'Cohere'];
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
