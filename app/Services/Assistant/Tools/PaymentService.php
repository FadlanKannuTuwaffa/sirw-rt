<?php

namespace App\Services\Assistant\Tools;

use App\Models\Payment;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;

class PaymentService
{
    public function getPaymentsThisMonth(int $residentId): array
    {
        $now = Carbon::now();

        $baseQuery = Payment::query()
            ->where('user_id', $residentId)
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()]);

        $count = (clone $baseQuery)->count();

        /** @var Collection<int, Payment> $payments */
        $payments = (clone $baseQuery)
            ->orderByDesc('paid_at')
            ->limit(10)
            ->get([
                'id',
                'bill_id',
                'amount',
                'fee_amount',
                'customer_total',
                'gateway',
                'paid_at',
            ]);

        $items = $payments->map(static function (Payment $payment): array {
            $total = $payment->customer_total;

            if ($total === null || (int) $total <= 0) {
                $total = (int) $payment->amount + (int) $payment->fee_amount;
            }

            return [
                'id' => $payment->id,
                'bill_id' => $payment->bill_id,
                'gateway' => $payment->gateway,
                'paid_at' => optional($payment->paid_at)->toDateTimeString(),
                'total' => (int) $total,
            ];
        })->all();

        $totalAmount = array_sum(array_column($items, 'total'));

        return [
            'summary' => [
                'count' => $count,
                'total_amount' => $totalAmount,
                'route' => route('resident.bills'),
            ],
            'items' => $items,
        ];
    }
}

