<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;

class SiteSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    private const SENSITIVE_COMPOSITES = [
        'payment:tripay_api_key',
        'payment:tripay_private_key',
        'payment:tripay_merchant_code',
        'smtp:password',
        'telegram:bot_token',
        'telegram:webhook_secret',
    ];

    private const SENSITIVE_FALLBACK_KEYS = [
        'tripay_api_key',
        'tripay_private_key',
        'tripay_merchant_code',
        'bot_token',
        'webhook_secret',
        'password',
    ];

    protected function value(): Attribute
    {
        return Attribute::make(
            get: function ($value, array $attributes) {
                if ($value === null) {
                    return null;
                }

                if (! $this->shouldEncrypt($attributes)) {
                    return $value;
                }

                try {
                    return Crypt::decryptString($value);
                } catch (\Throwable) {
                    return $value;
                }
            },
            set: function ($value, array $attributes) {
                if ($value === null) {
                    return null;
                }

                if (! $this->shouldEncrypt($attributes)) {
                    return $value;
                }

                return Crypt::encryptString((string) $value);
            }
        );
    }

    public static function keyValue(?string $group = null): Collection
    {
        $query = static::query();

        if ($group !== null) {
            $query->where('group', $group);
        }

        return $query
            ->get()
            ->mapWithKeys(fn (SiteSetting $setting) => [$setting->key => $setting->value]);
    }

    private function shouldEncrypt(array $attributes): bool
    {
        $key = $attributes['key'] ?? $this->attributes['key'] ?? null;
        $group = $attributes['group'] ?? $this->attributes['group'] ?? null;

        if (! $key) {
            return false;
        }

        $composite = $group ? "{$group}:{$key}" : $key;

        if (in_array($composite, self::SENSITIVE_COMPOSITES, true)) {
            return true;
        }

        return in_array($key, self::SENSITIVE_FALLBACK_KEYS, true);
    }
}
