<?php

namespace App\Services\Assistant;

use Illuminate\Support\Arr;

class ProviderRouter
{
    /**
     * @param  array<string,mixed>  $options
     * @return array{primary:string,chain:array<int,string>,final:string,fallback_from:?string}
     */
    public static function choose(array $options = []): array
    {
        $defaultChain = ['groq', 'gemini', 'openrouter', 'small_lm', 'synth'];
        $primary = (string) ($options['primary'] ?? config('assistant.llm.primary', $defaultChain[0]));
        $chain = array_values(array_unique(array_filter(
            Arr::wrap($options['chain'] ?? config('assistant.llm.chain', $defaultChain)),
            static fn ($value) => is_string($value) && $value !== ''
        )));

        if ($chain === []) {
            $chain = $defaultChain;
        }

        if (!in_array($primary, $chain, true)) {
            array_unshift($chain, $primary);
            $chain = array_values(array_unique($chain));
        }

        foreach ($chain as $provider) {
            if (self::probe($provider)) {
                return [
                    'primary' => $primary,
                    'chain' => $chain,
                    'final' => $provider,
                    'fallback_from' => $provider === $primary ? null : $primary,
                ];
            }
        }

        return [
            'primary' => $primary,
            'chain' => $chain,
            'final' => 'synthesizer',
            'fallback_from' => $primary,
        ];
    }

    private static function probe(string $provider): bool
    {
        // Placeholder health-check; integrate actual probing per provider budget/latency later.
        return true;
    }
}
