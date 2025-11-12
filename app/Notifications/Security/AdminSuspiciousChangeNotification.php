<?php

namespace App\Notifications\Security;

use App\Models\User;
use App\Notifications\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AdminSuspiciousChangeNotification extends Notification
{
    use Queueable;

    /**
     * @param array<int, array{action: string, meta?: array<string, mixed>, attempted_at: string}> $history
     */
    public function __construct(
        private readonly User $actor,
        private readonly string $action,
        private readonly int $attempts,
        private readonly array $history
    ) {
    }

    public function via(mixed $notifiable): array
    {
        $channels = [];

        if (filled($notifiable->email)) {
            $channels[] = 'mail';
        }

        $account = $notifiable->telegramAccount ?? null;
        if ($account && ! $account->unlinked_at && $account->telegram_chat_id) {
            $channels[] = TelegramChannel::class;
        }

        return $channels;
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Peringatan Keamanan: Aktivitas Mencurigakan pada Akun Admin')
            ->view('emails.security.admin_suspicious_change', [
                'admin' => $notifiable,
                'actor' => $this->actor,
                'actionLabel' => $this->actionLabel(),
                'attempts' => $this->attempts,
                'history' => $this->history,
            ]);
    }

    public function toTelegram(mixed $notifiable): string
    {
        $lines = [];

        $lines[] = '<b>⚠️ Peringatan Keamanan Admin</b>';
        $lines[] = 'Akun: <b>' . e($this->actor->name) . '</b> (ID #' . $this->actor->id . ')';
        $lines[] = 'Aksi dicoba: ' . e($this->actionLabel());
        $lines[] = 'Jumlah percobaan: ' . $this->attempts . ' dalam 10 menit.';

        if ($latest = Arr::last($this->history)) {
            if ($ip = Arr::get($latest, 'meta.ip_address')) {
                $lines[] = 'IP terakhir: ' . e($ip);
            }
            if ($device = Arr::get($latest, 'meta.device')) {
                $lines[] = 'Perangkat: ' . e($device);
            }
        }

        $lines[] = '';
        $lines[] = 'Sistem otomatis mengeluarkan sesi yang aktif. Mohon login ulang dan pastikan keamanan akun.';

        return implode("\n", $lines);
    }

    private function actionLabel(): string
    {
        return match ($this->action) {
            'email_change' => 'Perubahan Email Admin',
            'password_change' => 'Perubahan Password Admin',
            default => Str::headline($this->action),
        };
    }
}

