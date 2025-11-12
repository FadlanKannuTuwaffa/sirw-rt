<?php

namespace App\Services\Assistant\Intent;

use Illuminate\Support\Facades\Http;

class ExternalIntentClient
{
    private string $endpoint;
    private ?string $token;
    private float $timeout;
    private string $source;
    private array $payloadDefaults;

    public function __construct(
        string $endpoint,
        ?string $token = null,
        float $timeout = 3.0,
        string $source = 'ml',
        array $payloadDefaults = []
    ) {
        $this->endpoint = $endpoint;
        $this->token = $token;
        $this->timeout = $timeout;
        $this->source = $source;
        $this->payloadDefaults = $payloadDefaults;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>|null
     */
    public function classify(string $message, array $context = []): ?array
    {
        if ($this->endpoint === '') {
            return null;
        }

        $payload = array_merge($this->payloadDefaults, [
            'message' => $message,
            'tokens' => $context['tokens'] ?? [],
            'entities' => $context['entities'] ?? [],
            'language' => $context['language'] ?? null,
        ]);

        try {
            $request = Http::timeout($this->timeout);

            if ($this->token !== null && $this->token !== '') {
                $request = $request->withToken($this->token);
            }

            $response = $request->post($this->endpoint, $payload);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();

            if (!is_array($data)) {
                return null;
            }

            $intent = isset($data['intent']) ? (string) $data['intent'] : '';

            if ($intent === '') {
                return null;
            }

            return [
                'intent' => $intent,
                'score' => isset($data['score']) ? (float) $data['score'] : 0.0,
                'slots' => is_array($data['slots'] ?? null) ? $data['slots'] : [],
                'entities' => is_array($data['entities'] ?? null) ? $data['entities'] : [],
                'source' => $this->source,
            ];
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
}
