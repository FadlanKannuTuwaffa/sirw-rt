<?php

namespace App\Jobs;

use App\Jobs\Concerns\BuildsReminderContent;
use App\Mail\ReminderMail;
use App\Models\Bill;
use App\Models\Event;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Psr\Log\LoggerInterface;
use Throwable;

class SendReminderEmails implements ShouldQueue
{
    use BuildsReminderContent;
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public int $reminderId)
    {
        $this->onQueue('reminders');
    }

    public function handle(): void
    {
        /** @var Reminder|null $reminder */
        $reminder = Reminder::query()->with('model')->find($this->reminderId);

        if (! $reminder) {
            $this->logger()->warning('Reminder tidak ditemukan saat mengirim email', [
                'reminder_id' => $this->reminderId,
            ]);

            return;
        }

        $model = $reminder->model;

        if (! $model instanceof Bill && ! $model instanceof Event) {
            $this->markChannelFailed($reminder->id, 'email', 'Model pengingat tidak valid');
            return;
        }

        $recipients = $this->resolveEmailRecipients($model);

        if ($recipients->isEmpty()) {
            $this->markChannelSkipped($reminder->id, 'email', 'Tidak ada penerima email yang aktif');
            $this->finalizeIfCompleted($reminder->id);

            return;
        }

        $metadata = $this->buildMetadata($model);
        $sentEmails = [];

        try {
            foreach ($recipients as $user) {
                $subject = $this->buildSubject($model, $user);
                $body = $this->buildBody($model, $user);

                Mail::to($user->email)->send(new ReminderMail($subject, $body, $metadata, $model, $user));

                $sentEmails[] = $user->email;
            }
        } catch (Throwable $exception) {
            $this->markChannelFailed($reminder->id, 'email', $exception->getMessage());
            $this->logger()->error('Gagal mengirim email reminder', [
                'reminder_id' => $reminder->id,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $this->updateChannelState($reminder->id, 'email', [
            'status' => 'sent',
            'sent_at' => Carbon::now()->toDateTimeString(),
            'recipients' => $sentEmails,
        ]);

        $this->logger()->info('Email reminder berhasil dikirim', [
            'reminder_id' => $reminder->id,
            'recipients' => $sentEmails,
        ]);

        $this->finalizeIfCompleted($reminder->id);
    }

    private function resolveEmailRecipients(Bill|Event $model): Collection
    {
        $recipients = $model instanceof Bill
            ? $this->billRecipients($model)
            : $this->eventRecipients($model);

        return $recipients
            ->filter(fn (User $user) => filled($user->email))
            ->unique(fn (User $user) => $user->email);
    }

    private function markChannelFailed(int $reminderId, string $channel, string $reason): void
    {
        DB::transaction(function () use ($reminderId, $channel, $reason) {
            $reminder = Reminder::query()->lockForUpdate()->find($reminderId);

            if (! $reminder) {
                return;
            }

            $payload = $reminder->payload ?? [];
            $channelStates = Arr::get($payload, 'channel_states', []);

            $channelStates[$channel] = array_merge($channelStates[$channel] ?? [], [
                'status' => 'failed',
                'failed_at' => Carbon::now()->toDateTimeString(),
                'reason' => $reason,
            ]);

            $payload['channel_states'] = $channelStates;
            $payload['last_error'] = $reason;

            $reminder->forceFill([
                'status' => 'failed',
                'payload' => $payload,
            ])->save();
        });
    }

    private function markChannelSkipped(int $reminderId, string $channel, string $reason): void
    {
        $this->updateChannelState($reminderId, $channel, [
            'status' => 'skipped',
            'skipped_at' => Carbon::now()->toDateTimeString(),
            'reason' => $reason,
        ]);

        $this->logger()->notice('Kanal email dilewati', [
            'reminder_id' => $reminderId,
            'reason' => $reason,
        ]);
    }

    private function updateChannelState(int $reminderId, string $channel, array $attributes): void
    {
        DB::transaction(function () use ($reminderId, $channel, $attributes) {
            $reminder = Reminder::query()->lockForUpdate()->find($reminderId);

            if (! $reminder) {
                return;
            }

            $payload = $reminder->payload ?? [];
            $channelStates = Arr::get($payload, 'channel_states', []);

            $channelStates[$channel] = array_merge($channelStates[$channel] ?? [], $attributes);
            $payload['channel_states'] = $channelStates;

            $reminder->forceFill([
                'payload' => $payload,
            ])->save();
        });
    }

    private function finalizeIfCompleted(int $reminderId): void
    {
        DB::transaction(function () use ($reminderId) {
            $reminder = Reminder::query()->lockForUpdate()->find($reminderId);

            if (! $reminder) {
                return;
            }

            $requiredChannels = $this->determineChannels($reminder);
            $payload = $reminder->payload ?? [];
            $channelStates = Arr::get($payload, 'channel_states', []);

            $completed = collect($requiredChannels)->every(function (string $channel) use ($channelStates) {
                $status = Arr::get($channelStates, "{$channel}.status");

                return in_array($status, ['sent', 'skipped'], true);
            });

            if ($completed) {
                $payload['last_successful_send_date'] = Carbon::now()->toDateString();
                $payload['completed_at'] = Carbon::now()->toDateTimeString();

                $reminder->forceFill([
                    'status' => 'sent',
                    'sent_at' => Carbon::now(),
                    'payload' => $payload,
                ])->save();
            } else {
                $reminder->forceFill([
                    'status' => 'processing',
                    'payload' => $payload,
                ])->save();
            }
        });
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
                'path' => storage_path('logs/reminder_email.log'),
            ]);
        }

        return $logger;
    }
}
