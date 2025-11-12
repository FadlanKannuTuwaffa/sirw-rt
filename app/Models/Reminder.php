<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Reminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'model_type',
        'model_id',
        'channel',
        'send_at',
        'sent_at',
        'status',
        'payload',
    ];

    protected $casts = [
        'send_at' => 'datetime',
        'sent_at' => 'datetime',
        'payload' => 'array',
    ];

    public function model(): MorphTo
    {
        return $this->morphTo();
    }
}