<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $subject,
        protected string $body,
        protected ?array $metadata = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage())
            ->subject($this->subject)
            ->line($this->body);

        if ($this->metadata) {
            foreach ($this->metadata as $label => $value) {
                $message->line($label . ': ' . $value);
            }
        }

        $message->line('Terima kasih telah aktif berpartisipasi dalam lingkungan kita.');

        return $message;
    }
}