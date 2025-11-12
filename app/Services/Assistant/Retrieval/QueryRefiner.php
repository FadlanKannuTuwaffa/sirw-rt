<?php

namespace App\Services\Assistant\Retrieval;

use App\Support\Assistant\LanguageDetector;
use Illuminate\Support\Str;

class QueryRefiner
{
    /**
     * @var array<string, array<int, string>>
     */
    private array $synonymMap = [
        'surat' => ['letter', 'document', 'dokumen'],
        'prosedur' => ['procedure', 'proses', 'langkah', 'steps', 'how to', 'cara'],
        'pindah' => ['mutasi', 'moving', 'relocate'],
        'domisili' => ['alamat', 'address letter'],
        'ktp' => ['kartu tanda penduduk'],
        'kk' => ['kartu keluarga'],
        'pengurus' => ['petugas', 'admin', 'officer'],
        'iuran' => ['bayaran', 'fee', 'payment'],
        'izin' => ['permit', 'permission'],
        'jadwal' => ['schedule', 'timeline'],
    ];

    /**
     * Refine query and return normalized variants + metadata.
     *
     * @return array{
     *     primary:string,
     *     variants:array<int,string>,
     *     keywords:array<int,string>,
     *     focus:?string
     * }
     */
    public function refine(string $query): array
    {
        $normalized = Str::of($query)->lower()->squish()->value();
        $variants = [];
        $keywords = [];

        foreach ($this->synonymMap as $canonical => $aliases) {
            foreach ($aliases as $alias) {
                if (Str::contains($normalized, $alias)) {
                    $variants[] = Str::replace($alias, $canonical, $normalized);
                    $keywords[] = $canonical;
                }

                if (Str::contains($normalized, $canonical) && !in_array($alias, $keywords, true)) {
                    $keywords[] = $alias;
                }
            }
        }

        $language = LanguageDetector::detect($query);
        if ($language !== 'id') {
            $translatedHints = $this->languageHints($language);
            $keywords = array_merge($keywords, $translatedHints);
        }

        $focus = $this->questionFocus($normalized);

        return [
            'primary' => $normalized,
            'variants' => array_values(array_unique(array_filter($variants))),
            'keywords' => array_values(array_unique(array_filter($keywords))),
            'focus' => $focus,
        ];
    }

    /**
     * @return array<int,string>
     */
    private function languageHints(string $language): array
    {
        return match ($language) {
            'en' => ['procedure', 'document', 'steps'],
            'jv' => ['prosedur', 'surat', 'dokumen'],
            'su' => ['prosedur', 'surat'],
            default => [],
        };
    }

    private function questionFocus(string $normalized): ?string
    {
        if (Str::contains($normalized, ['kapan', 'jam berapa', 'tanggal', 'when'])) {
            return 'time';
        }

        if (Str::contains($normalized, ['siapa', 'who', 'petugas', 'pengurus'])) {
            return 'person';
        }

        if (Str::contains($normalized, ['bagaimana', 'cara', 'langkah', 'prosedur', 'how'])) {
            return 'procedure';
        }

        if (Str::contains($normalized, ['dimana', 'di mana', 'where', 'lokasi'])) {
            return 'location';
        }

        return null;
    }
}
