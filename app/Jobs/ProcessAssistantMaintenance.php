<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ProcessAssistantMaintenance implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    /**
     * Prevent duplicate maintenance jobs from stacking up when dispatched
     * via scheduler and manual triggers.
     */
    public int $uniqueFor = 300;

    public function __construct(
        private readonly ?int $kbBatch = null,
        private readonly ?int $factBatch = null,
    ) {
        $this->onQueue('reminders');
    }

    public function uniqueId(): string
    {
        return 'assistant_maintenance';
    }

    public function handle(): void
    {
        $this->runCommand('assistant:process-kb-feedback', $this->kbBatch);
        $this->runCommand('assistant:promote-llm-facts', null);
        $this->runCommand('assistant:process-fact-corrections', $this->factBatch);
        $this->runCommand('assistant:auto-learn-success', null);
        $this->runCommand('assistant:recommend-tool-blueprints', null);
    }

    private function runCommand(string $command, ?int $batchSize): void
    {
        $parameters = [];

        if ($batchSize !== null) {
            $parameters['--batch'] = $batchSize;
        }

        try {
            $exitCode = Artisan::call($command, $parameters);

            Log::info('Assistant maintenance command executed', [
                'command' => $command,
                'exit_code' => $exitCode,
            ]);
        } catch (\Throwable $e) {
            Log::error('Assistant maintenance command failed', [
                'command' => $command,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
