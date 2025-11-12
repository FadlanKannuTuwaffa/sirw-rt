<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Slide extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subtitle',
        'description',
        'button_label',
        'button_url',
        'image_path',
        'position',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'position' => 0,
        'is_active' => true,
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('position');
    }
}