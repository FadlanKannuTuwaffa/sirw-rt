<?php

namespace App\Services\Assistant\Support;

use App\Models\AssistantFactCorrection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FactCorrectionQueue
{
    /**
     * @param  array<string,mixed>  $patch
     * @param  array<string,mixed>  $context
     * @return array{status:string,record:?AssistantFactCorrection}
     */
    public function enqueue(array $patch, array $context = []): array
    {
        if (!Schema::hasTable('assistant_fact_corrections')) {
            return ['status' => 'missing_table', 'record' => null];
        }

        $fingerprint = $this->fingerprint($patch, $context);
        $payload = [
            'assistant_correction_event_id' => $context['assistant_correction_event_id'] ?? null,
            'user_id' => $context['user_id'] ?? null,
            'org_id' => $context['org_id'] ?? null,
            'thread_id' => $context['thread_id'] ?? null,
            'turn_id' => $context['turn_id'] ?? null,
            'scope' => $context['scope'] ?? 'user',
            'entity_type' => $patch['entity'] ?? 'unknown',
            'field' => $patch['field'] ?? 'value',
            'fingerprint' => $fingerprint,
            'status' => $context['status'] ?? 'pending',
            'value' => $this->stringifyValue($patch['value'] ?? null),
            'value_raw' => isset($patch['value_raw']) ? (string) $patch['value_raw'] : null,
            'match_context' => $patch['match'] ?? null,
            'source_feedback' => $context['source_feedback'] ?? null,
            'meta' => $context['meta'] ?? null,
        ];

        $existing = null;
        if ($fingerprint !== null) {
            $existing = AssistantFactCorrection::query()
                ->where('fingerprint', $fingerprint)
                ->whereIn('status', ['pending', 'queued'])
                ->first();
        }

        if ($existing) {
            $existing->fill(array_filter($payload, static fn ($value) => $value !== null));
            $existing->save();

            return ['status' => 'existing', 'record' => $existing];
        }

        $record = AssistantFactCorrection::create($payload);

        return ['status' => 'queued', 'record' => $record];
    }

    /**
     * @param  array<string,mixed>  $patch
     * @param  array<string,mixed>  $context
     */
    private function fingerprint(array $patch, array $context): ?string
    {
        $entity = Str::lower((string) ($patch['entity'] ?? ''));
        $field = Str::lower((string) ($patch['field'] ?? ''));
        $needle = Str::lower((string) ($patch['match']['needle'] ?? $patch['match']['keywords'][0] ?? ''));

        if ($entity === '' || $field === '' || $needle === '') {
            return null;
        }

        return substr(sha1($entity . '|' . $field . '|' . $needle . '|' . ($context['org_id'] ?? 'global')), 0, 40);
    }

    private function stringifyValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}
