<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramLinkToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
        'used_at',
        'meta',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query
            ->whereNull('used_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function markUsed(array $extraMeta = []): void
    {
        $payload = [
            'used_at' => now(),
        ];

        if (! empty($extraMeta)) {
            $payload['meta'] = array_merge($this->meta ?? [], $extraMeta);
        }

        $this->forceFill($payload)->save();
    }
}
