<?php

namespace App\Http\Controllers\Resident;

use App\Http\Controllers\Controller;
use App\Models\EmailVerificationOtp;
use App\Models\SiteSetting;
use App\Services\EmailVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    private const CONTEXT_INITIAL = 'initial';
    private const CONTEXT_CHANGE = 'change';

    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user || $user->role !== 'warga') {
            Auth::logout();

            return redirect()->route('login');
        }

        $config = $this->resolveContext($request, $request->query('context'));

        if ($config['context'] === self::CONTEXT_INITIAL && $user->email_verified_at !== null) {
            return redirect()->route('resident.dashboard');
        }

        $token = $request->session()->get($config['session_key']);

        $otpRecord = $token
            ? EmailVerificationOtp::query()
                ->whereIn('token', EmailVerificationOtp::tokenLookupValues($token))
                ->first()
            : null;

        if (! $otpRecord || $otpRecord->isExpired() || $otpRecord->isUsed()) {
            $otpRecord = EmailVerificationOtp::query()
                ->where('user_id', $user->id)
                ->whereIn('purpose', $config['allowed_purposes'])
                ->whereNull('used_at')
                ->latest()
                ->first();

            if ($otpRecord) {
                $request->session()->put($config['session_key'], $otpRecord->token);
            }
        }

        if (! $otpRecord) {
            if ($config['context'] === self::CONTEXT_INITIAL) {
                try {
                    $otpRecord = app(EmailVerificationService::class)->issue(
                        $user,
                        $user->email,
                        EmailVerificationOtp::PURPOSE_INITIAL
                    );
                } catch (ValidationException $exception) {
                return view('resident.verify-email', $this->verificationViewData(
                    $config['context'],
                    $this->maskEmail($user->email ?? ''),
                    null
                ))->withErrors($exception->errors());
            }

            $request->session()->put($config['session_key'], $otpRecord->token);

            if (! $request->session()->has('status')) {
                    $request->session()->flash('status', 'Kode OTP telah dikirim ke email Anda. Silakan periksa kotak masuk atau folder spam.');
                }
            } else {
                return redirect()
                    ->route('resident.profile')
                    ->with('status', 'Tidak ada permintaan perubahan email yang menunggu verifikasi.')
                    ->with('status_type', 'info');
            }
        }

        $maskedEmailSource = $otpRecord?->email;
        if (! $maskedEmailSource && $config['context'] === self::CONTEXT_INITIAL) {
            $maskedEmailSource = $user->email;
        } elseif (! $maskedEmailSource && $config['context'] === self::CONTEXT_CHANGE) {
            $maskedEmailSource = $user->pending_email;
        }

        return view('resident.verify-email', $this->verificationViewData(
            $config['context'],
            $this->maskEmail($maskedEmailSource ?? ''),
            $otpRecord?->expires_at
        ));
    }

    public function verify(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user || $user->role !== 'warga') {
            Auth::logout();

            return redirect()->route('login');
        }

        $config = $this->resolveContext($request, $request->input('context'));

        if ($config['context'] === self::CONTEXT_INITIAL && $user->email_verified_at !== null) {
            return redirect()->route('resident.dashboard');
        }

        $token = $request->session()->get($config['session_key']);

        if (! $token) {
            return $this->missingTokenRedirect($config['context']);
        }

        $data = $request->validate([
            'otp' => ['required', 'digits:6'],
        ], [
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.digits' => 'Kode OTP harus terdiri dari 6 digit angka.',
        ]);

        try {
            $record = app(EmailVerificationService::class)->verify($token, $data['otp']);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        if ($config['context'] === self::CONTEXT_INITIAL) {
            $user->forceFill([
                'email_verified_at' => now(),
                'pending_email' => null,
            ])->save();

            $request->session()->forget($config['session_key']);

            return redirect()
                ->route('resident.dashboard')
                ->with('status', 'Email berhasil diverifikasi. Selamat datang kembali!');
        }

        $user->forceFill([
            'email' => $record->email,
            'pending_email' => null,
            'email_verified_at' => now(),
        ])->save();

        $request->session()->forget($config['session_key']);

        return redirect()
            ->route('resident.profile')
            ->with('status', 'Email berhasil diperbarui setelah verifikasi.')
            ->with('status_type', 'success');
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user || $user->role !== 'warga') {
            Auth::logout();

            return redirect()->route('login');
        }

        $config = $this->resolveContext($request, $request->input('context', $request->query('context')));

        if ($config['context'] === self::CONTEXT_INITIAL && $user->email_verified_at !== null) {
            return redirect()->route('resident.dashboard');
        }

        if ($config['context'] === self::CONTEXT_CHANGE && blank($user->pending_email)) {
            return redirect()
                ->route('resident.profile')
                ->with('status', 'Tidak ada perubahan email yang menunggu verifikasi.')
                ->with('status_type', 'info');
        }

        $purpose = $config['default_issue_purpose'];
        $currentToken = $request->session()->get($config['session_key']);

        if ($currentToken) {
            $currentRecord = EmailVerificationOtp::query()
                ->whereIn('token', EmailVerificationOtp::tokenLookupValues($currentToken))
                ->first();
            if ($currentRecord) {
                $purpose = $currentRecord->purpose;
            }
        }

        $targetEmail = $config['context'] === self::CONTEXT_CHANGE
            ? ($user->pending_email ?? $user->email)
            : $user->email;

        try {
            $record = app(EmailVerificationService::class)->issue(
                $user,
                $targetEmail,
                $purpose
            );
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        $request->session()->put($config['session_key'], $record->token);

        return redirect()
            ->route('resident.verification.notice', ['context' => $config['context']])
            ->with('status', 'Kode OTP baru telah dikirim ke email tujuan.')
            ->with('status_type', 'info');
    }

    private function maskEmail(?string $email): string
    {
        if (blank($email)) {
            return 'email belum diatur';
        }

        if (! str_contains($email, '@')) {
            return $email;
        }

        [$local, $domain] = explode('@', $email);
        $visible = mb_substr($local, 0, min(2, mb_strlen($local)));
        $maskedLength = max(mb_strlen($local) - mb_strlen($visible), 1);

        return $visible . str_repeat('*', $maskedLength) . '@' . $domain;
    }

    private function resolveContext(Request $request, ?string $context): array
    {
        $context = $context ?? $request->query('context', self::CONTEXT_INITIAL);

        if (! in_array($context, [self::CONTEXT_INITIAL, self::CONTEXT_CHANGE], true)) {
            $context = self::CONTEXT_INITIAL;
        }

        return [
            'context' => $context,
            'session_key' => $context === self::CONTEXT_CHANGE
                ? 'email_verification.change_token'
                : 'email_verification.initial_token',
            'allowed_purposes' => $context === self::CONTEXT_CHANGE
                ? [EmailVerificationOtp::PURPOSE_EMAIL_CHANGE]
                : [
                    EmailVerificationOtp::PURPOSE_INITIAL,
                    EmailVerificationOtp::PURPOSE_LEGACY_ENFORCEMENT,
                ],
            'default_issue_purpose' => $context === self::CONTEXT_CHANGE
                ? EmailVerificationOtp::PURPOSE_EMAIL_CHANGE
                : EmailVerificationOtp::PURPOSE_INITIAL,
        ];
    }

    private function missingTokenRedirect(string $context): RedirectResponse
    {
        if ($context === self::CONTEXT_CHANGE) {
            return redirect()
                ->route('resident.profile')
                ->with('status', 'Permintaan verifikasi email tidak ditemukan. Silakan mulai ulang perubahan email.')
                ->with('status_type', 'error');
        }

        return redirect()
            ->route('resident.verification.notice')
            ->withErrors(['otp' => 'Kode verifikasi belum dikirim atau sudah tidak berlaku.']);
    }

    private function verificationViewData(string $context, string $maskedEmail, $expiresAt): array
    {
        $isChange = $context === self::CONTEXT_CHANGE;

        return [
            'context' => $context,
            'maskedEmail' => $maskedEmail,
            'expiresAt' => $expiresAt,
            'isChange' => $isChange,
            'title' => $isChange ? 'Konfirmasi Email Baru' : 'Verifikasi Email Warga',
            'subtitle' => $isChange
                ? 'Masukkan kode OTP 6 digit yang kami kirim ke email baru Anda.'
                : 'Masukkan kode OTP 6 digit yang kami kirim ke email akun Anda.',
            'welcome' => $isChange
                ? 'Selesaikan verifikasi untuk menyimpan email baru Anda.'
                : 'Selangkah lagi menuju akses penuh ke portal warga.',
            'buttonLabel' => $isChange ? 'Konfirmasi Email Baru' : 'Verifikasi Sekarang',
            'resendPrompt' => $isChange
                ? 'Belum menerima email verifikasi perubahan?'
                : 'Belum menerima email verifikasi?',
            'backRoute' => $isChange ? route('resident.verification.cancel') : route('landing'),
            'backLabel' => $isChange ? 'Kembali ke profil' : 'Kembali ke beranda',
            'site' => $this->siteMeta(),
        ];
    }

    private function siteMeta(): array
    {
        $settings = SiteSetting::keyValue()->toArray();

        return [
            'name' => Arr::get($settings, 'site_name', 'Sistem Informasi RT'),
            'tagline' => Arr::get($settings, 'tagline', 'Kelola lingkungan dengan mudah'),
            'contact_email' => Arr::get($settings, 'contact_email'),
            'contact_phone' => Arr::get($settings, 'contact_phone'),
            'address' => Arr::get($settings, 'address'),
        ];
    }

    public function cancel(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user || $user->role !== 'warga') {
            Auth::logout();

            return redirect()->route('login');
        }

        $pendingEmail = $user->pending_email;

        if ($pendingEmail) {
            EmailVerificationOtp::query()
                ->where('user_id', $user->id)
                ->where('purpose', EmailVerificationOtp::PURPOSE_EMAIL_CHANGE)
                ->whereNull('used_at')
                ->update(['used_at' => now()]);

            $user->forceFill(['pending_email' => null])->save();
        }

        $request->session()->forget('email_verification.change_token');

        return redirect()
            ->route('resident.profile')
            ->with('status', $pendingEmail
                ? 'Perubahan email dibatalkan. Profil Anda tetap menggunakan email sebelumnya.'
                : 'Tidak ada perubahan email yang perlu dibatalkan.')
            ->with('status_type', 'info');
    }
}
