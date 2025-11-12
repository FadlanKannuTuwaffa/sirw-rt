<?php

namespace App\Livewire\Admin\Pengaturan;

use App\Models\SiteSetting;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class PaymentGateway extends Component
{
    protected array $layoutData = [
        'title' => 'Pengaturan',
        'titleClass' => 'text-white',
    ];

    public string $provider = 'manual';

    // Tripay
    public ?string $tripay_api_key = null;
    public ?string $tripay_private_key = null;
    public ?string $tripay_merchant_code = null;
    public ?string $tripay_channel = null;
    public array $tripay_channels_selected = [];
    public array $tripay_channel_fees = [];
    public string $tripay_mode = 'sandbox';

    // Fee configuration
    public string $tripay_fee_percent = '0';
    public string $tripay_fee_flat = '0';
    public string $tripay_min_fee = '0';

    public ?string $callback_url = null;

    public array $tripayChannels = [];
    public array $manual_bank_accounts = [];
    public array $manual_wallet_accounts = [];
    public string $manual_instructions = '';

    public function mount(): void
    {
        $settings = SiteSetting::keyValue('payment');

        $this->provider = $settings->get('payment_provider', 'manual');
        $this->tripay_api_key = $settings->get('tripay_api_key');
        $this->tripay_private_key = $settings->get('tripay_private_key');
        $this->tripay_merchant_code = $settings->get('tripay_merchant_code');
        $this->tripay_channel = PaymentGatewayManager::normalizeTripayChannel($settings->get('tripay_channel'));
        $this->tripay_channels_selected = PaymentGatewayManager::normalizeTripayChannels($settings->get('tripay_channels'));
        $this->tripay_channel_fees = PaymentGatewayManager::normalizeTripayChannelFees($settings->get('tripay_channel_fees'));
        $this->tripay_mode = $settings->get('tripay_mode', 'sandbox');
        $this->tripay_fee_percent = (string) $settings->get('tripay_fee_percent', config('services.tripay.fee_percent', 0));
        $this->tripay_fee_flat = (string) $settings->get('tripay_fee_flat', config('services.tripay.fee_flat', 0));
        $this->tripay_min_fee = (string) $settings->get('tripay_min_fee', config('services.tripay.min_fee', 0));
        $this->callback_url = $settings->get('payment_callback_url')
            ?? config('services.payment.callback_url')
            ?? config('services.tripay.callback_url')
            ?? PaymentGatewayManager::defaultWebhookUrl();

        $this->manual_bank_accounts = $this->hydrateManualItems($settings->get('manual_bank_accounts'), 'bank');
        $this->manual_wallet_accounts = $this->hydrateManualItems($settings->get('manual_wallet_accounts'), 'wallet');
        $this->manual_instructions = (string) ($settings->get('manual_instructions') ?? '');

        $this->tripayChannels = $this->groupTripayChannels();

        if ($this->tripay_channel && ($this->shouldApplyTripayDefaults() || isset($this->tripay_channel_fees[$this->tripay_channel]))) {
            $this->prefillTripayFees($this->tripay_channel);
        }
    }

    public function render()
    {
        return view('livewire.admin.pengaturan.payment-gateway', [
            'tripayChannels' => $this->tripayChannels,
        ]);
    }

    public function addBankAccount(): void
    {
        $this->manual_bank_accounts[] = [
            'id' => (string) Str::uuid(),
            'bank_name' => '',
            'account_number' => '',
            'account_name' => '',
            'notes' => '',
        ];
    }

    public function removeBankAccount(int $index): void
    {
        unset($this->manual_bank_accounts[$index]);
        $this->manual_bank_accounts = $this->reindex($this->manual_bank_accounts);
    }

    public function addWalletAccount(): void
    {
        $this->manual_wallet_accounts[] = [
            'id' => (string) Str::uuid(),
            'provider' => '',
            'account_number' => '',
            'account_name' => '',
            'notes' => '',
        ];
    }

    public function removeWalletAccount(int $index): void
    {
        unset($this->manual_wallet_accounts[$index]);
        $this->manual_wallet_accounts = $this->reindex($this->manual_wallet_accounts);
    }

    public function updatedProvider(string $value): void
    {
        $this->resetErrorBag();
    }

    public function updatedTripayChannel($value): void
    {
        $this->prefillTripayFees($value);
    }

    public function updatedTripayChannelsSelected(): void
    {
        $normalized = PaymentGatewayManager::normalizeTripayChannels($this->tripay_channels_selected ?? []);
        $this->tripay_channels_selected = array_values(array_unique(array_filter($normalized)));

        if ($this->tripay_channel && ! in_array($this->tripay_channel, $this->tripay_channels_selected, true)) {
            $this->tripay_channel = $this->tripay_channels_selected[0] ?? null;
            if ($this->tripay_channel) {
                $this->prefillTripayFees($this->tripay_channel);
            }
        }
    }

    public function selectAllTripayChannels(): void
    {
        $this->tripay_channels_selected = $this->availableTripayCodes();
        $this->updatedTripayChannelsSelected();

        if (empty($this->tripay_channels_selected)) {
            $this->tripay_channel = null;
            return;
        }

        if (! $this->tripay_channel) {
            $this->tripay_channel = $this->tripay_channels_selected[0];
        }

        $this->prefillTripayFees($this->tripay_channel);
    }

    public function save(): void
    {
        $tripayCodes = $this->availableTripayCodes();

        $rules = [
            'provider' => ['required', Rule::in(['manual', 'tripay'])],
            'tripay_api_key' => ['nullable', 'string', 'max:200'],
            'tripay_private_key' => ['nullable', 'string', 'max:200'],
            'tripay_merchant_code' => ['nullable', 'string', 'max:100'],
            'tripay_channel' => ['nullable', 'string', 'max:50', Rule::in($tripayCodes)],
            'tripay_channels_selected' => ['array'],
            'tripay_channels_selected.*' => ['nullable', 'string', 'max:50', Rule::in($tripayCodes)],
            'tripay_mode' => ['required', Rule::in(['sandbox', 'production'])],
            'tripay_fee_percent' => ['nullable', 'numeric', 'min:0'],
            'tripay_fee_flat' => ['nullable', 'numeric', 'min:0'],
            'tripay_min_fee' => ['nullable', 'numeric', 'min:0'],
            'callback_url' => ['nullable', 'url'],
            'manual_instructions' => ['nullable', 'string', 'max:5000'],
            'manual_bank_accounts' => ['array'],
            'manual_bank_accounts.*.id' => ['nullable', 'string'],
            'manual_bank_accounts.*.bank_name' => ['nullable', 'string', 'max:120'],
            'manual_bank_accounts.*.account_number' => ['nullable', 'string', 'max:60'],
            'manual_bank_accounts.*.account_name' => ['nullable', 'string', 'max:120'],
            'manual_bank_accounts.*.notes' => ['nullable', 'string', 'max:255'],
            'manual_wallet_accounts' => ['array'],
            'manual_wallet_accounts.*.id' => ['nullable', 'string'],
            'manual_wallet_accounts.*.provider' => ['nullable', 'string', 'max:120'],
            'manual_wallet_accounts.*.account_number' => ['nullable', 'string', 'max:60'],
            'manual_wallet_accounts.*.account_name' => ['nullable', 'string', 'max:120'],
            'manual_wallet_accounts.*.notes' => ['nullable', 'string', 'max:255'],
        ];

        $validated = $this->validate($rules);

        $manualBanks = $this->sanitizeBankAccounts($this->manual_bank_accounts);
        $manualWallets = $this->sanitizeWalletAccounts($this->manual_wallet_accounts);

        if ($this->provider === 'manual' && empty($manualBanks) && empty($manualWallets)) {
            $this->addError('manual_bank_accounts', 'Tambahkan minimal satu rekening bank atau e-wallet untuk pembayaran manual.');
            return;
        }

        if ($validated['provider'] === 'tripay') {
            $callbackUrl = $validated['callback_url'] ?? null;
            if ($callbackUrl && ! Str::startsWith($callbackUrl, 'https://')) {
                $this->addError('callback_url', 'URL callback Tripay harus menggunakan HTTPS.');
                return;
            }
            if (! $callbackUrl && app()->isProduction()) {
                $generatedUrl = PaymentGatewayManager::defaultWebhookUrl();
                if (! $generatedUrl || ! Str::startsWith($generatedUrl, 'https://')) {
                    $this->addError('callback_url', 'APP_URL harus menggunakan HTTPS agar webhook Tripay berjalan di mode produksi.');
                    return;
                }
            }
        }

        $selectedChannels = PaymentGatewayManager::normalizeTripayChannels($validated['tripay_channels_selected'] ?? []);
        $defaultTripayChannel = PaymentGatewayManager::normalizeTripayChannel($validated['tripay_channel'] ?? null);

        if ($defaultTripayChannel && ! in_array($defaultTripayChannel, $selectedChannels, true)) {
            $selectedChannels[] = $defaultTripayChannel;
        }

        $this->persist('payment_provider', $validated['provider']);
        $this->persist('tripay_api_key', $validated['tripay_api_key'] ?? null);
        $this->persist('tripay_private_key', $validated['tripay_private_key'] ?? null);
        $this->persist('tripay_merchant_code', $validated['tripay_merchant_code'] ?? null);
        $this->persist('tripay_channel', $validated['tripay_channel'] ?? null);
        $this->persist('tripay_channels', $selectedChannels);
        $this->persist('tripay_mode', $validated['tripay_mode'] ?? 'sandbox');
        $this->persist('tripay_fee_percent', $validated['tripay_fee_percent'] ?? null);
        $this->persist('tripay_fee_flat', $validated['tripay_fee_flat'] ?? null);
        $this->persist('tripay_min_fee', $validated['tripay_min_fee'] ?? null);

        $channelFees = $this->syncTripayChannelFees($selectedChannels, $defaultTripayChannel, $validated);
        $this->tripay_channel_fees = $channelFees;
        $this->persist('tripay_channel_fees', $channelFees);

        $this->persist('payment_callback_url', $validated['callback_url'] ?? null);
        $this->persist('manual_bank_accounts', $manualBanks);
        $this->persist('manual_wallet_accounts', $manualWallets);
        $this->persist('manual_instructions', $this->manual_instructions ?: null);

        $this->applyRuntimeConfig(array_merge($validated, [
            'tripay_channels_selected' => $selectedChannels,
            'tripay_channel_fees' => $channelFees,
            'manual_bank_accounts' => $manualBanks,
            'manual_wallet_accounts' => $manualWallets,
        ]));

        session()->flash('status', 'Pengaturan payment gateway berhasil diperbarui.');
    }

    private function persist(string $key, mixed $value): void
    {
        if (is_array($value)) {
            $value = empty($value)
                ? null
                : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                $value = null;
            }
        }

        SiteSetting::updateOrCreate(
            ['key' => $key],
            ['group' => 'payment', 'value' => $value]
        );
    }

    private function syncTripayChannelFees(array $selectedChannels, ?string $defaultChannel, array $validated): array
    {
        $fees = PaymentGatewayManager::normalizeTripayChannelFees($this->tripay_channel_fees);

        if (! empty($fees)) {
            $fees = collect($fees)
                ->filter(fn ($data, $code) => in_array($code, $selectedChannels, true))
                ->map(fn ($data) => [
                    'fee_percent' => (float) ($data['fee_percent'] ?? 0),
                    'fee_flat' => (int) round((float) ($data['fee_flat'] ?? 0)),
                    'min_fee' => (int) round((float) ($data['min_fee'] ?? 0)),
                ])
                ->all();
        }

        if ($defaultChannel) {
            $normalizedChannel = PaymentGatewayManager::normalizeTripayChannel($defaultChannel);

            $entry = [
                'fee_percent' => isset($validated['tripay_fee_percent']) ? (float) $validated['tripay_fee_percent'] : 0.0,
                'fee_flat' => (int) round((float) ($validated['tripay_fee_flat'] ?? 0)),
                'min_fee' => (int) round((float) ($validated['tripay_min_fee'] ?? 0)),
            ];

            if ($entry['fee_percent'] === 0.0 && $entry['fee_flat'] === 0 && $entry['min_fee'] === 0) {
                unset($fees[$normalizedChannel]);
            } else {
                $fees[$normalizedChannel] = $entry;
            }
        }

        return $fees;
    }

    private function applyRuntimeConfig(array $validated): void
    {
        Config::set('services.payment.provider', $validated['provider']);
        Config::set('services.tripay.api_key', $validated['tripay_api_key'] ?? null);
        Config::set('services.tripay.private_key', $validated['tripay_private_key'] ?? null);
        Config::set('services.tripay.merchant_code', $validated['tripay_merchant_code'] ?? null);
        Config::set('services.tripay.channel', PaymentGatewayManager::normalizeTripayChannel($validated['tripay_channel'] ?? null));
        Config::set('services.tripay.channels', PaymentGatewayManager::normalizeTripayChannels($validated['tripay_channels_selected'] ?? []));
        Config::set('services.tripay.mode', $validated['tripay_mode'] ?? 'sandbox');
        Config::set('services.tripay.callback_url', $validated['callback_url'] ?? null);
        Config::set('services.tripay.fee_percent', (float) ($validated['tripay_fee_percent'] ?? 0));
        Config::set('services.tripay.fee_flat', (float) ($validated['tripay_fee_flat'] ?? 0));
        Config::set('services.tripay.min_fee', (float) ($validated['tripay_min_fee'] ?? 0));
        Config::set('services.tripay.channel_fees', $validated['tripay_channel_fees'] ?? []);
        Config::set('services.payment.manual_destinations', $this->buildManualDestinations(
            $validated['manual_bank_accounts'] ?? [],
            $validated['manual_wallet_accounts'] ?? []
        ));
        Config::set('services.payment.manual_instructions', $this->manual_instructions ?: null);
    }

    public function prefillTripayFees(?string $code): void
    {
        if (! $code) {
            return;
        }

        $meta = $this->findTripayChannel($code);
        if (! $meta) {
            return;
        }

        $defaults = [
            'fee_percent' => (float) ($meta['fee_percent'] ?? 0),
            'fee_flat' => (int) round((float) ($meta['fee_flat'] ?? 0)),
            'min_fee' => (int) round((float) ($meta['min_fee'] ?? 0)),
        ];

        $override = $this->tripay_channel_fees[$code] ?? null;

        $percent = $override['fee_percent'] ?? $defaults['fee_percent'];
        $flat = $override['fee_flat'] ?? $defaults['fee_flat'];
        $min = $override['min_fee'] ?? $defaults['min_fee'];

        $this->tripay_fee_percent = (string) $percent;
        $this->tripay_fee_flat = (string) $flat;
        $this->tripay_min_fee = (string) $min;
    }

    private function hydrateManualItems(mixed $value, string $type): array
    {
        $items = $this->decodeSetting($value);

        return collect($items)
            ->map(function (array $item) use ($type) {
                if ($type === 'bank') {
                    return [
                        'id' => $item['id'] ?? (string) Str::uuid(),
                        'bank_name' => $item['bank_name'] ?? $item['bank'] ?? '',
                        'account_number' => $item['account_number'] ?? $item['number'] ?? '',
                        'account_name' => $item['account_name'] ?? $item['holder_name'] ?? '',
                        'notes' => $item['notes'] ?? '',
                    ];
                }

                return [
                    'id' => $item['id'] ?? (string) Str::uuid(),
                    'provider' => $item['provider'] ?? $item['label'] ?? '',
                    'account_number' => $item['account_number'] ?? $item['number'] ?? '',
                    'account_name' => $item['account_name'] ?? $item['holder_name'] ?? '',
                    'notes' => $item['notes'] ?? '',
                ];
            })
            ->values()
            ->all();
    }

    private function decodeSetting(mixed $value): array
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
        }

        return [];
    }

    private function groupTripayChannels(): array
    {
        return collect(config('payment_channels.tripay', []))
            ->groupBy(fn ($channel) => $channel['category'] ?? 'Lainnya')
            ->map(fn ($group) => collect($group)->sortBy('name')->values()->all())
            ->toArray();
    }

    private function findTripayChannel(string $code): ?array
    {
        foreach ($this->tripayChannels as $channels) {
            foreach ($channels as $channel) {
                if (($channel['code'] ?? null) === $code) {
                    return $channel;
                }
            }
        }

        return null;
    }

    private function applyTripayDefaults(array $meta): void
    {
        $this->tripay_fee_percent = (string) ($meta['fee_percent'] ?? 0);
        $this->tripay_fee_flat = (string) ($meta['fee_flat'] ?? 0);
        $this->tripay_min_fee = (string) ($meta['min_fee'] ?? 0);
    }

    private function shouldApplyTripayDefaults(): bool
    {
        $percentEmpty = $this->isEmptyNumericField($this->tripay_fee_percent);
        $flatEmpty = $this->isEmptyNumericField($this->tripay_fee_flat);
        $minEmpty = $this->isEmptyNumericField($this->tripay_min_fee);

        return $percentEmpty && $flatEmpty && $minEmpty;
    }

    private function isEmptyNumericField(?string $value): bool
    {
        if (is_null($value)) {
            return true;
        }

        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return true;
        }

        return (float) $trimmed === 0.0;
    }

    private function sanitizeBankAccounts(array $items): array
    {
        return collect($items)
            ->map(function ($item) {
                $item = is_array($item) ? $item : [];

                return [
                    'id' => $item['id'] ?? (string) Str::uuid(),
                    'bank_name' => trim((string) ($item['bank_name'] ?? '')),
                    'account_number' => trim((string) ($item['account_number'] ?? '')),
                    'account_name' => trim((string) ($item['account_name'] ?? '')),
                    'notes' => trim((string) ($item['notes'] ?? '')),
                ];
            })
            ->filter(fn ($item) => $item['account_number'] !== '' && $item['account_name'] !== '')
            ->values()
            ->all();
    }

    private function sanitizeWalletAccounts(array $items): array
    {
        return collect($items)
            ->map(function ($item) {
                $item = is_array($item) ? $item : [];

                return [
                    'id' => $item['id'] ?? (string) Str::uuid(),
                    'provider' => trim((string) ($item['provider'] ?? '')),
                    'account_number' => trim((string) ($item['account_number'] ?? '')),
                    'account_name' => trim((string) ($item['account_name'] ?? '')),
                    'notes' => trim((string) ($item['notes'] ?? '')),
                ];
            })
            ->filter(fn ($item) => $item['account_number'] !== '' && $item['account_name'] !== '')
            ->values()
            ->all();
    }

    private function buildManualDestinations(array $banks, array $wallets): array
    {
        $bankDestinations = collect($banks)->map(fn ($item) => array_filter([
            'id' => $item['id'] ?? (string) Str::uuid(),
            'type' => 'bank',
            'label' => $item['bank_name'] ?? null,
            'bank_name' => $item['bank_name'] ?? null,
            'account_name' => $item['account_name'] ?? null,
            'account_number' => $item['account_number'] ?? null,
            'notes' => $item['notes'] ?? null,
        ], fn ($value) => ! is_null($value) && $value !== ''));

        $walletDestinations = collect($wallets)->map(fn ($item) => array_filter([
            'id' => $item['id'] ?? (string) Str::uuid(),
            'type' => 'wallet',
            'label' => $item['provider'] ?? null,
            'provider' => $item['provider'] ?? null,
            'account_name' => $item['account_name'] ?? null,
            'account_number' => $item['account_number'] ?? null,
            'notes' => $item['notes'] ?? null,
        ], fn ($value) => ! is_null($value) && $value !== ''));

        return $bankDestinations
            ->merge($walletDestinations)
            ->values()
            ->all();
    }

    private function availableTripayCodes(): array
    {
        return collect($this->tripayChannels)
            ->flatMap(fn ($channels) => $channels)
            ->pluck('code')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function reindex(array $items): array
    {
        return array_values($items);
    }

    private function manualDestinationCount(): int
    {
        return count($this->manual_bank_accounts) + count($this->manual_wallet_accounts);
    }

}
