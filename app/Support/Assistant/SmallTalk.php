<?php

namespace App\Support\Assistant;

use App\Services\Assistant\QueryClassifier;
use App\Support\Assistant\LanguageDetector;

class SmallTalk
{
    public static function isSmallTalk(string $message): bool
    {
        $classifier = app(QueryClassifier::class);
        $result = $classifier->classify($message);
        return $result['type'] === 'small_talk';
    }

    public static function respond(string $message, ?string $language = null): string
    {
        $language = $language ?? LanguageDetector::detect($message);
        $lower = strtolower(trim($message));
        $tone = self::detectTone($message, $language);

        if (preg_match('/(terima kasih|makasih|thanks|thx|tengkyu)/i', $lower)) {
            return self::thankYouResponse($tone, $language);
        }

        if (preg_match('/(siapa|who are you|kamu siapa)/i', $lower)) {
            return self::introductionResponse($tone, $language);
        }

        if (preg_match('/(halo|hai|hi|hey|hello|hola|assalamualaikum)/i', $lower)) {
            return self::greetingResponse($lower, $tone, $language);
        }

        if (preg_match('/(apa kabar|gimana kabar|how are you|kabar)/i', $lower)) {
            return self::howAreYouResponse($tone, $language);
        }

        return self::defaultResponse($tone, $language);
    }

    private static function detectTone(string $message, string $language): string
    {
        $lower = strtolower(trim($message));

        if ($language === 'en') {
            $casualPatterns = [
                '/\b(bro|dude|mate)\b/i',
                '/\b(hey there|what\'s up|wassup|sup)\b/i',
                '/\b(can ya|ya gotta|gonna)\b/i',
                '/\b(thanks a ton|thanks bro)\b/i',
            ];

            foreach ($casualPatterns as $pattern) {
                if (preg_match($pattern, $lower)) {
                    return 'casual';
                }
            }

        }

        $casualPatterns = [
            '/\b(bro|bray)\b/i',
            '/\b(sist|sis)\b/i',
            '/\b(gan|cuy|woy)\b/i',
            '/\b(gue|gua|gw|ane)\b/i',
            '/\b(lu|loe|lo|elu|elo)\b/i',
            '/\b(dong|deh|nih|sih)\b/i',
            '/\b(kuy|yuk)\b/i',
            '/\bgimana\b/i',
            '/\b(ngga|nggak|gak|ga)\s/i',
        ];

        foreach ($casualPatterns as $pattern) {
            if (preg_match($pattern, $lower)) {
                return 'casual';
            }
        }

        return 'formal';
    }

    private static function greetingResponse(string $lower, string $tone, string $language): string
    {
        if ($language === 'en') {
            $greetingMap = [
                'hello' => 'Hello',
                'hi' => 'Hi',
                'hey' => 'Hey',
                'halo' => 'Hello',
                'hai' => 'Hi',
                'hola' => 'Hola',
            ];

            $greeting = 'Hello';
            foreach ($greetingMap as $key => $value) {
                if (str_contains($lower, $key)) {
                    $greeting = $value;
                    break;
                }
            }

            return $tone === 'casual'
                ? sprintf('%s! How can I help you today?', $greeting)
                : sprintf('%s! How may I assist you today?', $greeting);
        }

        $greetingMap = [
            'halo' => ['formal' => 'Halo', 'casual' => 'Halo'],
            'hai' => ['formal' => 'Hai', 'casual' => 'Hai'],
            'hi' => ['formal' => 'Hi', 'casual' => 'Hi'],
            'hey' => ['formal' => 'Hai', 'casual' => 'Hey'],
            'hello' => ['formal' => 'Hello', 'casual' => 'Halo'],
            'hola' => ['formal' => 'Halo', 'casual' => 'Hola'],
            'assalamualaikum' => ['formal' => 'Waalaikumsalam', 'casual' => 'Waalaikumsalam'],
        ];

        $greeting = 'Halo';
        foreach ($greetingMap as $key => $values) {
            if (str_contains($lower, $key)) {
                $greeting = $values[$tone];
                break;
            }
        }

        return $tone === 'casual'
            ? sprintf('%s! Ada yang bisa gue bantu?', $greeting)
            : sprintf('%s! Ada yang bisa saya bantu?', $greeting);
    }

    private static function thankYouResponse(string $tone, string $language): string
    {
        if ($language === 'en') {
            return $tone === 'casual'
                ? 'You got it! Need anything else?'
                : 'You’re welcome! Anything else I can help with?';
        }

        return $tone === 'casual'
            ? 'Sama-sama! Ada lagi yang mau dibahas?'
            : 'Sama-sama! Ada lagi yang bisa saya bantu?';
    }

    private static function introductionResponse(string $tone, string $language): string
    {
        if ($language === 'en') {
            return $tone === 'casual'
                ? 'I’m Aetheria, your neighborhood assistant—ready to help with bills, agendas, resident info, you name it. What’s up?'
                : 'I’m Aetheria, your neighborhood assistant. I can help with bills, agendas, resident info, and more. What would you like to know?';
        }

        return $tone === 'casual'
            ? 'Gue Aetheria, asisten virtual RT nih! Bisa bantu soal tagihan, agenda, info warga, dan lain-lain. Mau nanya apa?'
            : 'Saya Aetheria, asisten virtual RT Anda. Saya bisa bantu cek tagihan, agenda, direktori warga, dan informasi lainnya. Ada yang ingin ditanyakan?';
    }

    private static function howAreYouResponse(string $tone, string $language): string
    {
        if ($language === 'en') {
            return $tone === 'casual'
                ? 'Doing great! What can I help you with?'
                : 'I’m doing well, thank you! How about you—anything I can help with today?';
        }

        return $tone === 'casual'
            ? 'Baik nih! Ada yang bisa gue bantu?'
            : 'Baik, terima kasih! Bagaimana dengan Anda? Ada yang bisa saya bantu?';
    }

    private static function defaultResponse(string $tone, string $language): string
    {
        if ($language === 'en') {
            return $tone === 'casual'
                ? 'Hey there! Need a hand with anything?'
                : 'Hello! How can I assist you today?';
        }

        return $tone === 'casual'
            ? 'Halo! Ada yang bisa gue bantu?'
            : 'Halo! Ada yang bisa saya bantu?';
    }

    public static function quickReplies(): array
    {
        return ['Halo', 'Terima kasih', 'Siapa kamu?'];
    }
}
