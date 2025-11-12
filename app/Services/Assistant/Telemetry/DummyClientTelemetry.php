<?php

namespace App\Services\Assistant\Telemetry;

use App\Models\AssistantCorrection;
use App\Models\AssistantInteractionLog;
use App\Models\AssistantKnowledgeFeedback;
use App\Models\AssistantLlmSnapshot;
use App\Models\AssistantLlmSnapshotReview;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DummyClientTelemetry
{
    public function summary(): array
    {
        $total = AssistantInteractionLog::count();
        $success = $total > 0 ? AssistantInteractionLog::where('success', true)->count() : 0;
        $correctionReuse = AssistantInteractionLog::whereNotNull('correction_event_id')->count();
        $fallbackQuery = AssistantInteractionLog::whereNotNull('provider_fallback_from')->whereNotNull('duration_ms');
        $fallbackCount = $fallbackQuery->count();
        $medianFallbackLatency = (int) round($fallbackQuery->pluck('duration_ms')->median() ?? 0);

        $toolSample = AssistantInteractionLog::whereNotNull('tool_calls')
            ->latest()
            ->limit(2000)
            ->get(['tool_calls']);
        $toolAttempts = 0;
        $tool4xx = 0;
        foreach ($toolSample as $log) {
            foreach ((array) $log->tool_calls as $call) {
                $toolAttempts++;
                $code = (int) ($call['code'] ?? 0);
                $error = $call['error'] ?? null;
                if (($code >= 400 && $code < 500) || $error === 'validation_failed') {
                    $tool4xx++;
                }
            }
        }

        $avgRepetition = (float) (AssistantInteractionLog::avg('repetition_score') ?? 0.0);

        $knowledgeQuery = AssistantInteractionLog::query()->where(function ($q) {
            $q->where('responded_via', 'knowledge_base')
                ->orWhereJsonContains('intents', 'knowledge_base');
        });
        $knowledgeTotal = (clone $knowledgeQuery)->count();
        $knowledgeSuccess = $knowledgeTotal > 0
            ? (clone $knowledgeQuery)->where('success', true)->count()
            : 0;
        $knowledgeLowConfidence = $knowledgeTotal > 0
            ? (clone $knowledgeQuery)->where('success', false)->count()
            : 0;

        $autoPromoted = AssistantCorrection::where('notes', 'like', 'Auto-promoted%')->count();
        $activeCorrections = AssistantCorrection::where('is_active', true)->count();
        $kbHelpful = Schema::hasTable('assistant_kb_feedback')
            ? AssistantKnowledgeFeedback::where('helpful', true)->count()
            : 0;
        $kbUnhelpful = Schema::hasTable('assistant_kb_feedback')
            ? AssistantKnowledgeFeedback::where('helpful', false)->count()
            : 0;
        $llmStats = $this->llmSnapshotStats($fallbackCount);

        return [
            'total_interactions' => $total,
            'success_rate' => $total > 0 ? round(($success / $total) * 100, 2) : 0.0,
            'correction_reuse_rate' => $total > 0 ? round(($correctionReuse / $total) * 100, 2) : 0.0,
            'provider_fallback_rate' => $total > 0 ? round(($fallbackCount / $total) * 100, 2) : 0.0,
            'median_fallback_latency_ms' => $medianFallbackLatency,
            'tool_4xx_rate' => $toolAttempts > 0 ? round(($tool4xx / $toolAttempts) * 100, 2) : 0.0,
            'avg_repetition_score' => round($avgRepetition, 3),
            'knowledge_queries' => $knowledgeTotal,
            'knowledge_success_rate' => $knowledgeTotal > 0 ? round(($knowledgeSuccess / $knowledgeTotal) * 100, 2) : 0.0,
            'knowledge_low_confidence' => $knowledgeLowConfidence,
            'autopromoted_corrections' => $autoPromoted,
            'active_corrections' => $activeCorrections,
            'kb_feedback_helpful' => $kbHelpful,
            'kb_feedback_unhelpful' => $kbUnhelpful,
            'llm_snapshots_total' => $llmStats['total'],
            'llm_snapshots_promoted' => $llmStats['promoted'],
            'llm_promoted_ratio' => $llmStats['promoted_ratio'],
            'llm_avg_time_to_promotion_hours' => $llmStats['avg_hours'],
            'llm_learned_ratio' => $llmStats['learned_ratio'],
        ];
    }

    public function trend(int $days = 14): array
    {
        $start = Carbon::now('UTC')->subDays($days - 1)->startOfDay();
        $logs = AssistantInteractionLog::selectRaw('DATE(created_at) as d, COUNT(*) as total, SUM(success = 1) as success, SUM(correction_event_id IS NOT NULL) as correction_hits, SUM(provider_fallback_from IS NOT NULL) as fallback_hits')
            ->where('created_at', '>=', $start)
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();
            $series[$date] = [
                'total' => 0,
                'success' => 0,
                'correction_hits' => 0,
                'fallback_hits' => 0,
                'kb_total' => 0,
                'kb_success' => 0,
            ];
        }

        foreach ($logs as $row) {
            $date = (string) $row->d;
            if (isset($series[$date])) {
                $series[$date]['total'] = (int) $row->total;
                $series[$date]['success'] = (int) $row->success;
                $series[$date]['correction_hits'] = (int) $row->correction_hits;
                $series[$date]['fallback_hits'] = (int) $row->fallback_hits;
            }
        }

        $knowledgeLogs = AssistantInteractionLog::where('created_at', '>=', $start)
            ->get(['created_at', 'intents', 'responded_via', 'success']);

        foreach ($knowledgeLogs as $log) {
            $date = $log->created_at->toDateString();
            if (!isset($series[$date])) {
                continue;
            }

            if ($this->isKnowledgeInteraction($log)) {
                $series[$date]['kb_total']++;
                if ($log->success) {
                    $series[$date]['kb_success']++;
                }
            }
        }

        $labels = [];
        $successRate = [];
        $correctionRate = [];
        $fallbackRate = [];
        $knowledgeRate = [];

        foreach ($series as $date => $row) {
            $labels[] = Carbon::parse($date)->translatedFormat('d M');
            $total = max(1, $row['total']);
            $successRate[] = round(($row['success'] / $total) * 100, 2);
            $correctionRate[] = round(($row['correction_hits'] / $total) * 100, 2);
            $fallbackRate[] = round(($row['fallback_hits'] / $total) * 100, 2);
            $kbTotal = max(1, $row['kb_total']);
            $knowledgeRate[] = $row['kb_total'] > 0 ? round(($row['kb_success'] / $kbTotal) * 100, 2) : 0.0;
        }

        return [
            'labels' => $labels,
            'success_rate' => $successRate,
            'correction_rate' => $correctionRate,
            'fallback_rate' => $fallbackRate,
            'knowledge_rate' => $knowledgeRate,
        ];
    }

    public function recentCorrections(int $limit = 6): Collection
    {
        if (!Schema::hasTable('assistant_correction_events')) {
            return collect();
        }

        return DB::table('assistant_correction_events')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get([
                'id',
                'user_id',
                'thread_id',
                'correction_type',
                'scope',
                'patch_rules',
                'created_at',
                'user_feedback_raw',
                'original_input',
            ]);
    }

    public function recentFallbacks(int $limit = 6): Collection
    {
        return AssistantInteractionLog::whereNotNull('provider_fallback_from')
            ->latest()
            ->limit($limit)
            ->get([
                'id',
                'user_id',
                'query',
                'provider_primary',
                'provider_final',
                'provider_fallback_from',
                'duration_ms',
                'created_at',
            ]);
    }

    public function recentKbFeedback(int $limit = 5): Collection
    {
        if (!Schema::hasTable('assistant_kb_feedback')) {
            return collect();
        }

        return AssistantKnowledgeFeedback::whereNotNull('responded_at')
            ->latest('responded_at')
            ->limit($limit)
            ->get([
                'question',
                'answer_excerpt',
                'helpful',
                'note',
                'responded_at',
            ]);
    }

    public function recentRegressionReviews(int $limit = 6): Collection
    {
        if (!Schema::hasTable('assistant_llm_snapshot_reviews')) {
            return collect();
        }

        return AssistantLlmSnapshotReview::with([
            'snapshot:id,intent,provider,promotion_status',
        ])
            ->whereIn('action', ['regression_pass', 'regression_warn'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function llmFeedbackDistribution(int $limit = 6): array
    {
        if (!Schema::hasTable('assistant_llm_snapshots')) {
            return [];
        }

        $rows = AssistantLlmSnapshot::select(
            'intent',
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN is_helpful = 1 THEN 1 ELSE 0 END) as helpful'),
            DB::raw('SUM(CASE WHEN is_helpful = 0 THEN 1 ELSE 0 END) as unhelpful'),
            DB::raw('SUM(CASE WHEN is_helpful IS NULL THEN 1 ELSE 0 END) as pending'),
            DB::raw('SUM(CASE WHEN auto_promote_ready = 1 THEN 1 ELSE 0 END) as auto_ready')
        )
            ->groupBy('intent')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        return $rows->map(static function ($row) {
            $label = trim((string) ($row->intent ?? ''));
            $label = $label === '' ? 'unknown' : $label;

            return [
                'intent' => $label,
                'total' => (int) ($row->total ?? 0),
                'helpful' => (int) ($row->helpful ?? 0),
                'unhelpful' => (int) ($row->unhelpful ?? 0),
                'pending' => (int) ($row->pending ?? 0),
                'auto_ready' => (int) ($row->auto_ready ?? 0),
            ];
        })->toArray();
    }

    public function intentSkillScores(int $days = 7, int $limit = 5): array
    {
        $stats = $this->collectIntentStats($days);

        usort($stats, static function (array $a, array $b) {
            return $a['success_rate'] <=> $b['success_rate'];
        });

        return array_slice($stats, 0, $limit);
    }

    public function toolBlueprintRecommendations(int $days = 7, int $limit = 3): array
    {
        $stats = $this->collectIntentStats($days);

        $candidates = array_values(array_filter($stats, static function (array $entry) {
            return $entry['failure_rate'] >= 40.0
                && $entry['tool_usage_rate'] <= 20.0
                && $entry['total'] >= 3;
        }));

        usort($candidates, static function (array $a, array $b) {
            return $b['failure_rate'] <=> $a['failure_rate'];
        });

        return array_slice($candidates, 0, $limit);
    }

    private function collectIntentStats(int $days): array
    {
        $start = Carbon::now('UTC')->subDays(max(1, $days) - 1)->startOfDay();

        $logs = AssistantInteractionLog::query()
            ->where('created_at', '>=', $start)
            ->latest()
            ->limit(1500)
            ->get(['id', 'intents', 'success', 'query', 'responded_via', 'tool_calls']);

        if ($logs->isEmpty()) {
            return [];
        }

        $stats = [];

        foreach ($logs as $log) {
            $intentList = (array) ($log->intents ?? []);
            $intent = Str::lower((string) ($intentList[0] ?? ''));

            if ($intent === '' || $intent === 'unknown') {
                continue;
            }

            if (!isset($stats[$intent])) {
                $stats[$intent] = [
                    'intent' => $intent,
                    'total' => 0,
                    'success' => 0,
                    'tool_calls' => 0,
                    'failures' => [],
                ];
            }

            $entry = &$stats[$intent];
            $entry['total']++;

            if ($log->success) {
                $entry['success']++;
            } else {
                if (count($entry['failures']) < 3) {
                    $entry['failures'][] = Str::limit(Str::squish((string) ($log->query ?? '')), 160, '...');
                }
            }

            if (!empty($log->tool_calls)) {
                $entry['tool_calls']++;
            }
        }

        foreach ($stats as &$entry) {
            $total = max(1, $entry['total']);
            $success = $entry['success'];
            $tool = $entry['tool_calls'];

            $entry['success_rate'] = round(($success / $total) * 100, 1);
            $entry['failure_rate'] = round((($total - $success) / $total) * 100, 1);
            $entry['tool_usage_rate'] = round(($tool / $total) * 100, 1);
            $entry['sample_failure'] = $entry['failures'][0] ?? null;
        }
        unset($entry);

        return array_values($stats);
    }

    public function autoLearnedTimeline(int $days = 14): array
    {
        if (!Schema::hasTable('assistant_correction_events')) {
            return [
                'labels' => [],
                'counts' => [],
            ];
        }

        $start = Carbon::now('UTC')->subDays($days - 1)->startOfDay();
        $rows = DB::table('assistant_correction_events')
            ->selectRaw('DATE(created_at) as d, COUNT(*) as total')
            ->where('user_feedback_raw', 'like', 'auto_learn_success:%')
            ->where('created_at', '>=', $start)
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now('UTC')->subDays($i)->toDateString();
            $series[$date] = 0;
        }

        foreach ($rows as $row) {
            $date = (string) $row->d;
            if (array_key_exists($date, $series)) {
                $series[$date] = (int) $row->total;
            }
        }

        $labels = [];
        $counts = [];
        foreach ($series as $date => $value) {
            $labels[] = Carbon::parse($date)->translatedFormat('d M');
            $counts[] = $value;
        }

        return [
            'labels' => $labels,
            'counts' => $counts,
        ];
    }

    private function llmSnapshotStats(int $fallbackCount): array
    {
        if (!Schema::hasTable('assistant_llm_snapshots')) {
            return [
                'total' => 0,
                'promoted' => 0,
                'promoted_ratio' => 0.0,
                'avg_hours' => 0.0,
                'learned_ratio' => 0.0,
            ];
        }

        $total = AssistantLlmSnapshot::count();
        $promotedStatuses = ['promoted', 'kb_ingested'];
        $promoted = AssistantLlmSnapshot::whereIn('promotion_status', $promotedStatuses)->count();
        $avgMinutes = AssistantLlmSnapshot::whereIn('promotion_status', $promotedStatuses)
            ->whereNotNull('promoted_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, promoted_at)) as avg_minutes')
            ->value('avg_minutes');

        $avgHours = $avgMinutes !== null ? round(((float) $avgMinutes) / 60, 2) : 0.0;
        $promotedRatio = $total > 0 ? round(($promoted / $total) * 100, 2) : 0.0;
        $learnedRatio = $fallbackCount > 0 ? round(($promoted / $fallbackCount) * 100, 2) : 0.0;

        return [
            'total' => $total,
            'promoted' => $promoted,
            'promoted_ratio' => $promotedRatio,
            'avg_hours' => $avgHours,
            'learned_ratio' => $learnedRatio,
        ];
    }

    private function isKnowledgeInteraction(AssistantInteractionLog $log): bool
    {
        if (Str::lower((string) ($log->responded_via ?? '')) === 'knowledge_base') {
            return true;
        }

        $intents = (array) ($log->intents ?? []);

        foreach ($intents as $intent) {
            if (Str::lower((string) $intent) === 'knowledge_base') {
                return true;
            }
        }

        return false;
    }
}
