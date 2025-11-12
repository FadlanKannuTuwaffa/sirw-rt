<?php

namespace App\Http\Controllers\Resident;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class EventCalendarController extends Controller
{
    public function __invoke(Request $request, Event $event)
    {
        $user = $request->user();

        if (! $user || $event->status !== 'scheduled') {
            abort(404);
        }

        $filename = sprintf('agenda-%d.ics', $event->id);
        $payload = $this->generatePayload($event);

        return response($payload, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function generatePayload(Event $event): string
    {
        $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'sirw.local';
        $uid = sprintf('sirw-event-%d@%s', $event->id, $host);
        $timestamp = now()->utc()->format('Ymd\THis\Z');
        $start = $this->formatDate($event->start_at, $event->is_all_day);
        $end = $this->formatDate($event->end_at, $event->is_all_day, true);

        if (! $end && $event->is_all_day && $event->start_at instanceof Carbon) {
            $end = $this->formatDate($event->start_at, true, true);
        }

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//SIRW//Resident Calendar//ID',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:' . $uid,
            'DTSTAMP:' . $timestamp,
        ];

        if ($start) {
            $lines[] = $event->is_all_day
                ? 'DTSTART;VALUE=DATE:' . $start
                : 'DTSTART:' . $start;
        }

        if ($end) {
            $lines[] = $event->is_all_day
                ? 'DTEND;VALUE=DATE:' . $end
                : 'DTEND:' . $end;
        }

        $lines[] = 'SUMMARY:' . $this->escapeValue($event->title);
        $lines[] = 'DESCRIPTION:' . $this->escapeValue($event->description ?? '');
        $lines[] = 'LOCATION:' . $this->escapeValue($event->location ?? '');
        $lines[] = 'END:VEVENT';
        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines) . "\r\n";
    }

    private function formatDate(?Carbon $dateTime, bool $isAllDay, bool $isEnd = false): ?string
    {
        if (! $dateTime instanceof Carbon) {
            return null;
        }

        if ($isAllDay) {
            $date = $dateTime->copy()->startOfDay();

            if ($isEnd) {
                $date = $date->addDay();
            }

            return $date->format('Ymd');
        }

        return $dateTime->copy()->utc()->format('Ymd\THis\Z');
    }

    private function escapeValue(string $value): string
    {
        $escaped = str_replace('\\', '\\\\', $value);
        $escaped = str_replace([',', ';'], ['\,', '\;'], $escaped);
        $escaped = preg_replace("/\r\n|\r|\n/", '\\n', $escaped);

        return $escaped ?? '';
    }
}
