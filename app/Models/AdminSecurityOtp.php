<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class AdminSecurityOtp extends Model
{
    use HasFactory;

    public const PURPOSE_EMAIL_CHANGE = 'email_change';
    public const PURPOSE_PASSWORD_CHANGE = 'password_change';

    protected $fillable = [
        'user_id',
        'purpose',
        'token',
        'otp_code',
        'channels',
        'meta',
        'expires_at',
        'verified_at',
        'used_at',
    ];

    protected $casts = [
        'channels' => 'array',
        'meta' => 'array',
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    protected function token(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => filled($value) ? static::hashToken((string) $value) : null
        );
    }

    public static function hashToken(?string $token): string
    {
        $normalized = trim((string) $token);

        if ($normalized === '') {
            return '';
        }

        if (strlen($normalized) === 64 && ctype_xdigit($normalized)) {
            return strtolower($normalized);
        }

        return hash('sha256', $normalized);
    }

    public static function tokenLookupValues(string $token): array
    {
        $hash = static::hashToken($token);

        return $hash === $token ? [$hash] : [$hash, $token];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markVerified(): void
    {
        $this->forceFill(['verified_at' => now()])->save();
    }

    public function markUsed(): void
    {
        $this->forceFill(['used_at' => now()])->save();
    }

    public function isExpired(): bool
    {
        return $this->expires_at instanceof Carbon && $this->expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }
}
