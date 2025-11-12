<?php

namespace App\Services\Assistant\Reasoning;

use Illuminate\Support\Carbon;

class ReasoningRules
{
    public static function sum(string $label, float|int $total, array $parts, float $tolerance = 0.01): array
    {
        return [
            'type' => 'sum',
            'label' => $label,
            'total' => (float) $total,
            'parts' => array_map('floatval', $parts),
            'tolerance' => $tolerance,
        ];
    }

    public static function difference(string $label, float|int $minuend, float|int $subtrahend, float|int $expected, float $tolerance = 0.01): array
    {
        return [
            'type' => 'difference',
            'label' => $label,
            'minuend' => (float) $minuend,
            'subtrahend' => (float) $subtrahend,
            'expected' => (float) $expected,
            'tolerance' => $tolerance,
        ];
    }

    public static function range(string $label, float|int $value, ?float $min = null, ?float $max = null): array
    {
        return [
            'type' => 'range',
            'label' => $label,
            'value' => (float) $value,
            'min' => $min,
            'max' => $max,
        ];
    }

    public static function nonNegative(string $label, float|int $value): array
    {
        return [
            'type' => 'non_negative',
            'label' => $label,
            'value' => (float) $value,
        ];
    }

    public static function dateOrder(string $label, array $values, string $direction = 'asc', ?string $timezone = null): array
    {
        return [
            'type' => 'order',
            'label' => $label,
            'values' => array_map(
                fn ($value) => $value instanceof Carbon ? $value : Carbon::parse($value, $timezone ?? config('app.timezone', 'UTC')),
                $values
            ),
            'direction' => strtolower($direction) === 'desc' ? 'desc' : 'asc',
            'timezone' => $timezone,
        ];
    }
}
