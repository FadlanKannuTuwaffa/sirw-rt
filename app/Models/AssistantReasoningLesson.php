<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssistantReasoningLesson extends Model
{
    protected $fillable = [
        'intent',
        'title',
        'steps',
        'status',
        'priority',
        'source',
        'notes',
    ];

    protected $casts = [
        'steps' => 'array',
        'priority' => 'integer',
    ];
}
