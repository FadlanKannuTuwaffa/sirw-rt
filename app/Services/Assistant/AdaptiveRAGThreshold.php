<?php

namespace App\Services\Assistant;

use Illuminate\Support\Facades\Cache;

class AdaptiveRAGThreshold
{
    private const BASE_THRESHOLD = 0.45;
    private const MIN_THRESHOLD = 0.30;
    private const MAX_THRESHOLD = 0.70;
    private const FEEDBACK_WEIGHT = 0.05;

    public function getThreshold(?int $userId, ?string $threadId): float
    {
        $key = $this->cacheKey($userId, $threadId);
        
        return Cache::get($key, self::BASE_THRESHOLD);
    }

    public function recordFeedback(?int $userId, ?string $threadId, bool $wasHelpful, float $confidence): void
    {
        $current = $this->getThreshold($userId, $threadId);
        
        // Jika helpful tapi confidence rendah → turunkan threshold
        // Jika not helpful tapi confidence tinggi → naikkan threshold
        if ($wasHelpful && $confidence < $current) {
            $adjustment = -self::FEEDBACK_WEIGHT;
        } elseif (!$wasHelpful && $confidence >= $current) {
            $adjustment = self::FEEDBACK_WEIGHT;
        } else {
            $adjustment = 0;
        }

        $newThreshold = max(
            self::MIN_THRESHOLD,
            min(self::MAX_THRESHOLD, $current + $adjustment)
        );

        $key = $this->cacheKey($userId, $threadId);
        Cache::put($key, $newThreshold, now()->addDays(30));
    }

    public function recordSuccess(?int $userId, ?string $threadId, float $confidence): void
    {
        $this->recordFeedback($userId, $threadId, true, $confidence);
    }

    public function recordFailure(?int $userId, ?string $threadId, float $confidence): void
    {
        $this->recordFeedback($userId, $threadId, false, $confidence);
    }

    private function cacheKey(?int $userId, ?string $threadId): string
    {
        return 'rag_threshold:' . ($userId ?? 'guest') . ':' . ($threadId ?? 'default');
    }
}
