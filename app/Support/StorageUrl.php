<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class StorageUrl
{
    public static function forPublicDisk(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return null;
        }

        $publicPath = public_path('storage/' . ltrim($path, '/'));

        if (file_exists($publicPath)) {
            return $disk->url($path);
        }

        return URL::signedRoute('storage.proxy', ['path' => $path]);
    }
}
