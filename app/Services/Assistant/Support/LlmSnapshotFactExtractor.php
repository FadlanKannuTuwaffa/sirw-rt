<?php

namespace App\Services\Assistant\Support;

use App\Models\AssistantLlmSnapshot;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class LlmSnapshotFactExtractor
{
    /**
     * @var array<int, string>
     */
    private const STRUCTURED_INTENTS = [
        'agenda',
        'bills',
        'finance',
        'payments',
        'residents',
        'residents_new',
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function extract(AssistantLlmSnapshot $snapshot): array
    {
        $intent = $this->resolveIntent($snapshot);

        if ($intent === null || !in_array($intent, self::STRUCTURED_INTENTS, true)) {
            return [];
        }

        $content = trim((string) $snapshot->content);
        if ($content === '') {
            return [];
        }

        $lines = $this->normalizeLines($content);
        $facts = [];

        foreach ($lines as $line) {
            $facts = array_merge($facts, match ($intent) {
                'bills', 'finance' => $this->extractBillFacts($line, $snapshot, 'bill', $intent),
                'payments' => $this->extractBillFacts($line, $snapshot, 'payment', $intent),
                'agenda' => $this->extractAgendaFacts($line, $snapshot, $intent),
                'residents', 'residents_new' => $this->extractResidentFacts($line, $snapshot, $intent),
                default => [],
            });

            if (count($facts) >= 6) {
                break;
            }
        }

        return array_values(array_filter($facts, static fn ($fact) => isset($fact['field'], $fact['entity'])));
    }

    private function resolveIntent(AssistantLlmSnapshot $snapshot): ?string
    {
        $intent = $snapshot->intent ?: Arr::get($snapshot->metadata, 'classification.intents.0');

        if (!$intent) {
            $intent = Arr::get($snapshot->metadata, 'state.last_intent');
        }

        return $intent ? Str::lower((string) $intent) : null;
    }

    /**
     * @return array<int, string>
     */
    private function normalizeLines(string $content): array
    {
        $rawLines = preg_split("/\r\n|\n|\r/", $content) ?: [];

        return array_values(array_filter(array_map(static function (string $line) {
            $line = trim($line);
            $line = preg_replace('/^\s*[\-\*\d\.•]+\s*/u', '', $line) ?: $line;

            return trim($line);
        }, $rawLines)));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractBillFacts(string $line, AssistantLlmSnapshot $snapshot, string $entity, string $intent): array
    {
        $facts = [];

        $amount = $this->extractAmount($line);
        $status = $this->extractStatus($line);
        $needle = $this->deriveNeedle($line);

        if ($amount !== null) {
            $facts[] = [
                'entity' => $entity,
                'field' => 'amount',
                'value' => $amount,
                'value_raw' => $line,
                'intent' => $intent,
                'match' => $this->matchContext($snapshot, $needle, $line, $entity),
            ];
        }

        if ($status !== null) {
            $facts[] = [
                'entity' => $entity,
                'field' => 'status',
                'value' => $status,
                'value_raw' => $line,
                'intent' => $intent,
                'match' => $this->matchContext($snapshot, $needle, $line, $entity),
            ];
        }

        return $facts;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractAgendaFacts(string $line, AssistantLlmSnapshot $snapshot, string $intent): array
    {
        $facts = [];
        $needle = $this->deriveNeedle($line);

        if ($location = $this->extractLocation($line)) {
            $facts[] = [
                'entity' => 'event',
                'field' => 'location',
                'value' => $location,
                'value_raw' => $line,
                'intent' => $intent,
                'match' => $this->matchContext($snapshot, $needle, $line, 'event'),
            ];
        }

        if ($title = $this->extractTitle($line)) {
            $facts[] = [
                'entity' => 'event',
                'field' => 'title',
                'value' => $title,
                'value_raw' => $line,
                'intent' => $intent,
                'match' => $this->matchContext($snapshot, $needle, $line, 'event'),
            ];
        }

        return $facts;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractResidentFacts(string $line, AssistantLlmSnapshot $snapshot, string $intent): array
    {
        $facts = [];
        $needle = $this->deriveNeedle($line);

        if ($phone = $this->extractPhone($line)) {
            $facts[] = [
                'entity' => 'resident',
                'field' => 'phone',
                'value' => $phone,
                'value_raw' => $line,
                'intent' => $intent,
                'match' => $this->matchContext($snapshot, $needle, $line, 'resident'),
            ];
        }

        if ($address = $this->extractAddress($line)) {
            $facts[] = [
                'entity' => 'resident',
                'field' => 'address',
                'value' => $address,
                'value_raw' => $line,
                'intent' => $intent,
                'match' => $this->matchContext($snapshot, $needle, $line, 'resident'),
            ];
        }

        if ($name = $this->extractName($line)) {
            $facts[] = [
                'entity' => 'resident',
                'field' => 'name',
                'value' => $name,
                'value_raw' => $line,
                'intent' => $intent,
                'match' => $this->matchContext($snapshot, $needle, $line, 'resident'),
            ];
        }

        return $facts;
    }

    private function extractAmount(string $line): ?int
    {
        if (!preg_match('/rp\s*([\d\.\,]+)/i', $line, $matches)) {
            return null;
        }

        $number = (int) str_replace(['.', ','], '', $matches[1]);

        return $number > 0 ? $number : null;
    }

    private function extractStatus(string $line): ?string
    {
        $normalized = Str::lower($line);

        return match (true) {
            Str::contains($normalized, ['lunas', 'sudah bayar', 'paid', 'settled']) => 'paid',
            Str::contains($normalized, ['belum', 'tunggak', 'outstanding', 'unpaid']) => 'unpaid',
            default => null,
        };
    }

    private function extractLocation(string $line): ?string
    {
        $normalized = Str::lower($line);

        return Str::contains($normalized, ['balai', 'aula', 'sekretariat', 'lapangan', 'rumah', 'blok', 'kantor', 'pos'])
            ? Str::squish($line)
            : null;
    }

    private function extractTitle(string $line): ?string
    {
        $segments = preg_split('/[:\-]/', $line);
        $candidate = trim($segments[0] ?? '');

        return $candidate !== '' ? Str::squish($candidate) : null;
    }

    private function extractPhone(string $line): ?string
    {
        $digits = preg_replace('/\D+/', '', $line);

        return (strlen($digits) >= 8) ? $digits : null;
    }

    private function extractAddress(string $line): ?string
    {
        $normalized = Str::lower($line);

        return Str::contains($normalized, ['blok', 'rt', 'rw', 'jalan', 'gang', 'perum'])
            ? Str::squish($line)
            : null;
    }

    private function extractName(string $line): ?string
    {
        if (preg_match('/^(pak|bu)\s+[a-z0-9 ]+/iu', $line, $matches)) {
            return Str::squish($matches[0]);
        }

        $segments = preg_split('/[\-\|]/', $line);
        $candidate = trim($segments[0] ?? '');

        return $candidate !== '' ? Str::squish($candidate) : null;
    }

    private function deriveNeedle(string $line): string
    {
        $beforeCurrency = preg_split('/rp/i', $line);
        $needle = trim($beforeCurrency[0] ?? $line);

        if ($needle === '') {
            $needle = Str::limit(Str::squish($line), 60, '');
        }

        $needle = Str::lower(Str::squish($needle));

        return $needle === '' ? 'snapshot_context' : $needle;
    }

    /**
     * @return array<string, mixed>
     */
    private function matchContext(AssistantLlmSnapshot $snapshot, string $needle, string $raw, string $entity): array
    {
        $context = [
            'needle' => $needle,
            'keywords' => $this->extractKeywords($raw),
        ];

        $fields = match ($entity) {
            'bill' => ['title', 'type', 'description'],
            'payment' => ['reference', 'description'],
            'event' => ['title', 'location', 'description'],
            'resident' => ['name', 'alamat', 'address'],
            default => [],
        };

        if ($fields !== []) {
            $id = $this->matchEntityId(Arr::get($snapshot->metadata, 'state.last_data'), $needle, $fields);
            if ($id !== null) {
                $context['id'] = $id;
            }
        }

        return array_filter($context, static fn ($value) => $value !== null && $value !== [] && $value !== '');
    }

    /**
     * @param  array<int|string, mixed>|null  $source
     */
    private function matchEntityId(mixed $source, string $needle, array $fields): ?int
    {
        if (!is_iterable($source)) {
            return null;
        }

        $needle = Str::lower(Str::squish($needle));
        if ($needle === '') {
            return null;
        }

        foreach ($source as $entry) {
            if (is_object($entry)) {
                $entry = (array) $entry;
            }

            if (!is_array($entry)) {
                continue;
            }

            $haystack = Str::lower(Str::squish(implode(' ', array_map(static function ($field) use ($entry) {
                return (string) ($entry[$field] ?? '');
            }, $fields))));

            if ($haystack !== '' && Str::contains($haystack, $needle)) {
                $id = $entry['id'] ?? $entry['bill_id'] ?? $entry['event_id'] ?? null;
                if ($id !== null && is_numeric($id)) {
                    return (int) $id;
                }
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function extractKeywords(string $text): array
    {
        $tokens = preg_split('/[\s,\.]+/', Str::lower(Str::squish($text))) ?: [];

        return array_values(array_unique(array_slice(array_filter($tokens, static fn ($token) => strlen($token) >= 4), 0, 5)));
    }
}
