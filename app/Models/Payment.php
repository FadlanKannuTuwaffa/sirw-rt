<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'bill_id',
        'user_id',
        'gateway',
        'status',
        'amount',
        'fee_amount',
        'customer_total',
        'paid_at',
        'manual_channel',
        'manual_destination',
        'manual_proof_path',
        'manual_proof_uploaded_at',
        'reference',
        'raw_payload',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'manual_destination' => 'array',
        'manual_proof_uploaded_at' => 'datetime',
        'raw_payload' => 'array',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    protected function feeAmount(): Attribute
    {
        return Attribute::get(fn () => (int) ($this->attributes['fee_amount'] ?? 0));
    }

    protected function customerTotal(): Attribute
    {
        return Attribute::get(function () {
            $total = $this->attributes['customer_total'] ?? null;

            if (is_null($total) || (int) $total <= 0) {
                return (int) $this->amount + (int) $this->fee_amount;
            }

            return (int) $total;
        });
    }
}
