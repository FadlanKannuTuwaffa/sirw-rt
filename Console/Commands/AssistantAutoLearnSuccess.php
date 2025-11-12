<?php

namespace App\Console\Commands;

use App\Models\AssistantInteractionLog;
use App\Services\Assistant\CorrectionIngestor;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AssistantAutoLearnSuccess extends Command
{
    protected $signature = 'assistant:auto-learn-success
        {--days=3 : Rentang hari interaksi yang dianalisis}
        {--min=4 : Minimal jumlah keberhasilan sebelum dipromosikan}
        {--limit=5 : Maksimal intent yang dipromosikan per eksekusi}';

    protected $description = 'Deteksi percakapan berhasil berulang dan promosikan menjadi correction memory otomatis.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $minHits = max(2, (int) $this->option('min'));
        $limit = max(1, (int) $this->option('limit'));

        $windowStart = Carbon::now()->subDays($days);

        $logs = AssistantInteractionLog::query()
            ->where('success', true)
            ->whereNull('correction_event_id')
            ->where('created_at', '>=', $windowStart)
            ->whereIn('responded_via', ['intent_handler', 'dummy_client'])
            ->select('id', 'query', 'intents')
            ->latest()
            ->get();

        if ($logs->isEmpty()) {
            $this->info('Tidak ada interaksi sukses baru yang bisa dipromosikan.');
            return self::SUCCESS;
        }

        $grouped = $logs->groupBy(function (AssistantInteractionLog $log) {
            $intentList = (array) ($log->intents ?? []);
            $intent = $intentList[0] ?? null;

            return Str::lower((string) $intent);
        });

        $created = 0;
        foreach ($grouped as $intent => $intentLogs) {
            if ($intent === '' || $intent === 'unknown') {
                continue;
            }

            if ($intentLogs->count() < $minHits) {
                continue;
            }

            if ($created >= $limit) {
                break;
            }

            $eventId = $this->promoteIntent($intent, $intentLogs);
            if ($eventId === null) {
                continue;
            }

            AssistantInteractionLog::whereIn('id', $intentLogs->pluck('id'))
                ->update(['correction_event_id' => $eventId]);

            $created++;
            $this->info("Intent {$intent} dipromosikan dari {$intentLogs->count()} interaksi sukses.");
        }

        if ($created === 0) {
            $this->info('Belum ada intent yang memenuhi ambang auto-learn.');
        }

        return self::SUCCESS;
    }

    private function promoteIntent(string $intent, Collection $logs): ?int
    {
        $questions = $logs->pluck('query')
            ->filter(fn ($question) => is_string($question) && trim($question) !== '')
            ->take(3)
            ->values();

        if ($questions->isEmpty()) {
            return null;
        }

        $logIds = $logs->pluck('id')->take(5)->implode(',');
        $feedback = sprintf(
            'auto_learn_success:%s | source_interactions:%s | hits:%d',
            $intent,
            $logIds,
            $logs->count()
        );

        $examples = $questions->map(function (string $question) use ($intent) {
            return [
                'input' => Str::limit(Str::squish($question), 280, '...'),
                'preferred_response' => "Gunakan jalur intent {$intent} dan tampilkan data internal secara ringkas.",
            ];
        })->toArray();

        $payload = [
            'thread_id' => 'auto-learn',
            'turn_id' => (string) Str::uuid(),
            'scope' => 'global',
            'correction_type' => 'langkah',
            'original_input' => $questions->first(),
            'user_feedback_raw' => $feedback,
            'examples' => $examples,
            'patch_rules' => [
                'intent_bias' => [$intent => 0.35],
            ],
        ];

        try {
            return CorrectionIngestor::store($payload);
        } catch (\Throwable $e) {
            Log::warning('Auto-learn intent gagal dipromosikan', [
                'intent' => $intent,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
