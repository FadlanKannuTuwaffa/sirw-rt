<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KbArticle extends Model
{
    protected $fillable = ['title', 'body'];

    public function chunks(): HasMany
    {
        return $this->hasMany(KbChunk::class, 'article_id');
    }
}
