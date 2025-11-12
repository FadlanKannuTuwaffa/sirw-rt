<?php

namespace App\Services\Assistant;

use App\Services\Assistant\Exceptions\LLMClientException;
use Illuminate\Support\Facades\Http;

class GeminiClient implements LLMClient
{
    private string $apiKey;
    private string $model;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model', 'gemini-2.5-flash');
        
        if (!$this->apiKey) {
            throw new \RuntimeException('GEMINI_API_KEY is not configured.');
        }
    }

    public function chat(array $messages, array $tools = []): array
    {
        $fullMessages = $this->ensureSystemPrompt($messages);
        $contents = $this->convertMessages($fullMessages);

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 1000,
            ],
        ];

        if (!empty($tools)) {
            $payload['tools'] = $this->convertTools($tools);
        }

        try {
            \Log::info('Gemini request', [
                'model' => $this->model,
                'messages_count' => count($fullMessages),
            ]);

            $endpoint = "{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}";
            
            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($endpoint, $payload);

            \Log::info('Gemini response', ['status' => $response->status()]);

            if ($response->failed()) {
                throw LLMClientException::fromService(
                    'Gemini',
                    'API request failed: ' . $response->body(),
                    $response->status()
                );
            }

            $data = $response->json();

            if (isset($data['error'])) {
                throw LLMClientException::fromService('Gemini', $data['error']['message'] ?? 'Unknown error');
            }

            $candidate = $data['candidates'][0] ?? null;
            if (!$candidate) {
                throw LLMClientException::fromService('Gemini', 'No candidates returned');
            }

            $content = $candidate['content'] ?? null;
            if (!$content) {
                throw LLMClientException::fromService('Gemini', 'No content in response');
            }

            // Check for function calls
            if (isset($content['parts'][0]['functionCall'])) {
                return $this->handleFunctionCall($content['parts'][0]['functionCall']) + ['provider' => 'Gemini'];
            }

            // Extract text response
            $text = $content['parts'][0]['text'] ?? '';
            if (empty(trim($text))) {
                throw LLMClientException::fromService('Gemini', 'Empty response text');
            }

            return [
                'content' => trim($text),
                'provider' => 'Gemini',
            ];

        } catch (LLMClientException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Gemini exception', ['error' => $e->getMessage()]);
            throw LLMClientException::fromService('Gemini', $e->getMessage());
        }
    }

    private function convertMessages(array $messages): array
    {
        $contents = [];
        $systemInstruction = null;

        foreach ($messages as $message) {
            $role = $message['role'] ?? 'user';
            $content = $message['content'] ?? '';

            if ($role === 'system') {
                $systemInstruction = $content;
                continue;
            }

            $geminiRole = $role === 'assistant' ? 'model' : 'user';
            
            $contents[] = [
                'role' => $geminiRole,
                'parts' => [['text' => $content]],
            ];
        }

        return $contents;
    }

    private function convertTools(array $tools): array
    {
        $functionDeclarations = [];

        foreach ($tools as $tool) {
            if ($tool['type'] === 'function') {
                $func = $tool['function'];
                $functionDeclarations[] = [
                    'name' => $func['name'],
                    'description' => $func['description'],
                    'parameters' => $func['parameters'] ?? ['type' => 'object', 'properties' => []],
                ];
            }
        }

        return [['functionDeclarations' => $functionDeclarations]];
    }

    private function handleFunctionCall(array $functionCall): array
    {
        return [
            'tool_calls' => [[
                'id' => uniqid('call_'),
                'type' => 'function',
                'function' => [
                    'name' => $functionCall['name'],
                    'arguments' => json_encode($functionCall['args'] ?? []),
                ],
            ]],
        ];
    }

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

        return $response + ['provider' => 'Gemini'];
    }

    public function embed(string $text): ?array
    {
        return null;
    }
}
