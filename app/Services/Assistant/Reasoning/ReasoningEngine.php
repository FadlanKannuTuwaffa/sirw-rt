<?php

namespace App\Services\Assistant\Reasoning;

use Illuminate\Support\Carbon;

/**
 * Two-pass reasoning orchestrator: draft -> verify -> repair/clarify.
 */
class ReasoningEngine
{
    private string $timezone;
    private int $maxRepairAttempts;
    private $violationLogger = null;

    public function __construct(?string $timezone = null, int $maxRepairAttempts = 1)
    {
        $this->timezone = $timezone ?: config('app.timezone', 'UTC');
        $this->maxRepairAttempts = max(0, $maxRepairAttempts);
    }

    /**
     * Full reasoning pipeline. The callable must return a ReasoningDraft.
     */
    public function run(callable $draftCallback, string $language = 'id'): string
    {
        $draft = $this->draft($draftCallback);

        return $this->finalize($draft, $language);
    }

    public function draft(callable $callback): ReasoningDraft
    {
        $draft = $callback();

        if (!$draft instanceof ReasoningDraft) {
            throw new \InvalidArgumentException('Reasoning draft callback must return an instance of ReasoningDraft.');
        }

        return $draft;
    }

    public function setViolationLogger(?callable $logger): void
    {
        $this->violationLogger = $logger;
    }

    public function verify(ReasoningDraft $draft): array
    {
        $violations = [];
        $violations = array_merge($violations, $this->verifyNumerics($draft->numerics));
        $violations = array_merge($violations, $this->verifyDates($draft->dates));

        if (($draft->negation['user_negated'] ?? false) && !($draft->negation['resolved'] ?? false)) {
            $violations[] = [
                'code' => 'negation_conflict',
                'label' => $draft->negation['label'] ?? $draft->intent,
                'message' => 'User negated the request but draft answered positively.',
            ];
        }

        return $violations;
    }

    private function finalize(ReasoningDraft $draft, string $language): string
    {
        $violations = $this->verify($draft);
        if (!empty($violations)) {
            $this->logViolations($draft, $violations);
        }

        if (empty($violations)) {
            return $draft->message;
        }

        $currentDraft = $draft;
        $attempt = 0;

        while (!empty($violations) && is_callable($currentDraft->repairCallback) && $attempt < $this->maxRepairAttempts) {
            $attempt++;
            $candidate = call_user_func($currentDraft->repairCallback, $violations, $currentDraft);

            if ($candidate === null) {
                break;
            }

            if (is_string($candidate)) {
                $candidate = new ReasoningDraft(
                    intent: $currentDraft->intent,
                    message: $candidate,
                    numerics: [],
                    dates: $currentDraft->dates,
                    negation: $currentDraft->negation,
                    clarifications: $currentDraft->clarifications
                );
            }

            if (!$candidate instanceof ReasoningDraft) {
                throw new \UnexpectedValueException('Reasoning repair callback must return string, ReasoningDraft, or null.');
            }

            $currentDraft = $candidate;
            $violations = $this->verify($currentDraft);
            if (!empty($violations)) {
                $this->logViolations($currentDraft, $violations);
            }
        }

        if (empty($violations)) {
            return $currentDraft->message;
        }

        return $this->clarify($currentDraft, $language, $violations);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rules
     * @return array<int, array<string, string>>
     */
    private function verifyNumerics(array $rules): array
    {
        $violations = [];

        foreach ($rules as $rule) {
            $type = $rule['type'] ?? 'sum';
            $label = $rule['label'] ?? 'numeric';

            if ($type === 'sum') {
                $total = (float) ($rule['total'] ?? 0);
                $parts = array_map('floatval', $rule['parts'] ?? []);

                if ($parts === []) {
                    continue;
                }

                $sum = array_sum($parts);
                $tolerance = (float) ($rule['tolerance'] ?? 0.01);

                if (abs($sum - $total) > $tolerance) {
                    $violations[] = [
                        'code' => 'numeric_sum_mismatch',
                        'label' => $label,
                        'message' => "Total {$label} ({$total}) != sum({$sum}).",
                    ];
                }

                continue;
            }

            if ($type === 'range') {
                $value = (float) ($rule['value'] ?? 0);
                $min = array_key_exists('min', $rule) ? (float) $rule['min'] : null;
                $max = array_key_exists('max', $rule) ? (float) $rule['max'] : null;

                if ($min !== null && $value < $min) {
                    $violations[] = [
                        'code' => 'numeric_below_min',
                        'label' => $label,
                        'message' => "Value {$label} ({$value}) is below minimum {$min}.",
                    ];
                }

                if ($max !== null && $value > $max) {
                    $violations[] = [
                        'code' => 'numeric_above_max',
                        'label' => $label,
                        'message' => "Value {$label} ({$value}) is above maximum {$max}.",
                    ];
                }

                continue;
            }

            if ($type === 'non_negative') {
                $value = (float) ($rule['value'] ?? 0);
                if ($value < 0) {
                    $violations[] = [
                        'code' => 'numeric_below_min',
                        'label' => $label,
                        'message' => "Value {$label} ({$value}) is negative.",
                    ];
                }

                continue;
            }

            if ($type === 'difference') {
                $minuend = (float) ($rule['minuend'] ?? 0);
                $subtrahend = (float) ($rule['subtrahend'] ?? 0);
                $expected = (float) ($rule['expected'] ?? 0);
                $tolerance = (float) ($rule['tolerance'] ?? 0.01);
                $diff = $minuend - $subtrahend;

                if (abs($diff - $expected) > $tolerance) {
                    $violations[] = [
                        'code' => 'numeric_difference_mismatch',
                        'label' => $label,
                        'message' => "Difference {$label} ({$diff}) != expected {$expected}.",
                    ];
                }
            }
        }

        return $violations;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rules
     * @return array<int, array<string, string>>
     */
    private function verifyDates(array $rules): array
    {
        $violations = [];

        foreach ($rules as $rule) {
            $label = $rule['label'] ?? 'date';
            $timezone = $rule['timezone'] ?? $this->timezone;
            $type = $rule['type'] ?? null;

            if ($type === 'order') {
                $values = $rule['values'] ?? [];
                if (count($values) < 2) {
                    continue;
                }

                $normalizedValues = [];
                foreach ($values as $value) {
                    $carbon = $value instanceof Carbon ? $value : $this->normalizeDate($value);
                    if (!$carbon) {
                        $violations[] = [
                            'code' => 'date_invalid',
                            'label' => $label,
                            'message' => 'Date order contains invalid date.',
                        ];
                        continue 2;
                    }
                    $normalizedValues[] = $carbon;
                }

                $direction = $rule['direction'] ?? 'asc';
                $isAscending = $direction === 'asc';

                for ($i = 1; $i < count($normalizedValues); $i++) {
                    if ($isAscending && $normalizedValues[$i - 1]->greaterThan($normalizedValues[$i])) {
                        $violations[] = [
                            'code' => 'date_order_invalid',
                            'label' => $label,
                            'message' => 'Dates are not in ascending order.',
                        ];
                        break;
                    }
                    if (!$isAscending && $normalizedValues[$i - 1]->lessThan($normalizedValues[$i])) {
                        $violations[] = [
                            'code' => 'date_order_invalid',
                            'label' => $label,
                            'message' => 'Dates are not in descending order.',
                        ];
                        break;
                    }
                }

                continue;
            }

            if (isset($rule['start'], $rule['end'])) {
                $start = $this->normalizeDate($rule['start']);
                $end = $this->normalizeDate($rule['end']);

                if (!$start || !$end) {
                    $violations[] = [
                        'code' => 'date_invalid',
                        'label' => $label,
                        'message' => 'Date range is invalid.',
                    ];
                    continue;
                }

                if ($start->greaterThan($end)) {
                    $violations[] = [
                        'code' => 'date_range_invalid',
                        'label' => $label,
                        'message' => 'Start date is greater than end date.',
                    ];
                }

                if ($timezone && ($start->getTimezone()->getName() !== $timezone || $end->getTimezone()->getName() !== $timezone)) {
                    $violations[] = [
                        'code' => 'date_timezone_mismatch',
                        'label' => $label,
                        'message' => 'Date range timezone mismatch.',
                    ];
                }

                continue;
            }

            if (!isset($rule['value'])) {
                continue;
            }

            $value = $this->normalizeDate($rule['value']);

            if (!$value) {
                $violations[] = [
                    'code' => 'date_invalid',
                    'label' => $label,
                    'message' => 'Date is invalid.',
                ];
                continue;
            }

            if ($timezone && $value->getTimezone()->getName() !== $timezone) {
                $violations[] = [
                    'code' => 'date_timezone_mismatch',
                    'label' => $label,
                    'message' => 'Date timezone mismatch.',
                ];
            }
        }

        return $violations;
    }

    private function normalizeDate(mixed $date): ?Carbon
    {
        if ($date instanceof Carbon) {
            return $date;
        }

        if (is_string($date)) {
            try {
                return Carbon::parse($date, $this->timezone);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    private function clarify(ReasoningDraft $draft, string $language, array $violations): string
    {
        foreach ($violations as $violation) {
            $code = $violation['code'];
            $clarification = $draft->clarifications[$code] ?? null;

            if ($clarification !== null) {
                return $this->clarificationText($clarification, $language);
            }
        }

        if (isset($draft->clarifications['default'])) {
            return $this->clarificationText($draft->clarifications['default'], $language);
        }

        return $language === 'en'
            ? 'Some details look inconsistent. Could you clarify the numbers or dates so I can re-check them?'
            : 'Ada detail yang masih janggal. Bisa bantu jelaskan angka atau tanggalnya supaya bisa kucek ulang?';
    }

    private function clarificationText(mixed $clarification, string $language): string
    {
        if (is_string($clarification)) {
            return $clarification;
        }

        if (!is_array($clarification)) {
            return $language === 'en'
                ? 'Could you share more detail so I can be precise?'
                : 'Boleh dibantu detailnya supaya jawabanku lebih tepat?';
        }

        if ($language === 'en' && isset($clarification['en'])) {
            return $clarification['en'];
        }

        return $clarification['id'] ?? $clarification['en'] ?? (
            $language === 'en'
                ? 'Could you share more detail so I can be precise?'
                : 'Boleh dibantu detailnya supaya jawabanku lebih tepat?'
        );
    }

    private function logViolations(ReasoningDraft $draft, array $violations): void
    {
        if ($this->violationLogger === null) {
            return;
        }

        call_user_func($this->violationLogger, $draft->intent, $violations);
    }
}
