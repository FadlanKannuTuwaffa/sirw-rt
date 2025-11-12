<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CitizenRecord;
use App\Models\EmailVerificationOtp;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\EmailVerificationService;
use App\Services\Security\AccountSecurityService;
use App\Services\Security\PasswordHistoryService;
use App\Support\SensitiveData;
use App\Rules\StrongPassword;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class RegisteredUserController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            $redirect = Auth::user()->isAdmin() ? route('admin.dashboard') : route('resident.dashboard');
            return redirect()->intended($redirect);
        }

        return view('auth.register', [
            'site' => $this->siteMeta(),
            'title' => 'Daftar Akun Warga',
            'subtitle' => 'Masukkan data sesuai kartu kependudukan. Admin akan memverifikasi secara otomatis.',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'string', 'email', 'max:160'],
            'username' => ['nullable', 'string', 'alpha_dash', 'min:4', 'max:40'],
            'phone' => ['nullable', 'string', 'max:40'],
            'nik' => ['required', 'string', 'regex:/^[0-9]{16}$/'],
            'alamat' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed', new StrongPassword()],
        ], [
            'nik.required' => 'NIK wajib diisi agar kami dapat mencocokkan data.',
            'nik.regex' => 'NIK harus terdiri dari 16 digit angka.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $sanitizedNik = preg_replace('/[^0-9]/', '', $data['nik']);

        $candidate = User::query()
            ->where('role', 'warga')
            ->where('nik_hash', SensitiveData::hash($sanitizedNik))
            ->first();

        if (! $candidate) {
            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['nik' => 'Data Anda belum terdaftar. Hubungi pengurus RT untuk menambahkan data penduduk.']);
        }

        if ($candidate->registration_status === 'active') {
            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['nik' => 'Akun dengan NIK tersebut sudah aktif. Silakan masuk menggunakan kredensial yang pernah dibuat.']);
        }

        $record = CitizenRecord::where('nik', $sanitizedNik)->first();

        $expectedName = $record?->nama ?? $candidate->name ?? '';
        $expectedEmail = $record?->email ?? $candidate->email ?? '';
        $expectedAddress = $record?->alamat ?? $candidate->alamat ?? '';

        $nameMatches = Str::of($expectedName)->lower()->squish()->value() === Str::of($data['name'])->lower()->squish()->value();
        $emailMatches = Str::lower($expectedEmail) === Str::lower($data['email']);
        $addressMatches = Str::of($expectedAddress)->lower()->squish()->value() === Str::of($data['alamat'])->lower()->squish()->value();

        if (! ($nameMatches && $emailMatches && $addressMatches)) {
            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['nik' => 'Data registrasi tidak cocok dengan data yang telah disiapkan admin. Mohon periksa kembali.']);
        }

        if (filled($data['username']) && User::where('username', $data['username'])->where('id', '!=', $candidate->id)->exists()) {
            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['username' => 'Username sudah digunakan oleh akun lain.']);
        }

        $historyService = app(PasswordHistoryService::class);

        if ($historyService->hasBeenUsed($candidate, $data['password'])) {
            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['password' => 'Password ini sudah pernah digunakan. Silakan pilih password lain.']);
        }

        $username = filled($data['username'])
            ? Str::lower($data['username'])
            : ($candidate->username ?: $this->generateUsername($data['name']));

        $phone = filled($data['phone']) ? trim($data['phone']) : $candidate->phone;

        $user = DB::transaction(function () use ($candidate, $data, $username, $phone, $sanitizedNik) {
            $candidate->forceFill([
                'name' => trim($data['name']),
                'email' => Str::lower(trim($data['email'])),
                'username' => $username,
                'phone' => $phone ? Str::of($phone)->squish()->value() : null,
                'alamat' => Str::of($data['alamat'])->squish()->value(),
                'nik' => $sanitizedNik,
                'password' => Hash::make($data['password']),
                'status' => 'aktif',
                'registration_status' => 'active',
                'role' => 'warga',
                'email_verified_at' => null,
            ])->save();

            app(PasswordHistoryService::class)->record($candidate->fresh() ?? $candidate);

            CitizenRecord::updateOrCreate(
                ['nik' => $candidate->nik],
                [
                    'nama' => $candidate->name,
                    'email' => $candidate->email,
                    'alamat' => $candidate->alamat,
                    'status' => 'claimed',
                    'claimed_by' => $candidate->id,
                ]
            );

            return $candidate->fresh();
        });

        event(new Registered($user));
        Auth::login($user);

        app(AccountSecurityService::class)->handleLogin($user, $request);

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

            throw ValidationException::withMessages([
                'email' => $exception->errors()['email'][0] ?? 'Email akun Anda belum tersedia untuk verifikasi.',
            ]);
        }

        $request->session()->put('email_verification.initial_token', $verification->token);

        return redirect()
            ->route('resident.verification.notice')
            ->with('status', 'Pendaftaran berhasil. Kode OTP telah dikirim ke email Anda untuk verifikasi.');
    }

    private function generateUsername(string $name): string
    {
        $base = strtolower(preg_replace('/[^a-z0-9]+/i', '.', $name));
        $base = trim($base, '.');
        if ($base === '') {
            $base = 'warga';
        }

        $username = $base;
        $counter = 1;
        while (User::where('username', $username)->exists()) {
            $username = $base . $counter;
            $counter++;
        }

        return $username;
    }

    private function siteMeta(): array
    {
        $settings = SiteSetting::keyValue()->toArray();

        return [
            'name' => Arr::get($settings, 'site_name', 'Sistem Informasi RT'),
            'tagline' => Arr::get($settings, 'tagline'),
            'contact_email' => Arr::get($settings, 'contact_email'),
            'contact_phone' => Arr::get($settings, 'contact_phone'),
            'address' => Arr::get($settings, 'address'),
        ];
    }
}
