<?php

namespace App\Services\Assistant;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SlangNormalizer
{
    /**
     * Normalize slang terms from lexicon_slang table.
     *
     * @return array{0:string,1:array<int,string>}
     */
    public function normalize(string $text): array
    {
        $clean = trim($text);

        if ($clean === '') {
            return [$text, []];
        }

        $entries = $this->allTerms();
        $normalized = $text;
        $hits = [];

        foreach ($entries as $entry) {
            $term = $entry['term'] ?? '';
            $canonical = $entry['canonical'] ?? '';

            if ($term === '' || $canonical === '') {
                continue;
            }

            $pattern = '/\b' . preg_quote($term, '/') . '\b/iu';

            if (preg_match($pattern, $normalized)) {
                $normalized = preg_replace($pattern, $canonical, $normalized) ?? $normalized;
                $hits[] = $term;
            }
        }

        return [$normalized, array_values(array_unique($hits))];
    }

    /**
     * @return array<int,array{term:string,canonical:string}>
     */
    private function allTerms(): array
    {
        return Cache::remember('assistant.lexicon_slang', 300, static function () {
            return DB::table('lexicon_slang')
                ->orderByDesc('updated_at')
                ->get(['term', 'canonical'])
                ->map(static function ($row) {
                    return [
                        'term' => Str::of($row->term ?? '')->lower()->squish()->value(),
                        'canonical' => (string) ($row->canonical ?? ''),
                    ];
                })
                ->filter(static fn ($entry) => ($entry['term'] ?? '') !== '' && ($entry['canonical'] ?? '') !== '')
                ->values()
                ->all();
        });
    }
}
