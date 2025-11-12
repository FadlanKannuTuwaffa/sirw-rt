<?php

namespace App\Livewire\Admin\Pengaturan;

use App\Jobs\RunAssistantRegression;
use App\Models\AssistantLlmSnapshot;
use App\Models\AssistantLlmSnapshotReview;
use App\Services\Assistant\Support\LlmSnapshotManager;
use App\Services\Assistant\Support\LlmSnapshotPromoter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class LlmSnapshotCandidates extends Component
{
    use WithPagination;

    protected array $layoutData = [
        'title' => 'Pengaturan',
        'titleClass' => 'text-white',
    ];

    public string $search = '';
    public string $status = 'pending';
    public bool $onlyAutoReady = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => 'pending'],
        'onlyAutoReady' => ['except' => false],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingOnlyAutoReady(): void
    {
        $this->resetPage();
    }

    public function promoteSnapshot(int $snapshotId, string $mode = 'auto', LlmSnapshotPromoter $promoter): void
    {
        $snapshot = AssistantLlmSnapshot::with('interaction')->findOrFail($snapshotId);

        $result = $promoter->promote($snapshot, $mode);
        $promoter->persistResult($snapshot, $result);
        $this->logReview($snapshot->id, 'manual_' . $mode, $result['message'] ?? null, $result['payload'] ?? []);

        if (in_array($result['status'] ?? null, ['promoted', 'kb_ingested'], true)) {
            RunAssistantRegression::dispatch($snapshot->id);
        }

        $this->dispatch('banner-message', type: 'success', message: $result['message'] ?? 'Promosi selesai.');
    }

    public function markNeedsReview(int $snapshotId): void
    {
        $snapshot = AssistantLlmSnapshot::findOrFail($snapshotId);
        $snapshot->update([
            'needs_review' => true,
            'promotion_status' => 'needs_review',
        ]);

        $this->logReview($snapshot->id, 'needs_review', 'Ditandai perlu kurasi manual.');

        $this->dispatch('banner-message', type: 'warning', message: 'Snapshot ditandai perlu review.');
    }

    public function dismissSnapshot(int $snapshotId): void
    {
        $snapshot = AssistantLlmSnapshot::findOrFail($snapshotId);
        $snapshot->update([
            'needs_review' => false,
            'promotion_status' => 'rejected',
            'auto_promote_ready' => false,
        ]);

        $this->logReview($snapshot->id, 'rejected', 'Snapshot ditolak.');

        $this->dispatch('banner-message', type: 'info', message: 'Snapshot ditolak.');
    }

    public function markEvaluationPass(int $snapshotId, LlmSnapshotManager $manager): void
    {
        $snapshot = AssistantLlmSnapshot::findOrFail($snapshotId);
        $manager->markEvaluation($snapshot->assistant_interaction_log_id, 'PASS', true);

        $snapshot->refresh();

        $this->logReview($snapshot->id, 'evaluation_pass', 'Ditandai PASS oleh admin.');

        $this->dispatch('banner-message', type: 'success', message: 'Snapshot ditandai PASS & siap autopromote.');
    }

    public function toggleAutoReady(int $snapshotId): void
    {
        $snapshot = AssistantLlmSnapshot::findOrFail($snapshotId);
        $snapshot->update([
            'auto_promote_ready' => !$snapshot->auto_promote_ready,
        ]);

        $this->logReview(
            $snapshot->id,
            $snapshot->auto_promote_ready ? 'force_ready' : 'force_unready',
            $snapshot->auto_promote_ready ? 'Diset siap autopromote.' : 'Auto-promote dinonaktifkan.'
        );

        $this->dispatch('banner-message', type: 'success', message: 'Status auto-promote diperbarui.');
    }

    public function render()
    {
        $statusMap = [
            'pending' => ['pending', 'retry', 'queued'],
            'needs-review' => ['needs_review'],
            'promoted' => ['promoted', 'kb_ingested'],
            'failed' => ['failed'],
        ];

        $query = AssistantLlmSnapshot::query()
            ->with([
                'interaction:id,query',
                'reviews' => fn ($q) => $q->latest()->with(['user:id,name'])->limit(3),
            ])
            ->where('is_helpful', true);

        if (isset($statusMap[$this->status])) {
            $query->whereIn('promotion_status', $statusMap[$this->status]);
        }

        if ($this->onlyAutoReady) {
            $query->where('auto_promote_ready', true);
        }

        if ($this->search !== '') {
            $term = Str::lower($this->search);
            $query->where(function ($builder) use ($term) {
                $builder
                    ->whereRaw('LOWER(content) like ?', ["%{$term}%"])
                    ->orWhere('intent', 'like', "%{$term}%")
                    ->orWhereHas('interaction', function ($sub) use ($term) {
                        $sub->whereRaw('LOWER(query) like ?', ["%{$term}%"]);
                    });
            });
        }

        $snapshots = $query
            ->orderByDesc('auto_promote_ready')
            ->orderByDesc('positive_feedback_count')
            ->orderByDesc('id')
            ->paginate(10);

        $stats = [
            'pending' => AssistantLlmSnapshot::where('promotion_status', 'pending')->count(),
            'auto_ready' => AssistantLlmSnapshot::where('auto_promote_ready', true)->whereIn('promotion_status', ['pending', 'retry'])->count(),
            'needs_review' => AssistantLlmSnapshot::where('promotion_status', 'needs_review')->count(),
            'promoted' => AssistantLlmSnapshot::whereIn('promotion_status', ['promoted', 'kb_ingested'])->count(),
        ];

        return view('livewire.admin.pengaturan.llm-snapshot-candidates', [
            'snapshots' => $snapshots,
            'stats' => $stats,
        ])->title('Kurasi LLM Snapshots');
    }

    private function logReview(int $snapshotId, string $action, ?string $notes = null, array $metadata = []): void
    {
        AssistantLlmSnapshotReview::create([
            'assistant_llm_snapshot_id' => $snapshotId,
            'user_id' => Auth::id(),
            'action' => $action,
            'notes' => $notes,
            'metadata' => $metadata,
        ]);
    }
}
