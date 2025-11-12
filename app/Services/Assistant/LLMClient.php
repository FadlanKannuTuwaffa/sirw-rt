<?php

namespace App\Services\Assistant;

interface LLMClient
{
    /**
     * Kirim percakapan ke model dan terima respons atau panggilan tool.
     *
     * @param array<int, array<string, mixed>> $messages
     * @param array<int, array<string, mixed>> $tools
     * @return array<string, mixed>
     */
    public function chat(array $messages, array $tools = []): array;

    /**
     * Apakah implementasi mendukung pengiriman token streaming.
     */
    public function supportsStreaming(): bool;

    /**
     * Stream percakapan dan kirim token secara bertahap melalui callback.
     *
     * @param array<int, array<string, mixed>> $messages
     * @param array<int, array<string, mixed>> $tools
     * @param callable $onEvent Signature: function (string $event, mixed $payload): void
     * @return array<string, mixed>
     */
    public function stream(array $messages, array $tools, callable $onEvent): array;

    /**
     * Dapatkan embedding dari teks tunggal bila tersedia.
     *
     * @return array<int, float>|null
     */
    public function embed(string $text): ?array;
}
