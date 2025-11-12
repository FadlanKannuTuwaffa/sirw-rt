<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssistantFactCorrection extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_EXISTING = 'existing';
    public const STATUS_APPLIED = 'applied';
    public const STATUS_NEEDS_REVIEW = 'needs_review';
    public const STATUS_ERROR = 'error';

    protected $fillable = [
        'assistant_correction_event_id',
        'user_id',
        'org_id',
        'thread_id',
        'turn_id',
        'scope',
        'entity_type',
        'field',
        'fingerprint',
        'status',
        'value',
        'value_raw',
        'match_context',
        'source_feedback',
        'meta',
        'reviewed_at',
        'applied_at',
        'reviewed_by',
    ];

    protected $casts = [
        'match_context' => 'array',
        'meta' => 'array',
        'reviewed_at' => 'datetime',
        'applied_at' => 'datetime',
    ];
}
