<?php

namespace App\Console\Commands;

use App\Models\AssistantHealthCheck;
use App\Services\Assistant\Support\AssistantHealthNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AssistantMonitorHealth extends Command
{
    protected $signature = 'assistant:monitor-health';

    protected $description = 'Send alert when scheduler or queue worker heartbeat is stale.';

    public function handle(AssistantHealthNotifier $notifier): int
    {
        $alerts = [];

        $schedulerGrace = (int) config('assistant.health.scheduler_grace_seconds', 150);
        $queueGrace = (int) config('assistant.health.queue_grace_seconds', 150);

        $schedulerStale = $this->isStale('scheduler', $schedulerGrace);
        $queueStale = $this->isStale('queue_worker', $queueGrace);

        if ($schedulerStale) {
            $alerts[] = 'Scheduler heartbeat tidak terdeteksi (cek Task Scheduler / cron).';
        }

        if ($queueStale) {
            $alerts[] = 'Queue worker tidak memproses job (jalankan ulang `php artisan queue:work --queue=reminders`).';
        }

        if ($alerts !== []) {
            $message = "Assistant Health Alert:\n- " . implode("\n- ", $alerts) . "\nTime: " . Carbon::now()->toDateTimeString();
            $notifier->notify('Assistant Health Alert', $message);
            $this->warn($message);
        } else {
            $this->info('Health checks OK.');
        }

        return self::SUCCESS;
    }

    private function isStale(string $name, int $graceSeconds): bool
    {
        $record = AssistantHealthCheck::where('name', $name)->first();

        if (!$record || $record->last_success_at === null) {
            return true;
        }

        return $record->last_success_at->lt(Carbon::now()->subSeconds($graceSeconds));
    }
}
