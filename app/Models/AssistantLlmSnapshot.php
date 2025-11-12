<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssistantLlmSnapshot extends Model
{
    protected $fillable = [
        'assistant_interaction_log_id',
        'user_id',
        'thread_id',
        'intent',
        'confidence',
        'provider',
        'responded_via',
        'is_fallback',
        'content',
        'rag_sources',
        'tool_calls',
        'assistant_interaction_id',
        'is_helpful',
        'positive_feedback_count',
        'negative_feedback_count',
        'needs_review',
        'auto_promote_ready',
        'feedback_source',
        'feedback_note',
        'last_feedback_at',
        'metadata',
        'promotion_status',
        'promotion_attempts',
        'promoted_at',
        'promotion_notes',
        'promotion_payload',
    ];

    protected $casts = [
        'confidence' => 'float',
        'is_fallback' => 'boolean',
        'rag_sources' => 'array',
        'tool_calls' => 'array',
        'is_helpful' => 'boolean',
        'needs_review' => 'boolean',
        'auto_promote_ready' => 'boolean',
        'positive_feedback_count' => 'integer',
        'negative_feedback_count' => 'integer',
        'assistant_interaction_id' => 'integer',
        'metadata' => 'array',
        'promotion_attempts' => 'integer',
        'promoted_at' => 'datetime',
        'promotion_payload' => 'array',
        'last_feedback_at' => 'datetime',
    ];

    public function interaction()
    {
        return $this->belongsTo(AssistantInteractionLog::class, 'assistant_interaction_log_id');
    }

    public function reviews()
    {
        return $this->hasMany(AssistantLlmSnapshotReview::class, 'assistant_llm_snapshot_id');
    }
}
