<?php

namespace App\Services\Assistant;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Pulls recent human-approved utterances from assistant_interactions
 * to extend the intent classifier with data-driven examples.
 */
class IntentExampleRepository
{
    private const CACHE_KEY = 'assistant.intent_examples';
    private const CACHE_SECONDS = 600;
    private const MAX_ROWS = 1200;
    private const MAX_PER_INTENT = 40;

    /**
     * @return array<string, array<int, array{tokens:array<int,string>,weight:float}>>
     */
    public function all(): array
    {
        if (!Schema::hasTable('assistant_interactions')) {
            return [];
        }

        return Cache::remember(self::CACHE_KEY, self::CACHE_SECONDS, function () {
            $rows = DB::table('assistant_interactions')
                ->select('intent', 'message', 'was_helpful', 'feedback_at', 'updated_at', 'created_at')
                ->whereNotNull('intent')
                ->whereNotNull('message')
                ->orderByDesc(DB::raw('COALESCE(feedback_at, updated_at, created_at)'))
                ->limit(self::MAX_ROWS)
                ->get();

            $bucket = [];

            foreach ($rows as $row) {
                $intent = Str::of($row->intent ?? '')
                    ->lower()
                    ->squish()
                    ->value();

                if ($intent === '') {
                    continue;
                }

                $tokens = $this->tokenize($row->message ?? '');

                if (count($tokens) < 2) {
                    continue;
                }

                $bucket[$intent][] = [
                    'tokens' => $tokens,
                    'weight' => $this->weight($row),
                    'signature' => implode(' ', array_slice($tokens, 0, 6)),
                ];
            }

            $results = [];

            foreach ($bucket as $intent => $examples) {
                $unique = [];
                $seen = [];

                foreach ($examples as $example) {
                    $signature = $example['signature'];

                    if (isset($seen[$signature])) {
                        continue;
                    }

                    $seen[$signature] = true;
                    $unique[] = [
                        'tokens' => $example['tokens'],
                        'weight' => $example['weight'],
                    ];

                    if (count($unique) >= self::MAX_PER_INTENT) {
                        break;
                    }
                }

                $results[$intent] = $unique;
            }

            return $results;
        });
    }

    /**
     * @return array<int,string>
     */
    private function tokenize(string $message): array
    {
        $normalized = Str::of($message)->lower()->squish()->value();

        if ($normalized === '') {
            return [];
        }

        $parts = preg_split('/\s+/u', $normalized) ?: [];
        $tokens = [];

        foreach ($parts as $part) {
            $token = preg_replace('/[^\pL\pN]+/u', '', $part);
            if ($token === null) {
                continue;
            }

            $token = trim($token);
            if ($token === '') {
                continue;
            }

            $tokens[] = $token;
        }

        return array_values(array_unique($tokens));
    }

    private function weight(object $row): float
    {
        $base = $row->was_helpful === false ? 0.2 : 0.9;
        if ($row->was_helpful === null) {
            $base = 0.8;
        }

        $timestamp = null;
        foreach (['feedback_at', 'updated_at', 'created_at'] as $column) {
            if (!empty($row->{$column})) {
                $timestamp = Carbon::parse($row->{$column});
                break;
            }
        }

        if ($timestamp instanceof Carbon) {
            if ($timestamp->greaterThan(Carbon::now()->subDays(7))) {
                $base += 0.2;
            } elseif ($timestamp->greaterThan(Carbon::now()->subDays(30))) {
                $base += 0.1;
            }
        }

        return round(min(1.3, max(0.2, $base)), 2);
    }

    public function refresh(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
