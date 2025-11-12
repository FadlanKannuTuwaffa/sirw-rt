<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmailVerificationOtp;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Services\Security\AccountSecurityService;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private readonly AccountSecurityService $accountSecurity
    ) {
    }

    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            $redirect = Auth::user()->isAdmin() ? route('admin.dashboard') : route('resident.dashboard');
            return redirect()->intended($redirect);
        }

        return view('auth.login', [
            'site' => $this->siteMeta(),
            'title' => 'Masuk Akun',
            'subtitle' => 'Gunakan email/username dan password yang sudah terdaftar.',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'identifier' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'identifier.required' => 'Silakan masukkan email atau username Anda.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $user = $this->attemptUserLookup($credentials['identifier']);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'identifier' => 'Email atau password tidak sesuai. Coba lagi atau gunakan akun lain.',
            ]);
        }

        if ($user->role === 'warga' && in_array($user->status, ['nonaktif', 'pindah'], true)) {
            throw ValidationException::withMessages([
                'identifier' => 'Akun Anda belum aktif. Hubungi pengurus untuk reaktivasi.',
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $this->accountSecurity->handleLogin($user, $request);

        if ($user->role === 'warga' && $user->email_verified_at === null) {
            try {
                $verification = app(EmailVerificationService::class)->issue(
                    $user,
                    $user->email,
                    EmailVerificationOtp::PURPOSE_INITIAL
                );
            } catch (ValidationException $exception) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $message = $exception->errors()['email'][0] ?? 'Email akun ini belum tersedia. Hubungi pengurus RT.';

                throw ValidationException::withMessages([
                    'identifier' => $message,
                ]);
            }

            $request->session()->put('email_verification.initial_token', $verification->token);

            return redirect()->route('resident.verification.notice')
                ->with('status', 'Kami telah mengirim kode OTP ke email Anda. Silakan verifikasi untuk melanjutkan.');
        }

        $redirect = $user->isAdmin() ? route('admin.dashboard') : route('resident.dashboard');

        return redirect()->intended($redirect);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing');
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
}
