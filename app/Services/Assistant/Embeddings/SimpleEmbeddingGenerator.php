<?php

namespace App\Services\Assistant\Embeddings;

use Illuminate\Support\Str;

class SimpleEmbeddingGenerator
{
    private int $dimensions;

    public function __construct(int $dimensions = 256)
    {
        $this->dimensions = max(32, $dimensions);
    }

    public function dimensions(): int
    {
        return $this->dimensions;
    }

    /**
     * Bangun embedding sederhana berbasis hashing dan n-gram.
     *
     * @return array<int, float>
     */
    public function generate(string $text): array
    {
        $tokens = $this->tokenize($text);
        $vector = array_fill(0, $this->dimensions, 0.0);

        if ($tokens === []) {
            return $vector;
        }

        $previous = null;

        foreach ($tokens as $token) {
            $this->accumulateToken($vector, $token, 1.0);

            if ($previous !== null) {
                $bigram = $previous . '_' . $token;
                $this->accumulateToken($vector, $bigram, 0.75);
            }

            $previous = $token;
        }

        return $this->normalize($vector);
    }

    /**
     * Hitung cosine similarity antar embedding.
     *
     * @param array<int, float> $a
     * @param array<int, float> $b
     */
    public function cosine(array $a, array $b): float
    {
        $length = min(count($a), count($b));

        if ($length === 0) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $length; $i++) {
            $ai = (float) ($a[$i] ?? 0.0);
            $bi = (float) ($b[$i] ?? 0.0);

            $dot += $ai * $bi;
            $normA += $ai * $ai;
            $normB += $bi * $bi;
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }

    /**
     * @return array<int, string>
     */
    private function tokenize(string $text): array
    {
        $normalized = Str::of($text)
            ->lower()
            ->replaceMatches('/[^\\p{L}\\p{N}\\s]/u', ' ')
            ->squish()
            ->value();

        if ($normalized === '') {
            return [];
        }

        $tokens = preg_split('/\\s+/u', $normalized) ?: [];

        return array_values(array_filter($tokens, static fn ($token) => Str::length($token) > 1));
    }

    /**
     * @param array<int, float> $vector
     */
    private function accumulateToken(array &$vector, string $token, float $weight): void
    {
        $hash = crc32($token);
        $index = (int) ($hash % $this->dimensions);
        if ($index < 0) {
            $index += $this->dimensions;
        }

        $vector[$index] += $weight;
    }

    /**
     * @param array<int, float> $vector
     *
     * @return array<int, float>
     */
    private function normalize(array $vector): array
    {
        $sumSquares = 0.0;

        foreach ($vector as $value) {
            $sumSquares += $value * $value;
        }

        if ($sumSquares <= 0.0) {
            return $vector;
        }

        $norm = sqrt($sumSquares);

        return array_map(static fn (float $value) => round($value / $norm, 6), $vector);
    }
}
