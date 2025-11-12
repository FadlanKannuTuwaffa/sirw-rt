<?php

namespace App\Livewire\Admin\Pengaturan;

use App\Models\AssistantToolBlueprint;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class ToolBlueprintManager extends Component
{
    use WithPagination;

    protected array $layoutData = [
        'title' => 'Pengaturan',
        'titleClass' => 'text-white',
    ];

    public string $status = 'pending';
    public string $search = '';
    public array $noteDrafts = [];

    protected $queryString = [
        'status' => ['except' => 'pending'],
        'search' => ['except' => ''],
    ];

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function markStatus(int $blueprintId, string $status): void
    {
        $allowed = ['pending', 'in_progress', 'implemented', 'rejected'];
        if (!in_array($status, $allowed, true)) {
            return;
        }

        $blueprint = AssistantToolBlueprint::findOrFail($blueprintId);
        $blueprint->status = $status;

        if ($status === 'implemented') {
            $blueprint->implemented_at = now();
        }

        if ($status !== 'implemented') {
            $blueprint->implemented_at = null;
        }

        $blueprint->save();

        session()->flash('banner-message', [
            'type' => 'success',
            'message' => 'Status blueprint diperbarui.',
        ]);
    }

    public function saveNote(int $blueprintId): void
    {
        $note = Str::of($this->noteDrafts[$blueprintId] ?? '')
            ->trim()
            ->limit(500, '...')
            ->value();

        $blueprint = AssistantToolBlueprint::findOrFail($blueprintId);
        $blueprint->notes = $note === '' ? null : $note;
        $blueprint->save();

        session()->flash('banner-message', [
            'type' => 'success',
            'message' => 'Catatan blueprint disimpan.',
        ]);
    }

    public function render()
    {
        $query = AssistantToolBlueprint::query();

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        if ($this->search !== '') {
            $term = Str::lower($this->search);
            $query->where(function ($builder) use ($term) {
                $builder
                    ->whereRaw('LOWER(intent) like ?', ["%{$term}%"])
                    ->orWhereRaw('LOWER(sample_failure) like ?', ["%{$term}%"])
                    ->orWhereRaw('LOWER(notes) like ?', ["%{$term}%"]);
            });
        }

        $blueprints = $query
            ->orderByRaw("FIELD(status, 'pending','in_progress','implemented','rejected')")
            ->orderByDesc('failure_rate')
            ->paginate(10);

        foreach ($blueprints as $blueprint) {
            if (!array_key_exists($blueprint->id, $this->noteDrafts)) {
                $this->noteDrafts[$blueprint->id] = $blueprint->notes;
            }
        }

        return view('livewire.admin.pengaturan.tool-blueprint-manager', [
            'blueprints' => $blueprints,
        ])->title('Tool Blueprint Manager');
    }
}
