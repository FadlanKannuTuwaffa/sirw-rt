<?php

namespace App\Services\Assistant;

use App\Services\Assistant\Support\GeminiKeyManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GenerativeResponseEngine
{
    private string $provider;
    private array $context = [];

    public function __construct()
    {
        $this->provider = config('copilot.default_provider', 'gemini');
    }

    public function provider(): string
    {
        return $this->provider;
    }

    public function generate(string $message, array $context = []): ?array
    {
        $this->context = $context;

        $cacheKey = 'gen_response:' . md5($message . json_encode($context));
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $systemPrompt = $this->buildSystemPrompt();
        $userPrompt = $this->buildUserPrompt($message);

        try {
            $response = $this->callLLM($systemPrompt, $userPrompt);
            
            if ($response !== null) {
                $result = [
                    'content' => $response,
                    'method' => 'generative',
                    'confidence' => 0.75,
                ];
                
                Cache::put($cacheKey, $result, now()->addMinutes(5));
                
                return $result;
            }
        } catch (\Throwable) {
            // Silent fail
        }

        return null;
    }

    private function buildSystemPrompt(): string
    {
        $language = $this->context['language'] ?? 'id';
        
        $prompt = $language === 'en'
            ? "You are Aetheria, a helpful RT (neighborhood) assistant. Respond naturally and concisely."
            : "Kamu adalah Aetheria, asisten RT yang membantu. Jawab dengan natural dan ringkas.";

        if (!empty($this->context['intent'])) {
            $prompt .= $language === 'en'
                ? "\nCurrent topic: {$this->context['intent']}"
                : "\nTopik saat ini: {$this->context['intent']}";
        }

        if (!empty($this->context['data'])) {
            $dataStr = is_array($this->context['data']) 
                ? json_encode($this->context['data'], JSON_UNESCAPED_UNICODE)
                : (string) $this->context['data'];
            
            $prompt .= $language === 'en'
                ? "\nContext data: {$dataStr}"
                : "\nData konteks: {$dataStr}";
        }

        return $prompt;
    }

    private function buildUserPrompt(string $message): string
    {
        return $message;
    }

    private function callLLM(string $systemPrompt, string $userPrompt): ?string
    {
        return match ($this->provider) {
            'gemini' => $this->callGemini($systemPrompt, $userPrompt),
            'cohere' => $this->callCohere($systemPrompt, $userPrompt),
            'mistral' => $this->callMistral($systemPrompt, $userPrompt),
            default => null,
        };
    }

    private function callGemini(string $systemPrompt, string $userPrompt): ?string
    {
        $apiKey = GeminiKeyManager::getNextKey() ?? config('services.gemini.api_key');
        
        if (!$apiKey) {
            return null;
        }

        $response = Http::timeout(15)
            ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $systemPrompt . "\n\n" . $userPrompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'maxOutputTokens' => 300,
                    'temperature' => 0.7,
                ],
            ]);

        if ($response->successful()) {
            return $response->json('candidates.0.content.parts.0.text');
        }

        return null;
    }

    private function callCohere(string $systemPrompt, string $userPrompt): ?string
    {
        $apiKey = config('services.cohere.key');
        
        if (!$apiKey) {
            return null;
        }

        $response = Http::withToken($apiKey)
            ->timeout(15)
            ->post('https://api.cohere.ai/v1/chat', [
                'model' => 'command-r',
                'message' => $userPrompt,
                'preamble' => $systemPrompt,
                'max_tokens' => 300,
                'temperature' => 0.7,
            ]);

        if ($response->successful()) {
            return $response->json('text');
        }

        return null;
    }

    private function callMistral(string $systemPrompt, string $userPrompt): ?string
    {
        $apiKey = config('services.mistral.key');
        
        if (!$apiKey) {
            return null;
        }

        $response = Http::withToken($apiKey)
            ->timeout(15)
            ->post('https://api.mistral.ai/v1/chat/completions', [
                'model' => 'mistral-small-latest',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'max_tokens' => 300,
                'temperature' => 0.7,
            ]);

        if ($response->successful()) {
            return $response->json('choices.0.message.content');
        }

        return null;
    }
}
