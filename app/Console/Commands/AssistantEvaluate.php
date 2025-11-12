<?php

namespace App\Console\Commands;

use App\Models\AssistantInteractionLog;
use App\Models\User;
use App\Services\Assistant\DummyClient;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Helper\Table;

class AssistantEvaluate extends Command
{
    protected $signature = 'assistant:evaluate 
        {--dataset=tests/data/assistant_eval_full.json : Path to the evaluation dataset}
        {--user= : User ID to impersonate}
        {--json= : Output JSON report path}
        {--html= : Output HTML report path}';

    protected $description = 'Run milestone-8 evaluation for DummyClient and emit JSON/HTML reports.';

    public function handle(): int
    {
        $datasetPath = base_path($this->option('dataset'));
        if (!File::exists($datasetPath)) {
            $this->error("Dataset not found at {$datasetPath}");
            return self::FAILURE;
        }

        $cases = json_decode(File::get($datasetPath), true);
        if (!is_array($cases) || $cases === []) {
            $this->error('Dataset is empty or invalid JSON.');
            return self::FAILURE;
        }

        $jsonPath = $this->option('json') ?? storage_path('app/assistant_eval/latest.json');
        $htmlPath = $this->option('html') ?? storage_path('app/assistant_eval/latest.html');

        $userId = $this->option('user');
        if (!$userId) {
            $userId = User::query()->value('id');
        }

        if ($userId !== null) {
            Auth::loginUsingId((int) $userId);
        }

        $metrics = [
            'total' => count($cases),
            'intent_correct' => 0,
            'slot_total' => 0,
            'slot_correct' => 0,
            'tool_required' => 0,
            'tool_success' => 0,
            'kb_required' => 0,
            'kb_success' => 0,
            'self_repair_required' => 0,
            'self_repair_success' => 0,
            'turn_success' => 0,
            'csat_sum' => 0,
            'guardrail_counts' => [],
        ];

        $caseResults = [];

        foreach ($cases as $case) {
            DummyClient::resetConversationState();
            /** @var DummyClient $client */
            $client = app(DummyClient::class);

            $result = $this->runCase($client, $case);
            $caseResults[] = $result;

            if ($result['intent_correct']) {
                $metrics['intent_correct']++;
            }

            $metrics['slot_total'] += $result['slot_total'];
            $metrics['slot_correct'] += $result['slot_correct'];

            if ($result['requires_tool']) {
                $metrics['tool_required']++;
                if ($result['tool_success']) {
                    $metrics['tool_success']++;
                }
            }

            if ($result['requires_kb']) {
                $metrics['kb_required']++;
                if ($result['kb_success']) {
                    $metrics['kb_success']++;
                }
            }

            if ($result['requires_follow_up']) {
                $metrics['self_repair_required']++;
                if ($result['self_repair_success']) {
                    $metrics['self_repair_success']++;
                }
            }

            if ($result['turn_success']) {
                $metrics['turn_success']++;
            }

            $metrics['csat_sum'] += $result['csat'];

            foreach ($result['guardrails'] as $guardrail) {
                $name = $guardrail['name'] ?? 'unknown';
                $metrics['guardrail_counts'][$name] = ($metrics['guardrail_counts'][$name] ?? 0) + 1;
            }
        }

        if ($userId !== null) {
            Auth::logout();
        }

        $summary = $this->buildSummary($metrics);
        $telemetry = app(\App\Services\Assistant\Telemetry\DummyClientTelemetry::class)->summary();
        $report = [
            'generated_at' => now()->toIso8601String(),
            'dataset' => $datasetPath,
            'summary' => $summary,
            'telemetry' => $telemetry,
            'cases' => $caseResults,
        ];

        $this->storeReport($jsonPath, $htmlPath, $report);
        $this->renderSummaryTable($summary);
        $this->renderTelemetryTable($telemetry);
        $this->info("JSON report saved to: {$jsonPath}");
        $this->info("HTML report saved to: {$htmlPath}");

        return self::SUCCESS;
    }

    private function runCase(DummyClient $client, array $case): array
    {
        $question = (string) ($case['question'] ?? '');
        $expectedIntent = $case['expected_intent'] ?? null;

        if ($question === '' || $expectedIntent === null) {
            return [
                'id' => $case['id'] ?? 'invalid',
                'question' => $question,
                'expected_intent' => $expectedIntent,
                'predicted_intent' => null,
                'intent_correct' => false,
                'slot_total' => 0,
                'slot_correct' => 0,
                'requires_tool' => (bool) ($case['requires_tool'] ?? false),
                'requires_kb' => (bool) ($case['requires_kb'] ?? false),
                'requires_follow_up' => isset($case['follow_up']),
                'self_repair_success' => false,
                'guardrails' => [],
                'tool_success' => false,
                'kb_success' => false,
                'turn_success' => false,
                'csat' => 1,
            ];
        }

        $client->chat([['role' => 'user', 'content' => $question]], []);
        $snapshot = $client->evaluationSnapshot();
        $predictedIntent = $client->getLastIntent();

        [$slotTotal, $slotCorrect] = $this->scoreSlots(
            Arr::get($case, 'expected_slots', []),
            Arr::get($snapshot, 'slots', [])
        );

        $requiresTool = (bool) ($case['requires_tool'] ?? in_array($expectedIntent, ['bills', 'payments', 'agenda', 'finance'], true));
        $requiresKb = (bool) ($case['requires_kb'] ?? ($expectedIntent === 'knowledge_base'));

        $toolSuccess = $requiresTool ? $this->inferToolSuccess($snapshot) : false;
        $kbSuccess = $requiresKb ? $this->inferKbSuccess($snapshot) : false;

        $intentCorrect = $predictedIntent === $expectedIntent;
        $turnSuccess = $intentCorrect
            && ($slotTotal === 0 || $slotTotal === $slotCorrect)
            && (!$requiresTool || $toolSuccess)
            && (!$requiresKb || $kbSuccess);

        $result = [
            'id' => $case['id'] ?? ('CASE_' . uniqid()),
            'question' => $question,
            'expected_intent' => $expectedIntent,
            'predicted_intent' => $predictedIntent,
            'intent_correct' => $intentCorrect,
            'slot_total' => $slotTotal,
            'slot_correct' => $slotCorrect,
            'requires_tool' => $requiresTool,
            'tool_success' => $toolSuccess,
            'requires_kb' => $requiresKb,
            'kb_success' => $kbSuccess,
            'guardrails' => $snapshot['guardrails'] ?? [],
            'requires_follow_up' => isset($case['follow_up']),
            'self_repair_success' => false,
            'turn_success' => $turnSuccess,
            'csat' => $this->simulateCsat($turnSuccess, $intentCorrect, $snapshot['guardrails'] ?? []),
        ];

        if (isset($case['follow_up']) && trim($case['follow_up']) !== '') {
            $client->chat([['role' => 'user', 'content' => $case['follow_up']]], []);
            $followSnapshot = $client->evaluationSnapshot();
            $followIntent = $client->getLastIntent();
            $expectedFollowIntent = $case['expected_intent_after_follow_up'] ?? $expectedIntent;
            [$followSlotTotal, $followSlotCorrect] = $this->scoreSlots(
                Arr::get($case, 'expected_slots_after_follow_up', []),
                Arr::get($followSnapshot, 'slots', [])
            );
            $result['self_repair_success'] = $followIntent === $expectedFollowIntent
                && ($followSlotTotal === 0 || $followSlotTotal === $followSlotCorrect);
            $result['follow_up'] = [
                'question' => $case['follow_up'],
                'expected_intent' => $expectedFollowIntent,
                'predicted_intent' => $followIntent,
                'slot_total' => $followSlotTotal,
                'slot_correct' => $followSlotCorrect,
            ];
        }

        return $result;
    }

    private function scoreSlots(array $expected, array $actual): array
    {
        $total = 0;
        $correct = 0;

        foreach ($expected as $key => $value) {
            $total++;
            $actualValue = $actual[$key] ?? null;
            if ($this->slotValueMatches($value, $actualValue)) {
                $correct++;
            }
        }

        return [$total, $correct];
    }

    private function slotValueMatches($expected, $actual): bool
    {
        if (is_array($expected)) {
            $expectedValues = array_map('strval', $expected);
            $actualValues = array_map('strval', (array) $actual);

            return count(array_diff($expectedValues, $actualValues)) === 0;
        }

        if (is_array($actual)) {
            return in_array((string) $expected, array_map('strval', $actual), true);
        }

        return (string) $expected === (string) $actual;
    }

    private function inferToolSuccess(array $snapshot): bool
    {
        foreach ($snapshot['tool_calls'] ?? [] as $call) {
            if (!empty($call['success'])) {
                return true;
            }
        }

        return !empty($snapshot['data']);
    }

    private function inferKbSuccess(array $snapshot): bool
    {
        $sources = $snapshot['kb_sources'] ?? [];
        if ($sources === []) {
            return false;
        }

        foreach ($snapshot['guardrails'] ?? [] as $guardrail) {
            if (($guardrail['name'] ?? '') === 'rag_low_confidence') {
                return false;
            }
        }

        return true;
    }

    private function simulateCsat(bool $turnSuccess, bool $intentCorrect, array $guardrails): int
    {
        if ($turnSuccess) {
            return 5;
        }

        if ($intentCorrect && $guardrails === []) {
            return 4;
        }

        if ($intentCorrect) {
            return 3;
        }

        return 2;
    }

    private function buildSummary(array $metrics): array
    {
        $total = max($metrics['total'], 1);
        $slotTotal = max($metrics['slot_total'], 1);
        $toolRequired = max($metrics['tool_required'], 1);
        $kbRequired = max($metrics['kb_required'], 1);
        $repairRequired = max($metrics['self_repair_required'], 1);

        return [
            'total_cases' => $metrics['total'],
            'intent_accuracy' => round(($metrics['intent_correct'] / $total) * 100, 2),
            'slot_accuracy' => round(($metrics['slot_correct'] / $slotTotal) * 100, 2),
            'tool_success_rate' => round(($metrics['tool_success'] / $toolRequired) * 100, 2),
            'rag_faithfulness' => round(($metrics['kb_success'] / $kbRequired) * 100, 2),
            'self_repair_rate' => round(($metrics['self_repair_success'] / $repairRequired) * 100, 2),
            'turn_success_rate' => round(($metrics['turn_success'] / $total) * 100, 2),
            'csat' => round($metrics['csat_sum'] / $total, 2),
            'guardrails' => $metrics['guardrail_counts'],
        ];
    }

    private function renderSummaryTable(array $summary): void
    {
        $table = new Table($this->output);
        $table->setHeaders(['Metric', 'Value']);
        $table->setRows([
            ['Total cases', $summary['total_cases']],
            ['Intent accuracy', $summary['intent_accuracy'] . '%'],
            ['Slot accuracy', $summary['slot_accuracy'] . '%'],
            ['Tool success rate', $summary['tool_success_rate'] . '%'],
            ['RAG faithfulness', $summary['rag_faithfulness'] . '%'],
            ['Self-repair success', $summary['self_repair_rate'] . '%'],
            ['Turn success rate', $summary['turn_success_rate'] . '%'],
            ['Simulated CSAT', $summary['csat']],
        ]);
        $table->render();

        if (!empty($summary['guardrails'])) {
            $this->info('Guardrail counts:');
            foreach ($summary['guardrails'] as $name => $count) {
                $this->line("- {$name}: {$count}");
            }
        }
    }

    private function renderTelemetryTable(array $telemetry): void
    {
        $table = new Table($this->output);
        $table->setHeaders(['Telemetry', 'Value']);
        $table->setRows([
            ['Correction reuse rate', $telemetry['correction_reuse_rate'] . '%'],
            ['Provider fallback rate', $telemetry['provider_fallback_rate'] . '%'],
            ['Median fallback latency', $telemetry['median_fallback_latency_ms'] . ' ms'],
            ['Tool 4xx rate', $telemetry['tool_4xx_rate'] . '%'],
            ['Avg repetition score', $telemetry['avg_repetition_score']],
        ]);
        $table->render();
    }

    private function storeReport(string $jsonPath, string $htmlPath, array $report): void
    {
        File::ensureDirectoryExists(dirname($jsonPath));
        File::put($jsonPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        File::ensureDirectoryExists(dirname($htmlPath));
        File::put($htmlPath, $this->renderHtmlReport($report));
    }

    private function renderHtmlReport(array $report): string
    {
        $summary = $report['summary'];
        $telemetry = $report['telemetry'] ?? [
            'correction_reuse_rate' => 0,
            'provider_fallback_rate' => 0,
            'median_fallback_latency_ms' => 0,
            'tool_4xx_rate' => 0,
            'avg_repetition_score' => 0.0,
        ];
        $rows = '';
        foreach ($report['cases'] as $case) {
            $status = $case['turn_success'] ? '✅' : '⚠️';
            $rows .= '<tr>';
            $rows .= '<td>' . e($case['id']) . '</td>';
            $rows .= '<td>' . e($case['question']) . '</td>';
            $rows .= '<td>' . e($case['expected_intent']) . '</td>';
            $rows .= '<td>' . e((string) $case['predicted_intent']) . '</td>';
            $rows .= '<td>' . $status . '</td>';
            $rows .= '<td>' . e((string) $case['csat']) . '</td>';
            $rows .= '</tr>';
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assistant Evaluation Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { border: 1px solid #ddd; padding: 0.5rem; text-align: left; }
        th { background: #f4f4f4; }
    </style>
</head>
<body>
    <h1>Assistant Evaluation Report</h1>
    <p><strong>Generated:</strong> {$report['generated_at']}</p>
    <ul>
        <li><strong>Total cases:</strong> {$report['summary']['total_cases']}</li>
        <li><strong>Intent accuracy:</strong> {$summary['intent_accuracy']}%</li>
        <li><strong>Slot accuracy:</strong> {$summary['slot_accuracy']}%</li>
        <li><strong>Tool success rate:</strong> {$summary['tool_success_rate']}%</li>
        <li><strong>RAG faithfulness:</strong> {$summary['rag_faithfulness']}%</li>
        <li><strong>Self-repair success:</strong> {$summary['self_repair_rate']}%</li>
        <li><strong>Turn success rate:</strong> {$summary['turn_success_rate']}%</li>
        <li><strong>Simulated CSAT:</strong> {$summary['csat']}</li>
    </ul>
    <h2>Telemetry</h2>
    <ul>
        <li><strong>Correction reuse rate:</strong> {$telemetry['correction_reuse_rate']}%</li>
        <li><strong>Provider fallback rate:</strong> {$telemetry['provider_fallback_rate']}%</li>
        <li><strong>Median fallback latency:</strong> {$telemetry['median_fallback_latency_ms']} ms</li>
        <li><strong>Tool 4xx rate:</strong> {$telemetry['tool_4xx_rate']}%</li>
        <li><strong>Avg repetition score:</strong> {$telemetry['avg_repetition_score']}</li>
    </ul>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Question</th>
                <th>Expected</th>
                <th>Predicted</th>
                <th>Turn</th>
                <th>CSAT</th>
            </tr>
        </thead>
        <tbody>
            {$rows}
        </tbody>
    </table>
</body>
</html>
HTML;
    }

}
