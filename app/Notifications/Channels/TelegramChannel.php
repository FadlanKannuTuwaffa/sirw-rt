<?php

namespace App\Notifications\Channels;

use App\Services\Telegram\TelegramBotService;
use Illuminate\Notifications\Notification;

class TelegramChannel
{
    public function __construct(
        private readonly TelegramBotService $telegram
    ) {
    }

    /**
     * Send the given notification via Telegram if the notifiable has an active account.
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toTelegram')) {
            return;
        }

        $account = $notifiable->telegramAccount ?? null;

        if (! $account || $account->unlinked_at || ! $account->receive_notifications) {
            return;
        }

        $chatId = (string) ($account->telegram_chat_id ?? '');

        if ($chatId === '') {
            return;
        }

        $payload = $notification->toTelegram($notifiable);

        if (is_string($payload)) {
            $message = $payload;
            $options = [];
        } elseif (is_array($payload)) {
            $message = (string) ($payload['message'] ?? $payload['html'] ?? $payload['text'] ?? '');
            $options = (array) ($payload['options'] ?? []);
        } else {
            return;
        }

        if (trim($message) === '') {
            return;
        }

        $this->telegram->sendHTML($chatId, $message, $options);
    }
}

