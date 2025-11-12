<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KbChunk extends Model
{
    protected $fillable = ['article_id', 'chunk_text', 'embedding'];

    protected $casts = [
        'embedding' => 'array',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(KbArticle::class, 'article_id');
    }
}
