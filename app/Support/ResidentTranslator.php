<?php

namespace App\Support;

final class ResidentTranslator
{
    private static function currentLocale(): string
    {
        $locale = app()->getLocale();

        return in_array($locale, ['id', 'en'], true) ? $locale : 'id';
    }

    public static function text(string $indonesian, ?string $english = null, array $replace = []): string
    {
        $locale = self::currentLocale();
        $raw = $locale === 'en' ? ($english ?? $indonesian) : $indonesian;

        foreach ($replace as $key => $value) {
            $raw = str_replace(':' . $key, (string) $value, $raw);
        }

        return $raw;
    }

    public static function sentence(string $indonesian, ?string $english = null, array $replace = []): string
    {
        return self::text($indonesian, $english, $replace);
    }

    public static function capitalized(string $indonesian, ?string $english = null, array $replace = []): string
    {
        $text = self::text($indonesian, $english, $replace);

        return mb_convert_case($text, MB_CASE_TITLE, 'UTF-8');
    }

    public static function upper(string $indonesian, ?string $english = null, array $replace = []): string
    {
        $text = self::text($indonesian, $english, $replace);

        return mb_strtoupper($text, 'UTF-8');
    }
}
