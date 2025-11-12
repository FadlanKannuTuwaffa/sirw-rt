<?php

namespace App\Services\Assistant;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AssistantMetrics
{
    private string $logFile;

    public function __construct(?string $logFile = null)
    {
        $this->logFile = $logFile ?? storage_path('logs/assistant-metrics.jsonl');
    }

    public function recordResponse(array $payload): void
    {
        $data = [
            'type' => 'response',
            'timestamp' => now()->toIso8601String(),
            'intent' => $payload['intent'] ?? null,
            'language' => $payload['language'] ?? null,
            'tone' => $payload['tone'] ?? null,
            'confidence' => $payload['confidence'] ?? null,
            'length' => Str::length($payload['message'] ?? ''),
            'followups' => array_values($payload['followups'] ?? []),
        ];

        $this->write($data);
    }

    public function recordGuardrail(string $guardrail, array $context = []): void
    {
        $data = [
            'type' => 'guardrail',
            'timestamp' => now()->toIso8601String(),
            'guardrail' => $guardrail,
            'context' => $context,
        ];

        $this->write($data);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function recordToolError(array $payload): void
    {
        $data = [
            'type' => 'tool_error',
            'timestamp' => now()->toIso8601String(),
            'tool' => $payload['tool'] ?? null,
            'code' => $payload['code'] ?? null,
            'error' => $payload['error'] ?? null,
            'intent' => $payload['intent'] ?? null,
            'parameters' => $payload['parameters'] ?? [],
            'details' => $payload['details'] ?? [],
        ];

        $this->write($data);
    }

    /**
     * @param  array<int, array<string, mixed>>  $violations
     */
    public function recordReasoningViolation(string $intent, array $violations, array $context = []): void
    {
        $data = [
            'type' => 'reasoning_violation',
            'timestamp' => now()->toIso8601String(),
            'intent' => $intent,
            'violations' => array_values($violations),
            'context' => $context,
        ];

        $this->write($data);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function recordClassifierDecision(array $payload): void
    {
        $data = [
            'type' => 'classifier',
            'timestamp' => now()->toIso8601String(),
            'stage' => $payload['stage'] ?? null,
            'intent' => $payload['intent'] ?? null,
            'score' => $payload['score'] ?? null,
            'slots' => array_values($payload['slots'] ?? []),
            'selected' => (bool) ($payload['selected'] ?? false),
            'source' => $payload['source'] ?? null,
            'details' => $payload['details'] ?? [],
        ];

        $this->write($data);
    }

    private function write(array $data): void
    {
        $directory = dirname($this->logFile);
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::append($this->logFile, json_encode($data) . PHP_EOL);
    }
}
