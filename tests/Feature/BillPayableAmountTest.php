<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillPayableAmountTest extends TestCase
{
    use RefreshDatabase;

    public function test_payable_amount_accessor_returns_total_with_gateway_fee(): void
    {
        $user = User::factory()->create();
        $bill = Bill::create([
            'user_id' => $user->id,
            'type' => 'iuran',
            'title' => 'Test Bill',
            'amount' => 50000,
            'gateway_fee' => 1500,
            'total_amount' => 51500,
            'due_date' => now()->addWeek(),
            'status' => 'unpaid',
            'invoice_number' => 'INV-TEST',
            'issued_at' => now(),
            'created_by' => $user->id,
        ]);

        $this->assertSame(51500, $bill->payable_amount);
    }
}
