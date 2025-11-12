<?php

namespace Tests\Feature;

use App\Livewire\Admin\Pengaturan\PaymentGateway;
use App\Models\SiteSetting;
use App\Services\Payments\PaymentFeeEstimator;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentGatewaySettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_manual_settings(): void
    {
        Livewire::test(PaymentGateway::class)
            ->set('provider', 'manual')
            ->set('manual_bank_accounts', [[
                'id' => 'bank-1',
                'bank_name' => 'BCA',
                'account_number' => '1234567890',
                'account_name' => 'RT 03',
            ]])
            ->set('manual_instructions', 'Transfer sesuai nominal tagihan.')
            ->call('save')
            ->assertHasNoErrors();

        $setting = SiteSetting::where('group', 'payment')
            ->where('key', 'manual_bank_accounts')
            ->first();

        $this->assertNotNull($setting);

        $accounts = json_decode($setting->value, true);
        $this->assertIsArray($accounts);
        $this->assertSame('BCA', $accounts[0]['bank_name'] ?? null);

        $this->assertSame('manual', config('services.payment.provider'));
        $manualDestinations = config('services.payment.manual_destinations');
        $this->assertIsArray($manualDestinations);
        $this->assertNotEmpty($manualDestinations);
        $this->assertSame('bank', $manualDestinations[0]['type'] ?? null);
    }

    public function test_selecting_tripay_channel_prefills_fee_fields(): void
    {
        Livewire::test(PaymentGateway::class)
            ->set('provider', 'tripay')
            ->call('prefillTripayFees', 'OVO')
            ->assertSet('tripay_fee_percent', '3')
            ->assertSet('tripay_fee_flat', '0')
            ->assertSet('tripay_min_fee', '1000');
    }

    public function test_tripay_estimator_respects_channel_defaults_when_global_is_lower(): void
    {
        SiteSetting::updateOrCreate(['key' => 'payment_provider'], ['group' => 'payment', 'value' => 'tripay']);
        SiteSetting::updateOrCreate(['key' => 'tripay_fee_flat'], ['group' => 'payment', 'value' => '4250']);
        SiteSetting::updateOrCreate(['key' => 'tripay_fee_percent'], ['group' => 'payment', 'value' => '0']);
        SiteSetting::updateOrCreate(['key' => 'tripay_min_fee'], ['group' => 'payment', 'value' => '0']);
        SiteSetting::updateOrCreate(['key' => 'tripay_channel'], ['group' => 'payment', 'value' => 'BNIVA']);
        SiteSetting::updateOrCreate(['key' => 'tripay_channels'], ['group' => 'payment', 'value' => json_encode(['BRIVA', 'BCAVA'])]);

        $manager = PaymentGatewayManager::resolve()->with([
            'provider' => 'tripay',
            'tripay_channel' => 'BCAVA',
        ]);

        $estimate = (new PaymentFeeEstimator($manager))->estimate(25_000);

        $this->assertSame(5_500, $estimate['fee']);
        $this->assertSame(30_500, $estimate['total']);
    }
}
