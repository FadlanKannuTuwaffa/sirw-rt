<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class UserLoginDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_fingerprint',
        'device_label',
        'ip_address',
        'user_agent',
        'is_trusted',
        'first_seen_at',
        'last_used_at',
        'last_alerted_at',
    ];

    protected $casts = [
        'is_trusted' => 'boolean',
        'first_seen_at' => 'datetime',
        'last_used_at' => 'datetime',
        'last_alerted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function touchUsage(?Carbon $when = null): void
    {
        $moment = $when ?: now();

        $this->forceFill([
            'last_used_at' => $moment,
        ])->save();
    }
}
