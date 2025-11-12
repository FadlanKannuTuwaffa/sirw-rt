<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordOtpMail;
use App\Models\PasswordResetOtp;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\Security\AccountSecurityService;
use App\Services\Security\PasswordHistoryService;
use App\Rules\NotInPasswordHistory;
use App\Rules\StrongPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function showIdentifierForm(): View
    {
        return view('auth.passwords.identifier', [
            'site' => $this->siteMeta(),
            'title' => 'Reset Password',
            'subtitle' => 'Masukkan email atau username untuk memulai proses reset password.',
        ]);
    }

    public function handleIdentifier(Request $request): RedirectResponse
    {
        $this->enforceThrottle($request);

        $data = $request->validate([
            'identifier' => ['required', 'string'],
        ], [
            'identifier.required' => 'Silakan isi email atau username yang terdaftar.',
        ]);

        $identifier = $data['identifier'];
        $user = $this->attemptUserLookup($identifier);

        if (! $user || blank($user->email)) {
            $this->fakeDelay();

            return $this->otpDispatchedResponse();
        }

        $maxAttempts = 3;
        $requestsToday = PasswordResetOtp::query()
            ->where('user_id', $user->id)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        if ($requestsToday >= $maxAttempts) {
            $this->fakeDelay();

            return $this->otpDispatchedResponse();
        }

        PasswordResetOtp::query()
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $otp = (string) random_int(100000, 999999);
        $token = Str::random(64);

        $otpRequest = PasswordResetOtp::create([
            'user_id' => $user->id,
            'token' => $token,
            'otp_code' => Hash::make($otp),
            'expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($user->email)->send(
            new ResetPasswordOtpMail($user, $otp, $otpRequest->expires_at, $token)
        );

        return $this->otpDispatchedResponse();
    }

    public function showOtpForm(string $token): RedirectResponse|View
    {
        $otpRequest = PasswordResetOtp::with('user')
            ->whereIn('token', PasswordResetOtp::tokenLookupValues($token))
            ->first();

        if (! $otpRequest || $otpRequest->isUsed() || $otpRequest->isExpired()) {
            return redirect()
                ->route('password.request')
                ->withErrors(['identifier' => 'Permintaan reset password sudah tidak berlaku. Silakan ulangi proses dari awal.']);
        }

        return view('auth.passwords.otp', [
            'site' => $this->siteMeta(),
            'title' => 'Verifikasi OTP',
            'subtitle' => 'Masukkan kode OTP 6 digit yang dikirim ke email Anda.',
            'token' => $token,
            'maskedEmail' => $this->maskEmail($otpRequest->user->email),
        ]);
    }

    public function verifyOtp(Request $request, string $token): RedirectResponse
    {
        $otpRequest = PasswordResetOtp::with('user')
            ->whereIn('token', PasswordResetOtp::tokenLookupValues($token))
            ->first();

        if (! $otpRequest || $otpRequest->isUsed() || $otpRequest->isExpired()) {
            return redirect()
                ->route('password.request')
                ->withErrors(['identifier' => 'Permintaan reset password sudah tidak berlaku. Silakan ulangi proses dari awal.']);
        }

        $attemptKey = $this->otpAttemptKey($request, $token);

        if (RateLimiter::tooManyAttempts($attemptKey, 5)) {
            $otpRequest->markUsed();

            return redirect()
                ->route('password.request')
                ->withErrors(['identifier' => 'Kode OTP sudah tidak berlaku. Silakan minta kode baru.']);
        }

        $data = $request->validate([
            'otp' => ['required', 'digits:6'],
        ], [
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.digits' => 'Kode OTP harus terdiri dari 6 digit angka.',
        ]);

        if (! Hash::check($data['otp'], $otpRequest->otp_code)) {
            RateLimiter::hit($attemptKey, 900);
            $this->fakeDelay();

            throw ValidationException::withMessages([
                'otp' => 'Kode OTP tidak sesuai atau sudah tidak berlaku. Periksa kembali atau minta kode baru.',
            ]);
        }

        RateLimiter::clear($attemptKey);

        if ($otpRequest->verified_at === null) {
            $otpRequest->markVerified();
        }

        session()->put('password_reset_verified_token', $token);

        return redirect()
            ->route('password.reset.show', ['token' => $token])
            ->with('status', 'Kode OTP berhasil diverifikasi. Silakan buat password baru.')
            ->with('status_type', 'success');
    }

    public function showResetForm(Request $request, string $token): RedirectResponse|View
    {
        $otpRequest = PasswordResetOtp::with('user')
            ->whereIn('token', PasswordResetOtp::tokenLookupValues($token))
            ->first();

        if (! $otpRequest || $otpRequest->isUsed() || $otpRequest->verified_at === null) {
            return redirect()
                ->route('password.request')
                ->withErrors(['identifier' => 'Sesi reset password sudah tidak berlaku. Silakan ulangi proses dari awal.']);
        }

        if ($request->session()->get('password_reset_verified_token') !== $token) {
            return redirect()
                ->route('password.otp.show', ['token' => $token])
                ->withErrors(['otp' => 'Silakan verifikasi kode OTP terlebih dahulu.']);
        }

        return view('auth.passwords.reset', [
            'site' => $this->siteMeta(),
            'title' => 'Buat Password Baru',
            'subtitle' => 'Gunakan password yang kuat dan mudah diingat.',
            'token' => $token,
        ]);
    }

    public function resetPassword(Request $request, string $token): RedirectResponse
    {
        $otpRequest = PasswordResetOtp::with('user')
            ->whereIn('token', PasswordResetOtp::tokenLookupValues($token))
            ->first();

        if (! $otpRequest || $otpRequest->isUsed() || $otpRequest->verified_at === null) {
            return redirect()
                ->route('password.request')
                ->withErrors(['identifier' => 'Sesi reset password sudah tidak berlaku. Silakan ulangi proses dari awal.']);
        }

        if ($request->session()->get('password_reset_verified_token') !== $token) {
            return redirect()
                ->route('password.otp.show', ['token' => $token])
                ->withErrors(['otp' => 'Silakan verifikasi kode OTP terlebih dahulu.']);
        }

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed', new StrongPassword(), new NotInPasswordHistory($otpRequest->user)],
        ], [
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal terdiri dari 8 karakter.',
            'password.confirmed' => 'Konfirmasi password belum sesuai.',
        ]);

        $user = $otpRequest->user;


        $user->forceFill(['password' => $data['password']])->save();

        $snapshot = $user->fresh() ?? $user;

        app(PasswordHistoryService::class)->record($snapshot);

        $otpRequest->markUsed();
        session()->forget('password_reset_verified_token');

        app(AccountSecurityService::class)->invalidateSessions($snapshot);

        return redirect()
            ->route('login')
            ->with('status', 'Password berhasil diperbarui. Silakan masuk menggunakan password baru.');
    }

    private function enforceThrottle(Request $request): void
    {
        $key = $this->rateLimiterKey($request);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'identifier' => 'Permintaan reset password sementara dibatasi. Coba lagi dalam beberapa menit.',
            ]);
        }

        RateLimiter::hit($key, 900);
    }

    private function rateLimiterKey(Request $request): string
    {
        $identifier = Str::lower((string) $request->input('identifier', ''));
        $ip = (string) $request->ip();

        return 'password-reset:identifier:' . hash('sha256', $identifier . '|' . $ip);
    }

    private function otpAttemptKey(Request $request, string $token): string
    {
        return 'password-reset:otp:' . hash('sha256', $token . '|' . (string) $request->ip());
    }

    private function fakeDelay(): void
    {
        usleep(random_int(200, 400) * 1000);
    }

    private function otpDispatchedResponse(): RedirectResponse
    {
        return redirect()
            ->route('password.request')
            ->with('status', 'Jika data cocok, kami telah mengirim instruksi reset password ke email terverifikasi. Periksa kotak masuk dan folder spam.')
            ->with('status_type', 'info');
    }

    private function attemptUserLookup(string $identifier): ?User
    {
        $query = User::query();

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return $query->where('email', $identifier)->first();
        }

        return $query->where('username', $identifier)->first();
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

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email);
        $visible = mb_substr($local, 0, min(2, mb_strlen($local)));
        $maskedLength = max(mb_strlen($local) - mb_strlen($visible), 1);

        return $visible . str_repeat('*', $maskedLength) . '@' . $domain;
    }
}

