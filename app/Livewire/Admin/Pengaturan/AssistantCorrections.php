<?php

namespace App\Livewire\Admin\Pengaturan;

use App\Models\AssistantCorrection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AssistantCorrections extends Component
{
    public string $alias = '';
    public string $canonical = '';
    public ?string $notes = null;
    public ?string $expires_at = null;

    public function render()
    {
        return view('livewire.admin.pengaturan.assistant-corrections', [
            'corrections' => AssistantCorrection::query()
                ->orderByDesc('is_active')
                ->orderBy('alias')
                ->limit(40)
                ->get(),
        ]);
    }

    public function save(): void
    {
        $data = $this->validate([
            'alias' => [
                'required',
                'string',
                'min:2',
                'max:120',
                Rule::unique('assistant_corrections', 'alias'),
            ],
            'canonical' => ['required', 'string', 'min:2', 'max:120'],
            'notes' => ['nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date'],
        ]);

        AssistantCorrection::create([
            'alias' => $data['alias'],
            'canonical' => $data['canonical'],
            'notes' => $data['notes'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'is_active' => true,
            'created_by' => Auth::id(),
        ]);

        $this->reset(['alias', 'canonical', 'notes', 'expires_at']);
        session()->flash('assistant_corrections_status', 'Koreksi manual ditambahkan.');
    }

    public function toggle(int $id): void
    {
        $correction = AssistantCorrection::find($id);

        if ($correction === null) {
            return;
        }

        $correction->is_active = !$correction->is_active;
        $correction->save();
    }

    public function delete(int $id): void
    {
        $correction = AssistantCorrection::find($id);

        if ($correction === null) {
            return;
        }

        $correction->delete();
    }
}
