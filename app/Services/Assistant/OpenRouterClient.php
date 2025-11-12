<?php

namespace App\Services\Assistant;

use App\Services\Assistant\Exceptions\LLMClientException;
use Illuminate\Support\Facades\Http;

class OpenRouterClient implements LLMClient
{
    private string $baseUrl = 'https://openrouter.ai/api/v1';
    private string $model;

    public function __construct()
    {
        $this->model = config('services.openrouter.model', 'meta-llama/llama-3.3-70b-instruct');
    }

    public function chat(array $messages, array $tools = []): array
    {
        $fullMessages = $this->ensureSystemPrompt($messages);

        $payload = [
            'model' => $this->model,
            'messages' => $fullMessages,
            'temperature' => 0.2,
        ];

        if (!empty($tools)) {
            $payload['tools'] = $tools;
        }

        try {
            \Log::info('OpenRouter request', ['model' => $this->model, 'messages_count' => count($fullMessages)]);
            
            $response = Http::timeout(30)
                ->withHeaders($this->headers())
                ->post("{$this->baseUrl}/chat/completions", $payload);

            \Log::info('OpenRouter response', ['status' => $response->status()]);

            if ($response->failed()) {
                \Log::error('OpenRouter failed', ['status' => $response->status(), 'body' => $response->body()]);
                throw LLMClientException::fromService('OpenRouter', 'API request failed: ' . $response->body(), $response->status());
            }

            $data = $response->json();
            $choice = $data['choices'][0] ?? null;
            
            if (!$choice) {
                throw LLMClientException::fromService('OpenRouter', 'No choices returned');
            }

            $message = $choice['message'] ?? [];

            if (isset($message['tool_calls']) && !empty($message['tool_calls'])) {
                return [
                    'tool_calls' => $message['tool_calls'],
                    'provider' => 'OpenRouter',
                ];
            }

            return [
                'content' => $message['content'] ?? '',
                'provider' => 'OpenRouter',
            ];
        } catch (LLMClientException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw LLMClientException::fromService('OpenRouter', $e->getMessage());
        }
    }

    public function supportsStreaming(): bool
    {
        return true;
    }

    public function stream(array $messages, array $tools, callable $onEvent): array
    {
        $fullMessages = $this->ensureSystemPrompt($messages);

        $payload = [
            'model' => $this->model,
            'messages' => $fullMessages,
            'temperature' => 0.2,
            'stream' => true,
        ];

        if (!empty($tools)) {
            $payload['tools'] = $tools;
        }

        try {
            $response = Http::timeout(60)
                ->withHeaders($this->headers())
                ->withOptions(['stream' => true])
                ->post("{$this->baseUrl}/chat/completions", $payload);

            if ($response->failed()) {
                throw LLMClientException::fromService('OpenRouter', 'Streaming request failed: ' . $response->body(), $response->status());
            }

            $body = $response->toPsrResponse()->getBody();
            $buffer = '';
            $content = '';
            $toolCalls = [];

            while (!$body->eof()) {
                $buffer .= $body->read(1024);

                while (($pos = strpos($buffer, "\n\n")) !== false) {
                    $chunk = substr($buffer, 0, $pos);
                    $buffer = substr($buffer, $pos + 2);

                    if (!str_starts_with($chunk, 'data:')) {
                        continue;
                    }

                    $data = trim(substr($chunk, 5));

                    if ($data === '') {
                        continue;
                    }

                    if ($data === '[DONE]') {
                        break 2;
                    }

                    $decoded = json_decode($data, true);
                    if (!is_array($decoded)) {
                        continue;
                    }

                    $delta = $decoded['choices'][0]['delta'] ?? [];

                    if (isset($delta['content']) && $delta['content'] !== '') {
                        $token = (string) $delta['content'];
                        $content .= $token;
                        $onEvent('token', $token);
                    }

                    if (!empty($delta['tool_calls'])) {
                        foreach ($delta['tool_calls'] as $toolDelta) {
                            $index = $toolDelta['index'] ?? 0;

                            if (!isset($toolCalls[$index])) {
                                $toolCalls[$index] = [
                                    'id' => $toolDelta['id'] ?? uniqid('call_', true),
                                    'type' => 'function',
                                    'function' => [
                                        'name' => '',
                                        'arguments' => '',
                                    ],
                                ];
                            }

                            if (isset($toolDelta['id'])) {
                                $toolCalls[$index]['id'] = $toolDelta['id'];
                            }

                            if (isset($toolDelta['function']['name'])) {
                                $toolCalls[$index]['function']['name'] = $toolDelta['function']['name'];
                            }

                            if (isset($toolDelta['function']['arguments'])) {
                                $toolCalls[$index]['function']['arguments'] .= $toolDelta['function']['arguments'];
                            }
                        }
                    }
                }
            }

            if ($toolCalls !== []) {
                return [
                    'tool_calls' => array_values($toolCalls),
                    'provider' => 'OpenRouter',
                ];
            }

            return [
                'content' => $content,
                'provider' => 'OpenRouter',
            ];
        } catch (LLMClientException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw LLMClientException::fromService('OpenRouter', $e->getMessage());
        }
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

    private function headers(): array
    {
        $apiKey = config('services.openrouter.api_key');

        if (!$apiKey) {
            throw new \RuntimeException('OPENROUTER_API_KEY is not configured.');
        }

        return [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
            'HTTP-Referer' => config('app.url'),
            'X-Title' => config('app.name'),
        ];
    }
}
