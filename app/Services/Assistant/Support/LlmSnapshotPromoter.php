<?php

namespace App\Services\Assistant\Support;

use App\Models\AssistantLlmSnapshot;
use App\Services\Assistant\CorrectionIngestor;
use App\Services\Assistant\RAGService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LlmSnapshotPromoter
{
    public function __construct(
        private readonly FactCorrectionQueue $factQueue,
        private readonly LlmSnapshotFactExtractor $factExtractor,
        private readonly RAGService $ragService,
    ) {
    }

    /**
     * @return array{status:string,message:string,payload:array<string,mixed>}
     */
    public function promote(AssistantLlmSnapshot $snapshot, string $mode = 'auto'): array
    {
        if (!config('assistant.features.llm_promotion', true)) {
            return $this->result('skipped', 'LLM promotion feature flag disabled.');
        }

        if (!$snapshot->is_helpful) {
            return $this->result('skipped', 'Snapshot not marked helpful.');
        }

        $mode = Str::lower($mode);

        if ($mode === 'fact') {
            $facts = $this->factExtractor->extract($snapshot);

            return $facts === []
                ? $this->result('failed', 'Tidak menemukan fakta terstruktur untuk dipromosikan.', ['mode' => 'fact'])
                : $this->promoteFacts($snapshot, $facts, 'fact');
        }

        if ($mode === 'kb') {
            return $this->promoteKnowledge($snapshot, 'kb');
        }

        $facts = $this->factExtractor->extract($snapshot);

        if ($facts !== []) {
            return $this->promoteFacts($snapshot, $facts, 'auto');
        }

        if ($this->shouldIngestKnowledge($snapshot)) {
            return $this->promoteKnowledge($snapshot, 'auto');
        }

        return $this->result('skipped', 'No structured data or KB-worthy content detected.', ['mode' => 'auto']);
    }

    /**
     * @param  array<int, array<string,mixed>>  $facts
     * @return array{status:string,message:string,payload:array<string,mixed>}
     */
    private function promoteFacts(AssistantLlmSnapshot $snapshot, array $facts, string $mode): array
    {
        $interaction = $snapshot->relationLoaded('interaction')
            ? $snapshot->interaction
            : $snapshot->interaction()->first();

        $feedbackSummary = $this->buildFeedbackSummary($snapshot, $interaction?->query);
        $autoLearnReason = Arr::get($snapshot->metadata ?? [], 'auto_learn_reason');
        if (is_string($autoLearnReason) && $autoLearnReason !== '') {
            $feedbackSummary = 'auto_learn_success:' . $autoLearnReason . ' | ' . $feedbackSummary;
        }

        try {
            $eventId = CorrectionIngestor::store([
                'user_id' => $snapshot->user_id,
                'thread_id' => $snapshot->thread_id,
                'original_input' => $interaction->query ?? null,
                'original_answer' => $snapshot->content,
                'user_feedback_raw' => $feedbackSummary,
                'scope' => $snapshot->user_id ? 'user' : 'global',
                'patch_rules' => [
                    'fact_patch' => $facts,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to store correction event from snapshot', [
                'snapshot_id' => $snapshot->id,
                'error' => $e->getMessage(),
            ]);

            return $this->result('failed', 'Unable to persist correction event.', [
                'exception' => $e->getMessage(),
                'mode' => $mode,
            ]);
        }

        $statuses = [];
        foreach ($facts as $fact) {
            $enqueueResult = $this->factQueue->enqueue($fact, [
                'assistant_correction_event_id' => $eventId,
                'user_id' => $snapshot->user_id,
                'thread_id' => $snapshot->thread_id,
                'scope' => $snapshot->user_id ? 'user' : 'global',
                'source_feedback' => $snapshot->feedback_note ?? $snapshot->feedback_source,
                'meta' => [
                    'intent' => $fact['intent'] ?? $snapshot->intent,
                    'snapshot_id' => $snapshot->id,
                    'provider' => $snapshot->provider,
                ],
            ]);

            $statuses[] = $enqueueResult['status'] ?? 'unknown';
        }

        try {
            Artisan::call('assistant:process-fact-corrections', ['--limit' => max(10, count($facts))]);
        } catch (\Throwable $e) {
            Log::warning('Fact correction processor failed after LLM promotion', [
                'snapshot_id' => $snapshot->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->result('promoted', 'Structured facts queued for correction.', [
            'event_id' => $eventId,
            'fact_statuses' => $statuses,
            'mode' => $mode,
        ]);
    }

    /**
     * @return array{status:string,message:string,payload:array<string,mixed>}
     */
    private function promoteKnowledge(AssistantLlmSnapshot $snapshot, string $mode): array
    {
        $interaction = $snapshot->relationLoaded('interaction')
            ? $snapshot->interaction
            : $snapshot->interaction()->first();

        $title = $this->buildKnowledgeTitle($snapshot->id, $interaction?->query);
        $content = trim((string) $snapshot->content);

        if ($content === '') {
            return $this->result('skipped', 'Snapshot content empty, skip KB ingestion.', ['mode' => $mode]);
        }

        try {
            $this->ragService->ingest($title, $content);
        } catch (\Throwable $e) {
            Log::warning('Failed to ingest snapshot into KB', [
                'snapshot_id' => $snapshot->id,
                'error' => $e->getMessage(),
            ]);

            return $this->result('failed', 'Knowledge ingestion failed.', [
                'exception' => $e->getMessage(),
                'mode' => $mode,
            ]);
        }

        return $this->result('kb_ingested', 'Snapshot ingested into knowledge base.', [
            'kb_title' => $title,
            'mode' => $mode,
        ]);
    }

    public function persistResult(AssistantLlmSnapshot $snapshot, array $result): void
    {
        $status = $result['status'] ?? 'skipped';
        $snapshot->promotion_attempts = (int) $snapshot->promotion_attempts + 1;
        $snapshot->promotion_status = $status;
        $snapshot->promotion_notes = $result['message'] ?? null;
        $snapshot->promotion_payload = $result['payload'] ?? null;

        if (in_array($status, ['promoted', 'kb_ingested'], true)) {
            $snapshot->promoted_at = now();
        }

        $snapshot->save();
    }

    private function shouldIngestKnowledge(AssistantLlmSnapshot $snapshot): bool
    {
        $contentLength = strlen(trim((string) $snapshot->content));

        if ($contentLength < 120) {
            return false;
        }

        if (is_array($snapshot->rag_sources) && $snapshot->rag_sources !== []) {
            return true;
        }

        $intent = Str::lower((string) ($snapshot->intent ?? ''));
        $structured = ['bills', 'payments', 'agenda', 'finance', 'residents', 'residents_new'];

        return $intent === '' || !in_array($intent, $structured, true);
    }

    private function buildFeedbackSummary(AssistantLlmSnapshot $snapshot, ?string $query): string
    {
        $pieces = [
            'LLM snapshot #' . $snapshot->id,
            'provider=' . ($snapshot->provider ?? 'unknown'),
        ];

        if ($query) {
            $pieces[] = 'question=' . Str::limit(Str::squish($query), 120, '...');
        }

        if ($snapshot->feedback_note) {
            $pieces[] = 'note=' . Str::limit(Str::squish($snapshot->feedback_note), 160, '...');
        } elseif ($snapshot->feedback_source) {
            $pieces[] = 'source=' . $snapshot->feedback_source;
        }

        return implode(' | ', $pieces);
    }

    private function buildKnowledgeTitle(int $snapshotId, ?string $query): string
    {
        if ($query !== null && trim($query) !== '') {
            return 'LLM Snapshot #' . $snapshotId . ': ' . Str::limit(Str::squish($query), 60, '...');
        }

        return 'LLM Snapshot #' . $snapshotId;
    }

    /**
     * @return array{status:string,message:string,payload:array<string,mixed>}
     */
    private function result(string $status, string $message, array $payload = []): array
    {
        return [
            'status' => $status,
            'message' => $message,
            'payload' => $payload,
        ];
    }
}
