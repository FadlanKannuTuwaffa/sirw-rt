<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'location',
        'start_at',
        'end_at',
        'is_all_day',
        'is_public',
        'status',
        'reminder_offsets',
        'created_by',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_all_day' => 'boolean',
        'is_public' => 'boolean',
        'reminder_offsets' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reminders(): MorphMany
    {
        return $this->morphMany(Reminder::class, 'model');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(EventAttendance::class);
    }
}
