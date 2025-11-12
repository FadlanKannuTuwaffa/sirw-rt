<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Pembayaran\Index;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_payments_by_search_term(): void
    {
        $alpha = $this->makePayment([
            'status' => 'paid',
            'gateway' => 'tripay',
        ], [], User::factory()->create(['name' => 'Alpha Resident']));

        $beta = $this->makePayment([
            'status' => 'paid',
            'gateway' => 'manual',
            'manual_channel' => 'BCA',
        ], [], User::factory()->create(['name' => 'Beta Resident']));

        Livewire::test(Index::class)
            ->set('status', 'all')
            ->set('search', 'Alpha')
            ->assertSee($alpha->user->name)
            ->assertDontSee($beta->user->name)
            ->set('search', $beta->bill->invoice_number)
            ->assertSee($beta->bill->invoice_number)
            ->assertDontSee($alpha->bill->invoice_number);
    }

    public function test_it_filters_by_payment_status(): void
    {
        $paid = $this->makePayment([
            'status' => 'paid',
            'gateway' => 'tripay',
        ], ['title' => 'Paid Bill'], User::factory()->create(['name' => 'Paid User']));

        $pending = $this->makePayment([
            'status' => 'pending',
            'gateway' => 'manual',
            'paid_at' => null,
            'manual_channel' => 'BRI',
        ], ['title' => 'Pending Bill'], User::factory()->create(['name' => 'Pending User']));

        Livewire::test(Index::class)
            ->set('status', 'pending')
            ->assertSee($pending->user->name)
            ->assertDontSee($paid->user->name);
    }

    public function test_it_filters_by_gateway_and_manual_channel_type(): void
    {
        $tripay = $this->makePayment([
            'status' => 'paid',
            'gateway' => 'tripay',
        ], ['title' => 'Tripay Bill'], User::factory()->create(['name' => 'Tripay User']));

        $manualBank = $this->makePayment([
            'status' => 'paid',
            'gateway' => 'manual',
            'manual_channel' => 'BCA',
            'manual_destination' => ['type' => 'bank', 'label' => 'BCA'],
        ], ['title' => 'Bank Bill'], User::factory()->create(['name' => 'Bank User']));

        $manualWallet = $this->makePayment([
            'status' => 'paid',
            'gateway' => 'manual',
            'manual_channel' => 'OVO',
            'manual_destination' => ['type' => 'wallet', 'label' => 'OVO'],
        ], ['title' => 'Wallet Bill'], User::factory()->create(['name' => 'Wallet User']));

        Livewire::test(Index::class)
            ->set('status', 'all')
            ->set('gateway', 'manual_bank')
            ->assertSee($manualBank->user->name)
            ->assertDontSee($manualWallet->user->name)
            ->assertDontSee($tripay->user->name)
            ->set('gateway', 'manual_virtual')
            ->assertSee($manualWallet->user->name)
            ->assertDontSee($manualBank->user->name)
            ->set('gateway', 'tripay')
            ->assertSee($tripay->user->name)
            ->assertDontSee($manualBank->user->name);
    }

    public function test_failed_filter_includes_cancelled_and_expired(): void
    {
        $failed = $this->makePayment(['status' => 'failed'], ['title' => 'Failed Bill'], User::factory()->create(['name' => 'Failed User']));
        $cancelled = $this->makePayment(['status' => 'cancelled'], ['title' => 'Cancelled Bill'], User::factory()->create(['name' => 'Cancelled User']));
        $expired = $this->makePayment(['status' => 'expired'], ['title' => 'Expired Bill'], User::factory()->create(['name' => 'Expired User']));

        $paid = $this->makePayment(['status' => 'paid'], ['title' => 'Paid Bill'], User::factory()->create(['name' => 'Other User']));

        Livewire::test(Index::class)
            ->set('status', 'failed')
            ->assertSee($failed->user->name)
            ->assertSee($cancelled->user->name)
            ->assertSee($expired->user->name)
            ->assertDontSee($paid->user->name);
    }

    private function makePayment(array $paymentOverrides = [], array $billOverrides = [], ?User $user = null): Payment
    {
        $user ??= User::factory()->create();

        $status = $paymentOverrides['status'] ?? 'paid';
        $amount = $paymentOverrides['amount'] ?? 50000;

        $billDefaults = [
            'user_id' => $user->id,
            'type' => 'iuran',
            'title' => 'Tagihan ' . Str::upper(Str::random(4)),
            'description' => null,
            'amount' => $amount,
            'gateway_fee' => 0,
            'total_amount' => $amount,
            'due_date' => now()->addDays(7),
            'status' => $status === 'paid' ? 'paid' : 'unpaid',
            'invoice_number' => 'INV-' . Str::upper(Str::random(8)),
            'issued_at' => now(),
            'paid_at' => $status === 'paid' ? now() : null,
            'created_by' => null,
        ];

        $bill = Bill::create(array_merge($billDefaults, $billOverrides));

        $paymentDefaults = [
            'bill_id' => $bill->id,
            'user_id' => $user->id,
            'gateway' => 'manual',
            'status' => $status,
            'amount' => $amount,
            'fee_amount' => 0,
            'customer_total' => $amount,
            'paid_at' => $status === 'paid' ? now() : null,
            'manual_channel' => null,
            'manual_destination' => null,
            'reference' => 'PAY-' . Str::upper(Str::random(8)),
            'raw_payload' => null,
        ];

        return Payment::create(array_merge($paymentDefaults, $paymentOverrides));
    }
}
