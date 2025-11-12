<?php

namespace App\Services\Assistant\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AssistantHealthNotifier
{
    public function notify(string $subject, string $message): void
    {
        $this->notifyEmail($subject, $message);
        $this->notifyTelegram($message);
    }

    private function notifyEmail(string $subject, string $message): void
    {
        $to = config('assistant.health.email');
        if (!$to) {
            return;
        }

        try {
            Mail::raw($message, function ($mail) use ($to, $subject) {
                $mail->to($to)->subject($subject);
            });
        } catch (\Throwable $e) {
            Log::warning('Assistant health email notification failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function notifyTelegram(string $message): void
    {
        $token = config('assistant.health.telegram_bot_token');
        $chatId = config('assistant.health.telegram_chat_id');

        if (!$token || !$chatId) {
            return;
        }

        try {
            Http::timeout(5)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Assistant health telegram notification failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
