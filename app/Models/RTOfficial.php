<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RTOfficial extends Model
{
    use HasFactory;

    protected $table = 'rt_officials';

    protected $fillable = [
        'name',
        'position',
        'phone',
        'email',
        'address',
        'is_active',
        'order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getMaskedPhoneAttribute(): ?string
    {
        if (!$this->phone) {
            return null;
        }
        
        $phone = preg_replace('/[^0-9]/', '', $this->phone);
        if (strlen($phone) < 4) {
            return $this->phone;
        }
        
        return substr($phone, 0, 4) . str_repeat('*', strlen($phone) - 4);
    }
}
