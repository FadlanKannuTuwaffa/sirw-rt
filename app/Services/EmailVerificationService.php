<?php

namespace App\Services;

use App\Mail\VerifyEmailOtpMail;
use App\Models\EmailVerificationOtp;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmailVerificationService
{
    public const OTP_VALID_MINUTES = 10;
    public const MAX_REQUESTS_PER_DAY = 5;

    public function issue(User $user, string $email, string $purpose, array $meta = []): EmailVerificationOtp
    {
        if (blank($email)) {
            throw ValidationException::withMessages([
                'email' => 'Email belum diatur pada akun ini. Silakan hubungi pengurus.',
            ]);
        }

        $email = Str::lower(trim($email));

        $requestsToday = EmailVerificationOtp::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        if ($requestsToday >= self::MAX_REQUESTS_PER_DAY) {
            throw ValidationException::withMessages([
                'email' => 'Permintaan verifikasi email sudah mencapai batas hari ini. Coba lagi besok atau hubungi pengurus.',
            ]);
        }

        EmailVerificationOtp::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $otp = (string) random_int(100000, 999999);
        $token = Str::random(64);

        $record = EmailVerificationOtp::create([
            'user_id' => $user->id,
            'email' => $email,
            'token' => $token,
            'otp_code' => Hash::make($otp),
            'purpose' => $purpose,
            'meta' => $meta,
            'expires_at' => now()->addMinutes(self::OTP_VALID_MINUTES),
        ]);

        Mail::to($email)->send(
            new VerifyEmailOtpMail($user, $otp, self::OTP_VALID_MINUTES)
        );

        return $record;
    }

    public function verify(string $token, string $otp): EmailVerificationOtp
    {
        $record = EmailVerificationOtp::query()
            ->whereIn('token', EmailVerificationOtp::tokenLookupValues($token))
            ->first();

        if (! $record) {
            throw ValidationException::withMessages([
                'otp' => 'Kode verifikasi tidak ditemukan atau sudah tidak berlaku.',
            ]);
        }

        if ($record->isExpired()) {
            $record->markUsed();

            throw ValidationException::withMessages([
                'otp' => 'Kode verifikasi sudah kedaluwarsa. Silakan minta kode baru.',
            ]);
        }

        if ($record->isUsed()) {
            throw ValidationException::withMessages([
                'otp' => 'Kode verifikasi sudah digunakan. Silakan minta kode baru.',
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

        return $record;
    }

    public function remainingValidityMinutes(EmailVerificationOtp $otp): int
    {
        return max(0, Carbon::now()->diffInMinutes($otp->expires_at, false));
    }
}
