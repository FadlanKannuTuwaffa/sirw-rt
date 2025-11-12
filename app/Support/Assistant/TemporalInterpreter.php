<?php

namespace App\Support\Assistant;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class TemporalInterpreter
{
    private string $timezone;

    /**
     * @param string|null $timezone Olson timezone identifier.
     */
    public function __construct(?string $timezone = null)
    {
        $this->timezone = $timezone ?: config('app.timezone', 'UTC');
    }

    /**
     * Parse month-based period (e.g., "bulan ini", "September 2024").
     *
     * @return array{type:string,start:string,end:string,label_id:string,label_en:string}|null
     */
    public function parsePeriod(string $text): ?array
    {
        $normalized = Str::of($text)->lower()->squish()->value();

        if ($normalized === '') {
            return null;
        }

        $now = Carbon::now($this->timezone);

        if ($this->containsAny($normalized, ['bulan ini', 'bulan sekarang', 'this month'])) {
            return $this->monthRange($now);
        }

        if ($this->containsAny($normalized, ['bulan lalu', 'bulan kemarin', 'last month', 'previous month'])) {
            return $this->monthRange($now->copy()->subMonth());
        }

        if ($this->containsAny($normalized, ['bulan depan', 'next month'])) {
            return $this->monthRange($now->copy()->addMonth());
        }

        if (preg_match('/(\d{1,2})\s*\/\s*(\d{4})/', $normalized, $match)) {
            $month = (int) $match[1];
            $year = (int) $match[2];
            return $this->monthRange(Carbon::create($year, $month, 1, 0, 0, 0, $this->timezone));
        }

        foreach ($this->monthLookup() as $month => $aliases) {
            foreach ($aliases as $alias) {
                if (Str::contains($normalized, $alias)) {
                    $year = $this->extractYear($normalized) ?? $now->year;
                    return $this->monthRange(Carbon::create($year, $month, 1, 0, 0, 0, $this->timezone));
                }
            }
        }

        return null;
    }

    /**
     * Parse agenda range keywords (today, tomorrow, this week).
     */
    public function parseAgendaRange(string $text): ?string
    {
        $normalized = Str::of($text)->lower()->squish()->value();

        if ($normalized === '') {
            return null;
        }

        if ($this->containsAny($normalized, ['hari ini', 'today', 'malam ini', 'siang ini'])) {
            return 'today';
        }

        if ($this->containsAny($normalized, ['besok', 'tomorrow', 'esok'])) {
            return 'tomorrow';
        }

        if ($this->containsAny($normalized, ['minggu ini', 'this week', '7 hari'])) {
            return 'week';
        }

        if ($this->containsAny($normalized, ['minggu depan', 'next week'])) {
            return 'next_week';
        }

        if ($this->containsAny($normalized, ['bulan ini', 'this month', '30 hari'])) {
            return 'month';
        }

        return null;
    }

    /**
     * Build month range array from Carbon reference.
     *
     * @return array{type:string,start:string,end:string,label_id:string,label_en:string}
     */
    public function monthRange(Carbon $reference): array
    {
        $start = $reference->copy()->startOfMonth();
        $end = $reference->copy()->endOfMonth();

        return [
            'type' => 'month',
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'label_id' => $start->copy()->locale('id')->translatedFormat('F Y'),
            'label_en' => $start->copy()->locale('en')->translatedFormat('F Y'),
        ];
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && Str::contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function extractYear(string $text): ?int
    {
        if (preg_match('/(20\d{2}|19\d{2})/', $text, $match)) {
            return (int) $match[1];
        }

        return null;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function monthLookup(): array
    {
        return [
            1 => ['januari', 'jan', 'january'],
            2 => ['februari', 'feb', 'february'],
            3 => ['maret', 'mar', 'march'],
            4 => ['april', 'apr'],
            5 => ['mei', 'may'],
            6 => ['juni', 'jun', 'june'],
            7 => ['juli', 'jul', 'july'],
            8 => ['agustus', 'aug', 'august'],
            9 => ['september', 'sept'],
            10 => ['oktober', 'oct', 'october'],
            11 => ['november', 'nov'],
            12 => ['desember', 'dec', 'december'],
        ];
    }
}
