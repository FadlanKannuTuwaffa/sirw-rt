<?php

namespace App\Support;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class RT
{
    public static function text(string $id, string $en, array $replace = []): string
    {
        $locale = App::getLocale();
        // Generate a simple key from the Indonesian text for now.
        // This will be replaced by actual keys later.
        $key = 'resident.' . Str::snake(preg_replace('/[^a-zA-Z0-9]+/', '_', $id));

        // If a translation exists, use it. Otherwise, return the appropriate string.
        if (trans()->has($key)) {
            return trans($key, $replace);
        } else {
            return $locale === 'id' ? $id : $en;
        }
    }
}
