<?php

namespace App\Services\Assistant;

use App\Models\AssistantCorrection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CorrectionPromoter
{
    private const COUNTER_CACHE_KEY = 'assistant.lexicon.correction.promoter';

    public function __construct(
        private readonly int $threshold = 3
    ) {
    }

    public function record(string $alias, string $canonical): void
    {
        $aliasKey = Str::of($alias)->lower()->squish()->value();
        $canonicalKey = Str::of($canonical)->lower()->squish()->value();

        if ($aliasKey === '' || $canonicalKey === '' || $aliasKey === $canonicalKey) {
            return;
        }

        $key = $aliasKey . '|' . $canonicalKey;
        $counters = Cache::get(self::COUNTER_CACHE_KEY, []);
        $count = (int) (($counters[$key] ?? 0) + 1);
        $counters[$key] = $count;
        Cache::put(self::COUNTER_CACHE_KEY, $counters, now()->addHours(12));

        if ($count < $this->threshold) {
            return;
        }

        $this->persistCorrection($aliasKey, $canonicalKey);
        unset($counters[$key]);
        Cache::put(self::COUNTER_CACHE_KEY, $counters, now()->addHours(12));
    }

    private function persistCorrection(string $alias, string $canonical): void
    {
        AssistantCorrection::firstOrCreate(
            [
                'alias' => $alias,
                'canonical' => $canonical,
            ],
            [
                'notes' => 'Auto-promoted from repeated user corrections',
                'is_active' => true,
            ]
        );

        Cache::forget(LexiconService::MANUAL_CORRECTIONS_CACHE_KEY);
    }
}
