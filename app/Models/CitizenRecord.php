<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CitizenRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'nik',
        'nama',
        'email',
        'alamat',
        'status',
        'claimed_by',
    ];

    public function claimedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }
}