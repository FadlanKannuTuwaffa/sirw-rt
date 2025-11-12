<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Bill extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'description',
        'amount',
        'gateway_fee',
        'total_amount',
        'due_date',
        'status',
        'invoice_number',
        'issued_at',
        'paid_at',
        'created_by',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'issued_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function reminders(): MorphMany
    {
        return $this->morphMany(Reminder::class, 'model');
    }

    /** Determine if the bill already has fully paid payments */
    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->where('status', 'unpaid');
    }

    protected function isOverdue(): Attribute
    {
        return Attribute::get(fn () => $this->status !== 'paid' && $this->due_date && $this->due_date->isPast());
    }

    protected function outstandingAmount(): Attribute
    {
        return Attribute::get(function () {
            if ($this->status === 'paid') {
                return 0;
            }

            $paid = $this->payments()->where('status', 'paid')->sum('amount');

            return max($this->amount - $paid, 0);
        });
    }

    protected function payableAmount(): Attribute
    {
        return Attribute::get(function () {
            $total = $this->total_amount ?? 0;

            if ($total <= 0) {
                $total = (int) $this->amount + (int) $this->gateway_fee;
            }

            return max($total, (int) $this->amount);
        });
    }

    protected function gatewayFee(): Attribute
    {
        return Attribute::get(fn () => (int) ($this->attributes['gateway_fee'] ?? 0));
    }
}
