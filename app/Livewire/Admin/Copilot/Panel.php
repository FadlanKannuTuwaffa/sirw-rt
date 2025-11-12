<?php

namespace App\Livewire\Admin\Copilot;

use App\Support\Copilot\CopilotAggregator;
use Livewire\Component;

class Panel extends Component
{
    public array $insights = [];
    public array $alerts = [];
    public array $actions = [];
    public array $timeline = [];
    public ?string $generatedAt = null;
    public bool $ready = false;

    protected $listeners = [
        'copilot:refresh' => 'refreshData',
        'copilot:execute-action' => 'executeAction',
        'copilot-open-state' => 'handleOpenState',
    ];

    protected CopilotAggregator $aggregator;

    public function boot(CopilotAggregator $aggregator): void
    {
        $this->aggregator = $aggregator;
    }

    public function mount(): void
    {
        $this->refreshData();
    }

    public function refreshData(): void
    {
        $snapshot = $this->aggregator->snapshot();
        $this->insights = $snapshot['insights'] ?? [];
        $this->alerts = $snapshot['alerts'] ?? [];
        $this->actions = $snapshot['actions'] ?? [];
        $this->timeline = $snapshot['timeline'] ?? [];
        $this->generatedAt = $snapshot['generated_at'] ?? now()->toIso8601String();
        $this->ready = true;

        $this->dispatch(
            'copilot-refreshed',
            insightCount: count($this->insights),
            alertCount: count($this->alerts)
        );
    }

    public function executeAction(string $actionId): void
    {
        $action = collect($this->actions)->firstWhere('id', $actionId);
        if (!$action) {
            return;
        }

        $this->dispatch('copilot-action-executed', detail: $action);
    }

    public function handleOpenState(array $payload = []): void
    {
        $this->panelOpen = (bool) ($payload['open'] ?? false);
    }

    public function render()
    {
        return view('livewire.admin.copilot.panel');
    }
}
