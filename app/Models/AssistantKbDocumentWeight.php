<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssistantKbDocumentWeight extends Model
{
    protected $fillable = [
        'document_id',
        'title',
        'helpful_count',
        'unhelpful_count',
        'weight',
        'needs_review',
        'last_note',
        'last_feedback_at',
    ];

    protected $casts = [
        'helpful_count' => 'integer',
        'unhelpful_count' => 'integer',
        'weight' => 'float',
        'needs_review' => 'boolean',
        'last_feedback_at' => 'datetime',
    ];
}