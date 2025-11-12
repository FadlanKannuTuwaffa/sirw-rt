<?php

namespace App\Models;

use App\Services\Assistant\LexiconService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class AssistantCorrection extends Model
{
    use HasFactory;

    protected $fillable = [
        'alias',
        'canonical',
        'notes',
        'is_active',
        'created_by',
        'expires_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        $flush = static function () {
            Cache::forget(LexiconService::MANUAL_CORRECTIONS_CACHE_KEY);
        };

        static::saved($flush);
        static::deleted($flush);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
