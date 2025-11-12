<?php

namespace App\Services\Telegram;

use App\Models\SiteSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class TelegramSettings
{
    public const CACHE_KEY = 'telegram.settings';
    public const CACHE_TTL = 300; // seconds

    public function all(): Collection
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $settings = SiteSetting::query()
                ->where('group', 'telegram')
                ->get()
                ->mapWithKeys(function (SiteSetting $setting) {
                    $value = $setting->value;

                    if ($setting->type === 'json' && is_string($value)) {
                        $decoded = json_decode($value, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $value = $decoded;
                        }
                    }

                    return [$setting->key => $value];
                })
                ->toArray();

            return collect($settings);
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $fromDatabase = $this->all()->get($key);

        if ($fromDatabase !== null && $fromDatabase !== '') {
            return $fromDatabase;
        }

        return config('services.telegram.' . $key, $default);
    }

    public function botToken(): ?string
    {
        return $this->get('bot_token');
    }

    public function webhookSecret(): ?string
    {
        return $this->get('webhook_secret');
    }

    public function webhookUrl(): ?string
    {
        return $this->get('webhook_url');
    }

    public function defaultLanguage(): string
    {
        return $this->get('default_language', 'id');
    }

    public function contactEmail(): ?string
    {
        return $this->get('contact_email');
    }

    public function contactWhatsapp(): ?string
    {
        return $this->get('contact_whatsapp');
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function store(array $data): void
    {
        foreach ($data as $key => $value) {
            SiteSetting::updateOrCreate(
                ['key' => $key],
                [
                    'group' => 'telegram',
                    'value' => is_array($value) ? json_encode($value) : $value,
                    'type' => is_array($value) ? 'json' : 'string',
                ]
            );
        }

        $this->flush();
    }

    public function toArray(): array
    {
        return $this->all()->toArray();
    }
}
