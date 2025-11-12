<?php

namespace App\Services\Assistant;

use App\Support\Assistant\TemporalInterpreter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ToolSchemaRegistry
{
    private TemporalInterpreter $temporal;

    private array $schemas = [
        'get_agenda' => [
            'range' => [
                'type' => 'enum',
                'values' => ['today', 'tomorrow', 'week', 'next_week', 'month'],
                'default' => 'month',
                'clarification' => [
                    'id' => 'Agenda untuk kapan? (hari ini/besok/minggu depan/satu bulan)',
                    'en' => 'For which range do you want the agenda? (today/tomorrow/next week/30 days)',
                ],
            ],
        ],
        'get_outstanding_bills' => [
            'period' => [
                'type' => 'period',
                'default' => 'current_month',
                'clarification' => [
                    'id' => 'Periode tagihan mana yang mau dicek? (bulan ini/bulan lalu/atau sebut bulan lain)',
                    'en' => 'Which billing period should I check? (this month/last month/mention another month)',
                ],
            ],
        ],
        'get_payments_this_month' => [
            'period' => [
                'type' => 'period',
                'default' => 'current_month',
                'clarification' => [
                    'id' => 'Pembayaran untuk periode apa yang kamu maksud? (bulan ini/bulan lalu/sebut bulan lain)',
                    'en' => 'Which payment period do you mean? (this month/last month/specify)',
                ],
            ],
        ],
        'get_payment_status' => [
            'month' => [
                'type' => 'month',
                'default' => 'current_month',
                'clarification' => [
                    'id' => 'Pembayaran bulan apa yang mau dicek? (bulan ini/bulan lalu/sebut bulan)',
                    'en' => 'Which month should I check payments for? (this month/last month/specify)',
                ],
            ],
            'type' => [
                'type' => 'enum',
                'values' => ['all', 'kebersihan', 'keamanan', 'kas'],
                'default' => 'all',
                'synonyms' => [
                    'all' => ['semua', 'all', 'semuanya'],
                    'kebersihan' => ['sampah', 'cleaning', 'kebersihan'],
                    'keamanan' => ['security', 'satpam', 'keamanan'],
                    'kas' => ['kas', 'operasional', 'dana'],
                ],
                'clarification' => [
                    'id' => 'Mau cek pembayaran iuran apa? (kebersihan/keamanan/kas)',
                    'en' => 'Which fee do you mean? (cleanliness/security/cash)',
                ],
            ],
        ],
        'search_directory' => [
            'query' => [
                'type' => 'string',
                'min_length' => 1,
                'max_length' => 64,
                'default' => '*',
                'clarification' => [
                    'id' => 'Nama atau kata kunci warga apa yang mau dicari?',
                    'en' => 'Which resident name or keyword do you want me to search for?',
                ],
            ],
            'status' => [
                'type' => 'enum',
                'values' => ['all', 'aktif', 'pindah', 'nonaktif'],
                'default' => 'all',
                'synonyms' => [
                    'all' => ['semua', 'all', 'total', '*'],
                    'aktif' => ['aktif', 'active'],
                    'pindah' => ['pindah', 'relokasi', 'move'],
                    'nonaktif' => ['nonaktif', 'non aktif', 'inactive', 'tidak aktif'],
                ],
                'clarification' => [
                    'id' => 'Perlu status tertentu? (aktif/pindah/nonaktif)',
                    'en' => 'Filter by status? (active/moved/inactive)',
                ],
            ],
        ],
        'export_financial_recap' => [
            'format' => [
                'type' => 'enum',
                'values' => ['pdf', 'xlsx'],
                'default' => 'pdf',
                'clarification' => [
                    'id' => 'Format ekspor apa yang kamu mau? (PDF atau XLSX)',
                    'en' => 'Which export format do you prefer? (PDF or XLSX)',
                ],
            ],
            'period' => [
                'type' => 'period',
                'default' => 'current_month',
                'clarification' => [
                    'id' => 'Rekap untuk periode apa? (bulan ini/bulan lalu/atau sebut rentang tanggal)',
                    'en' => 'Which period should I export? (this month/last month/or mention dates)',
                ],
            ],
        ],
        'get_rt_contacts' => [
            'position' => [
                'type' => 'enum',
                'values' => ['all', 'ketua', 'sekretaris', 'bendahara', 'keamanan', 'humas'],
                'default' => 'all',
                'synonyms' => [
                    'all' => ['semua', 'all', 'pengurus'],
                    'ketua' => ['ketua', 'chair', 'ketua rt', 'pak rt'],
                    'sekretaris' => ['sekretaris', 'sekre', 'secretary'],
                    'bendahara' => ['bendahara', 'treasurer'],
                    'keamanan' => ['keamanan', 'security', 'satpam'],
                    'humas' => ['humas', 'public relation', 'pr'],
                ],
                'clarification' => [
                    'id' => 'Kontak pengurus siapa yang kamu butuh? (ketua/sekretaris/bendahara/dst)',
                    'en' => 'Which committee contact do you need? (chair/secretary/treasurer/etc)',
                ],
            ],
        ],
        'rag_search' => [
            'query' => [
                'type' => 'string',
                'min_length' => 4,
                'max_length' => 200,
                'mode' => 'full',
                'clarification' => [
                    'id' => 'Pertanyaan atau topik apa yang mau dicari di panduan RT?',
                    'en' => 'Which topic should I search in the RT knowledge base?',
                ],
            ],
        ],
    ];

    private array $toolMeta = [
        'get_agenda' => [
            'title' => 'Agenda',
            'description' => 'Ambil agenda atau event mendatang. Gunakan saat pengguna menanyakan jadwal rapat, kegiatan, atau acara RT.',
        ],
        'get_outstanding_bills' => [
            'title' => 'Tagihan',
            'description' => 'Ambil daftar tagihan yang belum dibayar. Cocok untuk pertanyaan seperti "Tagihanku bulan ini apa?" atau "Apa saja yang belum lunas?".',
        ],
        'get_payments_this_month' => [
            'title' => 'Pembayaran',
            'description' => 'Ambil riwayat pembayaran periode berjalan. Gunakan saat pengguna ingin memastikan pembayaran apa saja yang sudah masuk.',
        ],
        'get_payment_status' => [
            'title' => 'Status Pembayaran',
            'description' => 'Cek status pembayaran tertentu berdasarkan bulan atau jenis iuran (kebersihan, keamanan, kas).',
        ],
        'export_financial_recap' => [
            'title' => 'Rekap Keuangan',
            'description' => 'Buat ringkasan pemasukan/pengeluaran RT untuk periode tertentu lalu siapkan file PDF/XLSX.',
        ],
        'search_directory' => [
            'title' => 'Direktori Warga',
            'description' => 'Cari warga berdasarkan nama atau status (aktif, pindah, nonaktif) atau tampilkan total warga.',
        ],
        'get_rt_contacts' => [
            'title' => 'Kontak Pengurus',
            'description' => 'Ambil informasi kontak ketua, sekretaris, bendahara, keamanan, atau humas RT.',
        ],
        'rag_search' => [
            'title' => 'Pencarian KB',
            'description' => 'Cari informasi di knowledge base (SOP/FAQ RT) untuk menjawab pertanyaan prosedur atau kebijakan.',
        ],
    ];

    private array $agendaSynonyms = [
        'today' => ['hari ini', 'today', 'malam ini', 'siang ini'],
        'tomorrow' => ['besok', 'tomorrow', 'esok'],
        'week' => ['minggu ini', '7 hari', 'pekan ini'],
        'next_week' => ['minggu depan', 'next week', 'pekan depan'],
        'month' => ['bulan ini', '30 hari', 'month'],
    ];

    private array $billTypeSynonyms = [
        'kebersihan' => ['kebersihan', 'sampah', 'trash', 'cleaning'],
        'keamanan' => ['keamanan', 'security', 'satpam', 'ronda'],
        'kas' => ['kas', 'operasional', 'dana umum', 'kas rt', 'iuran kas'],
    ];

    private array $residentStatusSynonyms = [
        'all' => ['semua', 'semuanya', 'all', 'total', '*'],
        'aktif' => ['aktif', 'active'],
        'pindah' => ['pindah', 'relokasi', 'move', 'moved'],
        'nonaktif' => ['nonaktif', 'non aktif', 'inactive', 'tidak aktif', 'non-active'],
    ];

    private array $rtPositionSynonyms = [
        'all' => ['semua', 'all', 'pengurus'],
        'ketua' => ['ketua', 'chair', 'chairman', 'pak rt', 'ketua rt'],
        'sekretaris' => ['sekretaris', 'sekre', 'secretary', 'sekertaris'],
        'bendahara' => ['bendahara', 'treasurer', 'bendahara rt'],
        'keamanan' => ['keamanan', 'security', 'satpam', 'ketua keamanan'],
        'humas' => ['humas', 'public relation', 'pr', 'hubungan masyarakat'],
    ];

    private array $monthNameMap = [
        'januari' => 1,
        'jan' => 1,
        'january' => 1,
        'februari' => 2,
        'feb' => 2,
        'february' => 2,
        'maret' => 3,
        'mar' => 3,
        'march' => 3,
        'april' => 4,
        'apr' => 4,
        'mei' => 5,
        'may' => 5,
        'juni' => 6,
        'jun' => 6,
        'june' => 6,
        'juli' => 7,
        'jul' => 7,
        'july' => 7,
        'agustus' => 8,
        'agu' => 8,
        'aug' => 8,
        'september' => 9,
        'sept' => 9,
        'sep' => 9,
        'oktober' => 10,
        'okt' => 10,
        'oct' => 10,
        'november' => 11,
        'nov' => 11,
        'desember' => 12,
        'des' => 12,
        'december' => 12,
        'dec' => 12,
    ];

    public function __construct(?TemporalInterpreter $temporal = null)
    {
        $this->temporal = $temporal ?? new TemporalInterpreter(config('app.timezone', 'UTC'));
    }

    /**
     * Return tool/function definitions compatible with LLM function-calling.
     *
     * @return array<int, array<string, mixed>>
     */
    public function definitions(): array
    {
        $definitions = [];

        foreach ($this->schemas as $tool => $schema) {
            $meta = $this->toolMeta[$tool] ?? [];

            $definitions[] = [
                'type' => 'function',
                'function' => [
                    'name' => $tool,
                    'description' => $meta['description'] ?? ('Execute ' . $tool),
                    'parameters' => $this->buildJsonSchema($schema),
                ],
            ];
        }

        return $definitions;
    }

    /**
     * @param  array<string, array<string, mixed>>  $schema
     */
    private function buildJsonSchema(array $schema): array
    {
        $properties = [];
        $required = [];

        foreach ($schema as $field => $definition) {
            $properties[$field] = $this->jsonSchemaForField($field, $definition);

            $isRequired = ($definition['required'] ?? false) || !array_key_exists('default', $definition);
            if ($isRequired) {
                $required[] = $field;
            }
        }

        $jsonSchema = [
            'type' => 'object',
            'properties' => $properties === [] ? new \stdClass() : $properties,
        ];

        if ($required !== []) {
            $jsonSchema['required'] = $required;
        }

        return $jsonSchema;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function jsonSchemaForField(string $field, array $definition): array
    {
        $type = $definition['type'] ?? 'string';
        $description = $this->describeField($definition);

        if ($type === 'enum') {
            $schema = [
                'type' => 'string',
                'enum' => array_values($definition['values'] ?? []),
            ];

            if ($description !== null) {
                $schema['description'] = $description;
            }

            return $schema;
        }

        if ($type === 'month') {
            return [
                'type' => 'string',
                'pattern' => '^\\d{4}-\\d{2}$',
                'description' => $description ?? 'Month in YYYY-MM format (e.g. 2025-01).',
            ];
        }

        if ($type === 'period') {
            $schema = [
                'anyOf' => [
                    [
                        'type' => 'object',
                        'properties' => [
                            'start' => ['type' => 'string', 'description' => 'Start date (ISO8601)'],
                            'end' => ['type' => 'string', 'description' => 'End date (ISO8601)'],
                        ],
                        'required' => ['start', 'end'],
                    ],
                    [
                        'type' => 'string',
                        'description' => 'Keyword such as this_month, last_month, next_month.',
                    ],
                ],
            ];

            if ($description !== null) {
                $schema['description'] = $description;
            }

            return $schema;
        }

        $schema = [
            'type' => 'string',
        ];

        if (isset($definition['min_length'])) {
            $schema['minLength'] = (int) $definition['min_length'];
        }

        if (isset($definition['max_length'])) {
            $schema['maxLength'] = (int) $definition['max_length'];
        }

        if ($description !== null) {
            $schema['description'] = $description;
        }

        return $schema;
    }

    private function describeField(array $definition): ?string
    {
        if (isset($definition['description']) && is_string($definition['description'])) {
            return $definition['description'];
        }

        $clarification = $definition['clarification'] ?? null;

        if (is_string($clarification)) {
            return $clarification;
        }

        if (is_array($clarification)) {
            if (isset($clarification['id'])) {
                return $clarification['id'];
            }

            if (isset($clarification['en'])) {
                return $clarification['en'];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @param  array<string, mixed>  $lexicalContext
     * @return array{valid:bool, parameters:array, errors:array, clarification?:string}
     */
    public function validate(string $tool, array $parameters, string $message = '', array $lexicalContext = []): array
    {
        $schema = $this->schemas[$tool] ?? null;

        if ($schema === null) {
            return [
                'valid' => true,
                'parameters' => $parameters,
                'errors' => [],
                'autofixed' => [],
            ];
        }

        $normalized = is_array($parameters) ? $parameters : [];
        $errors = [];
        $autoFixedFields = [];

        foreach ($schema as $field => $definition) {
            $filledAutomatically = false;
            $value = $normalized[$field] ?? null;

            if ($value === null || $value === '') {
                $value = $this->autoFill($field, $definition, $message, $lexicalContext);
                $filledAutomatically = $value !== null && $value !== '';

                if (($value === null || $value === '') && array_key_exists('default', $definition)) {
                    $value = $definition['default'];
                    $filledAutomatically = true;
                }

                if ($value !== null && $value !== '') {
                    [$isValid, $coerced, $errorMessage] = $this->validateField($value, $definition);
                    if ($isValid) {
                        $normalized[$field] = $coerced;
                        $autoFixedFields[] = $field;
                        continue;
                    }
                }
            }

            if (($value === null || $value === '') && array_key_exists('default', $definition)) {
                $value = $definition['default'];
                $filledAutomatically = true;
            }

            [$isValid, $coerced, $errorMessage] = $this->validateField($value, $definition);

            if (!$isValid) {
                $retry = $this->autoFill($field, $definition, $message, $lexicalContext);

                if (($retry === null || $retry === '') && array_key_exists('default', $definition)) {
                    $retry = $definition['default'];
                }

                if ($retry !== null && $retry !== '') {
                    [$retryValid, $retryCoerced, $retryError] = $this->validateField($retry, $definition);
                    if ($retryValid) {
                        $normalized[$field] = $retryCoerced;
                        $autoFixedFields[] = $field;
                        continue;
                    }

                    $errorMessage = $retryError;
                }

                $errors[$field] = $errorMessage;
                continue;
            }

            $normalized[$field] = $coerced;
            if ($filledAutomatically) {
                $autoFixedFields[] = $field;
            }
        }
        $autoFixedFields = array_values(array_unique($autoFixedFields));

        if ($errors !== []) {
            return [
                'valid' => false,
                'parameters' => $normalized,
                'errors' => $errors,
                'clarification' => $this->buildClarification($errors, $schema),
                'autofixed' => $autoFixedFields,
            ];
        }

        return [
            'valid' => true,
            'parameters' => $normalized,
            'errors' => [],
            'autofixed' => $autoFixedFields,
        ];
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function autoFill(string $field, array $definition, string $message, array $lexicalContext): mixed
    {
        $type = $definition['type'] ?? 'string';

        if ($type === 'enum') {
            if ($field === 'range') {
                $range = $this->rangeFromLexicon($lexicalContext);

                if ($range !== null) {
                    return $range;
                }

                foreach ($this->agendaSynonyms as $label => $synonyms) {
                    foreach ($synonyms as $synonym) {
                        if (Str::contains(Str::lower($message), Str::lower($synonym))) {
                            return $label;
                        }
                    }
                }
            }

            if ($field === 'status') {
                return $this->inferFromSynonyms($message, $lexicalContext, $this->residentStatusSynonyms);
            }

            if ($field === 'position') {
                return $this->inferFromSynonyms($message, $lexicalContext, $this->rtPositionSynonyms);
            }

            if ($field === 'type') {
                return $this->inferFromSynonyms($message, $lexicalContext, array_merge(
                    ['all' => ['semua', 'all', 'apapun']],
                    $this->billTypeSynonyms
                ));
            }
        }

        if ($type === 'period') {
            $period = $this->periodFromLexicon($lexicalContext);
            if ($period !== null) {
                return $period;
            }

            $parsed = $this->temporal->parsePeriod($message);
            if (is_array($parsed) && isset($parsed['start'], $parsed['end'])) {
                return $parsed;
            }
        }

        if ($type === 'month') {
            $fromLexicon = $this->monthFromLexicon($lexicalContext);
            if ($fromLexicon !== null) {
                return $fromLexicon;
            }

            return $this->monthFromMessage($message);
        }

        if ($field === 'query' && trim($message) !== '') {
            if (($definition['mode'] ?? null) === 'full') {
                $clean = $this->cleanFullQuery($message, (int) ($definition['max_length'] ?? 200));
                if ($clean !== '') {
                    return $clean;
                }
            }

            $tokens = $this->filterQueryTokens(preg_split('/\s+/', $message) ?: []);

            if (!empty($tokens)) {
                $max = (int) ($definition['max_length'] ?? 64);

                return Str::limit(implode(' ', array_slice($tokens, 0, 5)), $max, '');
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array{0:bool,1:mixed,2:string}
     */
    private function validateField(mixed $value, array $definition): array
    {
        $type = $definition['type'] ?? 'string';

        if ($value === null || $value === '') {
            return [false, null, 'missing'];
        }

        if ($type === 'enum') {
            $normalized = $this->normalizeEnumValue($value, $definition);

            if ($normalized === null) {
                return [false, null, 'invalid_enum'];
            }

            return [true, $normalized, ''];
        }

        if ($type === 'month') {
            $monthValue = $this->coerceMonthValue($value);

            if ($monthValue === null) {
                return [false, null, 'invalid_month'];
            }

            return [true, $monthValue, ''];
        }

        if ($type === 'period') {
            $range = $this->coercePeriodValue($value);

            if ($range === null) {
                return [false, null, 'invalid_period'];
            }

            return [true, $range, ''];
        }

        if ($type === 'string') {
            $stringValue = Str::of($value)->squish()->value();

            $min = $definition['min_length'] ?? null;
            $max = $definition['max_length'] ?? null;

            if ($min !== null && Str::length($stringValue) < $min) {
                return [false, null, 'too_short'];
            }

            if ($max !== null && Str::length($stringValue) > $max) {
                $stringValue = Str::limit($stringValue, $max, '');
            }

            return [true, $stringValue, ''];
        }

        return [true, $value, ''];
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    private function buildClarification(array $errors, array $schema): string
    {
        $messages = [];

        foreach ($errors as $field => $code) {
            $definition = $schema[$field] ?? [];
            $clarification = $definition['clarification'] ?? null;

            if (is_array($clarification)) {
                $messages[] = $clarification['id'] ?? $clarification['en'] ?? null;
            } elseif (is_string($clarification)) {
                $messages[] = $clarification;
            }
        }

        $messages = array_filter($messages);

        return $messages !== []
            ? implode("\n", $messages)
            : 'Parameter tool belum lengkap, bisa jelaskan lagi?';
    }

    private function rangeFromLexicon(array $lexicalContext): ?string
    {
        $dates = $lexicalContext['entities']['dates'] ?? [];

        foreach ($dates as $entity) {
            $range = $entity['range'] ?? null;

            if (is_string($range) && $range !== '') {
                return $range;
            }
        }

        return null;
    }

    private function periodFromLexicon(array $lexicalContext): ?array
    {
        $months = $lexicalContext['entities']['months'] ?? [];

        foreach ($months as $entity) {
            $month = isset($entity['month']) ? (int) $entity['month'] : null;
            if ($month === null || $month < 1 || $month > 12) {
                continue;
            }

            $year = isset($entity['year_hint']) ? (int) $entity['year_hint'] : Carbon::now()->year;
            try {
                $reference = Carbon::create($year, $month, 1, 0, 0, 0, config('app.timezone', 'UTC'));
            } catch (\Throwable) {
                continue;
            }

            return $this->temporal->monthRange($reference);
        }

        return null;
    }

    private function coercePeriodValue(mixed $value): ?array
    {
        if (is_array($value) && isset($value['start'], $value['end'])) {
            return $value;
        }

        if (is_string($value)) {
            return $this->periodFromKeyword($value);
        }

        return null;
    }

    private function periodFromKeyword(string $keyword): ?array
    {
        $normalized = Str::lower(Str::squish($keyword));
        $timezone = config('app.timezone', 'UTC');

        return match ($normalized) {
            'current', 'current_month', 'bulan_ini', 'this_month' => $this->temporal->monthRange(Carbon::now($timezone)),
            'previous', 'previous_month', 'bulan_lalu', 'last_month' => $this->temporal->monthRange(Carbon::now($timezone)->subMonth()),
            'next', 'next_month', 'bulan_depan' => $this->temporal->monthRange(Carbon::now($timezone)->addMonth()),
            default => null,
        };
    }

    private function normalizeEnumValue(mixed $value, array $definition): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $normalized = Str::lower(Str::squish((string) $value));
        $allowed = $definition['values'] ?? [];

        if (in_array($normalized, $allowed, true)) {
            return $normalized;
        }

        $synonyms = $definition['synonyms'] ?? [];
        foreach ($synonyms as $target => $terms) {
            foreach ((array) $terms as $term) {
                if ($normalized === Str::lower(Str::squish((string) $term))) {
                    return $target;
                }
            }
        }

        return null;
    }

    private function inferFromSynonyms(string $message, array $lexicalContext, array $map): ?string
    {
        $tokens = $this->lexiconTokens($lexicalContext);
        foreach ($tokens as $token) {
            if (array_key_exists($token, $map)) {
                return $token;
            }
        }

        $lower = Str::lower($message);

        foreach ($map as $value => $synonyms) {
            foreach ($synonyms as $synonym) {
                if (Str::contains($lower, Str::lower($synonym))) {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function lexiconTokens(array $lexicalContext): array
    {
        $tokens = $lexicalContext['tokens'] ?? [];

        return array_values(array_filter(array_map(
            fn ($token) => is_string($token) ? Str::lower(Str::squish($token)) : null,
            $tokens
        )));
    }

    private function monthFromLexicon(array $lexicalContext): ?string
    {
        $months = $lexicalContext['entities']['months'] ?? [];

        foreach ($months as $entity) {
            $month = isset($entity['month']) ? (int) $entity['month'] : null;
            if ($month === null || $month < 1 || $month > 12) {
                continue;
            }

            $year = isset($entity['year_hint']) ? (int) $entity['year_hint'] : Carbon::now()->year;
            try {
                return Carbon::create($year, $month, 1, 0, 0, 0, config('app.timezone', 'UTC'))->format('Y-m');
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private function monthFromMessage(string $message): ?string
    {
        $normalized = Str::lower($message);

        foreach ($this->monthNameMap as $label => $monthNumber) {
            if (Str::contains($normalized, $label)) {
                $year = $this->extractYearFromText($normalized) ?? Carbon::now()->year;

                return sprintf('%04d-%02d', $year, $monthNumber);
            }
        }

        if (Str::contains($normalized, 'bulan ini') || Str::contains($normalized, 'this month')) {
            return Carbon::now(config('app.timezone', 'UTC'))->format('Y-m');
        }

        if (Str::contains($normalized, 'bulan lalu') || Str::contains($normalized, 'last month')) {
            return Carbon::now(config('app.timezone', 'UTC'))->subMonth()->format('Y-m');
        }

        if (Str::contains($normalized, 'bulan depan') || Str::contains($normalized, 'next month')) {
            return Carbon::now(config('app.timezone', 'UTC'))->addMonth()->format('Y-m');
        }

        if (preg_match('/(20\\d{2})[-\\/](0[1-9]|1[0-2])/', $normalized, $matches)) {
            return "{$matches[1]}-{$matches[2]}";
        }

        if (preg_match('/(0?[1-9]|1[0-2])[-\\/](20\\d{2})/', $normalized, $matches)) {
            return sprintf('%s-%02d', $matches[2], (int) $matches[1]);
        }

        return null;
    }

    private function extractYearFromText(string $text): ?int
    {
        if (preg_match('/(20\\d{2}|19\\d{2})/', $text, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function cleanFullQuery(string $message, int $max): string
    {
        $clean = Str::of($message)->replaceMatches('/\\s+/', ' ')->trim()->value();

        return $clean === '' ? '' : Str::limit($clean, $max, '');
    }

    /**
     * @param  array<int, string>  $tokens
     * @return array<int, string>
     */
    private function filterQueryTokens(array $tokens): array
    {
        $noise = ['tolong', 'please', 'bantu', 'dong', 'ya', 'nih', 'dong', 'minta', 'bisa', 'boleh', 'aku', 'saya', 'mohon', 'cek', 'lihat'];
        $filtered = [];

        foreach ($tokens as $token) {
            $clean = Str::of($token)->lower()->replaceMatches('/[^a-z0-9]/u', '')->value();

            if ($clean === '' || in_array($clean, $noise, true) || Str::length($clean) < 2) {
                continue;
            }

            $filtered[] = $clean;
        }

        return $filtered;
    }

    private function coerceMonthValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            if (isset($value['month']) && isset($value['year'])) {
                $month = (int) $value['month'];
                $year = (int) $value['year'];

                if ($month >= 1 && $month <= 12) {
                    return sprintf('%04d-%02d', $year, $month);
                }
            }

            if (isset($value['start'])) {
                try {
                    return Carbon::parse($value['start'])->format('Y-m');
                } catch (\Throwable) {
                    return null;
                }
            }
        }

        if (is_string($value)) {
            $keyword = $this->parseMonthKeyword($value);
            if ($keyword !== null) {
                return $keyword;
            }

            $parsed = $this->monthFromMessage($value);
            if ($parsed !== null) {
                return $parsed;
            }

            try {
                return Carbon::parse($value, config('app.timezone', 'UTC'))->format('Y-m');
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    private function parseMonthKeyword(string $keyword): ?string
    {
        $normalized = Str::lower(Str::squish($keyword));
        $timezone = config('app.timezone', 'UTC');

        return match ($normalized) {
            'current', 'current_month', 'bulan_ini', 'this_month', 'now' => Carbon::now($timezone)->format('Y-m'),
            'previous', 'previous_month', 'bulan_lalu', 'last_month' => Carbon::now($timezone)->subMonth()->format('Y-m'),
            'next', 'next_month', 'bulan_depan' => Carbon::now($timezone)->addMonth()->format('Y-m'),
            default => null,
        };
    }
}
