<?php

namespace App\Console\Commands;

use App\Models\AssistantFactCorrection;
use App\Models\Bill;
use App\Models\Event;
use App\Models\Payment;
use App\Models\User;
use App\Support\SensitiveData;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;

class ProcessFactCorrections extends Command
{
    protected $signature = 'assistant:process-fact-corrections {--limit=50 : Number of correction rows per batch}';

    protected $description = 'Apply queued fact-correction events to the transactional tables so DummyClient stops repeating stale data.';

    public function handle(): int
    {
        if (!Schema::hasTable('assistant_fact_corrections')) {
            $this->warn('assistant_fact_corrections table not found. Skipping.');

            return self::FAILURE;
        }

        $limit = max(1, (int) $this->option('limit'));
        $processed = 0;

        while (true) {
            $batch = AssistantFactCorrection::query()
                ->whereIn('status', [
                    AssistantFactCorrection::STATUS_PENDING,
                    AssistantFactCorrection::STATUS_QUEUED,
                    AssistantFactCorrection::STATUS_EXISTING,
                ])
                ->orderBy('created_at')
                ->limit($limit)
                ->get();

            if ($batch->isEmpty()) {
                break;
            }

            foreach ($batch as $correction) {
                DB::transaction(function () use ($correction, &$processed) {
                    $outcome = $this->applyCorrection($correction);
                    $processed++;
                    $this->line(sprintf(
                        '[%s] #%d %s (%s) => %s',
                        now()->toDateTimeString(),
                        $correction->id,
                        $correction->entity_type,
                        $correction->field,
                        $outcome
                    ));
                });
            }
        }

        $this->info("Processed {$processed} fact corrections.");

        return self::SUCCESS;
    }

    private function applyCorrection(AssistantFactCorrection $correction): string
    {
        try {
            $applied = match ($correction->entity_type) {
                'bill' => $this->applyBillCorrection($correction),
                'payment' => $this->applyPaymentCorrection($correction),
                'event' => $this->applyEventCorrection($correction),
                'resident' => $this->applyResidentCorrection($correction),
                default => false,
            };
        } catch (\Throwable $e) {
            Log::error('Failed to apply fact correction', [
                'id' => $correction->id,
                'entity_type' => $correction->entity_type,
                'field' => $correction->field,
                'error' => $e->getMessage(),
            ]);

            $applied = null;
        }

        if ($applied === true) {
            $correction->status = AssistantFactCorrection::STATUS_APPLIED;
            $correction->applied_at = now();
        } elseif ($applied === false) {
            $correction->status = AssistantFactCorrection::STATUS_NEEDS_REVIEW;
        } else {
            $correction->status = AssistantFactCorrection::STATUS_ERROR;
        }

        $correction->reviewed_at = now();
        $correction->save();

        return $correction->status;
    }

    private function applyBillCorrection(AssistantFactCorrection $correction): bool
    {
        $bill = $this->locateBill($correction->match_context ?? []);

        if (!$bill) {
            return false;
        }

        $value = $this->normalizeBillValue($correction->field, $correction);

        if ($value === null) {
            return false;
        }

        $bill->{$correction->field} = $value;

        if ($correction->field === 'status' && $value === 'paid' && $bill->paid_at === null) {
            $bill->paid_at = now();
        }

        if ($correction->field === 'status' && $value === 'unpaid') {
            $bill->paid_at = null;
        }

        $bill->save();

        return true;
    }

    private function applyPaymentCorrection(AssistantFactCorrection $correction): bool
    {
        $payment = $this->locatePayment($correction->match_context ?? []);

        if (!$payment) {
            return false;
        }

        $value = $this->normalizePaymentValue($correction->field, $correction);

        if ($value === null) {
            return false;
        }

        $payment->{$correction->field} = $value;

        if ($correction->field === 'status' && $value === 'paid' && $payment->paid_at === null) {
            $payment->paid_at = now();
        }

        if ($correction->field === 'status' && $value !== 'paid') {
            $payment->paid_at = null;
        }

        $payment->save();

        return true;
    }

    private function applyEventCorrection(AssistantFactCorrection $correction): bool
    {
        $event = $this->locateEvent($correction->match_context ?? []);

        if (!$event) {
            return false;
        }

        $value = $this->normalizeEventValue($correction->field, $correction);

        if ($value === null) {
            return false;
        }

        $event->{$correction->field} = $value;
        $event->save();

        return true;
    }

    private function applyResidentCorrection(AssistantFactCorrection $correction): bool
    {
        $user = $this->locateResident($correction->match_context ?? []);

        if (!$user) {
            return false;
        }

        $value = $this->normalizeResidentValue($correction->field, $correction);

        if ($value === null) {
            return false;
        }

        if ($correction->field === 'address') {
            $this->assignResidentAddress($user, $value);
        } else {
            $user->{$correction->field} = $value;
        }

        $user->save();

        return true;
    }

    private function locateBill(array $context): ?Bill
    {
        $query = Bill::query();
        $hasConstraint = false;

        if (($context['id'] ?? null) !== null) {
            return Bill::find((int) $context['id']);
        }

        if ($needle = $this->normalizeNeedle($context['needle'] ?? null)) {
            $hasConstraint = true;
            $query->where(function ($builder) use ($needle) {
                $builder
                    ->where('title', 'like', "%{$needle}%")
                    ->orWhere('type', 'like', "%{$needle}%")
                    ->orWhere('description', 'like', "%{$needle}%");
            });
        }

        foreach (Arr::wrap($context['keywords'] ?? []) as $keyword) {
            $keyword = $this->normalizeNeedle($keyword);
            if (!$keyword) {
                continue;
            }
            $hasConstraint = true;
            $query->where(function ($builder) use ($keyword) {
                $builder
                    ->where('title', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        return $hasConstraint ? $query->first() : null;
    }

    private function locatePayment(array $context): ?Payment
    {
        $query = Payment::query();
        $hasConstraint = false;

        if (($context['id'] ?? null) !== null) {
            return Payment::find((int) $context['id']);
        }

        if ($needle = $this->normalizeNeedle($context['needle'] ?? null)) {
            $hasConstraint = true;
            $query->where(function ($builder) use ($needle) {
                $builder
                    ->where('reference', 'like', "%{$needle}%")
                    ->orWhere('gateway', 'like', "%{$needle}%");
            });
        }

        foreach (Arr::wrap($context['keywords'] ?? []) as $keyword) {
            $keyword = $this->normalizeNeedle($keyword);
            if (!$keyword) {
                continue;
            }
            $hasConstraint = true;
            $query->where(function ($builder) use ($keyword) {
                $builder
                    ->where('reference', 'like', "%{$keyword}%")
                    ->orWhere('gateway', 'like', "%{$keyword}%");
            });
        }

        return $hasConstraint ? $query->first() : null;
    }

    private function locateEvent(array $context): ?Event
    {
        if (($context['id'] ?? null) !== null) {
            return Event::find((int) $context['id']);
        }

        $query = Event::query();
        $hasConstraint = false;

        if ($needle = $this->normalizeNeedle($context['needle'] ?? null)) {
            $hasConstraint = true;
            $query->where(function ($builder) use ($needle) {
                $builder
                    ->where('title', 'like', "%{$needle}%")
                    ->orWhere('location', 'like', "%{$needle}%");
            });
        }

        foreach (Arr::wrap($context['keywords'] ?? []) as $keyword) {
            $keyword = $this->normalizeNeedle($keyword);
            if (!$keyword) {
                continue;
            }
            $hasConstraint = true;
            $query->where(function ($builder) use ($keyword) {
                $builder
                    ->where('title', 'like', "%{$keyword}%")
                    ->orWhere('location', 'like', "%{$keyword}%");
            });
        }

        return $hasConstraint ? $query->first() : null;
    }

    private function locateResident(array $context): ?User
    {
        if (($context['id'] ?? null) !== null) {
            return User::find((int) $context['id']);
        }

        $query = User::query()->where('role', 'warga');
        $hasConstraint = false;
        $usersTable = (new User())->getTable();
        $hasAlamatColumn = Schema::hasColumn($usersTable, 'alamat');

        if ($needle = $this->normalizeNeedle($context['needle'] ?? null)) {
            $hasConstraint = true;
            $query->where(function ($builder) use ($needle, $hasAlamatColumn) {
                $builder->where('name', 'like', "%{$needle}%");
                if ($hasAlamatColumn) {
                    $builder->orWhere('alamat', 'like', "%{$needle}%");
                }
            });
        }

        foreach (Arr::wrap($context['keywords'] ?? []) as $keyword) {
            $keyword = $this->normalizeNeedle($keyword);
            if (!$keyword) {
                continue;
            }
            $hasConstraint = true;
            $query->where(function ($builder) use ($keyword) {
                $builder->where('name', 'like', "%{$keyword}%");
            });
        }

        return $hasConstraint ? $query->first() : null;
    }

    private function normalizeBillValue(string $field, AssistantFactCorrection $correction): mixed
    {
        $value = $this->rawValue($correction);

        return match ($field) {
            'amount', 'gateway_fee', 'total_amount' => $this->numericValue($value),
            'status' => $this->normalizeBillStatus($value),
            'title', 'description' => $this->stringValue($value),
            default => null,
        };
    }

    private function normalizePaymentValue(string $field, AssistantFactCorrection $correction): mixed
    {
        $value = $this->rawValue($correction);

        return match ($field) {
            'amount', 'fee_amount', 'customer_total' => $this->numericValue($value),
            'status' => $this->normalizePaymentStatus($value),
            default => null,
        };
    }

    private function normalizeEventValue(string $field, AssistantFactCorrection $correction): mixed
    {
        $value = $this->rawValue($correction);

        return match ($field) {
            'start_at' => $this->eventDateValue($value),
            'title', 'location' => $this->stringValue($value),
            default => null,
        };
    }

    private function normalizeResidentValue(string $field, AssistantFactCorrection $correction): mixed
    {
        $value = $this->rawValue($correction);

        return match ($field) {
            'phone' => $this->normalizePhoneValue($value),
            'status' => $this->stringValue($value),
            'address', 'name' => $this->stringValue($value),
            default => null,
        };
    }

    private function numericValue(?string $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/[^\d]/', '', $value);

        if ($digits === '') {
            return null;
        }

        return (int) $digits;
    }

    private function normalizeBillStatus(?string $value): ?string
    {
        $value = $this->stringValue($value);
        if ($value === null) {
            return null;
        }

        $map = [
            'paid' => 'paid',
            'unpaid' => 'unpaid',
            'outstanding' => 'unpaid',
            'overdue' => 'unpaid',
        ];

        return $map[$value] ?? null;
    }

    private function normalizePaymentStatus(?string $value): ?string
    {
        $value = $this->stringValue($value);
        if ($value === null) {
            return null;
        }

        $allowed = ['paid', 'pending', 'failed', 'cancelled', 'refunded'];

        return in_array($value, $allowed, true) ? $value : null;
    }

    private function stringValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = Str::of($value)->lower()->squish()->value();

        return $normalized === '' ? null : $normalized;
    }

    private function normalizeNeedle(?string $needle): ?string
    {
        if ($needle === null) {
            return null;
        }

        $needle = trim($needle);

        return $needle === '' ? null : $needle;
    }

    private function rawValue(AssistantFactCorrection $correction): ?string
    {
        $value = $correction->value ?? $correction->value_raw;

        return is_string($value) ? $value : null;
    }

    private function eventDateValue(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizePhoneValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = SensitiveData::normalizeDigits($value);

        if ($normalized === null || strlen($normalized) < 8) {
            return null;
        }

        return $normalized;
    }

    private function assignResidentAddress(User $user, string $value): void
    {
        $column = $this->residentAddressColumn();

        if ($column === null) {
            return;
        }

        if ($column === 'alamat') {
            $user->alamat = $value;

            return;
        }

        try {
            $user->alamat_encrypted = Crypt::encryptString($value);
        } catch (\Throwable $e) {
            Log::warning('Failed to encrypt resident address', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function residentAddressColumn(): ?string
    {
        static $column;

        if ($column !== null) {
            return $column;
        }

        $table = (new User())->getTable();

        if (Schema::hasColumn($table, 'alamat')) {
            $column = 'alamat';

            return $column;
        }

        if (Schema::hasColumn($table, 'alamat_encrypted')) {
            $column = 'alamat_encrypted';
        }

        return $column;
    }
}
