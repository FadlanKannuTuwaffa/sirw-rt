<?php

namespace Tests\Feature\Admin;

use App\Console\Commands\CleanupStalePayments;
use App\Livewire\Admin\Pembayaran\Index;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_failed_payment_via_livewire(): void
    {
        $failed = $this->makePayment(['status' => 'failed']);

        Livewire::test(Index::class)
            ->call('confirmDeletePayment', $failed->id)
            ->assertSet('deleteModalOpen', true)
            ->call('deletePayment');

        $this->assertDatabaseMissing('payments', ['id' => $failed->id]);
    }

    public function test_paid_payment_is_not_deleted(): void
    {
        $paid = $this->makePayment(['status' => 'paid']);

        Livewire::test(Index::class)
            ->call('confirmDeletePayment', $paid->id)
            ->assertSet('deleteModalOpen', false)
            ->call('deletePayment');

        $this->assertDatabaseHas('payments', ['id' => $paid->id]);
    }

    public function test_cleanup_command_removes_old_failed_payments(): void
    {
        $oldFailed = $this->makePayment(['status' => 'failed']);
        DB::table('payments')->where('id', $oldFailed->id)->update([
            'updated_at' => now()->subHours(60),
        ]);

        $recentFailed = $this->makePayment(['status' => 'failed']);
        DB::table('payments')->where('id', $recentFailed->id)->update([
            'updated_at' => now()->subHours(2),
        ]);

        $paid = $this->makePayment(['status' => 'paid']);

        $this->artisan('payments:cleanup-stale')
            ->expectsOutputToContain('1 transaksi gagal/batal dihapus')
            ->assertExitCode(CleanupStalePayments::SUCCESS);

        $this->assertDatabaseMissing('payments', ['id' => $oldFailed->id]);
        $this->assertDatabaseHas('payments', ['id' => $recentFailed->id]);
        $this->assertDatabaseHas('payments', ['id' => $paid->id]);
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
