<?php

namespace App\Services\Assistant\Support;

use App\Models\AssistantLlmSnapshot;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class LlmSnapshotManager
{
    public function record(array $payload): ?AssistantLlmSnapshot
    {
        if (!config('assistant.features.llm_promotion', true)) {
            return null;
        }

        return AssistantLlmSnapshot::create($payload);
    }

    public function markFeedback(int $interactionLogId, ?bool $helpful, string $source, ?string $note = null): void
    {
        if (!config('assistant.features.llm_promotion', true)) {
            return;
        }

        $snapshot = $this->findSnapshotByInteraction($interactionLogId);

        if (!$snapshot) {
            return;
        }

        $positive = (int) $snapshot->positive_feedback_count;
        $negative = (int) $snapshot->negative_feedback_count;

        if ($helpful === true) {
            $positive++;
        } elseif ($helpful === false) {
            $negative++;
        }

        $metadata = $snapshot->metadata ?? [];
        $events = Arr::wrap($metadata['feedback_events'] ?? []);
        $events[] = [
            'source' => $source,
            'note' => $note,
            'helpful' => $helpful,
            'recorded_at' => now()->toIso8601String(),
        ];
        $metadata['feedback_events'] = array_slice($events, -6);

        $snapshot->fill([
            'is_helpful' => $helpful,
            'needs_review' => $helpful === null ? true : !$helpful,
            'feedback_source' => $source,
            'feedback_note' => $note,
            'positive_feedback_count' => $positive,
            'negative_feedback_count' => $negative,
            'auto_promote_ready' => $this->autoPromoteReady($positive, $metadata),
            'last_feedback_at' => now(),
            'metadata' => $metadata,
        ])->save();
    }

    public function markEvaluation(int $interactionLogId, string $label, ?bool $passed = null): void
    {
        if (!config('assistant.features.llm_promotion', true)) {
            return;
        }

        $snapshot = AssistantLlmSnapshot::where('assistant_interaction_log_id', $interactionLogId)->first();

        if (!$snapshot) {
            return;
        }

        $metadata = $snapshot->metadata ?? [];
        $labels = Arr::wrap($metadata['evaluation_labels'] ?? []);
        $labels[] = Str::upper($label);
        $metadata['evaluation_labels'] = array_values(array_unique($labels));

        if ($passed === null) {
            $passed = Str::contains(Str::upper($label), 'PASS');
        }

        if ($passed) {
            $metadata['evaluation_passed_at'] = now()->toIso8601String();
            $metadata['evaluation_passed'] = true;
        }

        $snapshot->fill([
            'auto_promote_ready' => $snapshot->auto_promote_ready
                || $this->autoPromoteReady((int) $snapshot->positive_feedback_count, $metadata),
            'metadata' => $metadata,
        ])->save();
    }

    private function findSnapshotByInteraction(int $reference): ?AssistantLlmSnapshot
    {
        $snapshot = AssistantLlmSnapshot::where('assistant_interaction_log_id', $reference)->first();
        if ($snapshot) {
            return $snapshot;
        }

        return AssistantLlmSnapshot::where('assistant_interaction_id', $reference)->first();
    }

    private function autoPromoteReady(int $positiveCount, array $metadata): bool
    {
        if ($positiveCount >= 2) {
            return true;
        }

        if (!empty($metadata['evaluation_passed'])) {
            return true;
        }

        $labels = array_map('strtoupper', Arr::wrap($metadata['evaluation_labels'] ?? []));

        return in_array('PASS', $labels, true);
    }
}
