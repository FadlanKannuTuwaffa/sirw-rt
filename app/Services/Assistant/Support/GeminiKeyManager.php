<?php

namespace App\Services\Assistant\Support;

use Illuminate\Support\Facades\Cache;

class GeminiKeyManager
{
    /**
     * @return array<int,string>
     */
    public static function keys(): array
    {
        static $keys = null;

        if ($keys !== null) {
            return $keys;
        }

        $configured = config('services.gemini.keys', []);
        $keys = array_values(array_filter(array_map('trim', $configured), static fn ($key) => $key !== ''));

        return $keys;
    }

    public static function getNextKey(): ?string
    {
        $keys = self::keys();

        if ($keys === []) {
            return null;
        }

        $index = Cache::get('gemini_key_cursor', 0);
        $key = $keys[$index % count($keys)];
        Cache::put('gemini_key_cursor', ($index + 1) % count($keys), now()->addMinutes(5));

        return $key;
    }
}
