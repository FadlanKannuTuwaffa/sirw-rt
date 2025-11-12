<?php

namespace App\Services\Payments;

use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Notifications\PaymentPaidNotification;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function markPaid(Payment $payment, CarbonInterface $paidAt, array $payload = []): void
    {
        if ($payment->status === 'paid') {
            return;
        }

        DB::transaction(function () use ($payment, $paidAt, $payload) {
            $payment->refresh();

            if ($payment->status === 'paid') {
                return;
            }

            $raw = $payment->raw_payload ?? [];
            $raw['webhook'] = $payload;
            $raw['last_synced_at'] = now()->toIso8601String();

            $payment->update([
                'status' => 'paid',
                'paid_at' => $paidAt,
                'raw_payload' => $raw,
            ]);

            $bill = $payment->bill()->lockForUpdate()->first();

            if ($bill) {
                $billUpdates = [
                    'status' => 'paid',
                    'paid_at' => $paidAt,
                ];

                if (!is_null($payment->fee_amount)) {
                    $billUpdates['gateway_fee'] = $payment->fee_amount;
                }

                if (!is_null($payment->customer_total)) {
                    $billUpdates['total_amount'] = $payment->customer_total;
                }

                $bill->update($billUpdates);

                if (!LedgerEntry::where('payment_id', $payment->id)->exists()) {
                    $category = $bill->type === 'sumbangan' ? 'sumbangan' : 'kas';
                    $fundDestination = $bill->type === 'sumbangan' ? 'sumbangan' : 'kas';
                    $reference = $bill->title ?? null;

                    LedgerEntry::create([
                        'category' => $category,
                        'amount' => $payment->amount,
                        'bill_id' => $bill->id,
                        'payment_id' => $payment->id,
                        'method' => $payment->gateway ? 'gateway:' . $payment->gateway : null,
                        'status' => 'paid',
                        'fund_destination' => $fundDestination,
                        'fund_reference' => $reference,
                        'occurred_at' => $paidAt,
                        'notes' => Arr::get($payload, 'notes', 'Pembayaran otomatis melalui gateway'),
                    ]);
                }
            }

            DB::afterCommit(function () use ($payment) {
                $paymentFresh = Payment::with(['bill', 'user'])->find($payment->id);

                if (! $paymentFresh || ! $paymentFresh->user) {
                    return;
                }

                $paymentFresh->user->notify(new PaymentPaidNotification($paymentFresh));
            });
        });
    }

    public function markFailed(Payment $payment, string $status, array $payload = []): void
    {
        $raw = $payment->raw_payload ?? [];
        $raw['webhook'] = $payload;
        $raw['last_synced_at'] = now()->toIso8601String();

        $payment->update([
            'status' => $status,
            'raw_payload' => $raw,
        ]);
    }
}
