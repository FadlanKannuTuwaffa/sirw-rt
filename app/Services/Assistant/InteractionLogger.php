<?php

namespace App\Services\Assistant;

use App\Models\AssistantInteractionLog;
use Illuminate\Support\Facades\Log;

class InteractionLogger
{
    /**
     * @param array<string, mixed> $payload
     */
    public function record(array $payload): ?AssistantInteractionLog
    {
        try {
            return AssistantInteractionLog::create($payload);
        } catch (\Throwable $e) {
            Log::warning('Failed to persist assistant interaction log', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
