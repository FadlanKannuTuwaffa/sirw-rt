<?php

namespace App\Support\Copilot;

use App\Models\Bill;
use App\Models\Event;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Collection;

class CopilotAggregator
{
    public function __construct(
        protected ConfigRepository $config
    ) {
    }

    public function snapshot(): array
    {
        $insights = $this->insights();
        $alerts = array_values(array_filter(
            $insights,
            fn (Insight $insight) => in_array($insight->severity, ['high', 'medium'], true)
        ));

        return [
            'generated_at' => now()->toIso8601String(),
            'insights' => array_map(fn (Insight $insight) => $insight->toArray(), $insights),
            'alerts' => array_map(fn (Insight $alert) => $alert->toArray(), $alerts),
            'actions' => array_map(fn (Action $action) => $action->toArray(), $this->actions($alerts)),
            'timeline' => array_map(fn (TimelineEntry $entry) => $entry->toArray(), $this->timeline()),
        ];
    }

    /**
     * @return Insight[]
     */
    public function insights(): array
    {
        $thresholds = $this->thresholds();
        $now = CarbonImmutable::now();

        $totalOutstanding = Bill::where('status', '!=', 'paid')->sum('amount');
        $overdueBills = Bill::where('status', '!=', 'paid')
            ->whereDate('due_date', '<', $now->toDateString())
            ->count();
        $nearDueBills = Bill::where('status', '!=', 'paid')
            ->whereBetween('due_date', [$now->toDateString(), $now->addDays(3)->toDateString()])
            ->count();
        $scheduledReminders = Event::where('status', 'scheduled')->count();

        $cashflowTrend = $this->cashflowTrend($now);
        $residentInactivity = $this->residentInactivity($now, $thresholds['inactivity_days']);

        $insights = [
            new Insight(
                id: 'outstanding-bills',
                title: 'Tagihan Belum Lunas',
                description: sprintf('Total tertunggak Rp %s dengan %d tagihan jatuh tempo.', number_format($totalOutstanding), $overdueBills),
                severity: $totalOutstanding >= $thresholds['outstanding_bills_high'] || $overdueBills >= 5 ? 'high' : ($totalOutstanding > 0 ? 'medium' : 'info'),
                tags: ['keuangan'],
                action: [
                    'label' => 'Tindak lanjuti tagihan',
                    'type' => 'route',
                    'payload' => route('admin.tagihan.index'),
                ],
                icon: 'credit-card'
            ),
            new Insight(
                id: 'cashflow-trend',
                title: 'Tren Arus Kas Mingguan',
                description: $cashflowTrend['description'],
                severity: $cashflowTrend['severity'],
                tags: ['kas'],
                action: [
                    'label' => 'Lihat detail kas',
                    'type' => 'route',
                    'payload' => route('admin.kas.index'),
                ],
                icon: 'chart-line'
            ),
            new Insight(
                id: 'reminder-health',
                title: 'Status Reminder Otomatis',
                description: $scheduledReminders > 0
                    ? sprintf('%d agenda/tagihan sudah terjadwal otomatis.', $scheduledReminders)
                    : 'Belum ada reminder otomatis aktif. Jadwalkan agar warga selalu teringat.',
                severity: $scheduledReminders > 0 ? 'info' : 'medium',
                tags: ['komunikasi'],
                action: [
                    'label' => 'Kelola reminder',
                    'type' => 'route',
                    'payload' => route('admin.reminder.automation'),
                ],
                icon: 'bell'
            ),
            new Insight(
                id: 'upcoming-bills',
                title: 'Tagihan Mendekati Jatuh Tempo',
                description: $nearDueBills > 0
                    ? sprintf('%d tagihan akan jatuh tempo dalam 3 hari ke depan.', $nearDueBills)
                    : 'Tidak ada tagihan jatuh tempo dalam 3 hari ke depan.',
                severity: $nearDueBills > 0 ? 'medium' : 'info',
                tags: ['keuangan'],
                action: [
                    'label' => 'Lihat jadwal tagihan',
                    'type' => 'route',
                    'payload' => route('admin.tagihan.index'),
                ],
                icon: 'calendar'
            ),
            new Insight(
                id: 'resident-inactivity',
                title: 'Aktivitas Warga',
                description: $residentInactivity['description'],
                severity: $residentInactivity['severity'],
                tags: ['partisipasi'],
                action: [
                    'label' => 'Lihat data warga',
                    'type' => 'route',
                    'payload' => route('admin.warga.index'),
                ],
                icon: 'users'
            ),
        ];

        return array_values(array_filter($insights, fn (Insight $insight) => !empty($insight->description)));
    }

    /**
     * @param  array<int, Insight>  $alerts
     * @return Action[]
     */
    public function actions(array $alerts = []): array
    {
        $actions = [
            new Action(
                id: 'copilot-open-dashboard',
                label: 'Buka dashboard analitik',
                description: 'Pantau metrik utama dan insight terbaru.',
                type: 'route',
                payload: route('admin.dashboard')
            ),
            new Action(
                id: 'copilot-open-reminder-playbook',
                label: 'Susun kampanye reminder',
                description: 'Atur ulang jadwal dan personalisasi pengingat otomatis.',
                type: 'route',
                payload: route('admin.reminder.automation')
            ),
            new Action(
                id: 'copilot-open-payments',
                label: 'Verifikasi pembayaran terbaru',
                description: 'Konfirmasi bukti bayar dan follow-up keterlambatan.',
                type: 'route',
                payload: route('admin.pembayaran.index')
            ),
        ];

        foreach ($alerts as $alert) {
            if ($alert->id === 'outstanding-bills') {
                $actions[] = new Action(
                    id: 'copilot-send-bill-reminders',
                    label: 'Kirim pengingat tagihan',
                    description: 'Aktifkan pengingat massal untuk tagihan tertunggak.',
                    type: 'command',
                    payload: 'copilot:send-bill-reminders',
                    meta: [
                        'severity' => $alert->severity,
                        'route' => route('admin.reminder.automation'),
                    ]
                );
            }
        }

        return $actions;
    }

    /**
     * @return TimelineEntry[]
     */
    public function timeline(): array
    {
        $now = CarbonImmutable::now();

        $recentPayments = Payment::with(['user', 'bill'])
            ->where('status', 'paid')
            ->latest('paid_at')
            ->limit(4)
            ->get();

        $newResidents = User::residents()
            ->latest()
            ->limit(3)
            ->get();

        $upcomingEvents = Event::whereIn('status', ['scheduled', 'draft'])
            ->where('start_at', '>=', $now->subDay())
            ->orderBy('start_at')
            ->limit(3)
            ->get();

        $entries = new Collection();

        foreach ($recentPayments as $payment) {
            $timestamp = $payment->paid_at ?? $payment->created_at ?? $now;
            $entries->push(new TimelineEntry(
                id: 'payment-' . $payment->id,
                icon: 'wallet',
                title: 'Pembayaran diterima',
                description: sprintf(
                    '%s membayar Rp %s untuk %s.',
                    $payment->user?->name ?? 'Warga',
                    number_format($payment->amount),
                    $payment->bill?->title ?? 'tagihan'
                ),
                time: $timestamp?->diffForHumans($now, true) . ' lalu',
                tags: ['pembayaran'],
                sortValue: $timestamp?->getTimestamp()
            ));
        }

        foreach ($newResidents as $resident) {
            $timestamp = $resident->created_at ?? $now;
            $entries->push(new TimelineEntry(
                id: 'resident-' . $resident->id,
                icon: 'user-plus',
                title: 'Warga baru terdaftar',
                description: $resident->name,
                time: $timestamp?->diffForHumans($now, true) . ' lalu',
                tags: ['warga'],
                sortValue: $timestamp?->getTimestamp()
            ));
        }

        foreach ($upcomingEvents as $event) {
            $timestamp = $event->start_at ?? $now;
            $entries->push(new TimelineEntry(
                id: 'event-' . $event->id,
                icon: 'calendar',
                title: 'Agenda mendatang',
                description: $event->title ?? 'Agenda komunitas',
                time: $timestamp?->diffForHumans($now, true) . ' lagi',
                tags: ['agenda'],
                sortValue: $timestamp?->getTimestamp()
            ));
        }

        return $entries
            ->sortByDesc(fn (TimelineEntry $entry) => $entry->sortValue ?? 0)
            ->slice(0, 8)
            ->values()
            ->all();
    }

    protected function thresholds(): array
    {
        $defaults = [
            'outstanding_bills_high' => 500000,
            'cashflow_drop_percent' => 20.0,
            'inactivity_days' => 5,
        ];

        $configured = (array) $this->config->get('copilot.thresholds', []);

        return array_merge($defaults, $configured);
    }

    protected function cashflowTrend(CarbonImmutable $now): array
    {
        $thresholds = $this->thresholds();

        $currentRange = [
            $now->startOfWeek(),
            $now->endOfWeek(),
        ];
        $previousRange = [
            $now->subWeek()->startOfWeek(),
            $now->subWeek()->endOfWeek(),
        ];

        $current = LedgerEntry::whereBetween('occurred_at', $currentRange)->sum('amount');
        $previous = LedgerEntry::whereBetween('occurred_at', $previousRange)->sum('amount');

        if ($previous == 0.0) {
            return [
                'description' => 'Data pembanding minggu lalu belum tersedia.',
                'severity' => 'info',
            ];
        }

        $delta = $current - $previous;
        $percent = ($delta / abs($previous)) * 100;

        if ($percent <= -$thresholds['cashflow_drop_percent']) {
            return [
                'description' => sprintf('Arus kas turun %.1f%% dibanding minggu lalu.', $percent),
                'severity' => 'high',
            ];
        }

        if ($percent < 0) {
            return [
                'description' => sprintf('Arus kas melemah %.1f%% dibanding minggu lalu.', $percent),
                'severity' => 'medium',
            ];
        }

        return [
            'description' => sprintf('Arus kas meningkat %.1f%% dibanding minggu lalu.', $percent),
            'severity' => 'info',
        ];
    }

    protected function residentInactivity(CarbonImmutable $now, int $inactivityDays): array
    {
        $inactiveResidents = User::residents()
            ->where(function ($query) use ($now, $inactivityDays) {
                $query->whereNull('last_seen_at')
                    ->orWhere('last_seen_at', '<', $now->subDays($inactivityDays));
            })
            ->count();

        if ($inactiveResidents === 0) {
            return [
                'description' => 'Mayoritas warga aktif dalam ' . $inactivityDays . ' hari terakhir.',
                'severity' => 'info',
            ];
        }

        return [
            'description' => sprintf('%d warga belum aktif dalam %d hari terakhir.', $inactiveResidents, $inactivityDays),
            'severity' => $inactiveResidents >= 5 ? 'medium' : 'info',
        ];
    }
}
