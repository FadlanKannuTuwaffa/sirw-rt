<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConversationState extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'thread_id',
        'owner_hash',
        'state',
    ];

    protected $casts = [
        'state' => 'array',
    ];
}
