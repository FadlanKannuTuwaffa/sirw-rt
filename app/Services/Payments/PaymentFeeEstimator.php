<?php

namespace App\Services\Payments;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentFeeEstimator
{
    private array $tripayChannels;
    private array $channelOverrides;

    public function __construct(private PaymentGatewayManager $manager)
    {
        $this->tripayChannels = collect(config('payment_channels.tripay', []))
            ->keyBy(fn ($channel) => $channel['code'] ?? null)
            ->filter(fn ($channel, $code) => !is_null($code))
            ->map(fn ($channel) => $channel)
            ->all();

        $overrides = $this->manager->config('tripay_channel_fees', []);
        $this->channelOverrides = is_array($overrides) ? $overrides : [];
    }

    public static function resolve(): self
    {
        return new self(PaymentGatewayManager::resolve());
    }

    /**
     * Estimate additional fee borne by the resident/customer.
     *
     * @return array{fee:int,total:int,details:array<string,mixed>}
     */
    public function estimate(int $amount): array
    {
        $amount = max($amount, 0);
        $provider = $this->manager->config('provider', 'manual');

        return match ($provider) {
            'tripay' => $this->estimateTripay($amount),
            default => $this->zeroFee($amount, 'manual'),
        };
    }

    private function estimateTripay(int $amount): array
    {
        $channel = trim((string) $this->manager->config('tripay_channel'));
        $apiKey = trim((string) $this->manager->config('tripay_api_key'));
        $mode = strtolower((string) $this->manager->config('tripay_mode', 'sandbox'));

        $meta = $this->tripayChannels[$channel] ?? null;

        $details = [
            'provider' => 'tripay',
            'source' => 'manual',
            'channel' => $channel,
        ];
        if ($meta) {
            $details['channel_name'] = $meta['name'] ?? null;
            $details['category'] = $meta['category'] ?? null;
        }

        if ($amount <= 0) {
            return $this->zeroFee($amount, 'tripay');
        }

        $override = $this->channelOverrides[$channel] ?? null;
        if ($override) {
            $details['override_source'] = 'settings';
        }

        if ($channel && $apiKey) {
            try {
                $baseUrl = $mode === 'production'
                    ? 'https://tripay.co.id/api/merchant/fee'
                    : 'https://tripay.co.id/api-sandbox/merchant/fee';

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                ])
                    ->timeout(10)
                    ->retry(1, 250)
                    ->get($baseUrl, [
                        'code' => $channel,
                        'amount' => $amount,
                    ])
                    ->throw()
                    ->json();

                $data = Arr::get($response, 'data');
                if (is_array($data)) {
                    $feeCustomerRaw = Arr::get($data, 'customer_fee', 0);
                    $totalAmountRaw = Arr::get($data, 'total_amount', null);

                    $fee = $this->normalizeAmount($feeCustomerRaw);
                    $estimatedTotal = $this->normalizeAmount($totalAmountRaw);
                    if ($estimatedTotal <= 0) {
                        $estimatedTotal = $amount + $fee;
                    }

                    $details['source'] = 'tripay_api';
                    $details['api_response'] = $data;

                    return [
                        'fee' => max($fee, 0),
                        'total' => max($estimatedTotal, $amount),
                        'details' => $details,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('Tripay fee estimation failed', [
                    'message' => $e->getMessage(),
                    'channel' => $channel,
                ]);
            }
        }

        return $this->applyManualFee($amount, 'tripay', $details, $meta, $override);
    }

    /**
     * Apply manual configuration using fee_percent & fee_flat.
     *
     * @param  'tripay'|'manual'  $prefix
     */
    private function applyManualFee(int $amount, string $prefix, array $details = [], ?array $defaults = null, ?array $override = null): array
    {
        $hasDefaultPercent = $defaults && array_key_exists('fee_percent', $defaults);
        $hasDefaultFlat = $defaults && array_key_exists('fee_flat', $defaults);
        $hasDefaultMin = $defaults && array_key_exists('min_fee', $defaults);

        $percent = $hasDefaultPercent ? max((float) ($defaults['fee_percent'] ?? 0), 0) : null;
        $flat = $hasDefaultFlat ? $this->normalizeAmount($defaults['fee_flat'] ?? 0) : null;
        $minFee = $hasDefaultMin ? $this->normalizeAmount($defaults['min_fee'] ?? 0) : null;

        if (is_array($override)) {
            if (array_key_exists('fee_percent', $override) && $override['fee_percent'] !== null) {
                $percent = max((float) $override['fee_percent'], 0);
            }
            if (array_key_exists('fee_flat', $override) && $override['fee_flat'] !== null) {
                $flat = $this->normalizeAmount($override['fee_flat']);
            }
            if (array_key_exists('min_fee', $override) && $override['min_fee'] !== null) {
                $minFee = $this->normalizeAmount($override['min_fee']);
            }
        }

        if ($percent === null) {
            $configuredPercent = $this->manager->config("{$prefix}_fee_percent", 0);
            $percent = max((float) ($configuredPercent ?? 0), 0);
        }

        if ($flat === null) {
            $configuredFlat = $this->manager->config("{$prefix}_fee_flat", 0);
            $flat = $this->normalizeAmount($configuredFlat);
        }

        if ($minFee === null) {
            $configuredMin = $this->manager->config("{$prefix}_min_fee", 0);
            $minFee = $this->normalizeAmount($configuredMin);
        }

        $percent = max($percent, 0);
        $flat = max($flat, 0);
        $minFee = max($minFee, 0);

        $feeFromPercent = (int) round($amount * ($percent / 100), 0, PHP_ROUND_HALF_UP);
        $fee = $feeFromPercent + max($flat, 0);
        if ($minFee > 0) {
            $fee = max($fee, $minFee);
        }

        $details = array_merge($details, [
            'source' => 'configured',
            'percent' => $percent,
            'flat' => max($flat, 0),
            'min' => $minFee,
            'override' => $override,
        ]);

        if ($defaults) {
            $details['defaults'] = $defaults;
        }

        if ($fee <= 0) {
            return $this->zeroFee($amount, $prefix, $details);
        }

        return [
            'fee' => $fee,
            'total' => $amount + $fee,
            'details' => $details,
        ];
    }

    private function zeroFee(int $amount, string $provider, array $details = []): array
    {
        $details = array_merge([
            'provider' => $provider,
            'source' => 'none',
        ], $details);

        return [
            'fee' => 0,
            'total' => $amount,
            'details' => $details,
        ];
    }

    private function normalizeAmount(mixed $value): int
    {
        if (is_null($value)) {
            return 0;
        }

        if (is_string($value)) {
            $value = str_replace([',', ' '], '', $value);
        }

        return (int) round((float) $value, 0, PHP_ROUND_HALF_UP);
    }
}
