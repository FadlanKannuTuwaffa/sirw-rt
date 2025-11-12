<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssistantHealthCheck extends Model
{
    protected $fillable = [
        'name',
        'status',
        'last_success_at',
        'payload',
    ];

    protected $casts = [
        'last_success_at' => 'datetime',
        'payload' => 'array',
    ];
}
