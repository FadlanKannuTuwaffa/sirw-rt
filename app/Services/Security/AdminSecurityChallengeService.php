<?php

namespace App\Services\Security;

use App\Models\AdminSecurityOtp;
use App\Models\User;
use App\Notifications\Security\AdminSecurityOtpNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Throwable;

class AdminSecurityChallengeService
{
    public const OTP_VALID_MINUTES = 10;
    public const MAX_REQUESTS_PER_DAY = 5;

    public function issue(User $user, string $purpose, array $meta = []): AdminSecurityOtp
    {
        $requestsToday = AdminSecurityOtp::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        if ($requestsToday >= self::MAX_REQUESTS_PER_DAY) {
            throw ValidationException::withMessages([
                'otp' => 'Permintaan kode verifikasi telah mencapai batas pada rentang waktu 24 jam. Coba lagi nanti.',
            ]);
        }

        AdminSecurityOtp::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $otp = (string) random_int(100000, 999999);
        $token = Str::random(64);

        $channels = [];

        if (filled($user->email)) {
            $channels[] = 'mail';
        }

        $telegramAccount = $user->telegramAccount;
        if ($telegramAccount && ! $telegramAccount->unlinked_at) {
            $channels[] = 'telegram';
        }

        if (empty($channels)) {
            throw ValidationException::withMessages([
                'otp' => 'Tidak ada kanal verifikasi yang tersedia. Pastikan email lama masih aktif atau hubungkan akun Telegram admin.',
            ]);
        }

        $record = AdminSecurityOtp::create([
            'user_id' => $user->id,
            'purpose' => $purpose,
            'token' => $token,
            'otp_code' => Hash::make($otp),
            'channels' => $channels,
            'meta' => $meta,
            'expires_at' => now()->addMinutes(self::OTP_VALID_MINUTES),
        ]);

        try {
            Notification::send($user, new AdminSecurityOtpNotification($record, $otp));
        } catch (Throwable $exception) {
            $this->cleanupFailedIssue($record);
            report($exception);

            throw ValidationException::withMessages([
                'otp' => $this->friendlyDeliveryError($exception),
            ]);
        }

        Cache::forget('admin-security-otp:issued:' . $record->token);
        Cache::put($this->issuedCacheKey($record->token), $otp, now()->addMinutes(self::OTP_VALID_MINUTES));

        return $record;
    }

    public function verify(string $token, string $otp): AdminSecurityOtp
    {
        $record = AdminSecurityOtp::query()
            ->whereIn('token', AdminSecurityOtp::tokenLookupValues($token))
            ->first();

        if (! $record) {
            throw ValidationException::withMessages([
                'otp' => 'Kode verifikasi tidak ditemukan atau sesi telah berakhir. Minta kode baru.',
            ]);
        }

        if ($record->isExpired()) {
            $record->markUsed();

            throw ValidationException::withMessages([
                'otp' => 'Kode verifikasi sudah kedaluwarsa. Minta kode baru untuk melanjutkan.',
            ]);
        }

        if ($record->isUsed()) {
            throw ValidationException::withMessages([
                'otp' => 'Kode verifikasi sudah digunakan. Minta kode baru untuk melanjutkan.',
            ]);
        }

        if (! Hash::check($otp, $record->otp_code)) {
            throw ValidationException::withMessages([
                'otp' => 'Kode verifikasi tidak sesuai. Periksa kembali atau minta kode baru.',
            ]);
        }

        if (! $record->isVerified()) {
            $record->markVerified();
        }

        $record->markUsed();
        Cache::forget($this->issuedCacheKey($record->token));
        Cache::forget('admin-security-otp:issued:' . $record->token);

        return $record;
    }

    public function resend(AdminSecurityOtp $record): string
    {
        if ($record->isUsed() || $record->isExpired()) {
            throw ValidationException::withMessages([
                'otp' => 'Permintaan kode verifikasi sudah tidak berlaku. Minta kode baru.',
            ]);
        }

        $otp = Cache::get($this->issuedCacheKey($record->token));

        if (! $otp) {
            $otp = Cache::get('admin-security-otp:issued:' . $record->token);
        }

        if (! $otp) {
            $otp = (string) random_int(100000, 999999);
            $record->forceFill(['otp_code' => Hash::make($otp), 'expires_at' => now()->addMinutes(self::OTP_VALID_MINUTES)])->save();
            Cache::forget('admin-security-otp:issued:' . $record->token);
            Cache::put($this->issuedCacheKey($record->token), $otp, now()->addMinutes(self::OTP_VALID_MINUTES));
        }

        try {
            Notification::send($record->user, new AdminSecurityOtpNotification($record, $otp));
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'otp' => $this->friendlyDeliveryError($exception),
            ]);
        }

        return $otp;
    }

    private function issuedCacheKey(string $token): string
    {
        return 'admin-security-otp:issued:' . AdminSecurityOtp::hashToken($token);
    }

    private function cleanupFailedIssue(AdminSecurityOtp $record): void
    {
        Cache::forget('admin-security-otp:issued:' . $record->token);
        Cache::forget($this->issuedCacheKey($record->token));

        $record->delete();
    }

    private function friendlyDeliveryError(Throwable $exception): string
    {
        $message = $exception->getMessage();

        if ($exception instanceof TransportExceptionInterface) {
            if (Str::contains($message, ['Username and Password not accepted', '535-5.7.8'])) {
                return 'Server SMTP menolak kredensial. Gunakan sandi aplikasi (App Password) Gmail dan pastikan alamat pengirim sudah disetujui.';
            }

            if (Str::contains($message, ['Connection could not be established', 'Connection timed out'])) {
                return 'Tidak bisa terhubung ke server SMTP. Periksa host, port, enkripsi, dan firewall Anda.';
            }

            if (Str::contains($message, 'Expected response code "250"')) {
                return 'Server SMTP menolak email. Pastikan alamat pengirim sudah terverifikasi di layanan email Anda.';
            }
        }

        return 'Gagal mengirim kode OTP melalui email. Periksa pengaturan SMTP di menu Pengaturan > SMTP atau coba opsi lain.';
    }
}
