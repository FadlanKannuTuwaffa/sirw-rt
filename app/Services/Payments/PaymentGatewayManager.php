<?php

namespace App\Services\Payments;

use App\Models\SiteSetting;
use App\Services\Payments\Contracts\PaymentGatewayContract;
use App\Services\Payments\Gateways\ManualGateway;
use App\Services\Payments\Gateways\TripayGateway;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Throwable;

class PaymentGatewayManager
{
    public function __construct(private array $config)
    {
    }

    public static function resolve(): self
    {
        $settings = SiteSetting::keyValue('payment');

        $callbackUrl = $settings->get('payment_callback_url')
            ?? config('services.payment.callback_url')
            ?? config('services.tripay.callback_url')
            ?? static::defaultWebhookUrl();

        if ($callbackUrl && app()->isProduction() && ! Str::startsWith($callbackUrl, 'https://')) {
            $callbackUrl = preg_replace('/^http:/i', 'https:', $callbackUrl);
        }

        $tripayMode = strtolower((string) $settings->get('tripay_mode', config('services.tripay.mode', 'sandbox')));
        if (!in_array($tripayMode, ['sandbox', 'production'], true)) {
            $tripayMode = 'sandbox';
        }

        $tripayChannels = static::normalizeTripayChannels($settings->get('tripay_channels') ?? config('services.tripay.channels'));
        $defaultTripayChannel = $settings->get('tripay_channel', config('services.tripay.channel'));
        $defaultTripayChannel = static::normalizeTripayChannel($defaultTripayChannel);
        if ($defaultTripayChannel && !in_array($defaultTripayChannel, $tripayChannels, true)) {
            $tripayChannels[] = $defaultTripayChannel;
        }

        $config = [
            'provider' => $settings->get('payment_provider', config('services.payment.provider', 'manual')),
            'tripay_api_key' => $settings->get('tripay_api_key', config('services.tripay.api_key')),
            'tripay_private_key' => $settings->get('tripay_private_key', config('services.tripay.private_key')),
            'tripay_merchant_code' => $settings->get('tripay_merchant_code', config('services.tripay.merchant_code')),
            'tripay_channel' => $defaultTripayChannel,
            'tripay_channels' => $tripayChannels,
            'tripay_mode' => $tripayMode,
            'tripay_fee_percent' => (float) $settings->get('tripay_fee_percent', config('services.tripay.fee_percent', 0)),
            'tripay_fee_flat' => (float) $settings->get('tripay_fee_flat', config('services.tripay.fee_flat', 0)),
            'tripay_min_fee' => (float) $settings->get('tripay_min_fee', config('services.tripay.min_fee', 0)),
            'tripay_channel_fees' => static::normalizeTripayChannelFees($settings->get('tripay_channel_fees') ?? config('services.tripay.channel_fees', [])),
            'payment_callback_url' => $callbackUrl,
            'manual_destinations' => static::mergeManualDestinations($settings),
            'manual_instructions' => trim((string) $settings->get('manual_instructions', '')),
        ];

        return new self($config);
    }

    public function driver(?string $provider = null): PaymentGatewayContract
    {
        $driver = $provider ?? Arr::get($this->config, 'provider', 'manual');

        return match ($driver) {
            'tripay' => new TripayGateway($this->config),
            default => new ManualGateway($this->config),
        };
    }

    public function config(?string $key = null, mixed $default = null): mixed
    {
        return is_null($key) ? $this->config : Arr::get($this->config, $key, $default);
    }

    public function with(array $overrides): self
    {
        return new self(array_merge($this->config, $overrides));
    }

    public static function normalizeTripayChannel(?string $channel): ?string
    {
        if (! $channel) {
            return null;
        }

        return match (strtoupper($channel)) {
            'QRIS_GENERAL' => 'QRIS2',
            'QRIS_CUSTOM' => 'QRISC',
            'QRIS_SHOPEE' => 'QRIS',
            default => $channel,
        };
    }

    public static function normalizeTripayChannels(mixed $channels): array
    {
        $normalized = collect(static::decodeJsonList($channels))
            ->filter()
            ->map(fn ($channel) => static::normalizeTripayChannel($channel))
            ->filter()
            ->values();

        return $normalized->unique()->values()->all();
    }

    public static function normalizeTripayChannelFees(mixed $value): array
    {
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            }
        }

        if ($value instanceof Collection) {
            $value = $value->toArray();
        }

        if (! is_array($value)) {
            return [];
        }

        $normalized = [];

        foreach ($value as $code => $data) {
            if (is_array($data) && array_key_exists('code', $data) && !is_string($code)) {
                $code = $data['code'];
            }

            $channel = static::normalizeTripayChannel(is_string($code) ? $code : null);

            if (! $channel) {
                continue;
            }

            $normalized[$channel] = [
                'fee_percent' => isset($data['fee_percent']) ? (float) $data['fee_percent'] : 0.0,
                'fee_flat' => static::normalizeMoneyValue($data['fee_flat'] ?? 0),
                'min_fee' => static::normalizeMoneyValue($data['min_fee'] ?? 0),
            ];
        }

        return $normalized;
    }

    public function manualDestinations(): array
    {
        return Arr::get($this->config, 'manual_destinations', []);
    }

    private static function mergeManualDestinations(Collection $settings): array
    {
        $banks = collect(static::decodeManualItems($settings->get('manual_bank_accounts')))
            ->map(function (array $item) {
                $id = $item['id'] ?? (string) Str::uuid();

                return [
                    'id' => $id,
                    'type' => 'bank',
                    'label' => $item['bank_name'] ?? $item['bank'] ?? 'Bank',
                    'bank_name' => $item['bank_name'] ?? $item['bank'] ?? null,
                    'account_name' => $item['account_name'] ?? $item['holder_name'] ?? null,
                    'account_number' => $item['account_number'] ?? null,
                    'notes' => $item['notes'] ?? null,
                ];
            });

        $wallets = collect(static::decodeManualItems($settings->get('manual_wallet_accounts')))
            ->map(function (array $item) {
                $id = $item['id'] ?? (string) Str::uuid();

                return [
                    'id' => $id,
                    'type' => 'wallet',
                    'label' => $item['provider'] ?? $item['label'] ?? 'E-Wallet',
                    'provider' => $item['provider'] ?? null,
                    'account_name' => $item['account_name'] ?? $item['holder_name'] ?? null,
                    'account_number' => $item['account_number'] ?? $item['number'] ?? null,
                    'notes' => $item['notes'] ?? null,
                ];
            });

        return $banks
            ->merge($wallets)
            ->map(fn ($item) => array_filter($item, fn ($value) => !is_null($value) && $value !== ''))
            ->values()
            ->all();
    }

    private static function decodeManualItems(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    private static function normalizeMoneyValue(mixed $value): int
    {
        if (is_null($value) || $value === '') {
            return 0;
        }

        if (is_string($value)) {
            $value = str_replace([',', ' '], '', $value);
        }

        return (int) round((float) $value, 0, PHP_ROUND_HALF_UP);
    }

    private static function decodeJsonList(mixed $value): array
    {
        if (is_null($value) || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }

            return preg_split('/[\s,;]+/', $value, flags: PREG_SPLIT_NO_EMPTY) ?: [];
        }

        return [];
    }

    public static function defaultWebhookUrl(): ?string
    {
        if (! app()->bound('router')) {
            return null;
        }

        try {
            if (Route::has('payments.webhook')) {
                return route('payments.webhook', absolute: true);
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }
}
