<?php

namespace App\Livewire\Admin\Agenda;

use App\Models\Event;
use Carbon\CarbonInterface;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = 'all';
    public ?string $from = null;
    public ?string $to = null;
    protected array $layoutData = [
        'title' => 'Agenda Kegiatan',
    ];

    public function render()
    {
        $this->syncAutoCompletion();

        $now = now();
        $statusOptions = [
            'all' => 'Semua',
            'today' => 'Hari Ini',
            'upcoming' => 'Mendatang',
            'ongoing' => 'Sedang Berlangsung',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            'past' => 'Sudah Lewat',
        ];

        $events = Event::query()
            ->with('creator:id,name')
            ->when($this->search, function ($q) {
                $keyword = '%' . $this->search . '%';
                $q->where(function ($query) use ($keyword) {
                    $query->where('title', 'like', $keyword)
                        ->orWhere('description', 'like', $keyword)
                        ->orWhere('location', 'like', $keyword);
                });
            })
            ->when($this->status !== 'all', function ($query) use ($now) {
                switch ($this->status) {
                    case 'today':
                        $query->whereDate('start_at', $now->toDateString());
                        break;
                    case 'upcoming':
                        $query->where('status', 'scheduled')
                            ->where('start_at', '>', $now);
                        break;
                    case 'ongoing':
                        $query->where('status', 'scheduled')
                            ->where('start_at', '<=', $now)
                            ->where(function ($timeScope) use ($now) {
                                $timeScope->where(function ($withEnd) use ($now) {
                                    $withEnd->whereNotNull('end_at')
                                        ->where('end_at', '>=', $now);
                                })->orWhere(function ($noEnd) use ($now) {
                                    $noEnd->whereNull('end_at')
                                        ->whereDate('start_at', $now->toDateString());
                                });
                            });
                        break;
                    case 'completed':
                        $query->where('status', 'completed');
                        break;
                    case 'cancelled':
                        $query->where('status', 'cancelled');
                        break;
                    case 'past':
                        $query->where('status', 'scheduled')
                            ->where('start_at', '<', $now)
                            ->where(function ($timeScope) use ($now) {
                                $timeScope->where(function ($withEnd) use ($now) {
                                    $withEnd->whereNotNull('end_at')
                                        ->where('end_at', '<', $now);
                                })->orWhere(function ($noEnd) use ($now) {
                                    $noEnd->whereNull('end_at')
                                        ->whereDate('start_at', '<', $now->toDateString());
                                });
                            });
                        break;
                }
            })
            ->when($this->from, fn ($q) => $q->whereDate('start_at', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('start_at', '<=', $this->to))
            ->orderBy('start_at')
            ->paginate(10);

        $stats = $this->buildStats($now);

        return view('livewire.admin.agenda.index', [
            'events' => $events,
            'stats' => $stats,
            'statusOptions' => $statusOptions,
        ]);
    }

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => 'all'],
        'from' => ['except' => null],
        'to' => ['except' => null],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFrom(): void
    {
        $this->resetPage();
    }

    public function updatingTo(): void
    {
        $this->resetPage();
    }

    public function markCompleted(int $eventId): void
    {
        $event = Event::findOrFail($eventId);
        if ($event->status === 'completed') {
            session()->flash('status', 'Agenda sudah diselesaikan.');
            return;
        }

        $updates = ['status' => 'completed'];

        if ($event->is_all_day) {
            $updates['end_at'] = now();
        }

        $event->update($updates);
        session()->flash('status', 'Agenda ditandai selesai.');
    }

    public function cancelEvent(int $eventId): void
    {
        $event = Event::findOrFail($eventId);
        $event->update(['status' => 'cancelled']);
        session()->flash('status', 'Agenda dibatalkan.');
    }

    public function deleteEvent(int $eventId): void
    {
        Event::whereKey($eventId)->delete();
        session()->flash('status', 'Agenda dihapus.');
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'from', 'to']);
        $this->status = 'all';
        $this->resetPage();
    }

    protected function syncAutoCompletion(): void
    {
        $now = now();

        Event::query()
            ->where('status', 'scheduled')
            ->where('is_all_day', false)
            ->whereNotNull('start_at')
            ->whereNotNull('end_at')
            ->where('start_at', '<=', $now)
            ->where('end_at', '<=', $now)
            ->update(['status' => 'completed']);
    }

    protected function buildStats(CarbonInterface $now): array
    {
        $today = $now->toDateString();
        $scheduledBase = Event::query()->where('status', 'scheduled');

        $ongoingScope = function ($query) use ($now, $today) {
            $query->where('start_at', '<=', $now)
                ->where(function ($timeScope) use ($now, $today) {
                    $timeScope->where(function ($withEnd) use ($now) {
                        $withEnd->whereNotNull('end_at')
                            ->where('end_at', '>=', $now);
                    })->orWhere(function ($noEnd) use ($today) {
                        $noEnd->whereNull('end_at')
                            ->whereDate('start_at', $today);
                    });
                });
        };

        $pastScope = function ($query) use ($now, $today) {
            $query->where('start_at', '<', $now)
                ->where(function ($timeScope) use ($now, $today) {
                    $timeScope->where(function ($withEnd) use ($now) {
                        $withEnd->whereNotNull('end_at')
                            ->where('end_at', '<', $now);
                    })->orWhere(function ($noEnd) use ($today) {
                        $noEnd->whereNull('end_at')
                            ->whereDate('start_at', '<', $today);
                    });
                });
        };

        $ongoingQuery = (clone $scheduledBase);
        $ongoingScope($ongoingQuery);

        $pastQuery = (clone $scheduledBase);
        $pastScope($pastQuery);

        return [
            'all' => Event::count(),
            'today' => Event::whereDate('start_at', $today)->count(),
            'upcoming' => (clone $scheduledBase)->where('start_at', '>', $now)->count(),
            'ongoing' => $ongoingQuery->count(),
            'completed' => Event::where('status', 'completed')->count(),
            'cancelled' => Event::where('status', 'cancelled')->count(),
            'past' => $pastQuery->count(),
        ];
    }
}
