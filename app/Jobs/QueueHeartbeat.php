<?php

namespace App\Jobs;

use App\Models\AssistantHealthCheck;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class QueueHeartbeat implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public function handle(): void
    {
        AssistantHealthCheck::updateOrCreate(
            ['name' => 'queue_worker'],
            [
                'status' => 'ok',
                'last_success_at' => Carbon::now(),
                'payload' => null,
            ]
        );
    }
}
