<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventAttendance extends Model
{
    use HasFactory;

    public const STATUS_GOING = 'going';
    public const STATUS_MAYBE = 'maybe';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_PENDING = 'pending';

    protected $fillable = [
        'event_id',
        'user_id',
        'status',
        'responded_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function validStatuses(): array
    {
        return [
            self::STATUS_GOING,
            self::STATUS_MAYBE,
            self::STATUS_DECLINED,
            self::STATUS_PENDING,
        ];
    }

    public static function normalizeStatus(?string $status): ?string
    {
        if ($status === null) {
            return null;
        }

        $normalized = strtolower(trim($status));

        return in_array($normalized, self::validStatuses(), true) ? $normalized : null;
    }
}
