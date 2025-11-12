<?php

namespace App\Livewire\Admin\Tagihan;

use App\Models\Bill;
use App\Support\SensitiveData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = 'unpaid';
    public string $type = 'all';
    public int $perPage = 10;
    public bool $showDetailModal = false;
    public ?array $detailBill = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => 'unpaid'],
        'type' => ['except' => 'all'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingType(): void
    {
        $this->resetPage();
    }

    public function showDetail(int $billId): void
    {
        $bill = Bill::query()
            ->with([
                'user:id,name,email',
                'creator:id,name',
                'payments' => fn ($q) => $q->latest('paid_at')->limit(5),
            ])
            ->findOrFail($billId);

        $this->detailBill = [
            'id' => $bill->id,
            'title' => $bill->title,
            'description' => $bill->description,
            'type' => $bill->type ? Str::headline($bill->type) : null,
            'status' => $bill->status ? Str::headline($bill->status) : null,
            'status_tone' => $this->statusTone($bill->status),
            'invoice' => $bill->invoice_number,
            'due_date' => optional($bill->due_date)->translatedFormat('d F Y'),
            'issued_at' => optional($bill->issued_at)->translatedFormat('d F Y H:i'),
            'paid_at' => optional($bill->paid_at)->translatedFormat('d F Y H:i'),
            'amount' => $this->formatCurrency($bill->amount),
            'gateway_fee' => $this->formatCurrency($bill->gateway_fee),
            'total_amount' => $this->formatCurrency($bill->total_amount),
            'payable_amount' => $this->formatCurrency($bill->payable_amount),
            'outstanding_amount' => $this->formatCurrency($bill->outstanding_amount),
            'user' => [
                'name' => $bill->user?->name,
                'email' => $bill->user?->email,
            ],
            'creator' => $bill->creator?->name,
            'payments' => $bill->payments->map(function ($payment) {
                return [
                    'amount' => $this->formatCurrency($payment->amount),
                    'fee' => $this->formatCurrency($payment->fee_amount),
                    'customer_total' => $this->formatCurrency($payment->customer_total),
                    'status' => Str::headline($payment->status ?? ''),
                    'channel' => $payment->gateway ?? $payment->manual_channel ?? '-',
                    'reference' => $payment->reference,
                    'paid_at' => optional($payment->paid_at)->translatedFormat('d F Y H:i'),
                ];
            })->all(),
        ];

        $this->showDetailModal = true;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->detailBill = null;
    }

    protected function formatCurrency($value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        return 'Rp ' . number_format((int) $value, 0, ',', '.');
    }

    protected function statusTone(?string $status): string
    {
        $normalized = strtolower($status ?? '');

        return match (true) {
            in_array($normalized, ['paid', 'lunas', 'settled'], true) => 'success',
            in_array($normalized, ['overdue', 'late'], true) => 'danger',
            in_array($normalized, ['pending', 'unpaid', 'draft'], true) => 'warning',
            default => 'info',
        };
    }

    public function render()
    {
        $query = Bill::query()
            ->with(['user:id,name', 'payments' => fn ($q) => $q->where('status', 'paid')])
            ->when($this->search, function (Builder $q) {
                $keyword = '%' . $this->search . '%';
                $digitsOnly = preg_replace('/[^\d]/', '', $this->search);

                $q->where(function (Builder $inner) use ($keyword, $digitsOnly) {
                    $inner->where('title', 'like', $keyword)
                        ->orWhere('invoice_number', 'like', $keyword)
                        ->orWhereHas('user', function (Builder $user) use ($keyword, $digitsOnly) {
                            $user->where('name', 'like', $keyword)
                                ->orWhere('email', 'like', $keyword)
                                ->orWhere('username', 'like', $keyword);

                            if ($digitsOnly !== '') {


                                if (strlen($digitsOnly) === 16) {
                                    $user->orWhere('nik_hash', SensitiveData::hash($digitsOnly));
                                }
                            }
                        });
                });
            })
            ->when($this->type !== 'all', fn (Builder $q) => $q->where('type', $this->type))
            ->when($this->status !== 'all', function (Builder $q) {
                return match ($this->status) {
                    'paid' => $q->where('status', 'paid'),
                    'unpaid' => $q->where('status', 'unpaid'),
                    'overdue' => $q->where('status', '!=', 'paid')
                        ->whereDate('due_date', '<', now()->toDateString()),
                    default => $q,
                };
            })
            ->latest('due_date');

        $baseStats = Bill::query();

        $aggregates = (clone $baseStats)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid_amount,
                SUM(
                    CASE WHEN status != 'paid' THEN GREATEST(
                        amount - COALESCE(
                            (
                                SELECT SUM(p.amount)
                                FROM payments p
                                WHERE p.bill_id = bills.id
                                  AND p.status = 'paid'
                            ),
                            0
                        ),
                        0
                    )
                    ELSE 0 END
                ) as outstanding_amount
            ")
            ->first();

        $stats = [
            'total' => (int) ($aggregates->total ?? 0),
            'paid' => (int) ($aggregates->paid_amount ?? 0),
            'outstanding' => (int) ($aggregates->outstanding_amount ?? 0),
            'overdue' => (clone $baseStats)
                ->where('status', '!=', 'paid')
                ->whereDate('due_date', '<', now()->toDateString())
                ->count(),
        ];

        return view('livewire.admin.tagihan.index', [
            'bills' => $query->paginate($this->perPage),
            'stats' => $stats,
            'recentBills' => Bill::query()
                ->with('user:id,name')
                ->latest()
                ->limit(5)
                ->get(),
            'statsByStatus' => [
                'paid' => Bill::where('status', 'paid')->count(),
                'unpaid' => Bill::where('status', 'unpaid')->count(),
                'overdue' => Bill::where('status', '!=', 'paid')
                    ->whereDate('due_date', '<', now()->toDateString())
                    ->count(),
            ],
        ])->layout('layouts.admin', [
            'title' => 'Tagihan Warga',
        ]);
    }
}
