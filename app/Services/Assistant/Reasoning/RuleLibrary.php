<?php

namespace App\Services\Assistant\Reasoning;

/**
 * Small helper to build reasoning verification rules consistently.
 */
class RuleLibrary
{
    /**
     * @param  array<int, float|int|string>  $parts
     */
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

    public static function difference(
        string $label,
        float|int $minuend,
        float|int $subtrahend,
        float|int $expected,
        float $tolerance = 0.01
    ): array {
        return [
            'type' => 'difference',
            'label' => $label,
            'minuend' => (float) $minuend,
            'subtrahend' => (float) $subtrahend,
            'expected' => (float) $expected,
            'tolerance' => $tolerance,
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

    /**
     * @param  array<int, mixed>  $values
     */
    public static function dateOrder(
        string $label,
        array $values,
        string $direction = 'asc',
        ?string $timezone = null
    ): array {
        return [
            'type' => 'order',
            'label' => $label,
            'values' => $values,
            'direction' => $direction,
            'timezone' => $timezone,
        ];
    }

    public static function dateRange(
        string $label,
        mixed $start,
        mixed $end,
        ?string $timezone = null
    ): array {
        return [
            'type' => 'range',
            'label' => $label,
            'start' => $start,
            'end' => $end,
            'timezone' => $timezone,
        ];
    }
}

