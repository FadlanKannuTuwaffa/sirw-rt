<?php

namespace App\Livewire\Admin\Laporan;

use App\Models\LedgerEntry;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;

class Index extends Component
{
    public string $from;
    public string $to;
    public string $category = 'all';

    public function mount(): void
    {
        $this->from = now()->startOfMonth()->format('Y-m-d');
        $this->to = now()->endOfMonth()->format('Y-m-d');
    }

    public function updatedFrom($value): void
    {
        $this->from = $this->sanitizeDate($value, now()->startOfMonth());

        if (Carbon::parse($this->from)->gt(Carbon::parse($this->to))) {
            $this->to = $this->from;
        }
    }

    public function updatedTo($value): void
    {
        $this->to = $this->sanitizeDate($value, now()->endOfMonth());

        if (Carbon::parse($this->to)->lt(Carbon::parse($this->from))) {
            $this->from = $this->to;
        }
    }

    public function updatedCategory($value): void
    {
        $value = $value === '__null__'
            ? '__null__'
            : trim((string) $value);

        $this->category = $value !== '' ? $value : 'all';
    }

    public function render()
    {
        [$periodStart, $periodEnd] = $this->resolvePeriod();

        $filteredQuery = LedgerEntry::query()
            ->whereBetween('occurred_at', [$periodStart, $periodEnd])
            ->when($this->category !== 'all', function (Builder $query) {
                if ($this->category === '__null__') {
                    $query->whereNull('category');
                } else {
                    $query->where('category', $this->category);
                }
            });

        $entries = (clone $filteredQuery)
            ->with(['bill:id,title,type', 'payment:id,gateway,status'])
            ->orderByDesc('occurred_at')
            ->get();

        $paidFilteredQuery = (clone $filteredQuery)->where('status', 'paid');

        $totals = [
            'income' => (clone $paidFilteredQuery)->where('amount', '>', 0)->sum('amount'),
            'expense' => abs((clone $paidFilteredQuery)->where('amount', '<', 0)->sum('amount')),
            'net' => (clone $paidFilteredQuery)->sum('amount'),
        ];

        $paymentSummary = Payment::query()
            ->whereBetween('paid_at', [$periodStart, $periodEnd])
            ->where('status', 'paid')
            ->selectRaw('gateway, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('gateway')
            ->get();

        return view('livewire.admin.laporan.index', [
            'entries' => $entries,
            'totals' => $totals,
            'paymentSummary' => $paymentSummary,
            'categoryOptions' => $this->buildCategoryOptions(),
        ])->layout('layouts.admin', [
            'title' => 'Laporan Keuangan',
        ]);
    }

    private function sanitizeDate(?string $value, Carbon $fallback): string
    {
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return $fallback->format('Y-m-d');
        }
    }

    private function resolvePeriod(): array
    {
        $start = Carbon::parse($this->from)->startOfDay();
        $end = Carbon::parse($this->to)->endOfDay();

        if ($start->gt($end)) {
            $end = $start->copy()->endOfDay();
            $this->to = $end->format('Y-m-d');
        }

        return [$start, $end];
    }

    private function buildCategoryOptions(): Collection
    {
        return LedgerEntry::query()
            ->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) {
                $value = $row->category ?? '__null__';
                $label = $row->category ? Str::headline($row->category) : 'Tanpa Kategori';

                return [
                    'value' => $value,
                    'label' => $label,
                    'total' => (int) $row->total,
                ];
            });
    }
}
