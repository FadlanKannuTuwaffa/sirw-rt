<?php

namespace App\Livewire\Admin\Agenda;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Form extends Component
{
    public ?Event $event = null;

    public string $mode = 'create';
    public string $title = '';
    public ?string $description = null;
    public ?string $location = null;
    public string $start_at = '';
    public ?string $end_at = null;
    public bool $is_all_day = false;
    public bool $is_public = true;
    public string $status = 'scheduled';
    public array $reminder_offsets = [];

    public function mount($event = null): void
    {
        $eventModel = $event instanceof Event
            ? $event
            : (filled($event) ? Event::query()->find($event) : null);

        Log::info('Admin.Agenda.Form mount', [
            'auth_id' => auth()->id(),
            'event_param' => $eventModel?->id,
            'route' => request()->path(),
        ]);

        $this->start_at = now()->addDay()->format('Y-m-d\TH:i');

        if ($eventModel && $eventModel->exists) {
            $this->event = $eventModel;
            $this->mode = 'edit';

            $this->fill([
                'title' => $eventModel->title,
                'description' => $eventModel->description,
                'location' => $eventModel->location,
                'start_at' => optional($eventModel->start_at)->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i'),
                'end_at' => optional($eventModel->end_at)->format('Y-m-d\TH:i'),
                'is_all_day' => $eventModel->is_all_day,
                'is_public' => $eventModel->is_public,
                'status' => $eventModel->status,
            ]);

            $this->reminder_offsets = $eventModel->reminder_offsets ?? [];
        }
    }

    public function render()
    {
        $title = $this->mode === 'create' ? 'Agenda Baru' : 'Ubah Agenda';

        return view('livewire.admin.agenda.form', [
            'title' => $title,
        ])->layout('layouts.admin', [
            'title' => $title,
        ]);
    }

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:160'],
            'start_at' => ['required', 'date_format:Y-m-d\TH:i', 'after_or_equal:' . now()->subHour()->format('Y-m-d\TH:i')],
            'end_at' => ['nullable', 'date_format:Y-m-d\TH:i', 'after_or_equal:start_at'],
            'is_all_day' => ['boolean'],
            'is_public' => ['boolean'],
            'status' => ['required', Rule::in(['scheduled', 'completed', 'cancelled'])],
            'reminder_offsets' => ['array'],
            'reminder_offsets.*' => ['integer', 'min:1'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        $payload = [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'location' => $validated['location'] ?? null,
            'start_at' => Carbon::createFromFormat('Y-m-d\TH:i', $validated['start_at']),
            'end_at' => $validated['end_at'] ? Carbon::createFromFormat('Y-m-d\TH:i', $validated['end_at']) : null,
            'is_all_day' => $validated['is_all_day'],
            'is_public' => $validated['is_public'],
            'status' => $validated['status'],
            'reminder_offsets' => $this->reminder_offsets,
            'created_by' => Auth::id(),
        ];

        if ($this->mode === 'create') {
            $event = Event::create($payload);
        } else {
            $event = $this->event;
            $event->update($payload);
        }

        session()->flash('status', 'Agenda berhasil disimpan.');
        $this->redirectRoute('admin.agenda.index');
    }
}