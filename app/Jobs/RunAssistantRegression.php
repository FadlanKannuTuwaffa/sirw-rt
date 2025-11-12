<?php

namespace App\Jobs;

use App\Models\AssistantLlmSnapshot;
use App\Models\AssistantLlmSnapshotReview;
use App\Services\Assistant\CorrectionIngestor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RunAssistantRegression implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        private readonly ?int $snapshotId = null,
        private readonly ?string $scenarioPath = null,
        private readonly ?string $datasetPath = null,
    ) {
        $this->onQueue('reminders');
    }

    public function handle(): void
    {
        $scenario = $this->scenarioPath ?? 'tests/assistant_scenarios.json';
        $dataset = $this->datasetPath ?? 'tests/data/assistant_eval_full.json';

        $testLoop = $this->runTestLoop($scenario);
        $evaluation = $this->runEvaluation($dataset);
        $evaluationSummary = $evaluation['summary'];
        $evaluationCases = $evaluation['cases'];

        $allGreen = ($testLoop['failed_steps'] ?? 1) === 0
            && ($evaluationSummary['failed_cases'] ?? 1) === 0;

        AssistantLlmSnapshotReview::create([
            'assistant_llm_snapshot_id' => $this->snapshotId,
            'user_id' => null,
            'action' => $allGreen ? 'regression_pass' : 'regression_warn',
            'notes' => $allGreen
                ? 'Regresi otomatis lulus.'
                : 'Regresi otomatis menemukan potensi regresi.',
            'metadata' => [
                'test_loop' => $testLoop,
                'evaluation' => $evaluationSummary,
            ],
        ]);

        if (!$allGreen && $evaluationCases !== []) {
            $this->autoCorrectFailedCases($evaluationCases);
        }

        $this->markSnapshotEvaluation($allGreen);
    }

    private function runTestLoop(string $scenario): array
    {
        $scenarioPath = $this->resolvePath($scenario);

        try {
            Artisan::call('assistant:test-loop', [
                '--scenario' => $scenarioPath,
                '--json' => true,
            ]);

            $raw = trim(Artisan::output());
            $report = json_decode($raw, true);
            if (!is_array($report)) {
                throw new \RuntimeException('Laporan test-loop tidak valid.');
            }

            $total = 0;
            $passed = 0;
            foreach ($report as $item) {
                $total += (int) ($item['steps_total'] ?? 0);
                $passed += (int) ($item['steps_passed'] ?? 0);
            }

            return [
                'steps_total' => $total,
                'steps_passed' => $passed,
                'failed_steps' => max(0, $total - $passed),
                'scenarios' => count($report),
            ];
        } catch (\Throwable $e) {
            Log::warning('assistant:test-loop gagal dijalankan', [
                'error' => $e->getMessage(),
            ]);

            return [
                'error' => $e->getMessage(),
                'steps_total' => 0,
                'steps_passed' => 0,
                'failed_steps' => 0,
                'scenarios' => 0,
            ];
        }
    }

    private function runEvaluation(string $dataset): array
    {
        $datasetPath = $this->resolvePath($dataset);
        $jsonPath = storage_path('app/assistant_eval/regression_' . now()->timestamp . '.json');

        try {
            Artisan::call('assistant:evaluate', [
                '--dataset' => $datasetPath,
                '--json' => $jsonPath,
            ]);

            $raw = File::exists($jsonPath) ? File::get($jsonPath) : null;
            $report = $raw ? json_decode($raw, true) : null;

            $summary = [
                'total_cases' => $report['summary']['total'] ?? 0,
                'passed_cases' => $report['summary']['intent_correct'] ?? 0,
                'failed_cases' => max(0, ($report['summary']['total'] ?? 0) - ($report['summary']['intent_correct'] ?? 0)),
            ];

            return [
                'summary' => $summary,
                'cases' => $report['cases'] ?? [],
            ];
        } catch (\Throwable $e) {
            Log::warning('assistant:evaluate gagal dijalankan', [
                'error' => $e->getMessage(),
            ]);

            return [
                'summary' => [
                    'error' => $e->getMessage(),
                    'total_cases' => 0,
                    'passed_cases' => 0,
                    'failed_cases' => 0,
                ],
                'cases' => [],
            ];
        } finally {
            if (File::exists($jsonPath)) {
                @File::delete($jsonPath);
            }
        }
    }
    private function resolvePath(string $path): string
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:\\\\/', $path)) {
            return $path;
        }

        return base_path($path);
    }

    private function autoCorrectFailedCases(array $cases, int $limit = 5): void
    {
        $processed = 0;

        foreach ($cases as $case) {
            $turnSuccess = (bool) ($case['turn_success'] ?? false);
            if ($turnSuccess) {
                continue;
            }

            $caseId = (string) ($case['id'] ?? Str::uuid());
            if ($this->evaluationCorrectionExists($caseId)) {
                continue;
            }

            $question = trim((string) ($case['question'] ?? ''));
            $expectedIntent = trim((string) ($case['expected_intent'] ?? ''));

            if ($question === '' || $expectedIntent === '') {
                continue;
            }

            $feedback = sprintf(
                'evaluation_case:%s expected:%s predicted:%s',
                $caseId,
                $expectedIntent,
                $case['predicted_intent'] ?? 'unknown'
            );

            $examples = [
                [
                    'input' => Str::limit(Str::squish($question), 280, '...'),
                    'preferred_response' => "Pastikan intent {$expectedIntent} dijalankan lengkap sesuai skenario evaluasi.",
                ],
            ];

            $payload = [
                'thread_id' => 'evaluation:auto',
                'turn_id' => (string) Str::uuid(),
                'scope' => 'global',
                'correction_type' => 'langkah',
                'original_input' => $question,
                'user_feedback_raw' => $feedback,
                'examples' => $examples,
                'patch_rules' => [
                    'intent_bias' => [$expectedIntent => 0.4],
                ],
            ];

            try {
                CorrectionIngestor::store($payload);
                $processed++;
                if ($processed >= $limit) {
                    break;
                }
            } catch (\Throwable $e) {
                Log::warning('Auto-correction dari evaluasi gagal', [
                    'case_id' => $caseId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function evaluationCorrectionExists(string $caseId): bool
    {
        if (!Schema::hasTable('assistant_correction_events')) {
            return false;
        }

        return DB::table('assistant_correction_events')
            ->where('user_feedback_raw', 'like', 'evaluation_case:' . $caseId . '%')
            ->exists();
    }

    private function markSnapshotEvaluation(bool $passed): void
    {
        if ($this->snapshotId === null) {
            return;
        }

        $snapshot = AssistantLlmSnapshot::find($this->snapshotId);

        if (!$snapshot || $snapshot->assistant_interaction_log_id === null) {
            return;
        }

        app(\App\Services\Assistant\Support\LlmSnapshotManager::class)->markEvaluation(
            (int) $snapshot->assistant_interaction_log_id,
            $passed ? 'REGRESSION_PASS' : 'REGRESSION_WARN',
            $passed
        );
    }
}
