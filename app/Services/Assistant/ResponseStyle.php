<?php

namespace App\Services\Assistant;

use Illuminate\Support\Str;

class ResponseStyle
{
    private array $ackLibrary = [
        'default' => [
            'id' => 'Siap, langsung kuurus ya.',
            'en' => 'Noted, I will take it from here.',
        ],
        'noted' => [
            'id' => 'Noted, kubantu cek ya.',
            'en' => 'Noted, let me check it for you.',
        ],
        'ready' => [
            'id' => 'Siap, langsung jalan sekarang.',
            'en' => 'All set, proceeding right away.',
        ],
    ];

    /**
     * Decorate assistant responses with tone, hedging, and follow-up cues.
     *
     * @param  array{
     *     tone?:string,
     *     confidence?:float,
     *     followups?:array<int,string>,
     *     ack?:string,
     *     language?:string,
     *     emoji_policy?:string,
     *     formality?:string
     * }  $options
     */
    public function format(string $message, array $options = []): string
    {
        $language = $options['language'] ?? 'id';
        $parts = [];

        if (!empty($options['ack'])) {
            $parts[] = $this->acknowledge($options['ack'], $language);
        }

        $body = $this->ensureFormatting($message);
        $body = $this->applyEmojiPolicy($body, $options);
        $parts[] = $body;

        $toneAppendix = $this->toneAppendix($options, $language);

        if ($toneAppendix !== '') {
            $parts[] = $toneAppendix;
        }

        if (!empty($options['followups'])) {
            $parts[] = $this->followUpsBlock($options['followups'], $language);
        }

        return trim(implode("\n\n", array_filter(array_map('trim', $parts))));
    }

    private function needsHedging(array $options): bool
    {
        $confidence = isset($options['confidence']) ? (float) $options['confidence'] : null;

        if ($confidence !== null && $confidence < 0.5) {
            return true;
        }

        return ($options['tone'] ?? '') === 'cautious';
    }

    private function toneAppendix(array $options, string $language): string
    {
        if ($this->needsHedging($options)) {
            return $this->hedge($language);
        }

        return match ($options['tone'] ?? null) {
            'celebrate' => $this->celebrate($language),
            'empathetic', 'empatic' => $this->empathetic($language),
            'urgent' => $this->urgent($language),
            default => '',
        };
    }

    private function hedge(string $language): string
    {
        return $language === 'en'
            ? 'If you spot anything that needs refinement, just tell me and I will re-check it right away.'
            : 'Kalau ada detail yang perlu diperjelas lagi, tinggal bilang ya biar bisa kucek ulang.';
    }

    private function celebrate(string $language): string
    {
        return $language === 'en'
            ? 'Great job keeping things on track! Let me know if you want something else.'
            : 'Mantap! Kalau ada yang mau dibantu lagi, langsung kabari ya.';
    }

    private function empathetic(string $language): string
    {
        return $language === 'en'
            ? 'I get that this situation can feel a little heavy, but I am here to help you handle it.'
            : 'Paham kok ini bisa bikin was-was, tenang aja aku bantu bereskan.';
    }

    private function urgent(string $language): string
    {
        return $language === 'en'
            ? 'Let’s act on this quickly so nothing slips through the cracks.'
            : 'Ayo ditindaklanjuti sekarang biar nggak keburu kelewat.';
    }

    private function followUpsBlock(array $suggestions, string $language): string
    {
        $clean = array_values(array_unique(array_filter(array_map(fn ($item) => $this->sanitize((string) $item), $suggestions))));

        if ($clean === []) {
            return '';
        }

        $intro = $language === 'en'
            ? 'You might also:'
            : 'Kamu bisa juga:';

        $lines = array_map(fn ($item) => '- ' . $item, array_slice($clean, 0, 3));

        return $intro . "\n" . implode("\n", $lines);
    }

    private function ensureFormatting(string $message): string
    {
        // Convert control bell characters to readable bullets.
        $message = str_replace(chr(7), '•', $message);

        // Replace multiple blank lines with max two.
        $message = preg_replace("/\n{3,}/", "\n\n", $message) ?? $message;

        return trim($message);
    }

    private function acknowledge(string|bool $option, string $language): string
    {
        if ($option === true) {
            $option = 'default';
        }

        if (!is_string($option)) {
            return $this->sanitize((string) $option);
        }

        $normalized = Str::of($option)->lower()->replaceMatches('/[^a-z_]/', '')->value();
        $aliasMap = [
            'siap' => 'ready',
            'ready' => 'ready',
            'noted' => 'noted',
            'notedok' => 'noted',
            'received' => 'noted',
        ];
        $key = $aliasMap[$normalized] ?? $normalized;

        if (isset($this->ackLibrary[$key])) {
            $entry = $this->ackLibrary[$key];

            return $language === 'en'
                ? ($entry['en'] ?? $entry['id'] ?? '')
                : ($entry['id'] ?? $entry['en'] ?? '');
        }

        return $this->sanitize($option);
    }

    private function sanitize(string $text): string
    {
        return trim(Str::of($text)->replaceMatches('/\s+/', ' '));
    }

    private function applyEmojiPolicy(string $message, array $options): string
    {
        $policy = $options['emoji_policy'] ?? null;
        $formality = $options['formality'] ?? null;

        if ($policy === null) {
            if ($formality === 'formal') {
                $policy = 'none';
            } elseif ($formality === 'santai') {
                $policy = 'light';
            }
        }

        if ($policy !== 'light') {
            return $message;
        }

        if ($this->containsEmoji($message)) {
            return $message;
        }

        $tone = $options['tone'] ?? 'default';
        $emoji = $this->pickEmoji($tone);

        return $emoji === '' ? $message : $message . ' ' . $emoji;
    }

    private function containsEmoji(string $text): bool
    {
        return (bool) preg_match('/[\x{1F300}-\x{1FAFF}]/u', $text);
    }

    private function pickEmoji(string $tone): string
    {
        $map = [
            'friendly' => ['🙂', '😊'],
            'celebrate' => ['🎉', '🙌'],
            'empathetic' => ['🤗', '🙏'],
            'urgent' => ['⚡', '⏱️'],
            'default' => ['🙂', '👌'],
        ];

        $pool = $map[$tone] ?? $map['default'];

        return $pool[array_rand($pool)] ?? '';
    }
}
