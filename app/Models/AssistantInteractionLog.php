<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssistantInteractionLog extends Model
{
    protected $fillable = [
        'user_id',
        'query',
        'classification_type',
        'confidence',
        'intents',
        'tool_calls',
        'tool_success',
        'responded_via',
        'llm_provider',
        'provider_primary',
        'provider_final',
        'provider_fallback_from',
        'repetition_score',
        'correction_event_id',
        'smalltalk_kind',
        'success',
        'duration_ms',
    ];

    protected $casts = [
        'confidence' => 'float',
        'intents' => 'array',
        'tool_calls' => 'array',
        'tool_success' => 'boolean',
        'llm_provider' => 'string',
        'provider_primary' => 'string',
        'provider_final' => 'string',
        'provider_fallback_from' => 'string',
        'repetition_score' => 'float',
        'correction_event_id' => 'integer',
        'smalltalk_kind' => 'string',
        'success' => 'boolean',
        'duration_ms' => 'integer',
    ];
}
