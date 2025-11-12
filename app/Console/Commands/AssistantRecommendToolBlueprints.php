<?php

namespace App\Console\Commands;

use App\Models\AssistantInteractionLog;
use App\Models\AssistantToolBlueprint;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class AssistantRecommendToolBlueprints extends Command
{
    protected $signature = 'assistant:recommend-tool-blueprints
        {--days=7 : Rentang hari interaksi yang dianalisis}
        {--limit=5 : Maksimal rekomendasi baru per eksekusi}';

    protected $description = 'Analisis intent yang gagal karena tidak ada tool dan buat blueprint otomatis untuk dikurasi admin.';

    public function handle(): int
    {
        $stats = $this->collectIntentStats((int) $this->option('days'));
        $limit = max(1, (int) $this->option('limit'));

        $candidates = array_values(array_filter($stats, static function (array $entry) {
            return $entry['failure_rate'] >= 40.0
                && $entry['tool_usage_rate'] <= 20.0
                && $entry['total'] >= 3;
        }));

        if ($candidates === []) {
            $this->info('Tidak ada intent yang butuh tool baru berdasarkan data saat ini.');
            return self::SUCCESS;
        }

        usort($candidates, static function (array $a, array $b) {
            return $b['failure_rate'] <=> $a['failure_rate'];
        });

        $created = 0;

        foreach ($candidates as $entry) {
            if ($created >= $limit) {
                break;
            }

            $blueprint = AssistantToolBlueprint::firstOrNew(['intent' => $entry['intent']]);

            if (in_array($blueprint->status, ['in_progress', 'implemented'], true)) {
                continue;
            }

            $blueprint->fill([
                'sample_failure' => $entry['sample_failure'],
                'failure_rate' => $entry['failure_rate'],
                'tool_usage_rate' => $entry['tool_usage_rate'],
                'total_interactions' => $entry['total'],
                'source_payload' => $entry['source_payload'],
                'recommended_at' => Carbon::now(),
            ]);

            if ($blueprint->exists === false) {
                $blueprint->status = 'pending';
            }

            $blueprint->save();
            $created++;

            $this->line(sprintf(
                'Blueprint intent %s diperbarui (failure %.1f%%, tool usage %.1f%%).',
                $entry['intent'],
                $entry['failure_rate'],
                $entry['tool_usage_rate']
            ));
        }

        $this->info("Total blueprint yang diperbarui/dibuat: {$created}");

        return self::SUCCESS;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function collectIntentStats(int $days): array
    {
        $start = Carbon::now('UTC')->subDays(max(1, $days) - 1)->startOfDay();

        $logs = AssistantInteractionLog::query()
            ->where('created_at', '>=', $start)
            ->latest()
            ->limit(2000)
            ->get(['intents', 'success', 'query', 'tool_calls']);

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
            } elseif (count($entry['failures']) < 3) {
                $entry['failures'][] = Str::limit(Str::squish((string) ($log->query ?? '')), 160, '...');
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
            $entry['source_payload'] = [
                'failures' => $entry['failures'],
                'period_start' => $start->toDateString(),
            ];
            unset($entry['failures']);
        }
        unset($entry);

        return array_values($stats);
    }
}
