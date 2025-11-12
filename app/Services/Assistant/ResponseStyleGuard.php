<?php

namespace App\Services\Assistant;

use Illuminate\Support\Str;

class ResponseStyleGuard
{
    /**
     * Normalize assistant output before it is styled to keep tone consistent.
     *
     * @param  array<string, mixed>  $style
     * @return array{0:string,1:array<string,mixed>}
     */
    public function enforce(string $message, array $style, string $language = 'id'): array
    {
        $message = $this->collapseApologies($message);
        $style = $this->capOverConfidence($message, $style);

        if ($this->needsClarifyingQuestion($style)) {
            $message = $this->appendClarifyingQuestion($message, $language);
        }

        return [$message, $style];
    }

    private function collapseApologies(string $message): string
    {
        return preg_replace('/\\b(maaf|sorry)\\b(?:[\\s,!]+\\b(maaf|sorry)\\b)+/i', '$1', $message) ?? $message;
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function capOverConfidence(string $message, array $style): array
    {
        $confidence = isset($style['confidence']) ? (float) $style['confidence'] : null;

        if ($confidence === null || $confidence <= 0.9) {
            return $style;
        }

        $normalized = Str::lower($message);

        if (Str::contains($normalized, ['mungkin', 'sepertinya', 'maybe', 'might', 'not sure'])) {
            $style['confidence'] = 0.85;
        }

        return $style;
    }

    /**
     * @param  array<string, mixed>  $style
     */
    private function needsClarifyingQuestion(array $style): bool
    {
        $confidence = isset($style['confidence']) ? (float) $style['confidence'] : null;

        return $confidence !== null && $confidence < 0.5;
    }

    private function appendClarifyingQuestion(string $message, string $language): string
    {
        if (Str::contains($message, '?')) {
            return $message;
        }

        $question = $language === 'en'
            ? 'Is there a specific detail you want me to double-check?'
            : 'Ada detail tertentu yang mau kamu perjelas lagi?';

        if (Str::contains($message, $question)) {
            return $message;
        }

        return trim($message) . "\n\n" . $question;
    }
}

