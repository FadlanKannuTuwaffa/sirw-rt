<?php

namespace App\Livewire\Admin\Pembayaran;

use App\Models\Bill;
use App\Models\Payment;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Payments\PaymentService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Index extends Component
{
    use WithPagination;

    private const DELETABLE_STATUSES = ['failed', 'cancelled', 'expired'];

    protected array $layoutData = [
        'title' => 'Pembayaran',
    ];

    public string $search = '';
    public string $status = 'all';
    public string $gateway = 'all';

    public ?int $bill_id = null;
    public string $payment_amount = '';
    public string $payment_date = '';
    public ?string $reference = null;
    public ?string $notes = null;

    public array $availableBills = [];
    public ?int $pendingPaymentId = null;
    public ?array $pendingPaymentMeta = null;
    public ?Payment $manualPayment = null;
    public bool $manualModalOpen = false;
    public ?string $manualNotes = null;
    public bool $deleteModalOpen = false;
    public ?Payment $paymentToDelete = null;
    public array $deletableStatuses = self::DELETABLE_STATUSES;

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => 'all'],
        'gateway' => ['except' => 'all'],
    ];

    public function mount(): void
    {
        $this->refreshBillOptions();
        $this->payment_date = now()->format('Y-m-d\TH:i');
    }

    public function updatedBillId($value): void
    {
        $this->pendingPaymentId = null;
        $this->pendingPaymentMeta = null;

        if (! $value) {
            $this->payment_amount = '';
            $this->reference = null;
            $this->notes = null;
            $this->payment_date = now()->format('Y-m-d\TH:i');
            return;
        }

        $bill = Bill::with(['payments' => fn ($q) => $q
            ->where('gateway', 'manual')
            ->orderByDesc('manual_proof_uploaded_at')
            ->orderByDesc('created_at')])
            ->find($value);

        if (! $bill) {
            $this->payment_amount = '';
            $this->reference = null;
            $this->notes = null;
            $this->payment_date = now()->format('Y-m-d\TH:i');
            return;
        }

        $this->payment_amount = (string) $bill->amount;
        $pending = $this->findPendingManualPayment($bill);

        if ($pending) {
            $this->pendingPaymentId = $pending->id;
            $this->payment_amount = (string) $pending->amount;
            $this->reference = $pending->reference;

            $timestamp = $pending->manual_proof_uploaded_at ?? $pending->paid_at ?? $pending->created_at;
            $this->payment_date = optional($timestamp)->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i');

            $this->notes = data_get($pending->raw_payload, 'manual.notes')
                ?? ($pending->manual_channel ? 'Verifikasi ' . $pending->manual_channel : null);

            $this->pendingPaymentMeta = [
                'payment_id' => $pending->id,
                'reference' => $pending->reference,
                'channel' => $pending->manual_channel,
                'amount' => $pending->amount,
                'manual_destination' => $pending->manual_destination,
                'proof_path' => $pending->manual_proof_path,
                'proof_uploaded_at' => optional($pending->manual_proof_uploaded_at)->toIso8601String(),
            ];
        } else {
            $this->reference = null;
            $this->notes = null;
            $this->payment_date = now()->format('Y-m-d\TH:i');
        }
    }

    public function updating($name, $value): void
    {
        if (in_array($name, ['search', 'status', 'gateway'], true)) {
            $this->resetPage();
        }
    }

    public function updatedSearch(): void
    {
        $this->search = trim($this->search);
    }

    public function updatedStatus($value): void
    {
        $value = strtolower(trim($value));
        $allowed = ['paid', 'pending', 'failed', 'cancelled', 'expired', 'all'];
        $this->status = in_array($value, $allowed, true) ? $value : 'all';
    }

    public function updatedGateway($value): void
    {
        $value = strtolower(trim($value));
        $allowed = ['manual', 'manual_bank', 'manual_virtual', 'tripay', 'all'];
        $this->gateway = in_array($value, $allowed, true) ? $value : 'all';
    }

    public function reviewManualPayment(int $paymentId): void
    {
        $payment = Payment::with(['user:id,name', 'bill'])
            ->where('gateway', 'manual')
            ->findOrFail($paymentId);

        if (! $payment->manual_proof_path) {
            $this->dispatch('notification', body: 'Bukti transfer belum diunggah oleh warga.');
            return;
        }

        $this->manualPayment = $payment;
        $this->manualNotes = null;
        $this->manualModalOpen = true;
    }

    public function closeManualModal(): void
    {
        $this->manualModalOpen = false;
        $this->manualPayment = null;
        $this->manualNotes = null;
    }

    public function confirmDeletePayment(int $paymentId): void
    {
        $payment = Payment::with(['user:id,name', 'bill:id,invoice_number,title'])
            ->find($paymentId);

        if (! $payment) {
            $this->dispatch('notification', body: 'Transaksi tidak ditemukan.');
            return;
        }

        if (! in_array($payment->status, self::DELETABLE_STATUSES, true)) {
            $this->dispatch('notification', body: 'Hanya transaksi gagal/batal/kedaluwarsa yang bisa dihapus.');
            return;
        }

        $this->paymentToDelete = $payment;
        $this->deleteModalOpen = true;
    }

    public function closeDeleteModal(): void
    {
        $this->deleteModalOpen = false;
        $this->paymentToDelete = null;
    }

    public function deletePayment(): void
    {
        if (! $this->paymentToDelete) {
            return;
        }

        $payment = $this->paymentToDelete->fresh(['bill']);

        if (! in_array($payment->status, self::DELETABLE_STATUSES, true)) {
            $this->closeDeleteModal();
            $this->dispatch('notification', body: 'Status transaksi berubah sehingga tidak dapat dihapus.');
            return;
        }

        $payment->delete();

        $this->closeDeleteModal();
        $this->refreshBillOptions();

        session()->flash('status', 'Transaksi berhasil dihapus.');
    }

    public function approveManualPayment(): void
    {
        if (! $this->manualPayment) {
            return;
        }

        $payment = $this->manualPayment->fresh();
        if (! $payment) {
            $this->closeManualModal();
            return;
        }

        $service = new PaymentService();
        $service->markPaid($payment, now(), [
            'validated_by' => Auth::id(),
            'notes' => $this->manualNotes,
            'source' => 'manual_verification',
        ]);

        $this->closeManualModal();
        session()->flash('status', 'Pembayaran manual berhasil divalidasi.');
        $this->refreshBillOptions();
    }

    public function rejectManualPayment(): void
    {
        if (! $this->manualPayment) {
            return;
        }

        $payment = $this->manualPayment->fresh();
        if (! $payment) {
            $this->closeManualModal();
            return;
        }

        $this->rejectManualPaymentInternal($payment, $this->manualNotes, 'manual_verification');

        $this->closeManualModal();
        session()->flash('status', 'Pembayaran manual ditolak.');
        $this->refreshBillOptions();
    }

    public function rejectPendingPayment(): void
    {
        if (! $this->pendingPaymentId) {
            $this->dispatch('notification', body: 'Tidak ada pembayaran manual yang perlu ditolak.');
            return;
        }

        $payment = Payment::query()
            ->where('id', $this->pendingPaymentId)
            ->where('gateway', 'manual')
            ->first();

        if (! $payment) {
            $this->dispatch('notification', body: 'Pembayaran manual tidak ditemukan.');
            $this->refreshBillOptions();
            return;
        }

        $this->rejectManualPaymentInternal($payment, $this->notes, 'manual_form');

        $this->reset(['bill_id', 'payment_amount', 'reference', 'notes', 'pendingPaymentId', 'pendingPaymentMeta']);
        $this->payment_date = now()->format('Y-m-d\TH:i');
        $this->refreshBillOptions();

        session()->flash('status', 'Pembayaran manual ditolak dan warga diminta mengunggah ulang bukti.');
    }

    public function render(): View
    {
        $query = Payment::query()
            ->with(['user:id,name', 'bill:id,user_id,title,invoice_number,type', 'bill.user:id,name'])
            ->when($this->search, function (Builder $q) {
                $keyword = '%' . $this->search . '%';
                $q->where(function ($inner) use ($keyword) {
                    $inner->whereHas('user', fn ($u) => $u->where('name', 'like', $keyword))
                        ->orWhereHas('bill', fn ($b) => $b->where('title', 'like', $keyword)
                            ->orWhere('invoice_number', 'like', $keyword))
                        ->orWhere('reference', 'like', $keyword)
                        ->orWhere('gateway', 'like', $keyword)
                        ->orWhere('status', 'like', $keyword)
                        ->orWhere('manual_channel', 'like', $keyword)
                        ->orWhere('manual_destination->label', 'like', $keyword)
                        ->orWhere('manual_destination->provider', 'like', $keyword)
                        ->orWhere('manual_destination->bank_name', 'like', $keyword)
                        ->orWhere('manual_destination->account_number', 'like', $keyword);
                });
            })
            ->tap(fn (Builder $builder) => $this->applyStatusFilter($builder))
            ->tap(fn (Builder $builder) => $this->applyGatewayFilter($builder));

        $payments = $query
            ->latest('paid_at')
            ->orderByDesc('created_at')
            ->paginate(12);

        $pendingQuery = Payment::query()
            ->where('status', 'pending')
            ->whereHas('bill', fn ($q) => $q->where('status', '!=', 'paid'));

        $paidQuery = Payment::query()->where('status', 'paid');

        $stats = [
            'total_paid' => (clone $paidQuery)->sum('amount'),
            'paid_count' => (clone $paidQuery)->count(),
            'paid_today' => (clone $paidQuery)->whereDate('paid_at', today())->sum('amount'),
            'manual_paid' => (clone $paidQuery)->where('gateway', 'manual')->sum('amount'),
            'online_paid' => (clone $paidQuery)->where('gateway', 'tripay')->sum('amount'),
            'pending' => (clone $pendingQuery)->count(),
            'pending_amount' => (clone $pendingQuery)->sum('amount'),
            'avg_confirmation' => $this->calculateAverageConfirmationMinutes(),
        ];

        $gatewayBreakdown = $this->buildGatewaySnapshot();

        return view('livewire.admin.pembayaran.index', [
            'payments' => $payments,
            'stats' => $stats,
            'gatewayBreakdown' => $gatewayBreakdown,
        ]);
    }

    private function applyStatusFilter(Builder $query): void
    {
        match ($this->status) {
            'paid' => $query->where('status', 'paid'),
            'pending' => $query->where('status', 'pending'),
            'failed' => $query->whereIn('status', ['failed', 'cancelled', 'expired']),
            default => null,
        };
    }

    private function applyGatewayFilter(Builder $query): void
    {
        switch ($this->gateway) {
            case 'tripay':
                $query->where('gateway', 'tripay');
                break;
            case 'manual':
                $query->where('gateway', 'manual');
                break;
            case 'manual_bank':
                $query->where('gateway', 'manual')
                    ->where(function (Builder $manual) {
                        $manual->where(function (Builder $type) {
                            $type->where('manual_destination->type', 'bank')
                                ->orWhereRaw("json_extract(manual_destination, '$.type') = ?", ['bank'])
                                ->orWhereRaw("json_extract(manual_destination, '$.type') = ?", ['"bank"']);
                        })->orWhereNull('manual_destination')
                          ->orWhereRaw("json_extract(manual_destination, '$.type') IS NULL");
                    });
                break;
            case 'manual_virtual':
                $query->where('gateway', 'manual')
                    ->where(function (Builder $manual) {
                        $manual->where(function (Builder $type) {
                            $type->where('manual_destination->type', 'wallet')
                                ->orWhereRaw("json_extract(manual_destination, '$.type') = ?", ['wallet'])
                                ->orWhereRaw("json_extract(manual_destination, '$.type') = ?", ['"wallet"']);
                        });
                    });
                break;
        }
    }

    public function recordPayment(): void
    {
        $validated = $this->validate([
            'bill_id' => ['required', Rule::exists('bills', 'id')],
            'payment_amount' => ['required', 'numeric', 'min:1000'],
            'payment_date' => ['required', 'date_format:Y-m-d\TH:i'],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string'],
        ]);

        $bill = Bill::with('user')->findOrFail($validated['bill_id']);
        $paidAt = Carbon::createFromFormat('Y-m-d\TH:i', $validated['payment_date']);
        $amount = (int) $validated['payment_amount'];

        if ($amount < $bill->amount) {
            $this->addError('payment_amount', 'Nominal tidak boleh kurang dari tagihan. Untuk pembayaran sebagian mohon gunakan pencatatan manual di luar sistem.');
            return;
        }

        $paymentService = new PaymentService();

        $payment = $this->pendingPaymentId
            ? Payment::query()
                ->where('id', $this->pendingPaymentId)
                ->where('bill_id', $bill->id)
                ->where('gateway', 'manual')
                ->first()
            : null;

        if ($payment) {
            $payment->update([
                'amount' => $amount,
                'reference' => $validated['reference'] ?: $payment->reference,
                'raw_payload' => tap($payment->raw_payload ?? [], function (array &$payload) use ($validated) {
                    if ($validated['notes'] ?? null) {
                        data_set($payload, 'manual.notes', $validated['notes']);
                    }
                }),
            ]);

            $paymentService->markPaid($payment, $paidAt, [
                'validated_by' => Auth::id(),
                'notes' => $validated['notes'] ?? 'Pembayaran manual diverifikasi admin',
                'source' => 'manual_form',
            ]);
        } else {
            $reference = $validated['reference'] ?: 'MAN-' . Str::upper(Str::random(8));

            $rawPayload = [
                'manual_entry' => [
                    'created_by' => Auth::id(),
                    'notes' => $validated['notes'],
                ],
            ];

            $payment = Payment::create([
                'bill_id' => $bill->id,
                'user_id' => $bill->user_id,
                'gateway' => 'manual',
                'status' => 'pending',
                'amount' => $amount,
                'fee_amount' => 0,
                'customer_total' => $amount,
                'reference' => $reference,
                'raw_payload' => $rawPayload,
            ]);

            $paymentService->markPaid($payment, $paidAt, [
                'validated_by' => Auth::id(),
                'notes' => $validated['notes'] ?? 'Pembayaran manual dicatat admin',
                'source' => 'manual_form',
            ]);
        }

        $this->reset(['bill_id', 'payment_amount', 'reference', 'notes', 'pendingPaymentId', 'pendingPaymentMeta']);
        $this->payment_date = now()->format('Y-m-d\TH:i');
        $this->refreshBillOptions();

        session()->flash('status', 'Pembayaran berhasil dicatat dan buku kas diperbarui.');
    }

    private function refreshBillOptions(): void
    {
        $this->availableBills = Bill::with('user:id,name')
            ->where('status', '!=', 'paid')
            ->orderBy('due_date')
            ->limit(100)
            ->get()
            ->map(fn ($bill) => [
                'id' => $bill->id,
                'title' => $bill->title,
                'invoice' => $bill->invoice_number,
                'user' => $bill->user?->name,
                'amount' => $bill->amount,
                'due_date' => optional($bill->due_date)->translatedFormat('d M Y'),
            ])
            ->toArray();
    }

    private function calculateAverageConfirmationMinutes(): ?int
    {
        $durations = Payment::query()
            ->where('status', 'paid')
            ->whereNotNull('paid_at')
            ->get(['created_at', 'paid_at'])
            ->map(function ($payment) {
                if (! $payment->created_at || ! $payment->paid_at) {
                    return null;
                }

                return $payment->created_at->diffInMinutes($payment->paid_at);
            })
            ->filter();

        if ($durations->isEmpty()) {
            return null;
        }

        return (int) round($durations->avg());
    }

    private function findPendingManualPayment(Bill $bill): ?Payment
    {
        return $bill->payments
            ->where('status', 'pending')
            ->whereNotNull('manual_proof_path')
            ->first();
    }

    private function buildGatewaySnapshot(): array
    {
        $manager = PaymentGatewayManager::resolve();
        $manualDestinations = collect($manager->manualDestinations());

        $manualBase = Payment::query()->where('gateway', 'manual');

        $tripayStats = $this->aggregateGatewayStats(Payment::query()->where('gateway', 'tripay'));
        $tripay = [
            'key' => 'tripay',
            'label' => 'Tripay',
            'description' => 'Transaksi otomatis Tripay',
        ] + $tripayStats + $this->summarizeStatusMeta(
            $tripayStats['paid_count'] ?? 0,
            $tripayStats['pending_count'] ?? 0,
            $tripayStats['failed_count'] ?? 0
        );

        $manualBankQuery = (clone $manualBase)->where('manual_destination->type', 'bank');
        $manualBankStats = $this->aggregateGatewayStats($manualBankQuery);
        $manualBank = [
            'key' => 'manual_bank',
            'label' => 'Manual (Bank)',
            'description' => 'Transfer ke rekening yang disiapkan pengurus',
            'channels' => $this->aggregateManualDestinationChannels($manualDestinations, 'bank'),
        ] + $manualBankStats + $this->summarizeStatusMeta(
            $manualBankStats['paid_count'] ?? 0,
            $manualBankStats['pending_count'] ?? 0,
            $manualBankStats['failed_count'] ?? 0
        );

        $manualWalletQuery = (clone $manualBase)->where('manual_destination->type', 'wallet');
        $manualWalletStats = $this->aggregateGatewayStats($manualWalletQuery);
        $manualWallet = [
            'key' => 'manual_virtual',
            'label' => 'Manual (E-Wallet)',
            'description' => 'Pembayaran manual via e-wallet terdaftar',
            'channels' => $this->aggregateManualDestinationChannels($manualDestinations, 'wallet'),
        ] + $manualWalletStats + $this->summarizeStatusMeta(
            $manualWalletStats['paid_count'] ?? 0,
            $manualWalletStats['pending_count'] ?? 0,
            $manualWalletStats['failed_count'] ?? 0
        );

        $manualFallback = $this->aggregateGatewayStats((clone $manualBase)->where(function ($query) {
            $query->whereNull('manual_destination')
                ->orWhereNull('manual_destination->type');
        }));
        $manualFallback += $this->summarizeStatusMeta(
            $manualFallback['paid_count'] ?? 0,
            $manualFallback['pending_count'] ?? 0,
            $manualFallback['failed_count'] ?? 0,
            'channel'
        );

        if (($manualFallback['pending_count'] ?? 0) > 0 || ($manualFallback['failed_count'] ?? 0) > 0) {
            $manualBank['unassigned'] = $manualFallback;
        }

        return [
            $tripay,
            $manualBank,
            $manualWallet,
        ];
    }

    private function aggregateGatewayStats(Builder $query): array
    {
        $result = $query->selectRaw('
            SUM(CASE WHEN status = "paid" THEN amount ELSE 0 END) as paid_amount,
            SUM(CASE WHEN status = "paid" THEN 1 ELSE 0 END) as paid_count,
            SUM(CASE WHEN status = "pending" THEN amount ELSE 0 END) as pending_amount,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status IN ("failed","cancelled","expired") THEN 1 ELSE 0 END) as failed_count,
            COUNT(*) as total_count
        ')->first();

        if (! $result) {
            return [
                'paid_amount' => 0,
                'paid_count' => 0,
                'pending_amount' => 0,
                'pending_count' => 0,
                'failed_count' => 0,
                'total_count' => 0,
            ];
        }

        return [
            'paid_amount' => (int) ($result->paid_amount ?? 0),
            'paid_count' => (int) ($result->paid_count ?? 0),
            'pending_amount' => (int) ($result->pending_amount ?? 0),
            'pending_count' => (int) ($result->pending_count ?? 0),
            'failed_count' => (int) ($result->failed_count ?? 0),
            'total_count' => (int) ($result->total_count ?? 0),
        ];
    }

    private function aggregateManualDestinationChannels(Collection $manualDestinations, string $type): array
    {
        $destinations = $manualDestinations
            ->where('type', $type)
            ->filter(fn ($destination) => ! empty($destination['id']))
            ->values();

        if ($destinations->isEmpty()) {
            return [];
        }

        $ids = $destinations->pluck('id')->all();

        $payments = Payment::query()
            ->where('gateway', 'manual')
            ->whereNotNull('manual_destination')
            ->get(['manual_destination', 'status', 'amount']);

        if ($payments->isEmpty()) {
            return [];
        }

        $stats = [];

        foreach ($payments as $payment) {
            $destination = $payment->manual_destination ?? [];
            $destinationId = $destination['id'] ?? null;

            if (! $destinationId || ! in_array($destinationId, $ids, true)) {
                continue;
            }

            if (! isset($stats[$destinationId])) {
                $stats[$destinationId] = [
                    'paid_amount' => 0,
                    'paid_count' => 0,
                    'pending_amount' => 0,
                    'pending_count' => 0,
                    'failed_count' => 0,
                    'total_count' => 0,
                ];
            }

            $stats[$destinationId]['total_count']++;

            switch ($payment->status) {
                case 'paid':
                    $stats[$destinationId]['paid_count']++;
                    $stats[$destinationId]['paid_amount'] += (int) ($payment->amount ?? 0);
                    break;
                case 'pending':
                    $stats[$destinationId]['pending_count']++;
                    $stats[$destinationId]['pending_amount'] += (int) ($payment->amount ?? 0);
                    break;
                case 'failed':
                case 'cancelled':
                case 'expired':
                    $stats[$destinationId]['failed_count']++;
                    break;
            }
        }

        if (empty($stats)) {
            return [];
        }

        return $destinations
            ->map(function (array $destination) use ($stats) {
                $stat = $stats[$destination['id']] ?? null;

                if (! $stat) {
                    return null;
                }

                $row = [
                    'id' => $destination['id'],
                    'label' => $destination['label'] ?? $destination['account_number'] ?? 'Manual',
                    'paid_amount' => (int) ($stat['paid_amount'] ?? 0),
                    'paid_count' => (int) ($stat['paid_count'] ?? 0),
                    'pending_amount' => (int) ($stat['pending_amount'] ?? 0),
                    'pending_count' => (int) ($stat['pending_count'] ?? 0),
                    'failed_count' => (int) ($stat['failed_count'] ?? 0),
                    'total_count' => (int) ($stat['total_count'] ?? 0),
                ];

                return $row + $this->summarizeStatusMeta(
                    $row['paid_count'],
                    $row['pending_count'],
                    $row['failed_count'],
                    'channel'
                );
            })
            ->filter(fn ($row) => $row && ($row['total_count'] ?? 0) > 0)
            ->sortByDesc('paid_amount')
            ->values()
            ->all();
    }

    private function rejectManualPaymentInternal(Payment $payment, ?string $notes, string $source): void
    {
        $reason = trim((string) $notes);
        if ($reason === '') {
            $reason = 'Bukti pembayaran tidak valid';
        }

        $raw = $payment->raw_payload ?? [];
        $raw['manual_validation'] = [
            'status' => 'rejected',
            'notes' => $reason,
            'reviewed_at' => now()->toIso8601String(),
            'reviewed_by' => Auth::id(),
            'source' => $source,
        ];

        $payment->update([
            'status' => 'failed',
            'raw_payload' => $raw,
        ]);
    }

    private function summarizeStatusMeta(int $paid, int $pending, int $failed, string $scope = 'gateway'): array
    {
        $format = static fn (int $value): string => number_format($value);

        if ($pending > 0) {
            return [
                'status' => 'pending',
                'status_label' => 'Menunggu',
                'status_tone' => 'warning',
                'status_message' => $scope === 'gateway'
                    ? 'Ada ' . $format($pending) . ' transaksi yang masih menunggu selesai.'
                    : 'Ada ' . $format($pending) . ' transaksi yang masih pending pada channel ini.',
            ];
        }

        if ($failed > 0) {
            return [
                'status' => 'failed',
                'status_label' => 'Perlu Tindakan',
                'status_tone' => 'danger',
                'status_message' => $scope === 'gateway'
                    ? 'Terdapat ' . $format($failed) . ' transaksi gagal atau dibatalkan.'
                    : 'Channel ini memiliki ' . $format($failed) . ' transaksi gagal/dibatalkan.',
            ];
        }

        if ($paid > 0) {
            return [
                'status' => 'success',
                'status_label' => 'Sukses',
                'status_tone' => 'success',
                'status_message' => $scope === 'gateway'
                    ? 'Semua transaksi pada metode ini berhasil diproses.'
                    : 'Semua transaksi pada channel ini berhasil.',
            ];
        }

        return [
            'status' => 'idle',
            'status_label' => 'Belum Ada Transaksi',
            'status_tone' => 'muted',
            'status_message' => $scope === 'gateway'
                ? 'Belum ada transaksi tercatat untuk metode ini.'
                : 'Belum ada transaksi tercatat pada channel ini.',
        ];
    }
}
