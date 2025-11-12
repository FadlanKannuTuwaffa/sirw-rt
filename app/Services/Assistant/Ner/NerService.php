<?php

namespace App\Services\Assistant\Ner;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class NerService
{
    private ?string $ducklingEndpoint;
    private float $ducklingTimeout;

    public function __construct(?string $ducklingEndpoint = null, float $ducklingTimeout = 2.5)
    {
        $this->ducklingEndpoint = $ducklingEndpoint !== '' ? $ducklingEndpoint : null;
        $this->ducklingTimeout = $ducklingTimeout;
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function extract(string $message): array
    {
        $entities = [
            'names' => [],
            'addresses' => [],
            'dates' => [],
            'period' => [],
            'amounts' => [],
            'numbers' => [],
        ];

        $this->matchNames($message, $entities);
        $this->matchAddresses($message, $entities);
        $this->matchAmounts($message, $entities);
        $this->matchNumbers($message, $entities);
        $this->matchTemporalKeywords($message, $entities);

        if ($this->ducklingEndpoint !== null) {
            $this->hydrateFromDuckling($message, $entities);
        }

        return $entities;
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $entities
     */
    private function matchNames(string $message, array &$entities): void
    {
        if (preg_match_all('/\b(?:pak|ibu|bapak|bu|bang|mbak)\s+([A-Z][\p{L}]+)/iu', $message, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $entities['names'][] = [
                    'token' => $match[0],
                    'name' => Str::title($match[1]),
                ];
            }
        }

        if (preg_match_all('/\b([A-Z][\p{L}]+(?:\s+[A-Z][\p{L}]+){0,2})\b/u', $message, $capitalMatches, PREG_SET_ORDER)) {
            foreach ($capitalMatches as $match) {
                $token = trim($match[0]);
                if (Str::length($token) < 3) {
                    continue;
                }

                if (Str::contains($token, ['RT', 'RW', 'Jl', 'Jalan'])) {
                    continue;
                }

                $entities['names'][] = [
                    'token' => $token,
                    'name' => $token,
                ];
            }
        }
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $entities
     */
    private function matchAddresses(string $message, array &$entities): void
    {
        if (preg_match_all('/\bblok\s+[a-z0-9\-]+/iu', $message, $matches)) {
            foreach ($matches[0] as $match) {
                $entities['addresses'][] = ['token' => trim($match)];
            }
        }

        if (preg_match_all('/\brt\s*\d+\s*\/?\s*rw\s*\d+/iu', $message, $matches)) {
            foreach ($matches[0] as $match) {
                $entities['addresses'][] = ['token' => Str::upper(trim($match))];
            }
        }

        if (preg_match_all('/\bjalan\s+[a-z0-9\s]+/iu', $message, $streetMatches)) {
            foreach ($streetMatches[0] as $match) {
                $entities['addresses'][] = ['token' => Str::title(trim($match))];
            }
        }
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $entities
     */
    private function matchAmounts(string $message, array &$entities): void
    {
        if (preg_match_all('/(Rp\.?\s*)?([\d\.]{2,})/iu', $message, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $raw = str_replace('.', '', $match[2]);
                if (!is_numeric($raw)) {
                    continue;
                }

                $amount = (int) $raw;
                if ($amount <= 0) {
                    continue;
                }

                $entities['amounts'][] = [
                    'token' => $match[0],
                    'value' => $amount,
                ];
            }
        }
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $entities
     */
    private function matchNumbers(string $message, array &$entities): void
    {
        if (preg_match_all('/\b\d+\b/', $message, $matches)) {
            foreach ($matches[0] as $match) {
                $entities['numbers'][] = ['token' => $match, 'value' => (int) $match];
            }
        }
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $entities
     */
    private function matchTemporalKeywords(string $message, array &$entities): void
    {
        $lower = Str::lower($message);
        $now = Carbon::now();

        $mapping = [
            'hari ini' => $now->copy(),
            'today' => $now->copy(),
            'besok' => $now->copy()->addDay(),
            'tomorrow' => $now->copy()->addDay(),
            'lusa' => $now->copy()->addDays(2),
            'minggu depan' => $now->copy()->addWeek(),
            'next week' => $now->copy()->addWeek(),
        ];

        foreach ($mapping as $keyword => $carbon) {
            if (Str::contains($lower, $keyword)) {
                $entities['dates'][] = [
                    'token' => $keyword,
                    'value' => $carbon->toDateString(),
                ];
            }
        }
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $entities
     */
    private function hydrateFromDuckling(string $message, array &$entities): void
    {
        try {
            $response = Http::timeout($this->ducklingTimeout)
                ->post($this->ducklingEndpoint, [
                    'text' => $message,
                    'locale' => 'id_ID',
                    'reftime' => Carbon::now()->timestamp * 1000,
                ]);

            if (!$response->successful()) {
                return;
            }

            $items = $response->json();

            if (!is_array($items)) {
                return;
            }

            foreach ($items as $item) {
                if (!is_array($item) || !isset($item['dim'])) {
                    continue;
                }

                $dim = $item['dim'];
                $value = $item['value'] ?? [];
                $body = $item['body'] ?? null;

                switch ($dim) {
                    case 'time':
                        if (isset($value['value'])) {
                            $entities['dates'][] = [
                                'token' => $body,
                                'value' => $value['value'],
                                'grain' => $value['grain'] ?? null,
                            ];
                        }
                        break;
                    case 'duration':
                        $entities['period'][] = [
                            'token' => $body,
                            'value' => $value,
                        ];
                        break;
                    case 'amount-of-money':
                        if (isset($value['value'])) {
                            $entities['amounts'][] = [
                                'token' => $body,
                                'value' => (float) $value['value'],
                                'unit' => $value['unit'] ?? 'IDR',
                            ];
                        }
                        break;
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
