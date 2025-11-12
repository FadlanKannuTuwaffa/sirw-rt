<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssistantToolBlueprint extends Model
{
    protected $fillable = [
        'intent',
        'sample_failure',
        'failure_rate',
        'tool_usage_rate',
        'total_interactions',
        'status',
        'source_payload',
        'recommended_at',
        'implemented_at',
        'notes',
    ];

    protected $casts = [
        'failure_rate' => 'float',
        'tool_usage_rate' => 'float',
        'total_interactions' => 'integer',
        'source_payload' => 'array',
        'recommended_at' => 'datetime',
        'implemented_at' => 'datetime',
    ];
}
