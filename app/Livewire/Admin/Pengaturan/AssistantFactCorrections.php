<?php

namespace App\Livewire\Admin\Pengaturan;

use App\Models\AssistantFactCorrection;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class AssistantFactCorrections extends Component
{
    use WithPagination;

    public string $statusFilter = 'pending';
    public string $search = '';
    public int $perPage = 10;
    #[Url(history: true, as: 'page')]
    public int $page = 1;

    protected $queryString = [
        'statusFilter' => ['except' => 'pending'],
        'search' => ['except' => ''],
    ];

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.admin.pengaturan.assistant-fact-corrections', [
            'records' => $this->corrections(),
            'allowedStatuses' => $this->allowedStatuses(),
        ]);
    }

    public function setStatus(int $correctionId, string $status): void
    {
        if (!Schema::hasTable('assistant_fact_corrections')) {
            $this->dispatch('notify', title: 'Tabel tidak ditemukan', body: 'Table assistant_fact_corrections belum tersedia.', level: 'error');

            return;
        }

        if (!in_array($status, $this->allowedStatuses(), true)) {
            $this->dispatch('notify', title: 'Status tidak sah', body: 'Status pembaruan tidak dikenali.', level: 'error');

            return;
        }

        $correction = AssistantFactCorrection::find($correctionId);

        if (!$correction) {
            $this->dispatch('notify', title: 'Data tidak ditemukan', body: 'Koreksi sudah hilang atau dihapus.', level: 'error');

            return;
        }

        $payload = ['status' => $status];

        if (in_array($status, [AssistantFactCorrection::STATUS_APPLIED, AssistantFactCorrection::STATUS_NEEDS_REVIEW], true)) {
            $payload['reviewed_at'] = now();
        }

        if ($status === AssistantFactCorrection::STATUS_APPLIED && $correction->applied_at === null) {
            $payload['applied_at'] = now();
        }

        $correction->fill($payload)->save();

        $this->dispatch('notify', title: 'Status diperbarui', body: "Koreksi #{$correctionId} sekarang {$status}.");
    }

    private function corrections()
    {
        if (!Schema::hasTable('assistant_fact_corrections')) {
            return collect();
        }

        $query = AssistantFactCorrection::query()
            ->latest()
            ->when($this->statusFilter !== 'all', function ($builder) {
                $builder->where('status', $this->statusFilter);
            })
            ->when($this->search !== '', function ($builder) {
                $builder->where(function ($inner) {
                    $inner->where('source_feedback', 'like', '%' . $this->search . '%')
                        ->orWhere('fingerprint', 'like', '%' . $this->search . '%')
                        ->orWhere('entity_type', 'like', '%' . $this->search . '%');
                });
            });

        return $query->paginate($this->perPage);
    }

    private function allowedStatuses(): array
    {
        return [
            AssistantFactCorrection::STATUS_PENDING,
            AssistantFactCorrection::STATUS_QUEUED,
            AssistantFactCorrection::STATUS_EXISTING,
            AssistantFactCorrection::STATUS_APPLIED,
            AssistantFactCorrection::STATUS_NEEDS_REVIEW,
            AssistantFactCorrection::STATUS_ERROR,
        ];
    }
}
