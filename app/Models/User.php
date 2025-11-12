<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\HasApiTokens;
use App\Support\SensitiveData;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, HasApiTokens;

    protected $fillable = [
        'name',
        'username',
        'email',
        'pending_email',
        'phone',
        'nik',
        'alamat',
        'role',
        'status',
        'registration_status',
        'password',
        'profile_photo_path',
        'notes',
        'email_verified_at',
        'last_seen_at',
        'telegram_prompt_enabled',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_seen_at' => 'datetime',
        'telegram_prompt_enabled' => 'boolean',
        'experience_preferences' => 'array',
    ];

    protected $appends = [
        'is_online',
        'profile_photo_url',
        'masked_nik',
        'masked_phone',
    ];

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function passwordHistories(): HasMany
    {
        return $this->hasMany(UserPasswordHistory::class);
    }

    public function loginDevices(): HasMany
    {
        return $this->hasMany(UserLoginDevice::class);
    }

    public function createdBills(): HasMany
    {
        return $this->hasMany(Bill::class, 'created_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'created_by');
    }

    public function telegramAccount(): HasOne
    {
        return $this->hasOne(TelegramAccount::class);
    }

    public function telegramLinkTokens(): HasMany
    {
        return $this->hasMany(TelegramLinkToken::class);
    }

    public function passwordResetOtps(): HasMany
    {
        return $this->hasMany(PasswordResetOtp::class);
    }

    public function adminSecurityOtps(): HasMany
    {
        return $this->hasMany(AdminSecurityOtp::class);
    }

    protected function isOnline(): Attribute
    {
        return Attribute::get(fn() => $this->last_seen_at && $this->last_seen_at->gt(now()->subMinutes(3)));
    }

    protected function profilePhotoUrl(): Attribute
    {
        return Attribute::get(function () {
            if (!$this->profile_photo_path) {
                return null;
            }

            if (str_starts_with($this->profile_photo_path, 'http')) {
                return $this->profile_photo_path;
            }

            return URL::temporarySignedRoute(
                'storage.proxy',
                now()->addMinutes(30),
                ['path' => $this->profile_photo_path]
            );
        });
    }

    protected static ?bool $hasPlainNikColumn = null;
    protected static ?bool $hasEncryptedNikColumn = null;
    protected static ?bool $hasNikHashColumn = null;
    protected static ?bool $hasNikLastFourColumn = null;
    protected static ?bool $hasPlainPhoneColumn = null;
    protected static ?bool $hasEncryptedPhoneColumn = null;
    protected static ?bool $hasPhoneHashColumn = null;
    protected static ?bool $hasPhoneLastFourColumn = null;

    protected static ?bool $hasPlainAlamatColumn = null;
    protected static ?bool $hasEncryptedAlamatColumn = null;

    protected function nik(): Attribute
    {
        return Attribute::make(
            get: function ($value, array $attributes) {
                $encrypted = $attributes['nik_encrypted'] ?? null;

                if ($encrypted) {
                    try {
                        return Crypt::decryptString($encrypted);
                    } catch (\Throwable) {
                        // ignore and fallback
                    }
                }

                return $value;
            },
            set: function ($value) {
                $normalized = SensitiveData::normalizeDigits($value);
                $plainColumn = $this->hasPlainNikColumn();
                $encryptedColumn = $this->hasEncryptedNikColumn();
                $hashColumn = $this->hasNikHashColumn();
                $lastFourColumn = $this->hasNikLastFourColumn();

                $payload = [];

                if ($normalized === null) {
                    if ($hashColumn) {
                        $payload['nik_hash'] = null;
                    }
                    if ($lastFourColumn) {
                        $payload['nik_last_four'] = null;
                    }
                    if ($plainColumn) {
                        $payload['nik'] = null;
                    }
                    if ($encryptedColumn) {
                        $payload['nik_encrypted'] = null;
                    }

                    return $payload;
                }

                if ($hashColumn) {
                    $payload['nik_hash'] = SensitiveData::hash($normalized);
                }

                if ($lastFourColumn) {
                    $payload['nik_last_four'] = substr($normalized, -4);
                }

                if ($plainColumn) {
                    $payload['nik'] = $normalized;
                }

                if ($encryptedColumn) {
                    $payload['nik_encrypted'] = Crypt::encryptString($normalized);
                }

                return $payload;
            }
        );
    }

    protected function phone(): Attribute
    {
        return Attribute::make(
            get: function ($value, array $attributes) {
                $encrypted = $attributes['phone_encrypted'] ?? null;

                if ($encrypted) {
                    try {
                        return Crypt::decryptString($encrypted);
                    } catch (\Throwable) {
                        // ignore and fallback
                    }
                }

                return $value;
            },
            set: function ($value) {
                $normalized = SensitiveData::normalizeDigits($value);

                $plainColumn = $this->hasPlainPhoneColumn();
                $encryptedColumn = $this->hasEncryptedPhoneColumn();
                $hashColumn = $this->hasPhoneHashColumn();
                $lastFourColumn = $this->hasPhoneLastFourColumn();

                $payload = [];

                if ($normalized === null) {
                    if ($hashColumn) {
                        $payload['phone_hash'] = null;
                    }
                    if ($lastFourColumn) {
                        $payload['phone_last_four'] = null;
                    }
                    if ($plainColumn) {
                        $payload['phone'] = null;
                    }
                    if ($encryptedColumn) {
                        $payload['phone_encrypted'] = null;
                    }

                    return $payload;
                }

                if ($hashColumn) {
                    $payload['phone_hash'] = SensitiveData::hash($normalized);
                }

                if ($lastFourColumn) {
                    $payload['phone_last_four'] = substr($normalized, -4);
                }

                if ($plainColumn) {
                    $payload['phone'] = $normalized;
                }

                if ($encryptedColumn) {
                    $payload['phone_encrypted'] = Crypt::encryptString($normalized);
                }

                return $payload;
            }
        );
    }

    protected function alamat(): Attribute
    {
        return Attribute::make(
            get: function ($value, array $attributes) {
                $encrypted = $attributes['alamat_encrypted'] ?? null;

                if ($encrypted) {
                    try {
                        return Crypt::decryptString($encrypted);
                    } catch (\Throwable) {
                        // fall through to attempt other sources
                    }
                }

                if ($value) {
                    try {
                        return Crypt::decryptString((string) $value);
                    } catch (\Throwable) {
                        return $value;
                    }
                }

                return null;
            },
            set: function ($value) {
                $plainColumn = $this->hasPlainAlamatColumn();
                $encryptedColumn = $this->hasEncryptedAlamatColumn();

                if ($value === null || trim((string) $value) === '') {
                    $payload = [];
                    if ($plainColumn) {
                        $payload['alamat'] = null;
                    }
                    if ($encryptedColumn) {
                        $payload['alamat_encrypted'] = null;
                    }

                    return $payload;
                }

                $trimmed = trim((string) $value);
                $payload = [];

                if ($plainColumn) {
                    $payload['alamat'] = $trimmed;
                }

                if ($encryptedColumn) {
                    $payload['alamat_encrypted'] = Crypt::encryptString($trimmed);
                }

                return $payload;
            }
        );
    }

    protected function maskedNik(): Attribute
    {
        return Attribute::get(fn () => SensitiveData::maskTrailing($this->nik));
    }

    protected function maskedPhone(): Attribute
    {
        return Attribute::get(fn () => SensitiveData::maskTrailing($this->phone));
    }

    public function decryptAttribute(string $key): ?string
    {
        $value = $this->getAttributes()[$key] ?? null;

        if ($value === null) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return null;
        }
    }

    public function scopeResidents($query)
    {
        return $query->where('role', 'warga');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    private function hasPlainNikColumn(): bool
    {
        if (static::$hasPlainNikColumn === null) {
            static::$hasPlainNikColumn = Schema::hasColumn($this->getTable(), 'nik');
        }

        return (bool) static::$hasPlainNikColumn;
    }

    private function hasEncryptedNikColumn(): bool
    {
        if (static::$hasEncryptedNikColumn === null) {
            static::$hasEncryptedNikColumn = Schema::hasColumn($this->getTable(), 'nik_encrypted');
        }

        return (bool) static::$hasEncryptedNikColumn;
    }

    private function hasNikHashColumn(): bool
    {
        if (static::$hasNikHashColumn === null) {
            static::$hasNikHashColumn = Schema::hasColumn($this->getTable(), 'nik_hash');
        }

        return (bool) static::$hasNikHashColumn;
    }

    private function hasNikLastFourColumn(): bool
    {
        if (static::$hasNikLastFourColumn === null) {
            static::$hasNikLastFourColumn = Schema::hasColumn($this->getTable(), 'nik_last_four');
        }

        return (bool) static::$hasNikLastFourColumn;
    }

    private function hasPlainPhoneColumn(): bool
    {
        if (static::$hasPlainPhoneColumn === null) {
            static::$hasPlainPhoneColumn = Schema::hasColumn($this->getTable(), 'phone');
        }

        return (bool) static::$hasPlainPhoneColumn;
    }

    private function hasEncryptedPhoneColumn(): bool
    {
        if (static::$hasEncryptedPhoneColumn === null) {
            static::$hasEncryptedPhoneColumn = Schema::hasColumn($this->getTable(), 'phone_encrypted');
        }

        return (bool) static::$hasEncryptedPhoneColumn;
    }

    private function hasPhoneHashColumn(): bool
    {
        if (static::$hasPhoneHashColumn === null) {
            static::$hasPhoneHashColumn = Schema::hasColumn($this->getTable(), 'phone_hash');
        }

        return (bool) static::$hasPhoneHashColumn;
    }

    private function hasPhoneLastFourColumn(): bool
    {
        if (static::$hasPhoneLastFourColumn === null) {
            static::$hasPhoneLastFourColumn = Schema::hasColumn($this->getTable(), 'phone_last_four');
        }

        return (bool) static::$hasPhoneLastFourColumn;
    }

    private function hasPlainAlamatColumn(): bool
    {
        if (static::$hasPlainAlamatColumn === null) {
            static::$hasPlainAlamatColumn = Schema::hasColumn($this->getTable(), 'alamat');
        }

        return (bool) static::$hasPlainAlamatColumn;
    }

    private function hasEncryptedAlamatColumn(): bool
    {
        if (static::$hasEncryptedAlamatColumn === null) {
            static::$hasEncryptedAlamatColumn = Schema::hasColumn($this->getTable(), 'alamat_encrypted');
        }

        return (bool) static::$hasEncryptedAlamatColumn;
    }
}

