<?php

namespace App\Console;

use App\Console\Commands\AssistantAutoLearnSuccess;
use App\Console\Commands\AssistantHeartbeat;
use App\Console\Commands\AssistantMonitorHealth;
use App\Console\Commands\AssistantPromoteLlmFacts;
use App\Console\Commands\AssistantRecommendToolBlueprints;
use App\Console\Commands\AssistantTestLoop;
use App\Console\Commands\ProcessFactCorrections;
use App\Console\Commands\ProcessKnowledgeFeedback;
use App\Jobs\ProcessAssistantMaintenance;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * @var array<int, class-string>
     */
    protected $commands = [
        ProcessKnowledgeFeedback::class,
        ProcessFactCorrections::class,
        AssistantTestLoop::class,
        AssistantPromoteLlmFacts::class,
        AssistantAutoLearnSuccess::class,
        AssistantHeartbeat::class,
        AssistantMonitorHealth::class,
        AssistantRecommendToolBlueprints::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Run synchronously via command to avoid queue worker dependency.
        $schedule->command('reminders:send')
            ->everyMinute()
            ->withoutOverlapping()
            ->onOneServer();

        $schedule->command('assistant:heartbeat')
            ->everyMinute()
            ->withoutOverlapping()
            ->onOneServer();

        $schedule->command('assistant:monitor-health')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->onOneServer();

        $schedule->command('assistant:recommend-tool-blueprints')
            ->dailyAt('03:15')
            ->withoutOverlapping()
            ->onOneServer();

        $schedule->command('payments:cleanup-stale')
            ->hourly()
            ->withoutOverlapping()
            ->onOneServer();

        if (config('rag.enabled')) {
            $schedule->command('rag:sync')
                ->dailyAt('02:00')
                ->withoutOverlapping()
                ->onOneServer();
        }

        $schedule->job(new ProcessAssistantMaintenance())
            ->hourly()
            ->withoutOverlapping()
            ->onOneServer();
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }
}
