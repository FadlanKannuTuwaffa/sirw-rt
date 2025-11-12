<?php

namespace App\Console\Commands;

use App\Models\AssistantKbDocumentWeight;
use App\Models\AssistantKnowledgeFeedback;
use App\Services\Assistant\AdaptiveRAGThreshold;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProcessKnowledgeFeedback extends Command
{
    protected $signature = 'assistant:process-kb-feedback {--batch=100 : Number of feedback rows to process per batch}';

    protected $description = 'Apply knowledge-base feedback to document weights and adaptive thresholds';

    public function handle(AdaptiveRAGThreshold $threshold): int
    {
        if (!Schema::hasTable('assistant_kb_feedback')) {
            $this->warn('assistant_kb_feedback table not found.');
            return self::FAILURE;
        }

        $batchSize = max(10, (int) $this->option('batch'));
        $processed = 0;

        do {
            $batch = AssistantKnowledgeFeedback::whereNotNull('responded_at')
                ->whereNull('processed_at')
                ->orderBy('responded_at')
                ->limit($batchSize)
                ->get();

            if ($batch->isEmpty()) {
                break;
            }

            foreach ($batch as $feedback) {
                DB::transaction(function () use ($feedback, $threshold) {
                    $wasHelpful = (bool) ($feedback->helpful ?? false);
                    $confidence = (float) ($feedback->confidence ?? 0.5);

                    $threshold->recordFeedback($feedback->user_id, null, $wasHelpful, $confidence);

                    foreach ((array) ($feedback->sources ?? []) as $source) {
                        $this->applyDocumentFeedback($source, $wasHelpful, $feedback);
                    }

                    $feedback->processed_at = now();
                    $feedback->save();
                });

                $processed++;
            }
        } while (true);

        if ($processed > 0) {
            Cache::forget('assistant_kb_document_weights');
        }

        $this->info("Processed {$processed} feedback entries.");

        return self::SUCCESS;
    }

    private function applyDocumentFeedback(array $source, bool $wasHelpful, AssistantKnowledgeFeedback $feedback): void
    {
        if (!Schema::hasTable('assistant_kb_document_weights')) {
            return;
        }

        $documentId = $source['document_id'] ?? null;
        if ($documentId === null || $documentId === '') {
            return;
        }

        $record = AssistantKbDocumentWeight::firstOrNew(['document_id' => $documentId]);
        $record->title = $record->title ?: ($source['title'] ?? 'Dokumen');
        $record->helpful_count = (int) $record->helpful_count + ($wasHelpful ? 1 : 0);
        $record->unhelpful_count = (int) $record->unhelpful_count + ($wasHelpful ? 0 : 1);
        $record->weight = $this->adjustWeight((float) ($record->weight ?? 1.0), $wasHelpful);

        if (!$wasHelpful) {
            $record->needs_review = true;
            $record->last_note = $feedback->note ?? $record->last_note;
        }

        $record->last_feedback_at = $feedback->responded_at ?? now();
        $record->save();
    }

    private function adjustWeight(float $current, bool $wasHelpful): float
    {
        $delta = $wasHelpful ? 0.05 : -0.1;

        return max(0.1, min(2.0, $current + $delta));
    }
}