<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramCommand extends Model
{
    use HasFactory;

    protected $fillable = [
        'command',
        'description',
        'type',
        'is_admin_only',
        'is_active',
        'is_system',
        'response_template',
        'meta',
    ];

    protected $casts = [
        'is_admin_only' => 'boolean',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'meta' => 'array',
    ];

    public const TYPE_SYSTEM = 'system';
    public const TYPE_CUSTOM = 'custom';

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_admin_only', false);
    }
}
