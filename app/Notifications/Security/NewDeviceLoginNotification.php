<?php

namespace App\Notifications\Security;

use App\Models\User;
use App\Notifications\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class NewDeviceLoginNotification extends Notification
{
    use Queueable;

    /**
     * @param array{
     *     device_label?: string|null,
     *     ip_address?: string|null,
     *     user_agent?: string|null,
     *     detected_at?: \Illuminate\Support\Carbon,
     *     location_hint?: string|null,
     *     is_new_device?: bool|null,
     * } $context
     */
    public function __construct(
        private readonly User $actor,
        private readonly array $context,
        private readonly bool $forAdmin
    ) {
    }

    public function via(mixed $notifiable): array
    {
        $channels = [];

        if (! $this->forAdmin && filled($notifiable->email)) {
            $channels[] = 'mail';
        }

        $account = $notifiable->telegramAccount ?? null;

        if ($account && ! $account->unlinked_at && $account->receive_notifications) {
            $channels[] = TelegramChannel::class;
        }

        return $channels;
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $subject = $this->forAdmin
            ? 'Peringatan Login Baru: ' . $this->actor->name
            : 'Perangkat Baru Mengakses Akun SIRW Anda';
        $manageUrl = route('password.request');

        return (new MailMessage())
            ->subject($subject)
            ->view('emails.security.new_device_login', [
                'subject' => $subject,
                'notifiable' => $notifiable,
                'actor' => $this->actor,
                'context' => $this->context,
                'forAdmin' => $this->forAdmin,
                'manageUrl' => $manageUrl,
            ]);
    }

    public function toTelegram(mixed $notifiable): string
    {
        $lines = [];

        $lines[] = '<b>⚠️ Peringatan Login Baru</b>';

        if ($this->forAdmin) {
            $lines[] = 'Akun: <b>' . e($this->actor->name) . '</b> (ID #' . $this->actor->id . ')';
        }

        $lines[] = 'Perangkat: ' . e($this->context['device_label'] ?? 'Tidak diketahui');

        if ($ip = Arr::get($this->context, 'ip_address')) {
            $lines[] = 'IP: ' . e($ip);
        }

        if ($location = Arr::get($this->context, 'location_hint')) {
            $lines[] = 'Perkiraan lokasi: ' . e(strtoupper($location));
        }

        $lines[] = 'Waktu: ' . e($this->formatDetectedAt());
        $lines[] = '';
        $lines[] = 'Jika aktivitas ini bukan oleh Anda, segera ganti password melalui tautan berikut:';
        $lines[] = route('password.request');

        return implode("\n", $lines);
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'user_id' => $this->actor->id,
            'device_label' => Arr::get($this->context, 'device_label'),
            'ip_address' => Arr::get($this->context, 'ip_address'),
            'detected_at' => Arr::get($this->context, 'detected_at'),
            'is_for_admin' => $this->forAdmin,
        ];
    }

    private function mailIntro(): string
    {
        if ($this->forAdmin) {
            return 'Sistem mendeteksi login dari perangkat baru pada akun seorang warga/admin. Mohon pastikan aktivitas ini sah.';
        }

        return 'Kami mendeteksi login dari perangkat atau lokasi yang belum pernah digunakan sebelumnya.';
    }

    private function mailDeviceLine(): string
    {
        $label = $this->context['device_label'] ?? 'Perangkat tidak diketahui';
        $agent = $this->context['user_agent'] ?? null;

        if ($agent) {
            return sprintf('Perangkat: %s (%s)', $label, Str::limit($agent, 120));
        }

        return 'Perangkat: ' . $label;
    }

    private function mailIpLine(): string
    {
        $ip = $this->context['ip_address'] ?? 'Tidak terdeteksi';

        if ($location = Arr::get($this->context, 'location_hint')) {
            return sprintf('Alamat IP: %s (%s)', $ip, strtoupper($location));
        }

        return 'Alamat IP: ' . $ip;
    }

    private function formatDetectedAt(): string
    {
        $detectedAt = Arr::get($this->context, 'detected_at');

        if (! $detectedAt) {
            return now()->setTimezone(config('app.timezone'))->format('d M Y H:i');
        }

        return $detectedAt->setTimezone(config('app.timezone'))->format('d M Y H:i');
    }
}
