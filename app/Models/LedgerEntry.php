<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LedgerEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'amount',
        'bill_id',
        'payment_id',
        'method',
        'status',
        'fund_source',
        'fund_destination',
        'fund_reference',
        'occurred_at',
        'notes',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function scopeIncome($query)
    {
        return $query->where('amount', '>', 0);
    }

    public function scopeExpense($query)
    {
        return $query->where('amount', '<', 0);
    }
}
