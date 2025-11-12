<?php

namespace App\Livewire\Admin\Pengaturan;

use App\Models\AssistantCorrection;
use App\Models\AssistantToolBlueprint;
use App\Services\Assistant\Telemetry\DummyClientTelemetry;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class DummyClientMonitor extends Component
{
    protected array $layoutData = [
        'title' => 'Pengaturan',
        'titleClass' => 'text-white',
    ];

    public function render(DummyClientTelemetry $telemetry)
    {
        $summary = $telemetry->summary();
        $trend = $telemetry->trend();
        $recentCorrections = Schema::hasTable('assistant_correction_events')
            ? $telemetry->recentCorrections()
            : collect();
        $recentFallbacks = $telemetry->recentFallbacks();
        $kbFeedbackSamples = Schema::hasTable('assistant_kb_feedback')
            ? $telemetry->recentKbFeedback()
            : collect();
        $autoPromoted = Schema::hasTable('assistant_corrections')
            ? AssistantCorrection::query()
                ->where('notes', 'like', 'Auto-promoted%')
                ->orderByDesc('updated_at')
                ->limit(6)
                ->get(['alias', 'canonical', 'updated_at'])
            : collect();

        /** @var \Illuminate\View\View $view */
        $view = view('livewire.admin.pengaturan.dummy-client-monitor', [
            'summary' => $summary,
            'trend' => $trend,
            'recentCorrections' => $recentCorrections,
            'recentFallbacks' => $recentFallbacks,
            'kbFeedbackSamples' => $kbFeedbackSamples,
            'autoPromoted' => $autoPromoted,
            'evaluationSummary' => $this->latestEvaluationSummary(),
            'llmDistribution' => $telemetry->llmFeedbackDistribution(),
            'regressionReviews' => $telemetry->recentRegressionReviews(),
            'autoLearnTimeline' => $telemetry->autoLearnedTimeline(),
            'intentSkillScores' => $telemetry->intentSkillScores(),
            'toolBlueprints' => AssistantToolBlueprint::query()
                ->orderByRaw("FIELD(status, 'pending','in_progress','implemented','rejected')")
                ->orderByDesc('failure_rate')
                ->limit(3)
                ->get(),
        ]);

        return $view->title('Monitor DummyClient');
    }

    private function latestEvaluationSummary(): array
    {
        $path = storage_path('app/assistant_eval/latest.json');
        if (!File::exists($path)) {
            return [];
        }

        $data = json_decode(File::get($path), true);
        if (!is_array($data) || !isset($data['summary'])) {
            return [];
        }

        return $data['summary'];
    }
}
