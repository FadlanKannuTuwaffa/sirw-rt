<?php

namespace App\Services\Assistant;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InteractionLearner
{
    public function recordInteraction(array $data): ?int
    {
        if (!$this->hasTable()) {
            return null;
        }

        return DB::table('assistant_interactions')->insertGetId([
            'user_id' => $data['user_id'] ?? null,
            'thread_id' => $data['thread_id'] ?? null,
            'message' => $data['message'] ?? '',
            'intent' => $data['intent'] ?? null,
            'response' => $data['response'] ?? '',
            'confidence' => $data['confidence'] ?? null,
            'method' => $data['method'] ?? 'rule',
            'was_helpful' => null,
            'feedback_reason' => null,
            'assistant_interaction_log_id' => $data['assistant_interaction_log_id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function recordFeedback(int $interactionId, bool $wasHelpful, ?string $reason = null): void
    {
        if (!$this->hasTable()) {
            return;
        }

        DB::table('assistant_interactions')
            ->where('id', $interactionId)
            ->update([
                'was_helpful' => $wasHelpful,
                'feedback_reason' => $reason,
                'feedback_at' => now(),
            ]);
    }

    public function getAccuracyByIntent(?int $userId = null, int $days = 30): array
    {
        if (!$this->hasTable()) {
            return [];
        }

        $query = DB::table('assistant_interactions')
            ->whereNotNull('was_helpful')
            ->where('created_at', '>=', now()->subDays($days));

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        $results = $query
            ->select('intent', DB::raw('AVG(CAST(was_helpful AS UNSIGNED)) as accuracy'), DB::raw('COUNT(*) as total'))
            ->groupBy('intent')
            ->get();

        return $results->mapWithKeys(function ($row) {
            return [$row->intent => [
                'accuracy' => (float) $row->accuracy,
                'total' => (int) $row->total,
            ]];
        })->toArray();
    }

    public function getFailurePatterns(int $limit = 10): array
    {
        if (!$this->hasTable()) {
            return [];
        }

        return DB::table('assistant_interactions')
            ->where('was_helpful', false)
            ->whereNotNull('feedback_reason')
            ->select('message', 'intent', 'confidence', 'feedback_reason')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function attachInteractionLog(int $interactionId, int $logId): void
    {
        if (!$this->hasTable()) {
            return;
        }

        DB::table('assistant_interactions')
            ->where('id', $interactionId)
            ->update([
                'assistant_interaction_log_id' => $logId,
                'updated_at' => now(),
            ]);
    }

    private function hasTable(): bool
    {
        static $exists;

        if ($exists === null) {
            $exists = Schema::hasTable('assistant_interactions');
        }

        return $exists;
    }
}
