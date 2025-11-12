<?php

namespace App\Livewire\Admin\Reminder;

use App\Jobs\DynamicReminderDispatcher;
use App\Jobs\ProcessAssistantMaintenance;
use App\Models\Bill;
use App\Models\Event;
use App\Models\Reminder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Automation extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $bill_scope = 'single';

    public string $bill_search = '';
    public ?int $bill_id = null;
    public array $bill_presets = [];
    public ?string $bill_manual_at = null;

    public string $event_search = '';
    public ?int $event_id = null;
    public array $event_presets = [];
    public ?string $event_manual_at = null;

    protected array $billPresetOptions = [
        '1_jam' => '1 jam sebelum jatuh tempo',
        '6_jam' => '6 jam sebelum jatuh tempo',
        '1_hari' => '1 hari sebelum jatuh tempo',
        '3_hari' => '3 hari sebelum jatuh tempo',
        '7_hari' => '7 hari sebelum jatuh tempo',
    ];

    protected array $eventPresetOptions = [
        '30_menit' => '30 menit sebelum acara',
        '1_jam' => '1 jam sebelum acara',
        '3_jam' => '3 jam sebelum acara',
        '1_hari' => '1 hari sebelum acara',
    ];

    protected $queryString = [
        'bill_search' => ['except' => ''],
        'event_search' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->bill_manual_at = now()->addDay()->format('Y-m-d\TH:i');
        $this->event_manual_at = now()->addDay()->format('Y-m-d\TH:i');
    }

    public function updatingBillSearch(): void
    {
        $this->resetPage('billPage');
    }

    public function updatingEventSearch(): void
    {
        $this->resetPage('eventPage');
    }

    public function updatedBillId($value): void
    {
        if (! $value) {
            return;
        }

        $bill = Bill::select('id', 'due_date')->find($value);
        if ($bill && $bill->due_date) {
            $suggested = $bill->due_date->copy()->subHours(6);
            if ($suggested->lessThan(now())) {
                $suggested = now()->addMinutes(30);
            }
            $this->bill_manual_at = $suggested->format('Y-m-d\TH:i');
        }
    }

    public function updatedBillScope(string $value): void
    {
        if ($value === 'all') {
            $this->bill_id = null;
        }
    }

    public function updatedEventId($value): void
    {
        if (! $value) {
            return;
        }

        $event = Event::select('id', 'start_at')->find($value);
        if ($event && $event->start_at) {
            $suggested = $event->start_at->copy()->subHour();
            if ($suggested->lessThan(now())) {
                $suggested = now()->addMinutes(30);
            }
            $this->event_manual_at = $suggested->format('Y-m-d\TH:i');
        }
    }

    public function render()
    {
        $billCandidates = Bill::query()
            ->with('user:id,name')
            ->where('status', 'unpaid')
            ->when($this->bill_search, function (Builder $query) {
                $keyword = '%' . $this->bill_search . '%';
                $query->where(function (Builder $inner) use ($keyword) {
                    $inner->whereHas('user', fn (Builder $u) => $u->where('name', 'like', $keyword))
                        ->orWhere('invoice_number', 'like', $keyword)
                        ->orWhere('title', 'like', $keyword);
                });
            })
            ->orderByDesc('due_date')
            ->limit(20)
            ->get();

        $outstandingQuery = Bill::query()->where('status', 'unpaid');
        $nextOutstanding = (clone $outstandingQuery)->orderBy('due_date')->first();
        $outstandingTotal = (clone $outstandingQuery)
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
            ->value('outstanding_total') ?? 0;
        $outstandingSummary = [
            'count' => (clone $outstandingQuery)->count(),
            'total' => (float) $outstandingTotal,
            'next_due' => optional($nextOutstanding?->due_date)->translatedFormat('d M Y'),
        ];

        $eventCandidates = Event::query()
            ->where('start_at', '>=', now())
            ->when($this->event_search, function (Builder $query) {
                $keyword = '%' . $this->event_search . '%';
                $query->where(function (Builder $inner) use ($keyword) {
                    $inner->where('title', 'like', $keyword)
                        ->orWhere('description', 'like', $keyword)
                        ->orWhere('location', 'like', $keyword);
                });
            })
            ->orderBy('start_at')
            ->limit(15)
            ->get();

        $billReminders = Reminder::query()
            ->with('model')
            ->where('model_type', Bill::class)
            ->orderByDesc('send_at')
            ->paginate(8, ['*'], 'billPage');

        $eventReminders = Reminder::query()
            ->with('model')
            ->where('model_type', Event::class)
            ->orderByDesc('send_at')
            ->paginate(8, ['*'], 'eventPage');

        $billStats = [
            'scheduled' => Reminder::where('model_type', Bill::class)
                ->whereNull('sent_at')
                ->count(),
            'today' => Reminder::where('model_type', Bill::class)
                ->whereDate('send_at', today())
                ->whereNull('sent_at')
                ->count(),
            'sent_today' => Reminder::where('model_type', Bill::class)
                ->whereDate('sent_at', today())
                ->count(),
        ];

        $eventStats = [
            'scheduled' => Reminder::where('model_type', Event::class)
                ->whereNull('sent_at')
                ->count(),
            'today' => Reminder::where('model_type', Event::class)
                ->whereDate('send_at', today())
                ->whereNull('sent_at')
                ->count(),
            'sent_today' => Reminder::where('model_type', Event::class)
                ->whereDate('sent_at', today())
                ->count(),
        ];

        return view('livewire.admin.reminder.automation', [
            'billCandidates' => $billCandidates,
            'eventCandidates' => $eventCandidates,
            'billReminders' => $billReminders,
            'eventReminders' => $eventReminders,
            'billPresetOptions' => $this->billPresetOptions,
            'eventPresetOptions' => $this->eventPresetOptions,
            'billStats' => $billStats,
            'eventStats' => $eventStats,
            'outstandingSummary' => $outstandingSummary,
        ])->layout('layouts.admin', [
            'title' => 'Reminder Automatis',
        ]);
    }

    public function scheduleBillReminders(): void
    {
        $rules = [
            'bill_scope' => ['required', Rule::in(['single', 'all'])],
            'bill_presets' => ['array'],
            'bill_presets.*' => [Rule::in(array_keys($this->billPresetOptions))],
            'bill_manual_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
        ];

        if ($this->bill_scope === 'single') {
            $rules['bill_id'] = [
                'required',
                Rule::exists('bills', 'id')->where(fn ($q) => $q->where('status', 'unpaid')),
            ];
        }

        $this->validate($rules);

        if (empty($this->bill_presets) && blank($this->bill_manual_at)) {
            $this->addError('bill_presets', 'Pilih minimal satu preset atau atur waktu custom.');
            return;
        }

        $manualAt = null;
        if (filled($this->bill_manual_at)) {
            $manualAt = Carbon::parse($this->bill_manual_at, config('app.timezone'));
        }

        $targets = $this->bill_scope === 'all'
            ? Bill::query()->where('status', 'unpaid')->with('reminders', 'user')->get()
            : Bill::query()->where('status', 'unpaid')->with('reminders', 'user')->where('id', $this->bill_id)->get();

        if ($targets->isEmpty()) {
            $this->addError($this->bill_scope === 'all' ? 'bill_scope' : 'bill_id', 'Tidak ada tagihan belum lunas yang cocok untuk dijadwalkan.');
            return;
        }

        $created = 0;

        foreach ($targets as $bill) {
            $created += $this->scheduleBillFor($bill, $this->bill_presets, $manualAt);
        }

        $this->bill_presets = [];

        if ($this->bill_scope === 'single') {
            $this->updatedBillId($this->bill_id);
        } else {
            $this->bill_id = null;
            $this->bill_manual_at = now()->addDay()->format('Y-m-d\TH:i');
        }

        DynamicReminderDispatcher::dispatchSync();
        ProcessAssistantMaintenance::dispatch();

        session()->flash('status', $created . ' reminder tagihan berhasil dijadwalkan.');
        $this->resetPage('billPage');
    }

    private function scheduleBillFor(Bill $bill, array $presets, ?Carbon $manualAt): int
    {
        $created = 0;

        foreach ($presets as $preset) {
            $sendAt = $this->resolveBillPreset($bill, $preset);

            if ($sendAt->lessThan(now())) {
                $sendAt = now()->addMinutes(5);
            }

            $bill->reminders()
                ->where('payload->preset', $preset)
                ->whereNull('sent_at')
                ->delete();

            $bill->reminders()->create([
                'channel' => 'multi',
                'send_at' => $sendAt,
                'status' => 'scheduled',
                'payload' => ['preset' => $preset, 'context' => 'bill'],
            ]);

            $created++;
        }

        if ($manualAt) {
            $scheduledAt = $manualAt->copy();
            if ($scheduledAt->lessThan(now())) {
                $scheduledAt = now()->addMinutes(10);
            }

            $bill->reminders()
                ->whereNull('payload->preset')
                ->whereNull('sent_at')
                ->delete();

            $bill->reminders()->create([
                'channel' => 'multi',
                'send_at' => $scheduledAt,
                'status' => 'scheduled',
                'payload' => ['preset' => null, 'context' => 'bill'],
            ]);

            $created++;
        }

        return $created;
    }

    public function scheduleEventReminders(): void
    {
        $this->validate([
            'event_id' => ['required', Rule::exists('events', 'id')],
            'event_presets' => ['array'],
            'event_presets.*' => [Rule::in(array_keys($this->eventPresetOptions))],
            'event_manual_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
        ]);

        if (empty($this->event_presets) && blank($this->event_manual_at)) {
            $this->addError('event_presets', 'Pilih minimal satu preset atau atur waktu custom.');
            return;
        }

        $event = Event::with('reminders')->find($this->event_id);

        if (! $event) {
            $this->addError('event_id', 'Agenda tidak ditemukan.');
            return;
        }

        if ($event->start_at && $event->start_at->isPast()) {
            $this->addError('event_id', 'Agenda telah dimulai atau selesai sehingga tidak bisa dijadwalkan ulang.');
            return;
        }

        $manualAt = filled($this->event_manual_at)
            ? Carbon::createFromFormat('Y-m-d\TH:i', $this->event_manual_at)
            : null;

        $created = $this->scheduleEventFor($event, $this->event_presets, $manualAt);

        $this->event_presets = [];
        $this->updatedEventId($this->event_id);

        DynamicReminderDispatcher::dispatchSync();
        ProcessAssistantMaintenance::dispatch();

        session()->flash('status', $created . ' reminder agenda berhasil dijadwalkan.');
        $this->resetPage('eventPage');
    }

    private function scheduleEventFor(Event $event, array $presets, ?Carbon $manualAt): int
    {
        $created = 0;

        foreach ($presets as $preset) {
            $sendAt = $this->resolveEventPreset($event, $preset);

            if ($sendAt->lessThan(now())) {
                $sendAt = now()->addMinutes(5);
            }

            $event->reminders()
                ->where('payload->preset', $preset)
                ->whereNull('sent_at')
                ->delete();

            $event->reminders()->create([
                'channel' => 'multi',
                'send_at' => $sendAt,
                'status' => 'scheduled',
                'payload' => ['preset' => $preset, 'context' => 'event'],
            ]);

            $created++;
        }

        if ($manualAt) {
            $scheduledAt = $manualAt->copy();
            if ($scheduledAt->lessThan(now())) {
                $scheduledAt = now()->addMinutes(10);
            }

            if ($event->start_at && $scheduledAt->greaterThanOrEqualTo($event->start_at)) {
                $scheduledAt = $event->start_at->copy()->subMinutes(10);
                if ($scheduledAt->lessThan(now())) {
                    $scheduledAt = now()->addMinutes(5);
                }
            }

            $event->reminders()
                ->whereNull('payload->preset')
                ->whereNull('sent_at')
                ->delete();

            $event->reminders()->create([
                'channel' => 'multi',
                'send_at' => $scheduledAt,
                'status' => 'scheduled',
                'payload' => ['preset' => null, 'context' => 'event'],
            ]);

            $created++;
        }

        return $created;
    }

    public function cancelReminder(int $reminderId): void
    {
        $reminder = Reminder::find($reminderId);

        if (! $reminder) {
            return;
        }

        if ($reminder->sent_at) {
            session()->flash('status', 'Reminder sudah dikirim dan tidak dapat dibatalkan.');
            return;
        }

        $reminder->delete();

        session()->flash('status', 'Reminder berhasil dibatalkan.');

        if ($reminder->model_type === Bill::class) {
            $this->resetPage('billPage');
        } else {
            $this->resetPage('eventPage');
        }
    }

    private function resolveBillPreset(Bill $bill, string $preset): Carbon
    {
        $dueDate = $bill->due_date
            ? $bill->due_date->copy()->endOfDay()
            : now()->addDay();

        return match ($preset) {
            '1_jam' => $dueDate->clone()->subHour(),
            '6_jam' => $dueDate->clone()->subHours(6),
            '1_hari' => $dueDate->clone()->subDay(),
            '3_hari' => $dueDate->clone()->subDays(3),
            '7_hari' => $dueDate->clone()->subDays(7),
            default => $dueDate->clone()->subDay(),
        };
    }

    private function resolveEventPreset(Event $event, string $preset): Carbon
    {
        $startAt = $event->start_at
            ? $event->start_at->copy()
            : now()->addHours(2);

        return match ($preset) {
            '30_menit' => $startAt->clone()->subMinutes(30),
            '1_jam' => $startAt->clone()->subHour(),
            '3_jam' => $startAt->clone()->subHours(3),
            '1_hari' => $startAt->clone()->subDay(),
            default => $startAt->clone()->subHour(),
        };
    }
}
