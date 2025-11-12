<?php

namespace App\Services\Assistant\Tools;

use App\Models\LedgerEntry;
use Illuminate\Support\Carbon;

class ExportService
{
    public function exportRecap(string $period, int $residentId): array
    {
        $now = Carbon::now();

        [$start, $end] = match ($period) {
            'last_month' => [
                $now->copy()->subMonth()->startOfMonth(),
                $now->copy()->subMonth()->endOfMonth(),
            ],
            'year_to_date' => [
                $now->copy()->startOfYear(),
                $now->copy(),
            ],
            default => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
            ],
        };

        $entries = LedgerEntry::query()
            ->whereBetween('occurred_at', [$start, $end])
            ->orderByDesc('occurred_at')
            ->limit(50)
            ->get([
                'id',
                'category',
                'amount',
                'occurred_at',
                'notes',
            ]);

        $income = (int) $entries
            ->where('amount', '>', 0)
            ->sum('amount');

        $expense = (int) $entries
            ->where('amount', '<', 0)
            ->sum('amount');

        $items = $entries->map(static function (LedgerEntry $entry): array {
            return [
                'id' => $entry->id,
                'category' => $entry->category,
                'amount' => (int) $entry->amount,
                'occurred_at' => optional($entry->occurred_at)->toDateTimeString(),
                'notes' => $entry->notes,
            ];
        })->all();

        return [
            'summary' => [
                'period' => $period,
                'range' => [
                    $start->toDateString(),
                    $end->toDateString(),
                ],
                'income' => $income,
                'expense' => $expense,
                'balance' => $income + $expense,
                'route' => route('resident.reports'),
            ],
            'items' => $items,
        ];
    }
}

