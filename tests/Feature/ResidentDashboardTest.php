<?php

namespace Tests\Feature;

use App\Livewire\Resident\Dashboard;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResidentDashboardTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function resident_dashboard_renders_successfully_with_sample_data(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $bill = Bill::create([
            'user_id' => $user->id,
            'type' => 'iuran',
            'title' => 'Iuran Kebersihan',
            'description' => 'Iuran bulanan',
            'amount' => 50000,
            'due_date' => Carbon::now()->addDays(7),
            'status' => 'unpaid',
            'invoice_number' => 'INV-001',
            'issued_at' => Carbon::now()->subDays(3),
        ]);

        Payment::create([
            'bill_id' => $bill->id,
            'user_id' => $user->id,
            'gateway' => 'manual',
            'status' => 'paid',
            'amount' => 50000,
            'fee_amount' => 2500,
            'customer_total' => null,
            'paid_at' => Carbon::now()->subDay(),
            'reference' => 'PAY-001',
        ]);

        Payment::create([
            'bill_id' => $bill->id,
            'user_id' => $user->id,
            'gateway' => 'manual',
            'status' => 'paid',
            'amount' => 45000,
            'fee_amount' => 0,
            'customer_total' => 47000,
            'paid_at' => Carbon::now()->subMonths(2),
            'reference' => 'PAY-002',
        ]);

        Livewire::test(Dashboard::class)
            ->assertStatus(200)
            ->assertViewHas('stats', function (array $stats) {
                return array_key_exists('outstanding', $stats)
                    && array_key_exists('paid_this_month', $stats)
                    && $stats['paid_this_month'] > 0;
            });
    }
}
