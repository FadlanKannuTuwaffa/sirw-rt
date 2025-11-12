<?php

namespace App\Support\Assistant;

use Illuminate\Support\Str;

class LanguageDetector
{
    private const ENGLISH_KEYWORDS = [
        'hello',
        'hi',
        'hey',
        'thanks',
        'thank you',
        'please',
        'how are you',
        'how about you',
        'what',
        'when',
        'where',
        'why',
        'who',
        'which',
        'how',
        'bill',
        'bills',
        'payment',
        'payments',
        'agenda',
        'event',
        'events',
        'resident',
        'report',
        'directory',
        'help',
        'english',
        'can you',
        'could you',
        'do you',
        'today',
        'tomorrow',
        'week',
        'month',
        'outstanding',
        'due',
        'overdue',
        'summary',
        'explain',
        'show',
        'need',
        'please',
    ];

    private const INDONESIAN_KEYWORDS = [
        'halo',
        'hai',
        'assalamualaikum',
        'terima kasih',
        'makasih',
        'apa kabar',
        'gimana',
        'apa',
        'kapan',
        'dimana',
        'di mana',
        'mengapa',
        'kenapa',
        'bagaimana',
        'tagihan',
        'iuran',
        'pembayaran',
        'laporan',
        'lapor',
        'agenda',
        'warga',
        'tolong',
        'bantu',
        'dong',
        'nih',
        'ya',
        'siapa',
        'perkenalkan',
        'namamu',
        'dirimu',
        'kamu',
        'aku',
        'nggak',
        'gak',
        'enggak',
        'udah',
        'belum',
        'bulan',
        'minggu',
        'hari',
        'besok',
        'hari ini',
        'rt',
        'rw',
        'gue',
        'loe',
        'lu',
        'ente',
        'gua',
        'gw',
        'w',
        'mereka',
        'dia',
        'kamu',
        'sumber',
        'sumbernya',
        'bray',
        'bro',
        'bahasa indonesia',
        'bahasa inggris',
        'indonesia',
        'inggris',
        'indo',
    ];

    private const JAVANESE_KEYWORDS = [
        'piye',
        'piye kabare',
        'opo',
        'nopo',
        'kowe',
        'kowé',
        'sampeyan',
        'panjenengan',
        'aku',
        'kula',
        'kulo',
        'nyuwun',
        'nuwun',
        'maturnuwun',
        'monggo',
        'mboten',
        'inggih',
        'ora',
        'ra',
        'nduk',
        'le',
        'sugeng',
        'rawuh',
        'dahar',
        'dhahar',
        'nedha',
        'dalan',
        'ngendi',
        'pundi',
        'kerjo',
        'gawe',
        'sedoyo',
        'basa jawa',
        'bahasa jawa',
        'jowo',
        'javanese',
    ];

    private const SUNDANESE_KEYWORDS = [
        'naha',
        'kumaha',
        'kumaha damang',
        'punten',
        'mangga',
        'mugia',
        'sampurasun',
        'rampes',
        'abdi',
        'urang',
        'anjeun',
        'maneh',
        'teu',
        'moal',
        'mah',
        'teh',
        'pisan',
        'kunaon',
        'dimana',
        'di mana',
        'kamana',
        'rek',
        'baraya',
        'dulur',
        'wargi',
        'kantor desa',
        'rw',
        'rt',
        'basa sunda',
        'bahasa sunda',
        'sunda',
        'sundanese',
    ];

    /**
     * Detect whether the provided message is closer to English, Indonesian, or Javanese.
     */
    public static function detect(string $message): string
    {
        $normalized = Str::of($message)
            ->lower()
            ->replaceMatches('/\s+/u', ' ')
            ->trim()
            ->value();

        $scores = [
            'en' => self::score($normalized, self::ENGLISH_KEYWORDS),
            'id' => self::scoreWithSuffixes($normalized, self::INDONESIAN_KEYWORDS, '(?:ku|mu|nya|kah|lah|pun|an|kan)?'),
            'jv' => self::scoreWithSuffixes($normalized, self::JAVANESE_KEYWORDS, '(?:mu|ne|é|e|ku)?'),
            'su' => self::scoreWithSuffixes($normalized, self::SUNDANESE_KEYWORDS, '(?:mah|teh|na|keun|keunna|keun|keunna)?'),
        ];

        $maxScore = max($scores);

        if ($maxScore === 0) {
            return self::fallbackLanguage($message);
        }

        $candidates = array_keys(array_filter($scores, fn ($score) => $score === $maxScore));

        if (count($candidates) === 1) {
            return $candidates[0];
        }

        $hasAsciiLetters = preg_match('/[a-z]/i', $message) > 0;
        $hasNonAscii = preg_match('/[^\x00-\x7F]/', $message) > 0;

        if (in_array('en', $candidates, true) && $hasAsciiLetters && !$hasNonAscii) {
            return 'en';
        }

        if (in_array('jv', $candidates, true) && $hasNonAscii) {
            return 'jv';
        }

        return $hasNonAscii ? 'id' : 'en';
    }

    public static function isEnglish(string $message): bool
    {
        return self::detect($message) === 'en';
    }

    /**
     * Count how many keywords are present inside the message.
     *
     * @param array<int, string> $keywords
     */
    private static function score(string $message, array $keywords): int
    {
        $score = 0;

        foreach ($keywords as $keyword) {
            $keyword = trim($keyword);

            if ($keyword === '') {
                continue;
            }

            if (str_contains($keyword, ' ')) {
                $score += substr_count($message, $keyword);
                continue;
            }

            $pattern = '/\b' . preg_quote($keyword, '/') . '\b/u';
            $score += preg_match_all($pattern, $message);
        }

        return $score;
    }

    /**
     * Count keywords for Indonesian detection while tolerating common suffixes.
     *
     * @param array<int, string> $keywords
     */
    private static function scoreWithSuffixes(string $message, array $keywords, string $suffixPattern = ''): int
    {
        $score = 0;

        foreach ($keywords as $keyword) {
            $keyword = trim($keyword);

            if ($keyword === '') {
                continue;
            }

            if (str_contains($keyword, ' ')) {
                $score += substr_count($message, $keyword);
                continue;
            }

            $pattern = '/\b' . preg_quote($keyword, '/') . ($suffixPattern ?: '') . '\b/u';
            $score += preg_match_all($pattern, $message);
        }

        return $score;
    }

    private static function fallbackLanguage(string $message): string
    {
        $hasAsciiLetters = preg_match('/[a-z]/i', $message) > 0;
        $hasNonAscii = preg_match('/[^\x00-\x7F]/', $message) > 0;

        if ($hasAsciiLetters && !$hasNonAscii) {
            return 'en';
        }

        if ($hasNonAscii && !$hasAsciiLetters) {
            return 'id';
        }

        return 'id';
    }
}
