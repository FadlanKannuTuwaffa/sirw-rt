<?php

namespace App\Livewire\Admin\Pengaturan;

use App\Models\AssistantInteractionLog;
use App\Models\SiteSetting;
use App\Support\Assistant\ProviderManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class AssistantAnalytics extends Component
{
    protected array $layoutData = [
        'title' => 'Pengaturan',
        'titleClass' => 'text-white',
    ];

    public array $providerStates = [];

    public function mount(): void
    {
        $this->providerStates = ProviderManager::stateMap();
    }

    private function appTimezone(): string
    {
        return config('app.timezone', 'UTC');
    }

    /**
     * @param int $days
     * @return array{localStart: Carbon, localEnd: Carbon, utcStart: Carbon, utcEnd: Carbon}
     */
    private function analyticsWindow(int $days = 14): array
    {
        $timezone = $this->appTimezone();
        $localEnd = Carbon::now($timezone)->endOfDay();
        $localStart = $localEnd->copy()->subDays(max($days - 1, 0))->startOfDay();

        return [
            'localStart' => $localStart,
            'localEnd' => $localEnd,
            'utcStart' => $localStart->copy()->setTimezone('UTC'),
            'utcEnd' => $localEnd->copy()->setTimezone('UTC'),
        ];
    }

    public function render()
    {
        $summary = $this->buildSummary();
        $trendSeries = $this->buildTrendSeries();
        $topIntents = $this->buildTopIntents();
        $channelStats = $this->buildChannelStats();
        $toolStats = $this->buildToolStats();
        $recentInteractions = $this->recentInteractions();

        $llmStats = $this->buildLLMStats();
        $llmTrend = $this->buildLLMTrendSeries();

        $this->dispatch('analytics-data-updated');

        return view('livewire.admin.pengaturan.assistant-analytics', [
            'summary' => $summary,
            'trendSeries' => $trendSeries,
            'topIntents' => $topIntents,
            'channelStats' => $channelStats,
            'toolStats' => $toolStats,
            'llmStats' => $llmStats,
            'llmTrend' => $llmTrend,
            'llmHighlights' => $this->buildLLMHighlights($llmStats, $llmTrend, $this->providerStates),
            'providerStates' => $this->providerStates,
            'recentInteractions' => $recentInteractions,
            'evaluationSummary' => $this->latestEvaluationSummary(),
        ])->title('Analitik Asisten Warga');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSummary(): array
    {
        $total = AssistantInteractionLog::count();
        $success = $total > 0 ? AssistantInteractionLog::where('success', true)->count() : 0;
        $avgDuration = (float) (AssistantInteractionLog::avg('duration_ms') ?? 0);
        $timezone = $this->appTimezone();
        $now = Carbon::now($timezone);
        $todayStartUtc = $now->copy()->startOfDay()->setTimezone('UTC');
        $todayEndUtc = $now->copy()->endOfDay()->setTimezone('UTC');
        $lastHourThresholdUtc = $now->copy()->subHour()->setTimezone('UTC');

        $today = AssistantInteractionLog::whereBetween('created_at', [$todayStartUtc, $todayEndUtc])->count();
        $lastHour = AssistantInteractionLog::where('created_at', '>=', $lastHourThresholdUtc)->count();

        $toolUsageCount = $this->toolInvocationCount();
        $llmUsageCount = AssistantInteractionLog::whereNotNull('llm_provider')->count();

        return [
            'total' => $total,
            'success_count' => $success,
            'success_rate' => $total > 0 ? round(($success / $total) * 100, 1) : 0.0,
            'tool_usage_count' => $toolUsageCount,
            'tool_usage_rate' => $total > 0 ? round(($toolUsageCount / $total) * 100, 1) : 0.0,
            'llm_usage_count' => $llmUsageCount,
            'llm_usage_rate' => $total > 0 ? round(($llmUsageCount / $total) * 100, 1) : 0.0,
            'average_duration_ms' => $avgDuration,
            'average_duration_human' => $this->formatDuration($avgDuration),
            'today' => $today,
            'last_hour' => $lastHour,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildTrendSeries(): array
    {
        $window = $this->analyticsWindow();
        $timezone = $this->appTimezone();

        $logs = AssistantInteractionLog::whereBetween('created_at', [$window['utcStart'], $window['utcEnd']])
            ->get(['created_at', 'success']);

        $groupedByDate = $logs->groupBy(function (AssistantInteractionLog $log) use ($timezone) {
            return $log->created_at
                ? $log->created_at->copy()->setTimezone($timezone)->toDateString()
                : null;
        })->filter();

        $series = [];

        for ($i = 0; $i < 14; $i++) {
            $day = $window['localStart']->copy()->addDays($i);
            $dateKey = $day->toDateString();
            /** @var Collection<int, AssistantInteractionLog> $entries */
            $entries = $groupedByDate->get($dateKey, collect());

            $total = $entries->count();
            $success = $entries->filter(static fn ($log) => (bool) $log->success)->count();

            $series[] = [
                'date' => $day->translatedFormat('d M'),
                'total' => $total,
                'success' => $success,
                'failed' => max($total - $success, 0),
            ];
        }

        return $series;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildTopIntents(): array
    {
        $logs = AssistantInteractionLog::latest()
            ->whereNotNull('intents')
            ->limit(2000)
            ->pluck('intents');

        $counter = [];

        foreach ($logs as $intents) {
            foreach ((array) $intents as $intent) {
                if (!is_string($intent) || $intent === '') {
                    continue;
                }

                $counter[$intent] = ($counter[$intent] ?? 0) + 1;
            }
        }

        $total = array_sum($counter);

        return collect($counter)
            ->sortDesc()
            ->take(7)
            ->map(function ($count, $intent) use ($total) {
                return [
                    'intent' => Str::headline(str_replace('_', ' ', $intent)),
                    'key' => $intent,
                    'count' => $count,
                    'share' => $total > 0 ? round(($count / $total) * 100, 1) : 0.0,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildChannelStats(): array
    {
        $total = AssistantInteractionLog::count();

        $channels = AssistantInteractionLog::select('responded_via', DB::raw('COUNT(*) as total'))
            ->groupBy('responded_via')
            ->orderByDesc('total')
            ->get();

        return $channels->map(function ($row) use ($total) {
            $label = $this->channelLabel((string) $row->responded_via);
            $count = (int) $row->total;

            return [
                'label' => $label,
                'key' => (string) $row->responded_via,
                'count' => $count,
                'share' => $total > 0 ? round(($count / $total) * 100, 1) : 0.0,
            ];
        })->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildLLMStats(): array
    {
        $rows = AssistantInteractionLog::select('llm_provider', DB::raw('COUNT(*) as total'))
            ->groupBy('llm_provider')
            ->orderByDesc('total')
            ->get();

        $knownProviders = $this->knownLLMProviders();
        $total = (int) $rows->sum('total');
        $aggregated = $rows->mapWithKeys(function ($row) {
            return [(string) ($row->llm_provider ?? 'Tidak tercatat') => (int) $row->total];
        });

        $stats = [];

        foreach ($knownProviders as $provider) {
            $count = $aggregated->get($provider, 0);

            $stats[] = [
                'provider' => $provider,
                'count' => $count,
                'share' => $total > 0 ? round(($count / $total) * 100, 1) : 0.0,
                'enabled' => $this->providerStates[$provider] ?? true,
            ];
        }

        $unknownProviders = $aggregated
            ->except($knownProviders)
            ->map(function ($count, $provider) use ($total) {
                return [
                    'provider' => $provider,
                    'count' => $count,
                    'share' => $total > 0 ? round(($count / $total) * 100, 1) : 0.0,
                    'enabled' => true,
                ];
            })
            ->values()
            ->all();

        return array_merge($stats, $unknownProviders);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildLLMTrendSeries(): array
    {
        $window = $this->analyticsWindow();
        $timezone = $this->appTimezone();

        $logs = AssistantInteractionLog::whereBetween('created_at', [$window['utcStart'], $window['utcEnd']])
            ->whereNotNull('llm_provider')
            ->get(['created_at', 'llm_provider']);

        $groupedByDate = $logs->groupBy(function (AssistantInteractionLog $log) use ($timezone) {
            return $log->created_at
                ? $log->created_at->copy()->setTimezone($timezone)->toDateString()
                : null;
        })->filter();

        $providersFromData = $logs->pluck('llm_provider')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $providers = array_values(array_unique(array_merge(
            $this->knownLLMProviders(),
            $providersFromData
        )));

        $series = [];

        for ($i = 0; $i < 14; $i++) {
            $day = $window['localStart']->copy()->addDays($i);
            $dateKey = $day->toDateString();
            /** @var Collection<int, AssistantInteractionLog> $entries */
            $entries = $groupedByDate->get($dateKey, collect());

            $data = [];
            foreach ($providers as $provider) {
                $data[$provider] = $entries
                    ->filter(static fn ($log) => $log->llm_provider === $provider)
                    ->count();
            }

            $series[] = [
                'date' => $day->translatedFormat('d M'),
                'data' => $data,
                'total' => array_sum($data),
            ];
        }

        return [
            'providers' => $providers,
            'series' => $series,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $stats
     * @param array<string, mixed> $trend
     * @return array<string, mixed>
     */
    private function buildLLMHighlights(array $stats, array $trend, array $states): array
    {
        $statsCollection = collect($stats);
        $activeProviders = $statsCollection->filter(function ($stat) use ($states) {
            return ($stat['count'] ?? 0) > 0 && ($states[$stat['provider']] ?? true);
        });

        /** @var array<string, mixed>|null $topProvider */
        $topProvider = $activeProviders->sortByDesc('count')->first();

        if (! $topProvider) {
            $topProvider = $statsCollection->sortByDesc('count')->first();
        }

        $series = collect($trend['series'] ?? []);
        /** @var array<string, mixed>|null $latest */
        $latest = $series->last();
        /** @var array<string, mixed>|null $previous */
        $previous = $series->slice(-2, 1)->first();

        $delta = null;

        if ($latest !== null && $previous !== null) {
            $difference = (int) ($latest['total'] ?? 0) - (int) ($previous['total'] ?? 0);
            $delta = [
                'value' => $difference,
                'direction' => $difference >= 0 ? 'up' : 'down',
            ];
        }

        return [
            'top_provider' => $topProvider,
            'active_count' => $activeProviders->count(),
            'total_usage' => $activeProviders->sum('count'),
            'last_snapshot' => $latest,
            'delta' => $delta,
            'provider_order' => $this->knownLLMProviders(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function knownLLMProviders(): array
    {
        return ProviderManager::providers();
    }

    public function toggleProvider(string $provider): void
    {
        $provider = trim($provider);

        if (!in_array($provider, $this->knownLLMProviders(), true)) {
            return;
        }

        $current = $this->providerStates[$provider] ?? true;
        $key = ProviderManager::settingKey($provider);

        if ($current) {
            SiteSetting::updateOrCreate(
                ['group' => 'assistant_llm', 'key' => $key],
                ['value' => '0']
            );
            $this->providerStates[$provider] = false;
        } else {
            SiteSetting::query()
                ->where('group', 'assistant_llm')
                ->where('key', $key)
                ->delete();

            $this->providerStates[$provider] = true;
        }

        app()->forgetInstance(\App\Services\Assistant\LLMClient::class);

        $this->dispatch('assistant-provider-toggled', provider: $provider, enabled: $this->providerStates[$provider]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildToolStats(): array
    {
        $logs = AssistantInteractionLog::latest()
            ->whereNotNull('tool_calls')
            ->limit(2000)
            ->get(['tool_calls']);

        $usage = [];
        $successCounter = [];

        foreach ($logs as $log) {
            foreach ((array) $log->tool_calls as $call) {
                $name = (string) Arr::get($call, 'name', 'unknown');
                $usage[$name] = ($usage[$name] ?? 0) + 1;
                if (Arr::get($call, 'success') === true) {
                    $successCounter[$name] = ($successCounter[$name] ?? 0) + 1;
                }
            }
        }

        return collect($usage)
            ->sortDesc()
            ->map(function ($count, $name) use ($successCounter) {
                $success = $successCounter[$name] ?? 0;

                return [
                    'name' => $name,
                    'count' => $count,
                    'success_rate' => $count > 0 ? round(($success / $count) * 100, 1) : 0.0,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, AssistantInteractionLog>
     */
    private function recentInteractions(): Collection
    {
        return AssistantInteractionLog::latest()
            ->withCasts(['intents' => 'array', 'tool_calls' => 'array'])
            ->limit(12)
            ->get();
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

    private function channelLabel(string $channel): string
    {
        return match ($channel) {
            'intent_handler' => 'Intent Handler',
            'dummy_client' => 'Rule Engine',
            'dummy_feedback' => 'Rule Feedback',
            'llm' => 'LLM',
            'llm_with_tools' => 'LLM + Tools',
            'small_talk' => 'Small Talk',
            'timeout' => 'Timeout',
            default => Str::headline(str_replace('_', ' ', $channel)),
        };
    }

    private function toolInvocationCount(): int
    {
        $driver = DB::getDriverName();
        $expression = $driver === 'sqlite'
            ? "json_array_length(COALESCE(tool_calls, '[]'))"
            : "JSON_LENGTH(tool_calls)";

        try {
            return AssistantInteractionLog::whereRaw("{$expression} > 0")->count();
        } catch (\Throwable) {
            // Fallback to PHP aggregation when JSON_LENGTH is unavailable
            return AssistantInteractionLog::whereNotNull('tool_calls')
                ->get(['tool_calls'])
                ->filter(fn ($log) => !empty($log->tool_calls))
                ->count();
        }
    }

    private function formatDuration(float $durationMs): string
    {
        if ($durationMs <= 0) {
            return '—';
        }

        if ($durationMs < 1000) {
            return number_format($durationMs, 0) . ' ms';
        }

        $seconds = $durationMs / 1000;

        if ($seconds < 60) {
            return number_format($seconds, 1) . ' dtk';
        }

        $minutes = floor($seconds / 60);
        $remaining = $seconds - ($minutes * 60);

        return sprintf('%d m %02d dtk', $minutes, (int) round($remaining));
    }
}
