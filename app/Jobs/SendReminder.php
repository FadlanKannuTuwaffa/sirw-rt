<?php

namespace App\Jobs;

use App\Jobs\Concerns\BuildsReminderContent;
use App\Models\Bill;
use App\Models\Event;
use App\Models\Reminder;
use App\Models\User;
use App\Notifications\ReminderNotification;
use App\Services\Telegram\TelegramBotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendReminder implements ShouldQueue
{
    use BuildsReminderContent;
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Reminder $reminder)
    {
        $this->onQueue('reminders');
    }

    public function handle(): void
    {
        $model = $this->reminder->model;

        if (! $model) {
            $this->markFailed('Model pengingat tidak ditemukan');
            return;
        }

        $recipients = match ($model::class) {
            Bill::class => $this->billRecipients($model),
            Event::class => $this->eventRecipients($model),
            default => collect(),
        };

        if ($recipients->isEmpty()) {
            $this->markFailed('Tidak ada penerima untuk pengingat ini');
            return;
        }

        $metadata = $this->buildMetadata($model);

        $telegram = app(TelegramBotService::class);

        foreach ($recipients as $user) {
            $subject = $this->buildSubject($model, $user);
            $body = $this->buildBody($model, $user);

            if ($user->email) {
                $user->notify(new ReminderNotification($subject, $body, $metadata));
            }

            $telegram->notifyReminder($user, $subject, $body, $metadata);
        }

        $this->reminder->forceFill([
            'status' => 'sent',
            'sent_at' => now(),
        ])->save();
    }

    private function markFailed(string $reason): void
    {
        $payload = $this->reminder->payload ?? [];
        $payload['last_error'] = $reason;

        $this->reminder->forceFill([
            'status' => 'failed',
            'payload' => $payload,
        ])->save();

        Log::warning('Reminder gagal dikirim', [
            'reminder_id' => $this->reminder->id,
            'reason' => $reason,
        ]);
    }
}
