<?php

namespace App\Jobs;

use App\Models\AssistantLlmSnapshot;
use App\Models\AssistantLlmSnapshotReview;
use App\Services\Assistant\Support\LlmSnapshotPromoter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PromoteLlmSnapshot implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(private readonly int $snapshotId)
    {
        $this->onQueue('reminders');
    }

    public function handle(LlmSnapshotPromoter $promoter): void
    {
        $snapshot = AssistantLlmSnapshot::find($this->snapshotId);

        if (!$snapshot) {
            Log::warning('LLM snapshot promotion skipped, snapshot missing', [
                'snapshot_id' => $this->snapshotId,
            ]);

            return;
        }

        $result = $promoter->promote($snapshot, 'auto');
        $promoter->persistResult($snapshot, $result);

        AssistantLlmSnapshotReview::create([
            'assistant_llm_snapshot_id' => $snapshot->id,
            'user_id' => null,
            'action' => 'auto_promote_' . ($result['status'] ?? 'unknown'),
            'notes' => $result['message'] ?? null,
            'metadata' => $result['payload'] ?? [],
        ]);

        if (in_array($result['status'] ?? null, ['promoted', 'kb_ingested'], true)) {
            RunAssistantRegression::dispatch($snapshot->id);
        }
    }

    public function failed(\Throwable $exception): void
    {
        $snapshot = AssistantLlmSnapshot::find($this->snapshotId);

        if (!$snapshot) {
            return;
        }

        $snapshot->promotion_attempts = (int) $snapshot->promotion_attempts + 1;
        $snapshot->promotion_status = 'failed';
        $snapshot->promotion_notes = $exception->getMessage();
        $snapshot->save();

        AssistantLlmSnapshotReview::create([
            'assistant_llm_snapshot_id' => $snapshot->id,
            'user_id' => null,
            'action' => 'auto_promote_exception',
            'notes' => $exception->getMessage(),
            'metadata' => [],
        ]);
    }
}
