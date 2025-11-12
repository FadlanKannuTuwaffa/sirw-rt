<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'telegram_user_id',
        'telegram_chat_id',
        'username',
        'first_name',
        'last_name',
        'language_code',
        'receive_notifications',
        'linked_at',
        'unlinked_at',
        'last_interaction_at',
        'preferences',
    ];

    protected $casts = [
        'receive_notifications' => 'boolean',
        'linked_at' => 'datetime',
        'unlinked_at' => 'datetime',
        'last_interaction_at' => 'datetime',
        'preferences' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('unlinked_at');
    }

    protected function isActive(): Attribute
    {
        return Attribute::get(fn () => is_null($this->unlinked_at));
    }
}
