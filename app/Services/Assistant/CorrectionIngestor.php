<?php

namespace App\Services\Assistant;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CorrectionIngestor
{
    /**
     * Persist a correction event and optional style preferences.
     *
     * @param  array{
     *     user_id?:int,
     *     org_id?:int,
     *     thread_id?:string|null,
     *     turn_id?:string|null,
     *     original_input?:string|null,
     *     original_answer?:string|null,
     *     user_feedback_raw:string,
     *     correction_type?:string,
     *     scope?:string,
     *     patch_rules?:array<string,mixed>
     * }  $payload
     */
    public static function store(array $payload): int
    {
      $feedback = Str::of($payload['user_feedback_raw'] ?? '')->trim()->value();

      if ($feedback === '') {
          throw new \InvalidArgumentException('user_feedback_raw is required');
      }

      $context = [
          'original_input' => $payload['original_input'] ?? null,
          'original_answer' => $payload['original_answer'] ?? null,
      ];

      $analysis = self::analyzeFeedback($feedback, $context);
      $patchRules = self::mergePatchRules($analysis['patch_rules'], $payload['patch_rules'] ?? []);
      $now = Carbon::now();
      $record = [
          'user_id' => $payload['user_id'] ?? null,
          'org_id' => $payload['org_id'] ?? null,
          'thread_id' => $payload['thread_id'] ?? null,
          'turn_id' => $payload['turn_id'] ?? null,
          'correction_type' => $payload['correction_type'] ?? $analysis['correction_type'],
          'scope' => $payload['scope'] ?? ($payload['user_id'] ? 'user' : 'global'),
          'original_input' => $payload['original_input'] ?? null,
          'original_answer' => $payload['original_answer'] ?? null,
          'user_feedback_raw' => $feedback,
          'normalized_instruction' => $analysis['normalized_instruction'] !== []
              ? json_encode($analysis['normalized_instruction'], JSON_UNESCAPED_UNICODE)
              : null,
          'patch_rules' => $patchRules !== []
              ? json_encode($patchRules, JSON_UNESCAPED_UNICODE)
              : null,
          'language_preference' => $analysis['language_preference'],
          'tone_preference' => $analysis['tone_preference'],
          'examples' => $analysis['examples'] !== []
              ? json_encode($analysis['examples'], JSON_UNESCAPED_UNICODE)
              : null,
          'is_active' => true,
          'expires_at' => $analysis['expires_at'],
          'created_at' => $now,
          'updated_at' => $now,
      ];

      $eventId = DB::table('assistant_correction_events')->insertGetId($record);

      if ($analysis['style_preferences'] !== []) {
          self::upsertStylePreferences(
              $payload['user_id'] ?? null,
              $payload['org_id'] ?? null,
              $analysis['style_preferences']
          );
      }

      return $eventId;
    }

    /**
     * @return array{
     *     correction_type:string,
     *     normalized_instruction:array<string,mixed>,
     *     patch_rules:array<string,mixed>,
     *     language_preference:?string,
     *     tone_preference:?string,
     *     style_preferences:array<string,mixed>,
     *     examples:array<int,array<string,mixed>>,
     *     expires_at:?string
     * }
     */
    private static function analyzeFeedback(string $feedback, array $context = []): array
    {
        $lower = Str::of($feedback)->lower()->value();
        $correctionType = self::detectCorrectionType($lower);
        $languagePref = self::detectLanguagePreference($lower);
        $tonePref = self::detectTonePreference($lower);
        $stylePrefs = self::detectStylePreferences($lower);
        $synonyms = self::extractSynonyms($feedback);
        $intentBias = self::extractIntentBias($lower);
        $examples = self::buildExamples($context['original_input'] ?? null, $feedback);
        $forbiddenPhrases = self::detectForbiddenPhrases($feedback);

        $normalizedInstruction = array_filter([
            'summary' => Str::limit(trim($feedback), 280, '...'),
            'focus' => array_keys($intentBias),
            'language' => $languagePref,
            'tone' => $tonePref,
        ], fn ($value) => $value !== null && $value !== []);

        $patchRules = array_filter([
            'synonym_add' => $synonyms,
            'intent_bias' => $intentBias,
            'style_toggle' => $stylePrefs,
            'forbidden_phrases' => $forbiddenPhrases,
        ], fn ($value) => $value !== null && $value !== []);

        return [
            'correction_type' => $correctionType,
            'normalized_instruction' => $normalizedInstruction,
            'patch_rules' => $patchRules,
            'language_preference' => $languagePref,
            'tone_preference' => $tonePref,
            'style_preferences' => $stylePrefs,
            'examples' => $examples,
            'expires_at' => self::detectExpiry($lower),
        ];
    }

    /**
     * @return array<int,string>
     */
    private static function detectForbiddenPhrases(string $feedback): array
    {
        $candidates = [];
        $patterns = [
            '/jangan\s+(?:lagi\s+)?(?:pakai|gunakan|ucapkan|sebutkan|pake|tulis|jawab)\s+(?:kata\s+)?(?:"([^"]{2,80})"|\'([^\']{2,80})\'|([^\n\r\.,!?]{2,80}))/iu',
            '/stop\s+(?:using|saying)\s+(?:"([^"]{2,80})"|\'([^\']{2,80})\'|([^\n\r\.,!?]{2,80}))/iu',
            '/no\s+more\s+(?:"([^"]{2,80})"|\'([^\']{2,80})\'|([^\n\r\.,!?]{2,80}))/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $feedback, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $phrase = $match[1] ?? '';
                    if ($phrase === '') {
                        $phrase = $match[2] ?? '';
                    }
                    if ($phrase === '') {
                        $phrase = $match[3] ?? '';
                    }
                    $phrase = Str::of($phrase)
                        ->replaceMatches('/(lainnya|lagi|ya|dong)$/iu', '')
                        ->squish()
                        ->trim(" \"'“”‘’.?!")
                        ->substr(0, 80)
                        ->value();

                    if ($phrase !== '') {
                        $candidates[] = $phrase;
                    }
                }
            }
        }

        return array_values(array_unique($candidates));
    }

    /**
     * @param  array<string,mixed>  $style
     */
    private static function upsertStylePreferences(?int $userId, ?int $orgId, array $style): void
    {
        if ($userId === null && $orgId === null) {
            return;
        }

        $payload = array_filter([
            'default_language' => $style['language'] ?? null,
            'formality' => $style['formality'] ?? null,
            'humor' => Arr::has($style, 'humor') ? (bool) $style['humor'] : null,
            'introduce_self_on_first_turn' => Arr::has($style, 'introduce_self')
                ? (bool) $style['introduce_self']
                : null,
            'emoji_policy' => $style['emoji_policy'] ?? null,
        ], fn ($value) => $value !== null);

        if ($payload === []) {
            return;
        }

        $payload['updated_at'] = Carbon::now();
        $payload['created_at'] = $payload['updated_at'];

        DB::table('user_style_prefs')->updateOrInsert(
            ['user_id' => $userId, 'org_id' => $orgId],
            $payload
        );
    }

    private static function detectCorrectionType(string $lower): string
    {
        $mapping = [
            'bahasa' => 'bahasa',
            'english' => 'bahasa',
            'tone' => 'gaya',
            'gaya' => 'gaya',
            'format' => 'gaya',
            'langkah' => 'langkah',
            'step' => 'langkah',
            'urutan' => 'langkah',
            'istilah' => 'istilah',
            'alias' => 'istilah',
            'sebut' => 'istilah',
            'persona' => 'persona',
            'karakter' => 'persona',
            'peran' => 'persona',
            'sikap' => 'persona',
            'humor' => 'gaya',
        ];

        foreach ($mapping as $needle => $type) {
            if (str_contains($lower, $needle)) {
                return $type;
            }
        }

        if (str_contains($lower, 'angka') || str_contains($lower, 'data') || str_contains($lower, 'jumlah')) {
            return 'fakta';
        }

        return 'lainnya';
    }

    private static function detectLanguagePreference(string $lower): ?string
    {
        return match (true) {
            str_contains($lower, 'bahasa inggris') || str_contains($lower, 'english') => 'en',
            str_contains($lower, 'bahasa indonesia') || str_contains($lower, 'indo aja') => 'id',
            default => null,
        };
    }

    private static function detectTonePreference(string $lower): ?string
    {
        return match (true) {
            str_contains($lower, 'santai') || str_contains($lower, 'casual') => 'santai',
            str_contains($lower, 'formal') || str_contains($lower, 'resmi') => 'formal',
            str_contains($lower, 'tegas') => 'tegas',
            str_contains($lower, 'serius') => 'serius',
            default => null,
        };
    }

    /**
     * @return array<string,mixed>
     */
    private static function detectStylePreferences(string $lower): array
    {
        $style = [];

        if (str_contains($lower, 'santai') || str_contains($lower, 'casual')) {
            $style['formality'] = 'santai';
            $style['humor'] = true;
            $style['emoji_policy'] = $style['emoji_policy'] ?? 'light';
        }

        if (str_contains($lower, 'formal') || str_contains($lower, 'resmi')) {
            $style['formality'] = 'formal';
            $style['humor'] = false;
            $style['emoji_policy'] = 'none';
        }

        if (str_contains($lower, 'jangan bercanda') || str_contains($lower, 'serius')) {
            $style['humor'] = false;
        }

        if (str_contains($lower, 'pakai emoji') || str_contains($lower, 'emoji dikit')) {
            $style['emoji_policy'] = 'light';
        }

        if (str_contains($lower, 'tanpa emoji') || str_contains($lower, 'no emoji')) {
            $style['emoji_policy'] = 'none';
        }

        if (str_contains($lower, 'kenalin diri') || str_contains($lower, 'perkenalkan diri')) {
            $style['introduce_self'] = true;
        }

        if (str_contains($lower, 'gak usah kenalin diri') || str_contains($lower, 'jangan kenalin diri')) {
            $style['introduce_self'] = false;
        }

        $language = self::detectLanguagePreference($lower);
        if ($language !== null) {
            $style['language'] = $language;
        }

        return $style;
    }

    /**
     * @return array<int,array{alias:string,canonical:string}>
     */
    private static function extractSynonyms(string $feedback): array
    {
        $synonyms = [];
        $patterns = [
            '/"(?P<wrong>[^"]{2,40})"\s*(?:itu|=|maksudnya)\s*"?(?P<right>[^"]{2,40})"?/iu',
            "/'(?P<wrong>[^']{2,40})'?\s*(?:itu|=|maksudnya)\s*'?(?P<right>[^']{2,40})'?/iu",
            '/(?P<wrong>[\p{L}\d\/&\'\-\s]{2,40})\s+(?:itu|maksudnya|=)\s+(?P<right>[\p{L}\d\/&\'\-\s]{2,40})/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $feedback, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $alias = Str::of($match['wrong'] ?? '')->squish()->value();
                    $canonical = Str::of($match['right'] ?? '')->squish()->value();

                    if ($alias === '' || $canonical === '' || Str::lower($alias) === Str::lower($canonical)) {
                        continue;
                    }

                    $synonyms[] = ['alias' => $alias, 'canonical' => $canonical];
                }
            }
        }

        return $synonyms;
    }

    /**
     * @return array<string,float>
     */
    private static function extractIntentBias(string $lower): array
    {
        $mapping = [
            'bills' => ['tagihan', 'iuran', 'tunggakan', 'bill'],
            'payments' => ['pembayaran', 'sudah bayar', 'riwayat bayar'],
            'agenda' => ['agenda', 'acara', 'rapat', 'event'],
            'finance' => ['keuangan', 'rekap', 'kas'],
            'residents' => ['warga', 'resident', 'kontak pengurus'],
        ];

        $bias = [];

        foreach ($mapping as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($lower, $keyword)) {
                    $bias[$intent] = ($bias[$intent] ?? 0) + 0.2;
                }
            }
        }

        foreach ($bias as $intent => $score) {
            $bias[$intent] = min(1.0, $score);
        }

        return $bias;
    }

    /**
     * @return array<int,array<string,string>>
     */
    private static function buildExamples(?string $originalInput, string $feedback): array
    {
        if ($originalInput === null || trim($originalInput) === '') {
            return [];
        }

        return [[
            'input' => Str::limit(Str::squish($originalInput), 280, '...'),
            'preferred_response' => Str::limit(Str::squish($feedback), 280, '...'),
        ]];
    }

    private static function detectExpiry(string $lower): ?string
    {
        if (str_contains($lower, 'sementara') || str_contains($lower, 'hari ini aja')) {
            return Carbon::now()->addHours(6)->toDateTimeString();
        }

        if (str_contains($lower, 'minggu ini')) {
            return Carbon::now()->addDays(7)->toDateTimeString();
        }

        return null;
    }

    /**
     * @param  array<string,mixed>  $base
     * @param  array<string,mixed>|null  $extra
     * @return array<string,mixed>
     */
    private static function mergePatchRules(array $base, ?array $extra): array
    {
        if ($extra === null || $extra === []) {
            return $base;
        }

        foreach ($extra as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (!array_key_exists($key, $base)) {
                $base[$key] = $value;
                continue;
            }

            if (is_array($base[$key]) && is_array($value)) {
                $base[$key] = self::isListArray($base[$key]) && self::isListArray($value)
                    ? array_values(array_merge($base[$key], $value))
                    : array_merge($base[$key], $value);
                continue;
            }

            $base[$key] = $value;
        }

        return $base;
    }

    private static function isListArray(array $value): bool
    {
        if ($value === []) {
            return true;
        }

        return array_keys($value) === range(0, count($value) - 1);
    }
}
