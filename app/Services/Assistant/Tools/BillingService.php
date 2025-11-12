<?php

namespace App\Services\Assistant\Tools;

use App\Models\Bill;
use Illuminate\Support\Collection;

class BillingService
{
    public function getOutstandingBills(int $residentId): array
    {
        $baseQuery = Bill::query()
            ->where('user_id', $residentId)
            ->where('status', '!=', 'paid');

        $count = (clone $baseQuery)->count();

        /** @var Collection<int, Bill> $bills */
        $bills = (clone $baseQuery)
            ->orderBy('due_date')
            ->limit(10)
            ->get([
                'id',
                'title',
                'amount',
                'gateway_fee',
                'total_amount',
                'due_date',
                'status',
                'invoice_number',
            ]);

        $items = $bills->map(static function (Bill $bill): array {
            $baseAmount = (int) $bill->amount + (int) $bill->gateway_fee;
            $totalAmount = (int) ($bill->total_amount ?: $baseAmount);

            return [
                'id' => $bill->id,
                'title' => $bill->title,
                'invoice' => $bill->invoice_number,
                'due_date' => optional($bill->due_date)->toDateString(),
                'status' => $bill->status,
                'amount' => $totalAmount,
                'is_overdue' => $bill->due_date ? $bill->due_date->isPast() : false,
            ];
        })->all();

        $totalAmount = array_sum(array_column($items, 'amount'));
        $overdueCount = count(array_filter($items, static fn (array $item) => $item['is_overdue']));

        return [
            'summary' => [
                'count' => $count,
                'total_amount' => $totalAmount,
                'overdue_count' => $overdueCount,
                'route' => route('resident.bills'),
            ],
            'items' => $items,
        ];
    }
}

