<?php

namespace App\Services\Assistant;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ResponseRewriter
{
    private array $variantLibrary = [
        'greeting' => [
            'id' => [
                'Hai! Semoga harimu lancar, kabari kalau butuh bantuan ya.',
                'Hai! Aku siap bantu cek info RT kamu kapan saja.',
                'Hai lagi! Selamat datang, ada yang mau kita kerjakan bareng?',
            ],
            'en' => [
                'Hi there! Hai! Hope your day is going smoothly-just say the word if you need anything.',
                'Hello! Hai! I am here if you want me to dig into your RT info.',
                'Great to hear from you again! Hai! What should we sort out today?',
            ],
        ],
        'thanks' => [
            'id' => [
                'Sama-sama, tinggal bilang kalau mau lanjut cek hal lain.',
                'Kapan pun! Kalau masih ada yang bikin penasaran, langsung tanya ya.',
                'Siap, senang bisa bantu. Mau kulanjutkan ke info lain?',
            ],
            'en' => [
                'Anytime—happy to double-check something else if you need.',
                'You are welcome! Let me know if there is another detail to review.',
                'Glad to help. Want me to keep digging into other items?',
            ],
        ],
        'joke' => [
            'id' => [
                'Haha noted, tapi datanya tetap tak jaga rapi ya.',
                'Wkwk baiklah! Yang penting info RT-nya tetap presisi.',
            ],
            'en' => [
                'Haha got it, still keeping the RT facts neat though.',
                'LOL sure thing—accuracy first, jokes second.',
            ],
        ],
        'clarification' => [
            'id' => [
                'Boleh dibantu detailnya supaya jawabanku tepat sasaran?',
                'Kasih tahu bagian mana yang ingin difokuskan biar bisa kuperjelas.',
                'Yuk kita persempit lagi, detail mana yang paling kamu butuhkan?',
            ],
            'en' => [
                'Could you share the detail you want me to focus on so I stay precise?',
                'Let me know which part needs clarity and I will tighten the answer.',
                'Help me narrow it down—what detail matters most for you?',
            ],
        ],
        'general' => [
            'id' => [
                'Ini rangkumannya, langsung kubuat ringkas biar mudah dibaca.',
                'Berikut update terbarunya, kutata supaya poin pentingnya kelihatan.',
                'Aku sudah siapkan rangkuman cepatnya berikut ini.',
            ],
            'en' => [
                'Here is the summary, trimmed so the key points stand out.',
                'I pulled the latest update and laid it out clearly below.',
                'Let me walk you through the essentials right here.',
            ],
        ],
    ];

    /**
     * Rewrite an opening sentence to avoid repetitive template phrasing.
     *
     * @param  array{
     *     language?:string,
     *     intent?:string,
     *     variant?:string,
     *     recent_openers?:array<int,string>
     * }  $context
     */
    public function rewrite(string $message, array $context = []): string
    {
        $trimmed = trim($message);

        if ($trimmed === '') {
            return $message;
        }

        $language = $context['language'] ?? 'id';
        $variant = $this->determineVariant($context);
        $recent = Arr::wrap($context['recent_openers'] ?? []);

        [$opening, $rest] = $this->splitOpening($trimmed);
        $normalizedOpening = $this->normalize($opening);
        $needsReplacement = $opening === '' || in_array($normalizedOpening, $recent, true) || $this->looksLikeTemplate($opening);

        if ($needsReplacement) {
            $replacement = $this->pickVariantLine($variant, $language, $recent);
            if ($replacement !== null) {
                $opening = $replacement;
            }
        }

        return trim($opening . ($rest !== '' ? ' ' . $rest : ''));
    }

    private function determineVariant(array $context): string
    {
        $variant = $context['variant'] ?? null;

        if ($variant) {
            return $this->mapVariant($variant);
        }

        $intent = $context['intent'] ?? '';
        if (Str::contains($intent, 'gratitude')) {
            return 'thanks';
        }

        if (Str::contains($intent, 'clarification') || Str::contains($intent, 'correction')) {
            return 'clarification';
        }

        return 'general';
    }

    private function mapVariant(string $variant): string
    {
        return match ($variant) {
            'smalltalk_greeting' => 'greeting',
            'smalltalk_thanks' => 'thanks',
            'smalltalk_joke' => 'joke',
            'clarification', 'retry', 'correction' => 'clarification',
            'greeting', 'thanks', 'joke' => $variant,
            default => 'general',
        };
    }

    /**
     * @param array<int,string> $recent
     */
    private function pickVariantLine(string $variant, string $language, array $recent): ?string
    {
        $library = $this->variantLibrary[$variant] ?? $this->variantLibrary['general'];
        $lines = $library[$language] ?? $library['id'];

        $candidates = array_values(array_filter($lines, function ($line) use ($recent) {
            return !in_array($this->normalize($line), $recent, true);
        }));

        if ($candidates === []) {
            $candidates = $lines;
        }

        return $candidates[array_rand($candidates)] ?? null;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function splitOpening(string $message): array
    {
        $parts = preg_split('/(?<=[.!?])\s+/u', $message, 2);
        $opening = trim($parts[0] ?? $message);
        $rest = trim($parts[1] ?? '');

        return [$opening, $rest];
    }

    private function looksLikeTemplate(string $opening): bool
    {
        $normalized = $this->normalize($opening);
        $templates = [
            'hai ada yang bisa bantu',
            'halo ada yang bisa bantu',
            'sama sama senang bisa membantu anda',
            'baik kubantu cek ya',
            'aku bantu cek ya',
        ];

        foreach ($templates as $template) {
            if (Str::startsWith($normalized, $template)) {
                return true;
            }
        }

        return false;
    }

    private function normalize(string $value): string
    {
        return Str::of($value)->lower()->squish()->value();
    }
}
