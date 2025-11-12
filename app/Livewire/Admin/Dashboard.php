<?php

namespace App\Livewire\Admin;

use App\Models\Bill;
use App\Models\Event;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\Reminder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $now = now();

        $residentCount = User::residents()->count();
        $activeResidents = User::residents()->where('status', 'aktif')->count();
        $onlineResidents = User::residents()->where('last_seen_at', '>=', $now->clone()->subMinutes(3))->count();

        $totalOutstandingBills = (float) (Bill::query()
            ->where('status', '!=', 'paid')
            ->selectRaw("
                SUM(GREATEST(
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
                )) as outstanding_total
            ")
            ->value('outstanding_total') ?? 0);
        $totalPaidThisMonth = Payment::where('status', 'paid')
            ->whereBetween('paid_at', [$now->clone()->startOfMonth(), $now->clone()->endOfMonth()])
            ->sum('amount');
        $billsGeneratedThisMonth = Bill::whereBetween('issued_at', [$now->clone()->startOfMonth(), $now->clone()->endOfMonth()])->count();

        $recentPayments = Payment::with(['user', 'bill'])
            ->where('status', 'paid')
            ->latest('paid_at')
            ->limit(6)
            ->get();

        $overdueBills = Bill::with('user')
            ->where('status', '!=', 'paid')
            ->whereDate('due_date', '<', $now->toDateString())
            ->orderBy('due_date')
            ->limit(6)
            ->get();

        $upcomingEvents = Event::where('status', 'scheduled')
            ->where('start_at', '>=', $now)
            ->orderBy('start_at')
            ->limit(5)
            ->get();

        $cashFlow = $this->monthlyCashFlow();
        $latestActivities = $this->latestActivities();

        return view('livewire.admin.dashboard', [
            'stats' => [
                'residents' => $residentCount,
                'active_residents' => $activeResidents,
                'online_residents' => $onlineResidents,
                'outstanding_bills' => $totalOutstandingBills,
                'paid_this_month' => $totalPaidThisMonth,
                'bills_generated' => $billsGeneratedThisMonth,
                'today_income' => Payment::where('status', 'paid')
                    ->whereDate('paid_at', $now->toDateString())
                    ->sum('amount'),
                'today_expense' => LedgerEntry::whereDate('occurred_at', $now->toDateString())
                    ->where('status', 'paid')
                    ->where('amount', '<', 0)
                    ->sum(DB::raw('ABS(amount)')),
                'reminders' => Reminder::where('status', 'scheduled')->count(),
                'new_residents' => User::residents()
                    ->whereBetween('created_at', [$now->clone()->startOfMonth(), $now->clone()->endOfMonth()])
                    ->count(),
                'reminders_sent' => Reminder::query()
                    ->whereNotNull('sent_at')
                    ->whereBetween('sent_at', [
                        $now->clone()->startOfWeek(),
                        $now->clone()->endOfWeek(),
                    ])
                    ->count(),
            ],
            'recentPayments' => $recentPayments,
            'overdueBills' => $overdueBills,
            'upcomingEvents' => $upcomingEvents,
            'cashFlow' => $cashFlow,
            'latestActivities' => $latestActivities,
        ])->layout('layouts.admin', [
            'title' => 'Dashboard Admin',
        ]);
    }

    private function monthlyCashFlow(): Collection
    {
        $start = now()->clone()->subMonths(5)->startOfMonth();
        $end = now()->clone()->endOfMonth();

        $entries = LedgerEntry::query()
            ->whereBetween('occurred_at', [$start, $end])
            ->where('status', 'paid')
            ->get()
            ->groupBy(fn ($entry) => Carbon::parse($entry->occurred_at)->format('Y-m'));

        return collect(range(0, 5))->map(function ($offset) use ($start, $entries) {
            $month = $start->clone()->addMonths($offset);
            $key = $month->format('Y-m');
            $data = $entries->get($key, collect());

            return [
                'label' => $month->translatedFormat('M Y'),
                'income' => $data->where('amount', '>', 0)->sum('amount'),
                'expense' => abs($data->where('amount', '<', 0)->sum('amount')),
                'net' => $data->sum('amount'),
            ];
        });
    }

    private function latestActivities(): array
    {
        $recentBills = Bill::latest()->limit(3)->get()->map(function (Bill $bill) {
            $timestamp = $bill->created_at;

            return [
                'title' => 'Tagihan ' . ($bill->title ?? 'Tanpa Judul'),
                'description' => 'Ditambahkan untuk ' . ($bill->user?->name ?? 'warga'),
                'timestamp' => $timestamp ?? now(),
                'time' => optional($timestamp)->diffForHumans() ?? '-',
            ];
        })->toBase();

        $recentPayments = Payment::where('status', 'paid')
            ->latest('paid_at')
            ->limit(3)
            ->get()
            ->map(function (Payment $payment) {
                $timestamp = $payment->paid_at ?? $payment->created_at;

                return [
                    'title' => 'Pembayaran ' . ($payment->bill?->title ?? 'tagihan'),
                    'description' => 'Dibayar oleh ' . ($payment->user?->name ?? 'warga') . ' sebesar Rp ' . number_format($payment->amount),
                    'timestamp' => $timestamp ?? now(),
                    'time' => optional($timestamp)->diffForHumans() ?? '-',
                ];
            })->toBase();

        $recentResidents = User::residents()
            ->latest()
            ->limit(3)
            ->get()
            ->map(function (User $user) {
                $timestamp = $user->created_at;

                return [
                    'title' => 'Warga baru terdaftar',
                    'description' => $user->name,
                    'timestamp' => $timestamp ?? now(),
                    'time' => optional($timestamp)->diffForHumans() ?? '-',
                ];
            })->toBase();

        return $recentBills
            ->merge($recentPayments)
            ->merge($recentResidents)
            ->sortByDesc(fn ($item) => $item['timestamp'] ?? now())
            ->values()
            ->all();
    }
}
