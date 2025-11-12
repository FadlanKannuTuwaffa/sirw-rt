<?php

namespace App\Livewire\Admin\Tagihan;

use App\Models\Bill;
use App\Models\User;
use App\Services\Payments\PaymentFeeEstimator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Form extends Component
{
    public ?Bill $bill = null;

    public string $mode = 'create';
    public $user_id = null;
    public string $type = 'iuran';
    public string $title = '';
    public ?string $description = null;
    public string $amount = '';
    public string $due_date = '';
    public string $issued_at = '';
    public string $status = 'unpaid';
    public string $iuran_period = '';
    public array $selected_resident_ids = [];

    public array $residentOptions = [];
    public array $residentDirectory = [];

    protected int $defaultIuranAmount = 50000;

    public function mount($bill = null): void
    {
        $billModel = $bill instanceof Bill
            ? $bill
            : (filled($bill) ? Bill::query()->find($bill) : null);

        Log::info('Admin.Tagihan.Form mount', [
            'auth_id' => auth()->id(),
            'bill_param' => $billModel?->id,
            'route' => request()->path(),
        ]);

        $this->residentOptions = User::residents()
            ->where('status', 'aktif')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
            ])
            ->toArray();

        $this->residentDirectory = User::residents()
            ->orderBy('name')
            ->get(['id', 'name', 'status'])
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'status' => $user->status,
            ])
            ->toArray();

        $this->issued_at = now()->format('Y-m-d');
        $this->due_date = now()->endOfMonth()->format('Y-m-d');
        $this->iuran_period = now()->format('Y-m');

        if ($billModel && $billModel->exists) {
            $this->bill = $billModel;
            $this->mode = 'edit';

            $this->fill([
                'user_id' => $billModel->user_id,
                'type' => $billModel->type,
                'title' => $billModel->title,
                'description' => $billModel->description,
                'amount' => (string) $billModel->amount,
                'due_date' => optional($billModel->due_date)->format('Y-m-d') ?? now()->format('Y-m-d'),
                'issued_at' => optional($billModel->issued_at)->format('Y-m-d') ?? now()->format('Y-m-d'),
                'status' => $billModel->status,
            ]);

            if ($billModel->type === 'iuran' && $billModel->due_date) {
                $this->iuran_period = $billModel->due_date->format('Y-m');
                $this->syncDatesFromPeriod(false);
            }
        } else {
            if (request()->get('prefill') === 'iuran') {
                $this->type = 'iuran';
                $this->user_id = 'all';
            }

            $this->amount = (string) $this->defaultIuranAmount;
            $this->syncDatesFromPeriod();
        }
    }

    public function render()
    {
        $title = $this->mode === 'create' ? 'Buat Tagihan' : 'Ubah Tagihan';

        return view('livewire.admin.tagihan.form', [
            'residents' => $this->residentOptions,
            'residentDirectory' => $this->residentDirectory,
            'title' => $title,
            'mode' => $this->mode,
        ])->layout('layouts.admin', [
            'title' => $title,
        ]);
    }

    public function updatedType($value): void
    {
        if ($value === 'iuran') {
            if (! filled($this->iuran_period)) {
                $this->iuran_period = now()->format('Y-m');
            }

            $this->amount = (string) $this->defaultIuranAmount;
            $this->syncDatesFromPeriod();
        } else {
            $this->iuran_period = '';
            $this->title = '';
            $this->description = null;
            $this->amount = '';
            $this->due_date = '';
        }
    }

    public function updatedIuranPeriod(): void
    {
        $this->syncDatesFromPeriod();
    }

    public function updatedUserId($value): void
    {
        if ($value !== 'all') {
            $this->selected_resident_ids = [];
        }
    }

    protected function rules(): array
    {
        $dueDateRule = ['required', 'date'];
        if ($this->mode === 'create') {
            $dueDateRule[] = 'after_or_equal:' . now()->format('Y-m-d');
        }

        $rules = [
            'user_id' => [
                'required',
                function (string $attribute, $value, $fail) {
                    if ($value === 'all' && $this->mode === 'edit') {
                        $fail('Tagihan yang sudah ada tidak dapat diubah menjadi massal.');
                    }
                },
            ],
            'type' => ['required', Rule::in(['iuran', 'sumbangan', 'lainnya'])],
            'title' => ['required', 'string', 'max:160'],
            'description' => [$this->type === 'lainnya' ? 'required' : 'nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'due_date' => $dueDateRule,
            'issued_at' => ['required', 'date'],
            'status' => ['required', Rule::in(['unpaid', 'paid', 'cancelled'])],
            'selected_resident_ids' => ['array'],
            'selected_resident_ids.*' => [Rule::exists('users', 'id')],
        ];

        $rules['iuran_period'] = $this->type === 'iuran'
            ? ['required', 'date_format:Y-m']
            : ['nullable', 'date_format:Y-m'];

        return $rules;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->mode === 'create' && $validated['user_id'] === 'all') {
            if ($this->type !== 'iuran') {
                $this->addError('user_id', 'Hanya tagihan iuran bulanan yang dapat dibuat massal dari formulir ini.');
                return;
            }

            $created = $this->generateMassIuran($validated);

            if ($created === 0) {
                session()->flash('status', 'Tidak ada tagihan baru yang dibuat karena warga sudah memiliki iuran pada periode tersebut.');
            } else {
                session()->flash('status', 'Tagihan iuran berhasil dibuat untuk ' . $created . ' warga.');
            }

            $this->redirectRoute('admin.tagihan.index');
            return;
        }

        $targetUsers = $validated['user_id'] === 'all'
            ? User::residents()->where('status', 'aktif')->pluck('id')
            : collect([(int) $validated['user_id']]);

        if ($targetUsers->isEmpty()) {
            $this->addError('user_id', 'Tidak ada warga aktif yang dapat menerima tagihan.');
            return;
        }

        $dueDate = Carbon::parse($validated['due_date']);
        $issuedAt = Carbon::parse($validated['issued_at'])->startOfDay();
        $feeEstimator = PaymentFeeEstimator::resolve();
        $baseAmount = (int) $validated['amount'];
        $feeData = $feeEstimator->estimate($baseAmount);

        $createdBills = collect();

        if ($this->mode === 'create') {
            foreach ($targetUsers as $userId) {
                $bill = Bill::create([
                    'user_id' => $userId,
                    'type' => $validated['type'],
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'amount' => $baseAmount,
                    'gateway_fee' => $feeData['fee'],
                    'total_amount' => $feeData['total'],
                    'due_date' => $dueDate->copy(),
                    'status' => $validated['status'],
                    'issued_at' => $issuedAt->copy(),
                    'created_by' => Auth::id(),
                    'invoice_number' => strtoupper(Str::ulid()),
                ]);

                $createdBills->push($bill);
            }
        } else {
            $bill = $this->bill;
            $bill->update([
                'user_id' => (int) $validated['user_id'],
                'type' => $validated['type'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'amount' => $baseAmount,
                'gateway_fee' => $feeData['fee'],
                'total_amount' => $feeData['total'],
                'due_date' => $dueDate,
                'status' => $validated['status'],
                'issued_at' => $issuedAt,
            ]);

            $createdBills->push($bill);
        }

        $message = $this->mode === 'create' && $createdBills->count() > 1
            ? 'Tagihan berhasil dibuat untuk ' . $createdBills->count() . ' warga.'
            : 'Tagihan berhasil disimpan.';

        session()->flash('status', $message);
        $this->redirectRoute('admin.tagihan.index');
    }

    private function syncDatesFromPeriod(bool $autofill = true): void
    {
        if ($this->type !== 'iuran' || empty($this->iuran_period)) {
            return;
        }

        try {
            $period = Carbon::createFromFormat('Y-m', $this->iuran_period)->startOfMonth();
        } catch (\Throwable $e) {
            return;
        }

        $this->due_date = $period->clone()->endOfMonth()->format('Y-m-d');

        if (! $autofill) {
            return;
        }

        $this->issued_at = $period->clone()->startOfMonth()->format('Y-m-d');

        if ($this->mode === 'create') {
            if (! filled($this->amount) || (int) $this->amount <= 0) {
                $this->amount = (string) $this->defaultIuranAmount;
            }

            $suggestedTitle = 'Iuran Bulan ' . $period->locale('id')->translatedFormat('F Y');

            if (! filled($this->title) || str_starts_with($this->title, 'Iuran Bulan ')) {
                $this->title = $suggestedTitle;
            }

            $defaultDescription = 'Tagihan iuran kas bulanan periode ' . $period->translatedFormat('F Y');

            if (! filled($this->description) || Str::contains(Str::lower($this->description), 'tagihan iuran kas bulanan')) {
                $this->description = $defaultDescription;
            }
        }
    }

    private function generateMassIuran(array $validated): int
    {
        try {
            $period = Carbon::createFromFormat('Y-m', $validated['iuran_period'])->startOfMonth();
        } catch (\Throwable $e) {
            $this->addError('iuran_period', 'Format periode iuran tidak valid.');
            return 0;
        }

        $dueDate = $period->clone()->endOfMonth();
        $issuedAt = Carbon::parse($validated['issued_at'])->startOfDay();
        $amount = (int) $validated['amount'];
        $status = $validated['status'];

        $selectedIds = collect($this->selected_resident_ids)
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $query = User::residents()->where('status', 'aktif');

        if ($selectedIds->isNotEmpty()) {
            $query->whereIn('id', $selectedIds->all());
        }

        /** @var Collection<int,\App\Models\User> $users */
        $users = $query->get(['id']);

        if ($users->isEmpty()) {
            $this->addError('user_id', 'Tidak ditemukan warga aktif untuk dibuatkan iuran pada periode ini.');
            return 0;
        }

        $feeData = PaymentFeeEstimator::resolve()->estimate($amount);
        $adminId = Auth::id();

        return DB::transaction(function () use ($users, $validated, $dueDate, $issuedAt, $amount, $status, $feeData, $adminId) {
            $created = 0;

            foreach ($users as $user) {
                $alreadyExists = Bill::query()
                    ->where('user_id', $user->id)
                    ->where('type', 'iuran')
                    ->whereDate('due_date', $dueDate)
                    ->exists();

                if ($alreadyExists) {
                    continue;
                }

                Bill::create([
                    'user_id' => $user->id,
                    'type' => 'iuran',
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'amount' => $amount,
                    'gateway_fee' => $feeData['fee'],
                    'total_amount' => $feeData['total'],
                    'due_date' => $dueDate->copy(),
                    'status' => $status,
                    'invoice_number' => strtoupper(Str::ulid()),
                    'issued_at' => $issuedAt->copy(),
                    'created_by' => $adminId,
                ]);

                $created++;
            }

            return $created;
        });
    }
}
