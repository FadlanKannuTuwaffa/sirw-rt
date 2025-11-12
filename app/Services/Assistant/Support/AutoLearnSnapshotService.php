<?php

namespace App\Services\Assistant\Support;

use App\Jobs\PromoteLlmSnapshot;
use App\Models\AssistantInteractionLog;
use App\Models\AssistantLlmSnapshot;
use App\Models\AssistantLlmSnapshotReview;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoLearnSnapshotService
{
    private const MIN_CONFIDENCE = 0.5;
    private const ALLOWED_CHANNELS = ['llm', 'llm_with_tools', 'dummy_knowledge_fallback'];

    public function scheduleFromInteraction(int $interactionId, bool $helpful): void
    {
        if (!$helpful || !config('assistant.features.llm_promotion', true)) {
            return;
        }

        $interaction = DB::table('assistant_interactions')
            ->select('id', 'assistant_interaction_log_id')
            ->where('id', $interactionId)
            ->first();

        if (!$interaction || !$interaction->assistant_interaction_log_id) {
            return;
        }

        $log = AssistantInteractionLog::find($interaction->assistant_interaction_log_id);
        if (!$log || !in_array($log->responded_via, self::ALLOWED_CHANNELS, true) || $log->success !== true) {
            return;
        }

        $snapshot = AssistantLlmSnapshot::where('assistant_interaction_log_id', $log->id)->first();
        if (!$snapshot || $snapshot->is_helpful !== true) {
            return;
        }

        if (!in_array($snapshot->promotion_status, ['pending', 'retry'], true)) {
            return;
        }

        if (!$this->passesConfidenceThreshold($snapshot) || !$this->passesGuardrails($snapshot)) {
            return;
        }

        $metadata = $snapshot->metadata ?? [];
        $metadata['auto_learn_reason'] = 'interaction_feedback';
        $metadata['auto_learn_triggered_at'] = now()->toIso8601String();

        $snapshot->metadata = $metadata;
        $snapshot->assistant_interaction_id = $snapshot->assistant_interaction_id ?? $interactionId;
        $snapshot->auto_promote_ready = true;
        $snapshot->promotion_status = 'queued';
        $snapshot->save();

        AssistantLlmSnapshotReview::create([
            'assistant_llm_snapshot_id' => $snapshot->id,
            'user_id' => null,
            'action' => 'auto_learn_feedback',
            'notes' => 'Auto-learn queued from positive user feedback.',
            'metadata' => [
                'interaction_id' => $interactionId,
                'interaction_log_id' => $log->id,
            ],
        ]);

        try {
            PromoteLlmSnapshot::dispatch($snapshot->id);
        } catch (\Throwable $e) {
            Log::warning('Failed to dispatch LLM auto-learn promotion', [
                'snapshot_id' => $snapshot->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function passesConfidenceThreshold(AssistantLlmSnapshot $snapshot): bool
    {
        if ($snapshot->confidence === null) {
            return true;
        }

        return $snapshot->confidence >= self::MIN_CONFIDENCE;
    }

    private function passesGuardrails(AssistantLlmSnapshot $snapshot): bool
    {
        $metadata = $snapshot->metadata ?? [];
        $recent = Arr::wrap(Arr::get($metadata, 'recent_guardrails', Arr::get($metadata, 'state.recent_guardrails', [])));
        $active = array_filter($recent, function ($value) {
            return is_string($value) && $value !== '';
        });

        return $active === [];
    }
}
