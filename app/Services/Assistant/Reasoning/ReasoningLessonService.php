<?php

namespace App\Services\Assistant\Reasoning;

use App\Models\AssistantReasoningLesson;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ReasoningLessonService
{
    /**
     * @return array<int,string>
     */
    public function lessonsForIntent(?string $intent, int $limit = 3): array
    {
        if ($intent === null) {
            return [];
        }

        $lessons = AssistantReasoningLesson::query()
            ->where('intent', $intent)
            ->where('status', 'active')
            ->orderByDesc('priority')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        return $this->formatLessons($lessons);
    }

    /**
     * @param \Illuminate\Support\Collection<int,\App\Models\AssistantReasoningLesson> $lessons
     * @return array<int,string>
     */
    private function formatLessons(Collection $lessons): array
    {
        return $lessons->map(function (AssistantReasoningLesson $lesson, int $index) {
            $steps = collect($lesson->steps ?? [])
                ->filter(fn ($step) => is_string($step) && $step !== '')
                ->map(fn ($step, $i) => ($i + 1) . '. ' . Str::of($step)->squish())
                ->implode(' ');

            $title = Str::of($lesson->title)->squish()->value();

            return trim(($index + 1) . ') ' . $title . ($steps !== '' ? ' — ' . $steps : ''));
        })->filter()->values()->all();
    }
}
