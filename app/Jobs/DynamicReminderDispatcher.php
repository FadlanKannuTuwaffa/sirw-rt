<?php

namespace App\Jobs;

use App\Models\Reminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

class DynamicReminderDispatcher implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct()
    {
        $this->onQueue('reminders');
    }

    public function handle(): void
    {
        $now = Carbon::now()->seconds(0);
        $reminderIds = Reminder::query()
            ->where('status', 'scheduled')
            ->where('send_at', '<=', $now)
            ->orderBy('send_at')
            ->limit(200)
            ->pluck('id');

        if ($reminderIds->isEmpty()) {
            $this->logger()->debug('Tidak ada reminder yang jatuh tempo pada menit ini', [
                'reference_time' => $now->toDateTimeString(),
            ]);

            return;
        }

        $dispatchQueue = [];

        foreach ($reminderIds as $reminderId) {
            DB::transaction(function () use ($reminderId, &$dispatchQueue, $now) {
                /** @var Reminder|null $reminder */
                $reminder = Reminder::query()->lockForUpdate()->find($reminderId);

                if (! $reminder) {
                    return;
                }

                if ($reminder->status !== 'scheduled') {
                    return;
                }

                $channels = $this->determineChannels($reminder);

                if (empty($channels)) {
                    $payload = $reminder->payload ?? [];
                    $payload['channel_states'] = Arr::get($payload, 'channel_states', []);

                    $reminder->forceFill([
                        'status' => 'sent',
                        'sent_at' => $now,
                        'payload' => $payload,
                    ])->save();

                    $this->logger()->warning('Reminder tidak memiliki kanal aktif, ditandai selesai', [
                        'reminder_id' => $reminder->id,
                    ]);

                    return;
                }

                $payload = $reminder->payload ?? [];
                $channelStates = Arr::get($payload, 'channel_states', []);
                $timestamp = $now->toDateTimeString();

                foreach ($channels as $channel) {
                    $channelStates[$channel] = [
                        'status' => 'queued',
                        'queued_at' => $timestamp,
                    ];
                }

                $payload['channel_states'] = $channelStates;
                $payload['last_dispatched_at'] = $timestamp;

                $reminder->forceFill([
                    'status' => 'processing',
                    'payload' => $payload,
                ])->save();

                $dispatchQueue[] = [
                    'reminder_id' => $reminder->id,
                    'channels' => $channels,
                ];
            });
        }

        foreach ($dispatchQueue as $item) {
            foreach ($item['channels'] as $channel) {
                match ($channel) {
                    'email' => SendReminderEmails::dispatchSync($item['reminder_id']),
                    'telegram' => SendReminderTelegram::dispatchSync($item['reminder_id']),
                    default => null,
                };
            }

            $this->logger()->info('Reminder diproses langsung', [
                'reminder_id' => $item['reminder_id'],
                'channels' => $item['channels'],
            ]);
        }
    }

    private function determineChannels(Reminder $reminder): array
    {
        return match ($reminder->channel) {
            'email' => ['email'],
            'telegram' => ['telegram'],
            default => ['email', 'telegram'],
        };
    }

    private function logger(): LoggerInterface
    {
        static $logger = null;

        if ($logger === null) {
            $logger = Log::build([
                'driver' => 'single',
                'path' => storage_path('logs/reminder_dispatcher.log'),
            ]);
        }

        return $logger;
    }
}
