<?php

namespace App\Services\Payments\Gateways;

use App\Models\Bill;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payments\Contracts\PaymentGatewayContract;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ManualGateway implements PaymentGatewayContract
{
    public function __construct(private array $config = [])
    {
    }

    public function initiate(Bill $bill, User $user): array
    {
        $this->cancelPendingTripayPayments($bill, $user);

        $existing = $bill->payments()
            ->where('gateway', 'manual')
            ->where('status', 'pending')
            ->latest('created_at')
            ->first();

        $payment = $existing ?? DB::transaction(function () use ($bill, $user) {
            return Payment::create([
                'bill_id' => $bill->id,
                'user_id' => $user->id,
                'gateway' => 'manual',
                'status' => 'pending',
                'amount' => $bill->amount,
                'fee_amount' => 0,
                'customer_total' => $bill->amount,
                'reference' => 'MAN-' . Str::upper(Str::random(8)),
                'raw_payload' => [
                    'note' => 'Instruksi pembayaran manual.',
                ],
            ]);
        });
        $destinations = Arr::get($this->config, 'manual_destinations', []);
        $instructions = Arr::get($this->config, 'manual_instructions');

        if ($payment->customer_total !== $bill->amount || $payment->fee_amount !== 0) {
            $payment->update([
                'customer_total' => $bill->amount,
                'fee_amount' => 0,
            ]);
        }

        if ($bill->status !== 'paid') {
            $bill->update([
                'gateway_fee' => 0,
                'total_amount' => $bill->amount,
            ]);
        }

        return [
            'payment' => $payment,
            'checkout' => [
                'provider' => 'manual',
                'reference' => $payment->reference,
                'amount' => $payment->customer_total,
                'fee_amount' => 0,
                'manual_destinations' => $destinations,
                'manual_instructions' => $instructions,
            ],
        ];
    }

    private function cancelPendingTripayPayments(Bill $bill, User $user): void
    {
        $pendingTripay = Payment::query()
            ->where('bill_id', $bill->id)
            ->where('user_id', $user->id)
            ->where('gateway', 'tripay')
            ->where('status', 'pending')
            ->get();

        if ($pendingTripay->isEmpty()) {
            return;
        }

        foreach ($pendingTripay as $tripayPayment) {
            $raw = $tripayPayment->raw_payload ?? [];
            $raw['cancelled_at'] = now()->toIso8601String();
            $raw['cancelled_reason'] = 'Pembayaran dialihkan ke transfer manual.';

            $tripayPayment->update([
                'status' => 'cancelled',
                'raw_payload' => $raw,
            ]);
        }
    }
}
