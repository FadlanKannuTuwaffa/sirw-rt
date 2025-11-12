<?php

namespace App\Livewire\Admin\Profil;

use App\Models\AdminSecurityOtp;
use App\Models\User;
use App\Notifications\Security\AdminSuspiciousChangeNotification;
use App\Rules\NotInPasswordHistory;
use App\Rules\StrongPassword;
use App\Services\Security\AccountSecurityService;
use App\Services\Security\AdminSecurityChallengeService;
use App\Services\Security\AdminSensitiveAttemptService;
use App\Services\Security\PasswordHistoryService;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class EditProfile extends Component
{
    use WithFileUploads;

    public string $name = '';
    public ?string $username = null;
    public ?string $phone = null;
    public ?string $notes = null;
    public $profilePhoto;

    public ?string $currentEmail = null;

    public ?string $newEmail = null;
    public ?string $emailCurrentPassword = null;
    public ?string $emailOtp = null;
    public ?string $emailChangeToken = null;
    public bool $emailOtpSent = false;
    public ?string $pendingEmail = null;
    public ?string $emailOtpExpiresAt = null;

    public ?string $passwordCurrent = null;
    public ?string $passwordNew = null;
    public ?string $passwordNewConfirmation = null;
    public ?string $passwordOtp = null;
    public ?string $passwordChangeToken = null;
    public bool $passwordOtpSent = false;

    public function mount(): void
    {
        $user = $this->currentUser();

        $this->fill([
            'name' => $user->name,
            'username' => $user->username,
            'phone' => $user->phone,
            'notes' => $user->notes,
            'currentEmail' => $user->email,
            'newEmail' => $user->email,
            'pendingEmail' => $user->pending_email,
        ]);
    }

    public function render()
    {
        return view('livewire.admin.profil.edit-profile', [
            'user' => $this->currentUser(),
        ])->layout('layouts.admin', [
            'title' => 'Ubah Profil Admin',
        ]);
    }

    public function updateProfile(): void
    {
        $user = $this->currentUser();

        $data = $this->validate([
            'name' => ['required', 'string', 'max:160'],
            'username' => ['nullable', 'alpha_dash', 'min:4', 'max:40', Rule::unique('users', 'username')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string'],
            'profilePhoto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $payload = [
            'name' => $data['name'],
            'username' => $data['username'],
            'phone' => $data['phone'],
            'notes' => $data['notes'] ?? null,
        ];

        $storedPhotoPath = null;

        if ($this->profilePhoto) {
            $storedPhotoPath = $this->profilePhoto->store('avatars', 'public');
            $payload['profile_photo_path'] = $storedPhotoPath;
        }

        try {
            $user->update($payload);
        } catch (Throwable $exception) {
            if ($storedPhotoPath) {
                Storage::disk('public')->delete($storedPhotoPath);
            }

            report($exception);

            session()->flash('status', 'Terjadi kesalahan saat memperbarui profil. Silakan coba lagi.');
            session()->flash('status_type', 'error');

            return;
        }

        if ($storedPhotoPath) {
            $this->profilePhoto = null;
        }

        session()->flash('status', 'Profil dasar berhasil diperbarui.');
        session()->flash('status_type', 'success');
    }

    public function initiateEmailChange(): void
    {
        $user = $this->currentUser();

        $data = $this->validate([
            'newEmail' => ['required', 'email', 'max:160', Rule::unique('users', 'email')->ignore($user->id)],
            'emailCurrentPassword' => ['required', 'string'],
        ], [
            'emailCurrentPassword.required' => 'Password saat ini wajib diisi untuk mengganti email.',
        ]);

        $normalizedEmail = Str::lower(trim($data['newEmail']));
        $currentEmail = Str::lower((string) $user->email);

        if ($normalizedEmail === $currentEmail) {
            $this->addError('newEmail', 'Email baru tidak boleh sama dengan email yang sedang digunakan.');

            return;
        }

        if (! Hash::check($data['emailCurrentPassword'], $user->getAuthPassword())) {
            $this->addError('emailCurrentPassword', 'Password saat ini tidak sesuai dengan catatan kami.');
            $this->registerSensitiveAttempt($user, 'email_change', [
                'reason' => 'invalid_password',
            ]);

            return;
        }

        try {
            $record = app(AdminSecurityChallengeService::class)->issue(
                $user,
                AdminSecurityOtp::PURPOSE_EMAIL_CHANGE,
                [
                    'new_email' => $normalizedEmail,
                    'ip_address' => request()->ip(),
                    'device' => Str::limit((string) request()->userAgent(), 160),
                ]
            );
        } catch (ValidationException $exception) {
            $this->addError('newEmail', $this->extractValidationMessage($exception));

            return;
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('newEmail', 'Gagal mengirim kode OTP. Silakan coba lagi nanti.');

            return;
        }

        $user->forceFill(['pending_email' => $normalizedEmail])->save();

        $this->emailChangeToken = $record->token;
        $this->emailOtpSent = true;
        $this->pendingEmail = $normalizedEmail;
        $this->emailOtp = null;
        $this->emailOtpExpiresAt = optional($record->expires_at)
            ?->setTimezone(config('app.timezone'))
            ->format('d M Y H:i');

        session()->flash('status', 'Kode OTP telah dikirim ke email lama Anda. Verifikasi untuk menyelesaikan perubahan.');
        session()->flash('status_type', 'info');
    }

    public function verifyEmailOtp(): void
    {
        if (! $this->emailOtpSent || blank($this->emailChangeToken)) {
            $this->addError('emailOtp', 'Tidak ada permintaan OTP yang aktif. Silakan mulai ulang proses penggantian email.');

            return;
        }

        $user = $this->currentUser();

        $data = $this->validate([
            'emailOtp' => ['required', 'digits:6'],
        ], [
            'emailOtp.required' => 'Kode OTP wajib diisi.',
            'emailOtp.digits' => 'Kode OTP harus 6 digit angka.',
        ]);

        try {
            $record = app(AdminSecurityChallengeService::class)->verify($this->emailChangeToken, $data['emailOtp']);
        } catch (ValidationException $exception) {
            $this->addError('emailOtp', $exception->getMessage());
            $this->registerSensitiveAttempt($user, 'email_change', [
                'reason' => 'invalid_otp',
            ]);

            return;
        }

        $emailToApply = $this->pendingEmail ?: Arr::get($record->meta ?? [], 'new_email');

        if (blank($emailToApply)) {
            $this->addError('emailOtp', 'Email baru tidak ditemukan pada permintaan ini. Silakan mulai ulang proses.');

            return;
        }

        $user->forceFill([
            'email' => Str::lower($emailToApply),
            'pending_email' => null,
            'email_verified_at' => now(),
        ])->save();

        app(AdminSensitiveAttemptService::class)->clear($user);

        $fresh = $user->fresh();
        $this->currentEmail = $fresh?->email ?? $emailToApply;
        $this->newEmail = $this->currentEmail;
        $this->pendingEmail = null;
        $this->emailOtpSent = false;
        $this->emailOtp = null;
        $this->emailChangeToken = null;
        $this->emailCurrentPassword = null;
        $this->emailOtpExpiresAt = null;

        session()->flash('status', 'Email admin berhasil diperbarui.');
        session()->flash('status_type', 'success');
    }

    public function cancelEmailOtp(): void
    {
        $user = $this->currentUser();
        $this->emailOtpSent = false;
        $this->emailChangeToken = null;
        $this->emailOtp = null;
        $this->emailOtpExpiresAt = null;
        $this->emailCurrentPassword = null;
        $this->pendingEmail = $user->pending_email;
    }

    public function cancelPasswordOtp(): void
    {
        $this->passwordOtpSent = false;
        $this->passwordChangeToken = null;
        $this->passwordOtp = null;
    }

    public function initiatePasswordChange(): void
    {
        $user = $this->currentUser();

        $data = $this->validate([
            'passwordCurrent' => ['required', 'string'],
            'passwordNew' => ['required', 'string', 'min:8', 'same:passwordNewConfirmation', new StrongPassword(), new NotInPasswordHistory($user)],
            'passwordNewConfirmation' => ['required', 'string', 'min:8'],
        ], [
            'passwordCurrent.required' => 'Password saat ini wajib diisi.',
            'passwordNew.same' => 'Konfirmasi password baru belum sesuai.',
        ]);

        if (! Hash::check($data['passwordCurrent'], $user->getAuthPassword())) {
            $this->addError('passwordCurrent', 'Password saat ini tidak sesuai.');
            $this->registerSensitiveAttempt($user, 'password_change', [
                'reason' => 'invalid_password',
            ]);

            return;
        }

        try {
            $record = app(AdminSecurityChallengeService::class)->issue(
                $user,
                AdminSecurityOtp::PURPOSE_PASSWORD_CHANGE,
                [
                    'ip_address' => request()->ip(),
                    'device' => Str::limit((string) request()->userAgent(), 160),
                ]
            );
        } catch (ValidationException $exception) {
            $this->addError('passwordNew', $this->extractValidationMessage($exception));

            return;
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('passwordNew', 'Gagal mengirim kode OTP. Silakan coba beberapa saat lagi.');

            return;
        }

        $this->passwordChangeToken = $record->token;
        $this->passwordOtpSent = true;
        $this->passwordOtp = null;

        session()->flash('status', 'Kode OTP keamanan telah dikirim. Masukkan kode untuk mengganti password.');
        session()->flash('status_type', 'info');
    }

    public function verifyPasswordOtp(): void
    {
        if (! $this->passwordOtpSent || blank($this->passwordChangeToken)) {
            $this->addError('passwordOtp', 'Tidak ada permintaan penggantian password yang aktif.');

            return;
        }

        $user = $this->currentUser();

        $data = $this->validate([
            'passwordOtp' => ['required', 'digits:6'],
        ], [
            'passwordOtp.required' => 'Kode OTP wajib diisi.',
            'passwordOtp.digits' => 'Kode OTP harus 6 digit angka.',
        ]);

        try {
            app(AdminSecurityChallengeService::class)->verify($this->passwordChangeToken, $data['passwordOtp']);
        } catch (ValidationException $exception) {
            $this->addError('passwordOtp', $exception->getMessage());
            $this->registerSensitiveAttempt($user, 'password_change', [
                'reason' => 'invalid_otp',
            ]);

            return;
        }

        if (blank($this->passwordNew)) {
            $this->addError('passwordNew', 'Password baru tidak ditemukan. Silakan mulai ulang proses.');

            return;
        }

        $user->forceFill(['password' => $this->passwordNew])->save();

        $snapshot = $user->fresh() ?? $user;
        app(PasswordHistoryService::class)->record($snapshot);

        $currentSessionId = session()->getId();
        app(AccountSecurityService::class)->invalidateSessions($snapshot, $currentSessionId);
        session()->migrate(true);

        app(AdminSensitiveAttemptService::class)->clear($user);

        $this->passwordCurrent = null;
        $this->passwordNew = null;
        $this->passwordNewConfirmation = null;
        $this->passwordOtp = null;
        $this->passwordChangeToken = null;
        $this->passwordOtpSent = false;

        session()->flash('status', 'Password admin berhasil diperbarui dan sesi lain telah dinonaktifkan.');
        session()->flash('status_type', 'success');
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }

    private function registerSensitiveAttempt(User $user, string $action, array $meta = []): void
    {
        $service = app(AdminSensitiveAttemptService::class);
        $exceeded = $service->record($user, $action, $meta);

        if ($exceeded) {
            $this->handleLockout($user, $action);
        }
    }

    private function handleLockout(User $user, string $action): void
    {
        $service = app(AdminSensitiveAttemptService::class);
        $attempts = $service->attempts($user);
        $history = $service->history($user);

        Notification::send(
            $user,
            new AdminSuspiciousChangeNotification($user, $action, $attempts, $history)
        );

        app(AccountSecurityService::class)->invalidateSessions($user);

        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        $service->clear($user);

        session()->flash('status', 'Untuk keamanan, kami mengeluarkan sesi Anda. Silakan login ulang.');
        session()->flash('status_type', 'error');

        $this->redirectRoute('login', navigate: true);

        return;
    }

    private function extractValidationMessage(ValidationException $exception): string
    {
        $errors = $exception->errors();

        if (empty($errors)) {
            return $exception->getMessage();
        }

        $first = Arr::first($errors);

        if (is_array($first)) {
            return (string) ($first[0] ?? $exception->getMessage());
        }

        return (string) $first;
    }
}
