<?php

namespace App\Support\Assistant;

use App\Models\SiteSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProviderManager
{
    /**
     * @return array<int, string>
     */
    public static function providers(): array
    {
        return [
            'Groq',
            'Gemini',
            'OpenRouter',
            'Mistral',
            'HuggingFace',
            'Cohere',
            'LangDB',
        ];
    }

    public static function settingKey(string $provider): string
    {
        return Str::slug($provider, '_') . '_enabled';
    }

    /**
     * @param array<string, mixed>|null $rawSettings
     */
    public static function isEnabled(string $provider, ?array $rawSettings = null): bool
    {
        $settings = $rawSettings ?? static::rawSettings()->all();
        $key = static::settingKey($provider);

        $value = $settings[$key] ?? null;

        if ($value === null) {
            return true;
        }

        return (string) $value !== '0';
    }

    /**
     * @return array<string, bool>
     */
    public static function stateMap(): array
    {
        $raw = static::rawSettings()->all();

        $states = [];

        foreach (static::providers() as $provider) {
            $states[$provider] = static::isEnabled($provider, $raw);
        }

        return $states;
    }

    private static function rawSettings(): Collection
    {
        return SiteSetting::keyValue('assistant_llm');
    }
}
