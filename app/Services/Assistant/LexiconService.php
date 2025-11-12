<?php

namespace App\Services\Assistant;

use App\Models\AssistantCorrection;
use App\Models\Bill;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * LexiconService keeps the assistant's domain vocabulary, handles slang/typo corrections,
 * and exposes lightweight entity extraction utilities. It is intentionally stateful so we
 * can promote frequently corrected out-of-vocabulary (OOV) tokens into a temporary lexicon.
 */
class LexiconService
{
    private const OOV_COUNT_CACHE_KEY = 'assistant.lexicon.oov.counts';
    private const OOV_MAP_CACHE_KEY = 'assistant.lexicon.oov.map';
    private const THREAD_CORRECTION_CACHE_KEY = 'assistant.lexicon.corrections';
    private const UNKNOWN_OOV_CACHE_KEY = 'assistant.lexicon.oov.unknown';
    public const MANUAL_CORRECTIONS_CACHE_KEY = 'assistant.lexicon.manual_corrections';
    private const OOV_PROMOTION_THRESHOLD = 3;
    private const CACHE_TTL_SECONDS = 60 * 60 * 24 * 7; // keep temporary lexicon for a week
    private const DB_VOCAB_CACHE_KEY = 'assistant.lexicon.db.vocab';
    private const DB_VOCAB_CACHE_SECONDS = 3600;
    private const MANUAL_CORRECTIONS_CACHE_SECONDS = 600;
    private const THREAD_KEY_PREFIX = 'thread:';

    /**
     * Canonical domain lexicon and their synonyms/slang variants.
     *
     * @var array<string, string[]>
     */
    private array $domainLexicon = [
        'aku' => ['saya', 'gue', 'gua', 'gw', 'aq', 'sy', 'ane', 'w', 'ak', 'ku'],
        'kamu' => ['kau', 'anda', 'lu', 'loe', 'lo', 'elu', 'elo', 'situ', 'km', 'kmu', 'kalian', 'dirimu'],
        'tagihan' => ['tunggakan', 'bill', 'bills', 'tagian', 'tghn', 'iuran', 'tgihan', 'taghan'],
        'iuran kebersihan' => ['duit sampah', 'uang sampah', 'kebersihan', 'sampah', 'smph'],
        'iuran keamanan' => ['security', 'security fee', 'uang keamanan', 'keamanan', 'ronda'],
        'pembayaran' => ['bayar', 'bayaran', 'payment', 'payments', 'transaksi', 'pelunasan', 'dibayar', 'lunas', 'bayr', 'transfer', 'setor', 'pembyaran'],
        'agenda' => ['acara', 'event', 'events', 'kegiatan', 'rapat', 'pertemuan', 'schedule', 'jadwal', 'kumpul warga'],
        'lokasi' => ['location', 'tempat', 'venue', 'lokasine', 'dimana', 'di mana', 'dmn'],
        'waktu' => ['kapan', 'jam', 'mulai', 'dimulai', 'start', 'jadwal mulai', 'pukul'],
        'besok' => ['esok', 'tomorrow', 'besokk', 'bsk'],
        'hari ini' => ['today', 'skrg', 'sekarang ini', 'tonight', 'hr ini'],
        'warga' => ['resident', 'residen', 'penduduk', 'anggota', 'wrga'],
        'warga baru' => ['pendatang', 'anggota baru', 'resident baru'],
        'laporan' => ['report', 'rekap', 'lprn'],
        'keluhan' => ['complaint', 'pengaduan', 'lapor', 'komplain', 'report'],
        'fasilitas' => ['facility', 'balai', 'aula', 'lapangan', 'fasilitas umum'],
        'berapa' => ['brp', 'brapa', 'jumlah', 'total', 'berpa'],
        'siapa' => ['sapa', 'nama', 'sp'],
        'sudah' => ['udah', 'udh', 'telah'],
        'belum' => ['blm', 'blom', 'not yet', 'lupa'],
        'ada' => ['exist', 'punya'],
        'urgent' => ['penting', 'prioritas', 'mendesak', 'segera'],
        'sumber' => ['source', 'referensi'],
        'bantuan' => ['tolong', 'help', 'panduan'],
        'jawab' => [],
        'syarat' => [],
        'lapor' => ['komplain', 'keluhan', 'aduan'],
        'kas' => ['keuangan', 'uang kas', 'dana rt'],
        'agenda minggu' => ['agenda minggu ini', 'agenda minggu depan'],
        'agenda bulan' => ['agenda bulan ini', 'agenda bulan depan'],
    ];

    /**
     * Database-driven lexicon slices (names, bill titles, facilities).
     *
     * @var array<string, string[]>
     */
    private array $databaseLexicon = [];

    /**
     * Multi-word slang/phrase replacements executed before tokenisation.
     *
     * @var array<string, string>
     */
    private array $phraseMappings = [
        'duit sampah' => 'iuran kebersihan',
        'uang sampah' => 'iuran kebersihan',
        'uang keamanan' => 'iuran keamanan',
        'uang ronda' => 'iuran keamanan',
        'uang kas rt' => 'kas',
        'uang kas' => 'kas',
        'uang kebersihan' => 'iuran kebersihan',
        'uang kebersihan rt' => 'iuran kebersihan',
        'uang keamanan rt' => 'iuran keamanan',
        'security fee' => 'iuran keamanan',
        'security guard' => 'keamanan',
        'uang bulanan' => 'tagihan',
        'kas rt' => 'tagihan',
        'uang listrik' => 'tagihan',
        'uang air' => 'tagihan',
        'uang sampah rt' => 'iuran kebersihan',
    ];

    /**
     * In-memory synonym index for quick lookup.
     *
     * @var array<string, string>
     */
    private array $synonymIndex = [];

    /**
     * Cache-backed temporary lexicon for frequent OOV terms.
     */
    private array $threadLexicon = [];
    private array $threadCorrections = [];
    private array $threadOovCounts = [];
    private array $threadUnknownOov = [];
    private ?string $currentThreadKey = null;
    private array $manualCorrections = [];

    /**
     * Lookup for resident names synced from DB.
     *
     * @var array<string, string>
     */
    private array $residentNameLookup = [];

    public function __construct()
    {
        $this->threadLexicon = Cache::get(self::OOV_MAP_CACHE_KEY, []);
        $this->threadOovCounts = Cache::get(self::OOV_COUNT_CACHE_KEY, []);
        $this->threadCorrections = Cache::get(self::THREAD_CORRECTION_CACHE_KEY, []);
        $this->threadUnknownOov = Cache::get(self::UNKNOWN_OOV_CACHE_KEY, []);
        $this->currentThreadKey = self::THREAD_KEY_PREFIX . 'global';

        $this->hydrateDatabaseLexicon();
        $this->hydratePhraseMappings();
        $this->hydrateManualCorrections();
        $this->buildSynonymIndex();
    }

    public function setThreadContext(?int $userId, ?string $threadId): void
    {
        $threadKey = $this->buildThreadKey($userId, $threadId);

        if ($threadKey !== $this->currentThreadKey) {
            $this->currentThreadKey = $threadKey;
        }

        $this->pruneThreadCorrections($threadKey);
        $this->buildSynonymIndex();
    }

    public function refreshDatabaseLexicon(bool $force = false): void
    {
        if ($force) {
            Cache::forget(self::DB_VOCAB_CACHE_KEY);
        }

        $this->databaseLexicon = [];
        $this->hydrateDatabaseLexicon();
        $this->buildSynonymIndex();
    }

    private function hydrateDatabaseLexicon(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        $payload = Cache::remember(self::DB_VOCAB_CACHE_KEY, self::DB_VOCAB_CACHE_SECONDS, function () {
            $result = [
                'names' => [],
                'bill_titles' => [],
                'facilities' => [],
            ];

            if (Schema::hasTable('users')) {
                $result['names'] = User::query()
                    ->whereNotNull('name')
                    ->orderByDesc('id')
                    ->limit(250)
                    ->pluck('name')
                    ->all();
            }

            if (Schema::hasTable('bills')) {
                $result['bill_titles'] = Bill::query()
                    ->whereNotNull('title')
                    ->distinct()
                    ->limit(120)
                    ->pluck('title')
                    ->all();
            }

            if (Schema::hasTable('events')) {
                $result['facilities'] = Event::query()
                    ->whereNotNull('location')
                    ->distinct()
                    ->limit(80)
                    ->pluck('location')
                    ->all();
            }

            return $result;
        });

        foreach ((array) ($payload['names'] ?? []) as $name) {
            $this->addDatabaseLexiconEntry($name);
            $normalized = Str::of($name)->lower()->squish()->value();
            if ($normalized !== '') {
                $this->residentNameLookup[$normalized] = $name;
            }
        }

        foreach ((array) ($payload['bill_titles'] ?? []) as $title) {
            $this->addDatabaseLexiconEntry($title);
        }

        foreach ((array) ($payload['facilities'] ?? []) as $facility) {
            $this->addDatabaseLexiconEntry($facility);
        }
    }

    /**
     * Normalize a user utterance into canonical tokens, returning extra metadata.
     *
     * @return array{
     *     normalized: string,
     *     tokens: string[],
     *     entities: array<string, array<int, mixed>>,
     *     oov: string[]
     * }
     */
    public function process(string $message): array
    {
        $clean = $this->preNormalize($message);
        $clean = $this->replacePhrases($clean);

        $rawTokens = preg_split('/\s+/', $clean) ?: [];
        $tokens = [];
        $oov = [];

        foreach ($rawTokens as $token) {
            $token = trim($token, " \t\n\r\0\x0B.,!?;:\"'()[]{}");
            if ($token === '') {
                continue;
            }

            $canonical = $this->canonicalize($token);
            if ($canonical === null) {
                $corrected = $this->fuzzyCorrect($token);

                if ($corrected !== $token && $this->canonicalize($corrected) !== null) {
                    $canonical = $this->canonicalize($corrected);
                    $this->recordOovMapping($token, (string) $canonical);
                } else {
                    $canonical = $token;
                    $oov[] = $token;
                    $this->recordUnknownToken($token);
                }
            }

            $tokens[] = $canonical;
        }

        return [
            'normalized' => implode(' ', $tokens),
            'tokens' => $tokens,
            'entities' => $this->extractEntities($message),
            'oov' => $oov,
        ];
    }

    /**
     * Apply fuzzy spelling correction using Levenshtein distance over the lexicon tokens.
     */
    public function fuzzyCorrect(string $token, int $maxDistance = 2): string
    {
        $token = Str::lower($token);

        if ($token === '' || mb_strlen($token) < 3 || is_numeric($token)) {
            return $token;
        }

        if (!preg_match('/[aeiou]/', $token)) {
            return $token;
        }

        $bestCandidate = $token;
        $bestDistance = $maxDistance + 1;
        $firstLetter = Str::substr($token, 0, 1);

        foreach ($this->synonymIndex as $variant => $canonical) {
            if ($variant === '' || $firstLetter !== Str::substr($variant, 0, 1)) {
                continue;
            }

            if (abs(mb_strlen($variant) - mb_strlen($token)) > 2) {
                continue;
            }

            similar_text($token, $variant, $percent);
            if ($percent < 70) {
                continue;
            }

            $distance = levenshtein($token, $variant);

            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $bestCandidate = $canonical;

                if ($distance === 0) {
                    break;
                }
            }
        }

        return $bestDistance <= $maxDistance ? $bestCandidate : $token;
    }

    /**
     * Extract lightweight entities (months, amounts, relative dates, and name hints).
     *
     * @return array{
     *     months: array<int, array<string, mixed>>,
     *     amounts: array<int, array<string, mixed>>,
     *     dates: array<int, array<string, mixed>>,
     *     names: array<int, array<string, mixed>>
     * }
     */
    public function extractEntities(string $message): array
    {
        $months = $this->extractMonths($message);
        $amounts = $this->extractAmounts($message);
        $dates = $this->extractRelativeDates($message);
        $names = $this->extractNames($message);

        return compact('months', 'amounts', 'dates', 'names');
    }

    private function canonicalize(string $token): ?string
    {
        $token = Str::lower($token);

        if (isset($this->synonymIndex[$token])) {
            return $this->synonymIndex[$token];
        }

        return null;
    }

    private function buildSynonymIndex(): void
    {
        $this->synonymIndex = [];

        $lexicons = [$this->domainLexicon, $this->databaseLexicon];

        foreach ($lexicons as $lexicon) {
            foreach ($lexicon as $canonical => $variants) {
                $canonicalToken = $this->canonicalValue($canonical);
                $this->synonymIndex[$canonicalToken] = $canonicalToken;

                foreach ($variants as $variant) {
                    $this->synonymIndex[$this->canonicalValue($variant)] = $canonicalToken;
                }
            }
        }

        $threadLexicon = $this->threadLexicon[$this->currentThreadKey] ?? [];
        foreach ($threadLexicon as $variant => $canonical) {
            $this->synonymIndex[$this->canonicalValue($variant)] = $this->canonicalValue($canonical);
        }

        $this->pruneThreadCorrections($this->currentThreadKey);
        $threadCorrections = $this->threadCorrections[$this->currentThreadKey] ?? [];
        foreach ($threadCorrections as $alias => $entry) {
            $aliasToken = $this->canonicalValue($entry['alias'] ?? $alias);
            $canonicalToken = $this->canonicalValue($entry['canonical'] ?? '');

            if ($aliasToken === '' || $canonicalToken === '') {
                continue;
            }

            $this->synonymIndex[$aliasToken] = $canonicalToken;
        }

        foreach ($this->manualCorrections as $entry) {
            $aliasToken = $this->canonicalValue($entry['alias'] ?? '');
            $canonicalToken = $this->canonicalValue($entry['canonical'] ?? '');

            if ($aliasToken === '' || $canonicalToken === '') {
                continue;
            }

            $this->synonymIndex[$aliasToken] = $canonicalToken;
        }
    }

    private function preNormalize(string $message): string
    {
        return Str::of($message)
            ->lower()
            ->replaceMatches('/[“”]/u', '"')
            ->replaceMatches('/[‘’]/u', "'")
            ->replaceMatches('/[^a-z0-9_\s@#\$%\-,\.\/]/u', ' ')
            ->squish()
            ->value();
    }

    private function replacePhrases(string $message): string
    {
        foreach ($this->phraseMappings as $phrase => $replacement) {
            $pattern = '/\b' . preg_quote(Str::lower($phrase), '/') . '\b/u';
            $message = preg_replace($pattern, ' ' . $this->canonicalValue($replacement) . ' ', $message) ?? $message;
        }

        return Str::of($message)->squish()->value();
    }

    private function recordOovMapping(string $token, string $canonical): void
    {
        $token = Str::lower($token);
        $canonical = $this->canonicalValue($canonical);

        if ($token === '' || $canonical === '') {
            return;
        }

        $threadKey = $this->currentThreadKey ?? self::THREAD_KEY_PREFIX . 'global';
        $this->threadOovCounts[$threadKey][$token][$canonical] = ($this->threadOovCounts[$threadKey][$token][$canonical] ?? 0) + 1;

        if ($this->threadOovCounts[$threadKey][$token][$canonical] >= self::OOV_PROMOTION_THRESHOLD) {
            $this->threadLexicon[$threadKey] ??= [];
            $this->threadLexicon[$threadKey][$token] = $canonical;
            Cache::put(self::OOV_MAP_CACHE_KEY, $this->threadLexicon, self::CACHE_TTL_SECONDS);
        }

        Cache::put(self::OOV_COUNT_CACHE_KEY, $this->threadOovCounts, self::CACHE_TTL_SECONDS);
        $this->buildSynonymIndex();
    }

    private function recordUnknownToken(string $token): void
    {
        $token = Str::lower(Str::squish($token));

        if ($token === '') {
            return;
        }

        $threadKey = $this->currentThreadKey ?? self::THREAD_KEY_PREFIX . 'global';
        $this->threadUnknownOov[$threadKey][$token] = ($this->threadUnknownOov[$threadKey][$token] ?? 0) + 1;

        Cache::put(self::UNKNOWN_OOV_CACHE_KEY, $this->threadUnknownOov, self::CACHE_TTL_SECONDS);
    }

    /**
     * @return array<int, array{token:string,count:int}>
     */
    public function topUnknownTokens(?int $userId = null, ?string $threadId = null, int $limit = 10): array
    {
        $threadKey = $userId === null && $threadId === null
            ? null
            : $this->buildThreadKey($userId, $threadId);

        $tokens = $threadKey === null
            ? $this->aggregateUnknownOov()
            : ($this->threadUnknownOov[$threadKey] ?? []);

        arsort($tokens);

        return collect($tokens)
            ->take(max($limit, 1))
            ->map(fn ($count, $token) => ['token' => $token, 'count' => (int) $count])
            ->values()
            ->all();
    }

    /**
     * @return array<string,int>
     */
    private function aggregateUnknownOov(): array
    {
        $aggregate = [];

        foreach ($this->threadUnknownOov as $entries) {
            foreach ($entries as $token => $count) {
                $aggregate[$token] = ($aggregate[$token] ?? 0) + (int) $count;
            }
        }

        return $aggregate;
    }

    /**
     * @param  array<int, array{input:string,expected:array<int,string>}>  $dataset
     * @return array{
     *     cases:int,
     *     exact_match:float,
     *     precision:float,
     *     recall:float,
     *     f1:float,
     *     details:array<int,array<string,mixed>>
     * }
     */
    public function evaluateDataset(array $dataset): array
    {
        $cases = 0;
        $exactMatches = 0;
        $tp = 0;
        $fp = 0;
        $fn = 0;
        $details = [];

        foreach ($dataset as $case) {
            $input = (string) ($case['input'] ?? '');
            $expectedTokens = $case['expected'] ?? [];

            if ($input === '' || !is_array($expectedTokens) || $expectedTokens === []) {
                continue;
            }

            $cases++;
            $result = $this->process($input);
            $predTokens = array_map(fn ($token) => $this->canonicalValue($token), $result['tokens'] ?? []);
            $predSet = array_values(array_unique(array_filter($predTokens)));
            $expectedSet = array_values(array_unique(array_filter(array_map(
                fn ($token) => $this->canonicalValue((string) $token),
                $expectedTokens
            ))));

            $caseTp = count(array_intersect($predSet, $expectedSet));
            $caseFp = count(array_diff($predSet, $expectedSet));
            $caseFn = count(array_diff($expectedSet, $predSet));

            $tp += $caseTp;
            $fp += $caseFp;
            $fn += $caseFn;

            if ($caseFp === 0 && $caseFn === 0 && $caseTp === count($expectedSet)) {
                $exactMatches++;
            }

            $details[] = [
                'input' => $input,
                'predicted' => $predSet,
                'expected' => $expectedSet,
                'tp' => $caseTp,
                'fp' => $caseFp,
                'fn' => $caseFn,
            ];
        }

        $precision = ($tp + $fp) > 0 ? $tp / ($tp + $fp) : 0.0;
        $recall = ($tp + $fn) > 0 ? $tp / ($tp + $fn) : 0.0;
        $f1 = ($precision + $recall) > 0 ? (2 * $precision * $recall) / ($precision + $recall) : 0.0;

        return [
            'cases' => $cases,
            'exact_match' => $cases > 0 ? round($exactMatches / $cases, 4) : 0.0,
            'precision' => round($precision, 4),
            'recall' => round($recall, 4),
            'f1' => round($f1, 4),
            'details' => $details,
        ];
    }

    public function addCorrectionAlias(string $alias, string $canonical, int $ttlSeconds = 1800): void
    {
        $alias = Str::squish($alias);
        $canonicalValue = $this->canonicalValue($canonical);
        $aliasKey = $this->canonicalValue($alias);

        if ($aliasKey === '' || $canonicalValue === '') {
            return;
        }

        $threadKey = $this->currentThreadKey ?? self::THREAD_KEY_PREFIX . 'global';
        $this->threadCorrections[$threadKey] ??= [];
        $this->threadCorrections[$threadKey][$aliasKey] = [
            'alias' => $alias,
            'canonical' => $canonicalValue,
            'expires_at' => time() + max($ttlSeconds, 60),
        ];

        Cache::put(self::THREAD_CORRECTION_CACHE_KEY, $this->threadCorrections, self::CACHE_TTL_SECONDS);
        $this->buildSynonymIndex();
    }

    private function pruneThreadCorrections(?string $threadKey = null): void
    {
        $keys = $threadKey !== null ? [$threadKey] : array_keys($this->threadCorrections);
        if ($keys === []) {
            return;
        }

        $modified = false;
        $now = time();

        foreach ($keys as $key) {
            if (!isset($this->threadCorrections[$key])) {
                continue;
            }

            foreach ($this->threadCorrections[$key] as $alias => $entry) {
                $expiresAt = (int) ($entry['expires_at'] ?? 0);
                if ($expiresAt > 0 && $expiresAt < $now) {
                    unset($this->threadCorrections[$key][$alias]);
                    $modified = true;
                }
            }

            if ($this->threadCorrections[$key] === []) {
                unset($this->threadCorrections[$key]);
                $modified = true;
            }
        }

        if ($modified) {
            Cache::put(self::THREAD_CORRECTION_CACHE_KEY, $this->threadCorrections, self::CACHE_TTL_SECONDS);
        }
    }

    private function hydratePhraseMappings(): void
    {
        $lexicons = [$this->domainLexicon, $this->databaseLexicon];

        foreach ($lexicons as $lexicon) {
            foreach ($lexicon as $canonical => $variants) {
                if (str_contains($canonical, ' ')) {
                    $this->phraseMappings[$canonical] = $canonical;
                }

                foreach ($variants as $variant) {
                    if (str_contains($variant, ' ')) {
                        $this->phraseMappings[$variant] = $canonical;
                    }
                }
            }
        }

        $this->phraseMappings = array_change_key_case($this->phraseMappings, CASE_LOWER);
    }

    private function hydrateManualCorrections(): void
    {
        if (!Schema::hasTable('assistant_corrections')) {
            $this->manualCorrections = [];

            return;
        }

        $this->manualCorrections = Cache::remember(
            self::MANUAL_CORRECTIONS_CACHE_KEY,
            self::MANUAL_CORRECTIONS_CACHE_SECONDS,
            function () {
                return AssistantCorrection::query()
                    ->active()
                    ->orderBy('alias')
                    ->get(['alias', 'canonical'])
                    ->map(fn (AssistantCorrection $correction) => [
                        'alias' => $correction->alias,
                        'canonical' => $correction->canonical,
                    ])
                    ->all();
            }
        );
    }

    private function canonicalValue(string $value): string
    {
        return Str::of($value)->lower()->squish()->replace(' ', '_')->value();
    }

    private function addDatabaseLexiconEntry(string $label): void
    {
        $label = trim($label);
        if ($label === '') {
            return;
        }

        $canonical = $this->canonicalValue($label);
        if ($canonical === '') {
            return;
        }

        if (!isset($this->databaseLexicon[$canonical])) {
            $this->databaseLexicon[$canonical] = [];
        }

        $this->databaseLexicon[$canonical][] = $label;
        $this->databaseLexicon[$canonical] = array_values(array_unique($this->databaseLexicon[$canonical]));
    }

    private function buildThreadKey(?int $userId, ?string $threadId): string
    {
        $thread = $threadId === null || $threadId === '' ? 'global' : $threadId;

        return self::THREAD_KEY_PREFIX . sha1(($userId ?? 'guest') . '|' . $thread);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractMonths(string $message): array
    {
        $normalized = Str::of($message)->lower()->value();
        $matches = [];
        $results = [];

        $monthAliases = [
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

        foreach ($monthAliases as $month => $aliases) {
            foreach ($aliases as $alias) {
                if (Str::contains($normalized, $alias)) {
                    $results[] = [
                        'token' => $alias,
                        'month' => $month,
                        'year_hint' => $this->extractYear($normalized),
                    ];
                    break;
                }
            }
        }

        if (preg_match_all('/(\d{1,2})\s*\/\s*(\d{2,4})/', $normalized, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $month = (int) $match[1];
                $year = (int) $match[2];
                if ($month >= 1 && $month <= 12) {
                    $results[] = [
                        'token' => $match[0],
                        'month' => $month,
                        'year_hint' => $year,
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractAmounts(string $message): array
    {
        $pattern = '/(?:rp|idr)?\s*([0-9]{1,3}(?:[.,]\d{3})+|\d+)\s*(jt|juta|rb|ribu|k|m|million)?/iu';
        preg_match_all($pattern, $message, $matches, PREG_SET_ORDER);

        $results = [];

        foreach ($matches as $match) {
            $raw = $match[0];
            $numeric = $match[1];
            $suffix = strtolower($match[2] ?? '');
            $value = (int) str_replace([',', '.'], '', $numeric);

            $multiplier = match ($suffix) {
                'jt', 'juta' => 1_000_000,
                'rb', 'ribu', 'k' => 1_000,
                'm', 'million' => 1_000_000,
                default => 1,
            };

            $results[] = [
                'token' => trim($raw),
                'value' => $value * $multiplier,
            ];
        }

        return $results;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function extractRelativeDates(string $message): array
    {
        $normalized = Str::of($message)->lower()->squish()->value();
        $results = [];

        $map = [
            'today' => ['hari ini', 'today', 'malam ini', 'siang ini'],
            'tomorrow' => ['besok', 'tomorrow', 'esok'],
            'week' => ['minggu ini', 'this week', '7 hari'],
            'next_week' => ['minggu depan', 'next week'],
            'month' => ['bulan ini', 'this month', '30 hari'],
        ];

        foreach ($map as $range => $aliases) {
            foreach ($aliases as $alias) {
                if (Str::contains($normalized, $alias)) {
                    $results[] = [
                        'token' => $alias,
                        'range' => $range,
                    ];
                    break;
                }
            }
        }

        return $results;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function extractNames(string $message): array
    {
        $results = [];
        $pattern = '/\b(?:pak|bu|bapak|ibu)\s+([\p{L}]+)/iu';

        if (preg_match_all($pattern, $message, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $results[] = [
                    'token' => trim($match[0]),
                    'name' => Str::title($match[1]),
                ];
            }
        }

        $normalized = Str::of($message)->lower()->squish()->value();
        if ($normalized !== '') {
            foreach ($this->residentNameLookup as $needle => $original) {
                if (Str::contains($normalized, $needle)) {
                    $results[] = [
                        'token' => $original,
                        'name' => $original,
                    ];

                    if (count($results) >= 5) {
                        break;
                    }
                }
            }
        }

        return $results;
    }

    private function extractYear(string $message): ?int
    {
        if (preg_match('/(20\d{2}|19\d{2})/', $message, $match)) {
            return (int) $match[1];
        }

        return null;
    }
}
