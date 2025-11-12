<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssistantLlmSnapshotReview extends Model
{
    protected $fillable = [
        'assistant_llm_snapshot_id',
        'user_id',
        'action',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function snapshot()
    {
        return $this->belongsTo(AssistantLlmSnapshot::class, 'assistant_llm_snapshot_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
