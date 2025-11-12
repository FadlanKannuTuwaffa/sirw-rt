<?php

namespace App\Support;

use Illuminate\Support\Str;

class SensitiveData
{
    public static function normalizeDigits(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value);

        return $digits !== '' ? $digits : null;
    }

    public static function normalizeText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = Str::of($value)
            ->squish()
            ->lower()
            ->value();

        return $normalized !== '' ? $normalized : null;
    }

    public static function hash(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return hash('sha256', $value . '|' . config('app.key'));
    }

    public static function maskTrailing(?string $value, int $visible = 4): ?string
    {
        if ($value === null) {
            return null;
        }

        $length = strlen($value);

        if ($length <= $visible) {
            return str_repeat('*', max($length - 1, 0)) . substr($value, -$visible);
        }

        return str_repeat('*', max($length - $visible, 0)) . substr($value, -$visible);
    }
}
