<?php

namespace App\Notifications\Security;

use App\Models\AdminSecurityOtp;
use App\Notifications\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AdminSecurityOtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly AdminSecurityOtp $record,
        private readonly string $otp
    ) {
    }

    public function via(mixed $notifiable): array
    {
        $channels = [];
        $configured = $this->record->channels ?? [];

        if (in_array('mail', $configured, true) && filled($notifiable->email)) {
            $channels[] = 'mail';
        }

        $account = $notifiable->telegramAccount ?? null;

        if (in_array('telegram', $configured, true) && $account && ! $account->unlinked_at && $account->telegram_chat_id) {
            $channels[] = TelegramChannel::class;
        }

        return $channels;
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject($this->subject())
            ->view('emails.security.admin_otp', [
                'subject' => $this->subject(),
                'user' => $notifiable,
                'otp' => $this->otp,
                'record' => $this->record,
                'purposeLabel' => $this->purposeLabel(),
                'description' => $this->purposeDescription(),
                'expiresAt' => $this->record->expires_at,
                'expiresInMinutes' => $this->expiresInMinutes(),
                'meta' => $this->metaSummary(),
            ]);
    }

    public function toTelegram(mixed $notifiable): string
    {
        $lines = [];

        $lines[] = '<b>🔐 ' . e($this->purposeLabel()) . '</b>';
        $lines[] = e($this->purposeDescription());
        $lines[] = '';
        $lines[] = '<b>Kode OTP:</b> <code>' . e($this->otp) . '</code>';
        $lines[] = 'Berlaku hingga ' . e($this->record->expires_at->setTimezone(config('app.timezone'))->format('d M Y H:i'));

        foreach ($this->metaSummary() as $metaLine) {
            $lines[] = e($metaLine);
        }

        $lines[] = '';
        $lines[] = 'Masukkan kode ini pada halaman keamanan admin untuk melanjutkan.';

        return implode("\n", $lines);
    }

    private function subject(): string
    {
        return match ($this->record->purpose) {
            AdminSecurityOtp::PURPOSE_EMAIL_CHANGE => 'Kode Verifikasi Perubahan Email Admin',
            AdminSecurityOtp::PURPOSE_PASSWORD_CHANGE => 'Kode Verifikasi Perubahan Password Admin',
            default => 'Kode Verifikasi Keamanan Admin',
        };
    }

    private function purposeLabel(): string
    {
        return match ($this->record->purpose) {
            AdminSecurityOtp::PURPOSE_EMAIL_CHANGE => 'Verifikasi Perubahan Email',
            AdminSecurityOtp::PURPOSE_PASSWORD_CHANGE => 'Verifikasi Perubahan Password',
            default => 'Verifikasi Keamanan Admin',
        };
    }

    private function purposeDescription(): string
    {
        return match ($this->record->purpose) {
            AdminSecurityOtp::PURPOSE_EMAIL_CHANGE => 'Gunakan kode berikut untuk mengonfirmasi perubahan email admin.',
            AdminSecurityOtp::PURPOSE_PASSWORD_CHANGE => 'Kode berikut diperlukan untuk mengganti password admin.',
            default => 'Masukkan kode verifikasi untuk melanjutkan tindakan keamanan admin.',
        };
    }

    private function expiresInMinutes(): int
    {
        $minutes = now()->diffInMinutes($this->record->expires_at, false);

        return max(1, $minutes);
    }

    private function metaSummary(): array
    {
        $meta = $this->record->meta ?? [];
        $lines = [];

        if ($newEmail = Arr::get($meta, 'new_email')) {
            $lines[] = 'Email baru: ' . Str::lower($newEmail);
        }

        if ($ip = Arr::get($meta, 'ip_address')) {
            $lines[] = 'IP: ' . $ip;
        }

        if ($device = Arr::get($meta, 'device')) {
            $lines[] = 'Perangkat: ' . $device;
        }

        return $lines;
    }
}
