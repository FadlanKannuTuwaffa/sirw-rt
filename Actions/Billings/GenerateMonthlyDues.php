<?php

namespace App\Actions\Billings;

use App\Models\Bill;
use App\Models\User;
use App\Services\Payments\PaymentFeeEstimator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateMonthlyDues
{
    /**
     * Generate dues for active residents for a given period.
     */
    public function __invoke(Carbon $period, int $amount, int $adminId, ?array $userIds = null): int
    {
        $period = $period->copy()->startOfMonth();
        $dueDate = $period->clone()->endOfMonth();
        $issuedAt = now();

        $query = User::query()
            ->where('role', 'warga')
            ->where('status', 'aktif');

        if ($userIds) {
            $query->whereIn('id', $userIds);
        }

        $users = $query->get();

        return DB::transaction(function () use ($users, $amount, $dueDate, $period, $issuedAt, $adminId) {
            $created = 0;
            $feeData = PaymentFeeEstimator::resolve()->estimate($amount);

            foreach ($users as $user) {
                $exists = Bill::query()
                    ->where('user_id', $user->id)
                    ->where('type', 'iuran')
                    ->whereDate('due_date', $dueDate)
                    ->exists();

                if ($exists) {
                    continue;
                }

                Bill::create([
                    'user_id' => $user->id,
                    'type' => 'iuran',
                    'title' => 'Iuran Bulan ' . $period->locale('id')->translatedFormat('F Y'),
                    'description' => 'Tagihan iuran kas bulanan periode ' . $period->translatedFormat('F Y'),
                    'amount' => $amount,
                    'gateway_fee' => $feeData['fee'],
                    'total_amount' => $feeData['total'],
                    'due_date' => $dueDate,
                    'status' => 'unpaid',
                    'invoice_number' => strtoupper(Str::ulid()),
                    'issued_at' => $issuedAt,
                    'created_by' => $adminId,
                ]);

                $created++;
            }

            return $created;
        });
    }
}
