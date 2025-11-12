<?php

namespace App\Services\Assistant;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CorrectionMemoryService
{
    /**
     * Aggregate active correction events for the current user/org/thread context.
     *
     * @return array{
     *     bias:array<string,float>,
     *     style:array<string,mixed>,
     *     syn:array<int,array{alias:string,canonical:string,ttl:int}>,
     *     fewshot:array<int,mixed>,
     *     facts:array<int,array<string,mixed>>,
     *     forbidden:array<int,string>
     * }
     */
    public function apply(?int $userId = null, ?int $orgId = null, ?string $threadId = null): array
    {
        $now = Carbon::now();
        $events = DB::table('assistant_correction_events')
            ->where('is_active', true)
            ->where(function ($query) use ($now) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            })
            ->where(function ($scoped) use ($userId, $orgId, $threadId) {
                $scoped->orWhere('scope', 'global');

                if ($threadId) {
                    $scoped->orWhere(function ($inner) use ($threadId) {
                        $inner->where('scope', 'thread')->where('thread_id', $threadId);
                    });
                }

                if ($userId) {
                    $scoped->orWhere(function ($inner) use ($userId) {
                        $inner->where('scope', 'user')->where('user_id', $userId);
                    });
                }

                if ($orgId) {
                    $scoped->orWhere(function ($inner) use ($orgId) {
                        $inner->where('scope', 'org')->where('org_id', $orgId);
                    });
                }
            })
            ->orderByRaw("CASE scope WHEN 'thread' THEN 0 WHEN 'user' THEN 1 WHEN 'org' THEN 2 ELSE 3 END")
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get();

        $bias = [];
        $style = [];
        $syn = [];
        $fewshot = [];
        $forbidden = [];
        $facts = [];
        $appliedIds = [];

        foreach ($events as $event) {
            $appliedIds[] = $event->id;
            $patchRules = json_decode($event->patch_rules ?? '[]', true) ?: [];

            foreach ($this->normalizeSynonyms($patchRules['synonym_add'] ?? []) as $entry) {
                $syn[] = $entry;
            }

            foreach ($this->normalizeBias($patchRules['intent_bias'] ?? []) as $intent => $score) {
                $bias[$intent] = min(1.0, ($bias[$intent] ?? 0) + $score);
            }

            $style = array_merge($style, $this->normalizeStyle($patchRules['style_toggle'] ?? []));

            foreach ($this->normalizeForbidden($patchRules['forbidden_phrases'] ?? []) as $phrase) {
                $forbidden[] = $phrase;
            }

            foreach ($this->normalizeFacts($patchRules['fact_patch'] ?? [], $event) as $factPatch) {
                $facts[] = $factPatch;
            }

            if (is_string($event->language_preference) && $event->language_preference !== '') {
                $style['language'] = $event->language_preference;
            }

            if (is_string($event->tone_preference) && $event->tone_preference !== '') {
                $style['tone'] = $event->tone_preference;
            }

            $examples = json_decode($event->examples ?? '[]', true) ?: [];
            if (is_array($examples)) {
                $fewshot = array_values(array_merge($fewshot, $examples));
            }
        }

        if ($appliedIds !== []) {
            DB::table('assistant_correction_events')
                ->whereIn('id', $appliedIds)
                ->update(['applied_at' => $now]);
        }

        $style = array_merge($style, $this->fetchStylePreferences($userId, $orgId));

        return [
            'bias' => $bias,
            'style' => $style,
            'syn' => $syn,
            'fewshot' => $fewshot,
            'facts' => $facts,
            'forbidden' => array_values(array_unique($forbidden)),
        ];
    }

    /**
     * @param  array<int|string,mixed>  $entries
     * @return array<int,array{alias:string,canonical:string,ttl:int}>
     */
    private function normalizeSynonyms(array $entries): array
    {
        $normalized = [];

        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $alias = trim((string) ($entry['alias'] ?? ''));
            $canonical = trim((string) ($entry['canonical'] ?? ''));

            if ($alias === '' || $canonical === '' || strcasecmp($alias, $canonical) === 0) {
                continue;
            }

            $normalized[] = [
                'alias' => $alias,
                'canonical' => $canonical,
                'ttl' => isset($entry['ttl']) ? (int) $entry['ttl'] : 3600,
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<int|string,mixed>  $entries
     * @return array<string,float>
     */
    private function normalizeBias(array $entries): array
    {
        $normalized = [];

        foreach ($entries as $intent => $score) {
            if (is_string($score)) {
                $score = (float) $score;
            }

            if (!is_numeric($score)) {
                continue;
            }

            $normalized[(string) $intent] = max(0.0, min(1.0, (float) $score));
        }

        return $normalized;
    }

    /**
     * @param  array<string,mixed>  $style
     */
    private function normalizeStyle(array $style): array
    {
        $allowed = [
            'language',
            'formality',
            'humor',
            'introduce_self',
            'emoji_policy',
            'tone',
        ];

        return array_filter(
            array_intersect_key($style, array_flip($allowed)),
            fn ($value) => $value !== null && $value !== ''
        );
    }

    /**
     * @param  array<int|string,mixed>  $phrases
     * @return array<int,string>
     */
    private function normalizeForbidden(array $phrases): array
    {
        $normalized = [];

        foreach ($phrases as $phrase) {
            if (!is_string($phrase)) {
                continue;
            }

            $clean = Str::of($phrase)
                ->squish()
                ->trim(" \"'“”‘’.?!")
                ->substr(0, 120)
                ->value();

            if ($clean === '') {
                continue;
            }

            $normalized[] = $clean;
        }

        return $normalized;
    }

    /**
     * @return array<string,mixed>
     */
    private function fetchStylePreferences(?int $userId, ?int $orgId): array
    {
        if (!Schema::hasTable('user_style_prefs')) {
            return [];
        }

        $query = DB::table('user_style_prefs');

        if ($userId !== null) {
            $query->where('user_id', $userId);
        } elseif ($orgId !== null) {
            $query->where('org_id', $orgId);
        } else {
            return [];
        }

        $record = $query->orderByDesc('updated_at')->first([
            'default_language',
            'formality',
            'humor',
            'introduce_self_on_first_turn',
            'emoji_policy',
        ]);

        if ($record === null) {
            return [];
        }

        return array_filter([
            'language' => $record->default_language ?? null,
            'formality' => $record->formality ?? null,
            'humor' => isset($record->humor) ? (bool) $record->humor : null,
            'introduce_self' => isset($record->introduce_self_on_first_turn)
                ? (bool) $record->introduce_self_on_first_turn
                : null,
            'emoji_policy' => $record->emoji_policy ?? null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @param  array<int|string,mixed>  $entries
     * @return array<int,array<string,mixed>>
     */
    private function normalizeFacts(array $entries, object $event): array
    {
        $normalized = [];

        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $entity = trim((string) ($entry['entity'] ?? ''));
            $field = trim((string) ($entry['field'] ?? ''));

            if ($entity === '' || $field === '') {
                continue;
            }

            $normalized[] = [
                'entity' => $entity,
                'field' => $field,
                'value' => $entry['value'] ?? null,
                'value_raw' => $entry['value_raw'] ?? null,
                'intent' => $entry['intent'] ?? null,
                'match' => is_array($entry['match'] ?? null) ? $entry['match'] : [],
                'scope' => $entry['scope'] ?? ($event->scope ?? 'global'),
                'event_id' => $event->id ?? null,
            ];
        }

        return $normalized;
    }
}
