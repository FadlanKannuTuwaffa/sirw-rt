<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Throwable;

class AutoTranslator
{
    private const CACHE_PREFIX = 'translation:';
    private const CACHE_TTL_MINUTES = 10080; // 7 days

    /**
     * Translate the provided text using Google Translate with simple caching.
     */
    public function translate(?string $text, string $targetLocale, string $sourceLocale = 'auto'): ?string
    {
        $normalized = $this->normalizeText($text);
        if ($normalized === null || $this->isSameLocale($targetLocale, $sourceLocale)) {
            return $text;
        }

        $target = $this->mapLocale(strtolower($targetLocale));
        $source = strtolower($sourceLocale);
        $source = $source === 'auto' ? 'auto' : $this->mapLocale($source);

        $cacheKey = self::CACHE_PREFIX . md5($source . '|' . $target . '|' . $normalized);

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MINUTES), function () use ($normalized, $target, $source, $text) {
            try {
                $translator = new GoogleTranslate($target, $source);
                return $translator->translate($normalized);
            } catch (Throwable $exception) {
                report($exception);
                return $text;
            }
        });
    }

    private function normalizeText(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        $trimmed = trim($text);

        if ($trimmed === '') {
            return null;
        }

        return $trimmed;
    }

    private function isSameLocale(string $target, string $source): bool
    {
        if ($source === 'auto') {
            return false;
        }

        return $this->mapLocale(strtolower($target)) === $this->mapLocale(strtolower($source));
    }

    private function mapLocale(string $locale): string
    {
        return match ($locale) {
            'jv', 'jw' => 'jw',
            'su', 'snd' => 'su',
            default => $locale,
        };
    }
}
