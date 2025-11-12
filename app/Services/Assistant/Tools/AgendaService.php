<?php

namespace App\Services\Assistant\Tools;

use App\Models\Event;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AgendaService
{
    public function getAgenda(string $range, int $residentId): array
    {
        $now = Carbon::now();

        [$start, $end] = match ($range) {
            'day' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };

        $query = Event::query()
            ->where(function ($builder) use ($residentId) {
                $builder
                    ->where('is_public', true)
                    ->orWhereHas('attendances', static function ($attendances) use ($residentId) {
                        $attendances->where('user_id', $residentId);
                    });
            })
            ->where(function ($builder) use ($start, $end) {
                $builder
                    ->whereBetween('start_at', [$start, $end])
                    ->orWhereBetween('end_at', [$start, $end]);
            })
            ->whereIn('status', ['published', 'draft'])
            ->orderBy('start_at');

        /** @var Collection<int, Event> $events */
        $events = $query
            ->with(['attendances' => static function ($builder) use ($residentId) {
                $builder->where('user_id', $residentId);
            }])
            ->limit(12)
            ->get([
                'id',
                'title',
                'location',
                'start_at',
                'end_at',
                'is_all_day',
                'status',
            ]);

        $items = $events->map(static function (Event $event): array {
            $attendance = $event->attendances->first();

            return [
                'id' => $event->id,
                'title' => $event->title,
                'location' => $event->location,
                'start_at' => optional($event->start_at)->toDateTimeString(),
                'end_at' => optional($event->end_at)->toDateTimeString(),
                'is_all_day' => (bool) $event->is_all_day,
                'status' => $event->status,
                'attendance_status' => $attendance?->status,
            ];
        })->all();

        return [
            'summary' => [
                'count' => count($items),
                'range' => $range,
                'route' => route('resident.dashboard') . '#agenda',
            ],
            'items' => $items,
        ];
    }
}
