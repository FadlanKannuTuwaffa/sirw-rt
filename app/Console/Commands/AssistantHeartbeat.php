<?php

namespace App\Console\Commands;

use App\Jobs\QueueHeartbeat;
use App\Models\AssistantHealthCheck;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AssistantHeartbeat extends Command
{
    protected $signature = 'assistant:heartbeat';

    protected $description = 'Update scheduler heartbeat and dispatch queue heartbeat job.';

    public function handle(): int
    {
        $this->touch('scheduler');

        QueueHeartbeat::dispatch()->onQueue('reminders');

        return self::SUCCESS;
    }

    private function touch(string $name): void
    {
        AssistantHealthCheck::updateOrCreate(
            ['name' => $name],
            [
                'status' => 'ok',
                'last_success_at' => Carbon::now(),
                'payload' => null,
            ]
        );
    }
}
