<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssistantKnowledgeFeedback extends Model
{
    protected $table = 'assistant_kb_feedback';

    protected $fillable = [
        'user_id',
        'assistant_interaction_log_id',
        'assistant_interaction_id',
        'token',
        'question',
        'answer_excerpt',
        'helpful',
        'note',
        'sources',
        'confidence',
        'responded_at',
    ];

    protected $casts = [
        'sources' => 'array',
        'helpful' => 'boolean',
        'confidence' => 'float',
        'responded_at' => 'datetime',
        'assistant_interaction_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function interaction(): BelongsTo
    {
        return $this->belongsTo(AssistantInteractionLog::class, 'assistant_interaction_log_id');
    }
}
