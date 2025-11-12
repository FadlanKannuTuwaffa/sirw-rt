<?php

namespace App\Livewire\Admin\Kas;

use App\Models\Bill;
use App\Models\LedgerEntry;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $range = 'month';
    public string $type = 'all';
    public string $search = '';
    public string $sort = 'latest';
    public int $perPage = 10;

    public string $entry_type = 'income';
    public string $entry_category = '';
    public string $entry_amount = '';
    public string $entry_occurred_at = '';
    public ?string $entry_notes = null;
    public string $entry_method = 'transfer';
    public string $entry_status = 'paid';
    public string $entry_bucket = 'kas';
    public ?int $entry_bucket_bill_id = null;
    public string $entry_bucket_reference = '';
    public array $donationOptions = [];

    protected $queryString = [
        'range' => ['except' => 'month'],
        'type' => ['except' => 'all'],
        'search' => ['except' => ''],
        'sort' => ['except' => 'latest'],
    ];

    public function mount(): void
    {
        $this->entry_occurred_at = now()->format('Y-m-d\TH:i');
        $this->entry_method = 'transfer';
        $this->entry_status = 'paid';
        $this->entry_bucket = 'kas';
        $this->entry_bucket_bill_id = null;
        $this->entry_bucket_reference = '';

        $this->donationOptions = Bill::query()
            ->where('type', 'sumbangan')
            ->orderBy('title')
            ->get(['id', 'title'])
            ->unique('title')
            ->map(fn (Bill $bill) => [
                'id' => $bill->id,
                'title' => $bill->title,
            ])
            ->values()
            ->all();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRange(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function updatedEntryBucket(string $value): void
    {
        $this->resetErrorBag(['entry_bucket_reference', 'entry_bucket_bill_id']);

        if ($value !== 'sumbangan') {
            $this->entry_bucket_bill_id = null;
            $this->entry_bucket_reference = '';
        }
    }

    public function render()
    {
        [$start, $end] = $this->resolveRangeBounds($this->range);

        $baseQuery = LedgerEntry::query()
            ->when($start && $end, fn (Builder $query) => $query->whereBetween('occurred_at', [$start, $end]))
            ->when($this->type === 'income', fn (Builder $query) => $query->where('amount', '>', 0))
            ->when($this->type === 'expense', fn (Builder $query) => $query->where('amount', '<', 0))
            ->when($this->search, function (Builder $query) {
                $keyword = '%' . $this->search . '%';
                $query->where(function (Builder $inner) use ($keyword) {
                    $inner->where('category', 'like', $keyword)
                        ->orWhere('notes', 'like', $keyword)
                        ->orWhereHas('bill', fn ($billQuery) => $billQuery->where('title', 'like', $keyword)->orWhere('invoice_number', 'like', $keyword))
                        ->orWhereHas('payment', fn ($paymentQuery) => $paymentQuery->where('reference', 'like', $keyword));
                });
            });

        $entries = (clone $baseQuery)
            ->with([
                'bill:id,title,invoice_number',
                'payment:id,reference,gateway,status',
            ])
            ->when(
                $this->sort === 'oldest',
                fn (Builder $query) => $query->orderBy('occurred_at'),
                fn (Builder $query) => $query->orderByDesc('occurred_at')
            )
            ->paginate($this->perPage);

        $paidRangeQuery = (clone $baseQuery)->where('status', 'paid');
        $rangeIncome = (clone $paidRangeQuery)->where('amount', '>', 0)->sum('amount');
        $rangeExpense = abs((clone $paidRangeQuery)->where('amount', '<', 0)->sum('amount'));

        $paidLedgerQuery = LedgerEntry::query()->where('status', 'paid');

        $overview = [
            'balance' => (clone $paidLedgerQuery)->sum('amount'),
            'income' => (clone $paidLedgerQuery)->where('amount', '>', 0)->sum('amount'),
            'expense' => abs((clone $paidLedgerQuery)->where('amount', '<', 0)->sum('amount')),
            'range_income' => $rangeIncome,
            'range_expense' => $rangeExpense,
            'range_net' => $rangeIncome - $rangeExpense,
        ];

        $trendData = $this->buildTrendData();
        $categoryBreakdown = $this->buildCategoryBreakdown(clone $baseQuery);
        return view('livewire.admin.kas.index', [
            'entries' => $entries,
            'overview' => $overview,
            'trendData' => $trendData,
            'categoryBreakdown' => $categoryBreakdown,
        ])->layout('layouts.admin', [
            'title' => 'Kelola Kas & Pengeluaran',
        ]);
    }

    public function createEntry(): void
    {
        $validated = $this->validate([
            'entry_type' => ['required', 'in:income,expense'],
            'entry_category' => ['required', 'string', 'max:120'],
            'entry_amount' => ['required', 'numeric', 'min:1000'],
            'entry_occurred_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'entry_notes' => ['nullable', 'string'],
            'entry_method' => ['required', 'in:transfer,cash'],
            'entry_status' => ['required', 'in:pending,paid'],
            'entry_bucket' => ['required', 'in:kas,sumbangan'],
            'entry_bucket_bill_id' => [
                'nullable',
                'integer',
                Rule::exists('bills', 'id')->where(fn (QueryBuilder $query) => $query->where('type', 'sumbangan')),
            ],
            'entry_bucket_reference' => ['nullable', 'string', 'max:160'],
        ]);

        if (
            $validated['entry_bucket'] === 'sumbangan'
            && empty($validated['entry_bucket_bill_id'])
            && blank($validated['entry_bucket_reference'])
        ) {
            $this->addError('entry_bucket_reference', 'Tentukan sumbangan yang digunakan atau pilih salah satu dari daftar.');
            return;
        }

        $occurredAt = Carbon::createFromFormat('Y-m-d\TH:i', $validated['entry_occurred_at']);
        $amount = (int) abs($validated['entry_amount']);
        $amount = $validated['entry_type'] === 'expense' ? -$amount : $amount;

        $billId = null;
        $fundReference = null;

        if ($validated['entry_bucket'] === 'sumbangan') {
            if (! empty($validated['entry_bucket_bill_id'])) {
                $bill = Bill::query()
                    ->where('type', 'sumbangan')
                    ->find($validated['entry_bucket_bill_id']);

                if (! $bill) {
                    $this->addError('entry_bucket_bill_id', 'Sumbangan tidak ditemukan atau sudah tidak aktif.');
                    return;
                }

                if ($validated['entry_type'] === 'income') {
                    $billId = $bill->id;
                }

                $fundReference = $bill->title;
            }

            if (! $fundReference && filled($validated['entry_bucket_reference'])) {
                $fundReference = trim($validated['entry_bucket_reference']);
            }
        }

        $ledgerData = [
            'category' => $validated['entry_category'],
            'amount' => $amount,
            'method' => $validated['entry_method'],
            'status' => $validated['entry_status'],
            'fund_source' => $validated['entry_type'] === 'expense' ? $validated['entry_bucket'] : null,
            'fund_destination' => $validated['entry_type'] === 'income' ? $validated['entry_bucket'] : null,
            'fund_reference' => $fundReference,
            'occurred_at' => $occurredAt,
            'notes' => $validated['entry_notes'],
        ];

        if ($billId) {
            $ledgerData['bill_id'] = $billId;
        }

        LedgerEntry::create($ledgerData);

        $this->reset(['entry_category', 'entry_amount', 'entry_notes', 'entry_bucket_bill_id', 'entry_bucket_reference']);
        $this->entry_type = $validated['entry_type'];
        $this->entry_method = $validated['entry_method'];
        $this->entry_status = $validated['entry_status'];
        $this->entry_bucket = $validated['entry_bucket'];
        $this->entry_occurred_at = now()->format('Y-m-d\TH:i');

        session()->flash('status', 'Catatan kas berhasil ditambahkan dan telah tersinkron secara realtime.');
    }

    public function markEntryAsPaid(int $entryId): void
    {
        $entry = LedgerEntry::query()->find($entryId);

        if (! $entry || $entry->payment_id || $entry->status !== 'pending') {
            return;
        }

        $entry->update([
            'status' => 'paid',
        ]);

        session()->flash('status', 'Status transaksi berhasil diperbarui menjadi paid.');
    }

    private function resolveRangeBounds(string $range): array
    {
        return match ($range) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            '7d' => [now()->copy()->subDays(6)->startOfDay(), now()->endOfDay()],
            'month' => [now()->startOfMonth(), now()->endOfDay()],
            '90d' => [now()->copy()->subDays(89)->startOfDay(), now()->endOfDay()],
            'year' => [now()->startOfYear(), now()->endOfDay()],
            default => [null, null],
        };
    }

    private function buildTrendData(): Collection
    {
        $start = now()->copy()->subDays(6)->startOfDay();
        $end = now()->endOfDay();

        $raw = LedgerEntry::query()
            ->whereBetween('occurred_at', [$start, $end])
            ->where('status', 'paid')
            ->selectRaw('DATE(occurred_at) as day, SUM(amount) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        return collect(range(0, 6))->map(function ($offset) use ($start, $raw) {
            $day = $start->copy()->addDays($offset);
            $key = $day->toDateString();

            return [
                'label' => $day->translatedFormat('D'),
                'date' => $day->translatedFormat('d M'),
                'total' => (float) ($raw[$key] ?? 0),
            ];
        });
    }

    private function buildCategoryBreakdown(Builder $query): Collection
    {
        $data = (clone $query)
            ->where('status', 'paid')
            ->selectRaw("
                category,
                SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as total_income,
                SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as total_expense
            ")
            ->groupBy('category')
            ->get();

        return $data->map(function ($item) {
            return [
                'category' => $item->category ?: 'Tanpa Kategori',
                'income' => (float) $item->total_income,
                'expense' => (float) $item->total_expense,
            ];
        })->sortByDesc(fn ($item) => $item['income'] + $item['expense'])->values();
    }
}
