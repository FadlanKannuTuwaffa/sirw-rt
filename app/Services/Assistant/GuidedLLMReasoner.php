<?php

namespace App\Services\Assistant;

use App\Models\AssistantLlmSnapshot;
use App\Models\AssistantLlmSnapshotReview;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Services\Assistant\Reasoning\ReasoningLessonService;

class GuidedLLMReasoner
{
    private ConversationStateRepository $stateRepository;
    private ReasoningLessonService $lessonService;
    private array $lastContext = [];

    public function __construct(
        ?ConversationStateRepository $stateRepository = null,
        ?ReasoningLessonService $lessonService = null
    ) {
        $this->stateRepository = $stateRepository ?? app(ConversationStateRepository::class);
        $this->lessonService = $lessonService ?? app(ReasoningLessonService::class);
    }

    /**
     * @param array<int, array<string, mixed>> $history
     * @param array<string, mixed> $classification
     * @param array<int, string> $predictedIntents
     * @return array<int, array<string, mixed>>
     */
    public function buildMessages(
        User $user,
        array $history,
        string $latestUserMessage,
        string $systemPrompt,
        array $classification = [],
        array $predictedIntents = []
    ): array {
        $trimmedHistory = $this->trimHistory($history);
        $context = $this->buildContextSnapshot($user, $classification, $predictedIntents);
        $this->lastContext = $context;
        $guidance = $this->formatGuidance($context, $latestUserMessage);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'system', 'content' => $guidance],
        ];

        foreach ($trimmedHistory as $turn) {
            $messages[] = $turn;
        }

        $messages[] = ['role' => 'user', 'content' => $latestUserMessage];

        return $messages;
    }

    /**
     * @param array<int, array<string, mixed>> $history
     * @return array<int, array<string, mixed>>
     */
    private function trimHistory(array $history): array
    {
        $slice = array_slice($history, -10);

        return array_values(array_map(function ($turn) {
            $role = $turn['role'] ?? ($turn['type'] ?? 'assistant');
            $content = isset($turn['text']) ? (string) $turn['text'] : (string) ($turn['content'] ?? '');

            if ($role === 'user') {
                return ['role' => 'user', 'content' => Str::limit($content, 600)];
            }

            return ['role' => 'assistant', 'content' => Str::limit($content, 600)];
        }, $slice));
    }

    /**
     * @param array<string, mixed> $classification
     * @param array<int, string> $predictedIntents
     */
    private function buildContextSnapshot(User $user, array $classification, array $predictedIntents): array
    {
        $threadId = 'user:' . ($user->id ?? 'guest');
        $state = $this->stateRepository->get($user->id, $threadId);
        $metadata = $state['metadata'] ?? [];

        return [
            'user' => [
                'name' => $user->name ?? 'Warga',
                'role' => $user->role,
                'language' => Arr::get($metadata, 'language_override'),
            ],
            'state' => [
                'last_intent' => $state['last_intent'] ?? null,
                'last_topic' => $state['last_topic'] ?? null,
                'timestamp' => now()->toDateTimeString(),
                'timezone' => $metadata['timezone'] ?? config('app.timezone'),
                'slots' => $this->summarizeSlots($state['slots'] ?? []),
                'pending_slots' => $this->summarizeSlots($state['pending_slots'] ?? []),
                'last_data' => $this->summarizeStructuredData($state['last_data'] ?? null),
                'kb_sources' => $this->summarizeKbSources($state['kb_sources'] ?? []),
                'retry_constraints' => Arr::get($metadata, 'retry_constraints', []),
                'recent_guardrails' => $this->summarizeGuardrails(Arr::get($metadata, 'recent_guardrails', [])),
                'reasoning_lessons' => $this->lessonService->lessonsForIntent($state['last_intent'] ?? null),
            ],
            'classification' => [
                'type' => $classification['type'] ?? null,
                'confidence' => $classification['confidence'] ?? null,
                'intents' => array_values(array_filter($predictedIntents)),
            ],
        ];
    }

    public function context(): array
    {
        return $this->lastContext;
    }

    private function summarizeStructuredData(mixed $data): string
    {
        if ($data === null || $data === []) {
            return 'Tidak ada data terstruktur dari jawaban sebelumnya.';
        }

        if (is_string($data)) {
            return Str::limit($data, 480);
        }

        $items = collect(Arr::wrap($data))
            ->map(function ($item) {
                if (is_string($item)) {
                    return Str::limit($item, 160);
                }

                if (is_array($item)) {
                    $parts = [];
                    foreach (['title', 'name', 'amount', 'due_date', 'start_at', 'location', 'status'] as $field) {
                        if (!isset($item[$field])) {
                            continue;
                        }

                        $value = is_array($item[$field]) ? json_encode($item[$field]) : (string) $item[$field];
                        $parts[] = "{$field}: {$value}";
                    }

                    if ($parts === []) {
                        return null;
                    }

                    return '- ' . implode(', ', $parts);
                }

                if (is_object($item)) {
                    return '- ' . Str::limit(json_encode($item, JSON_UNESCAPED_UNICODE), 160);
                }

                return null;
            })
            ->filter()
            ->take(5)
            ->values()
            ->all();

        if ($items === []) {
            return 'Tidak ada data detail yang bisa diringkas.';
        }

        return implode("\n", $items);
    }

    private function summarizeSlots($slots): string
    {
        if (empty($slots) || !is_array($slots)) {
            return 'Tidak ada slot yang terisi.';
        }

        $pairs = [];
        foreach ($slots as $intent => $values) {
            if (!is_array($values)) {
                continue;
            }

            foreach ($values as $slot => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                $pairs[] = "{$intent}.{$slot}={$value}";
            }
        }

        if ($pairs === []) {
            return 'Tidak ada slot yang terisi.';
        }

        return implode(', ', array_slice($pairs, 0, 6));
    }

    private function summarizeKbSources(array $sources): string
    {
        if ($sources === []) {
            return 'Belum ada sumber RAG yang terekam.';
        }

        $summary = collect($sources)
            ->map(function ($source) {
                if (is_string($source)) {
                    return Str::limit($source, 120);
                }

                $title = $source['title'] ?? ($source['document_id'] ?? null);
                $section = $source['section'] ?? null;
                if ($title === null) {
                    return null;
                }

                return $section
                    ? "{$title} (bagian {$section})"
                    : (string) $title;
            })
            ->filter()
            ->take(4)
            ->values()
            ->all();

        return $summary === []
            ? 'Sumber RAG belum jelas.'
            : implode('; ', $summary);
    }

    private function summarizeGuardrails(array $guards): string
    {
        if (!is_array($guards) || $guards === []) {
            return 'Belum ada guardrail yang terpicu baru-baru ini.';
        }

        $counts = [];
        foreach ($guards as $name) {
            if (!is_string($name) || $name === '') {
                continue;
            }

            $counts[$name] = ($counts[$name] ?? 0) + 1;
        }

        if ($counts === []) {
            return 'Belum ada guardrail yang terpicu baru-baru ini.';
        }

        $parts = [];
        foreach ($counts as $name => $total) {
            $label = Str::headline(str_replace('_', ' ', $name));
            $parts[] = "{$label} (x{$total})";
        }

        return implode('; ', array_slice($parts, 0, 4));
    }

    private function formatGuidance(array $context, string $latestUserMessage): string
    {
        $user = $context['user'];
        $state = $context['state'];
        $classification = $context['classification'];

        $intentList = $classification['intents'] !== [] ? implode(', ', $classification['intents']) : 'tidak terdeteksi';

        $structured = [
            "Pengguna: {$user['name']} (role: {$user['role']})",
            'Bahasa preferensi: ' . ($user['language'] ?? 'mengikuti user'),
            'Intent model terakhir: ' . ($state['last_intent'] ?? 'tidak ada'),
            'Prediksi intent saat ini: ' . $intentList,
            'Slot terisi: ' . $state['slots'],
            'Slot pending: ' . $state['pending_slots'],
            'Data terakhir: ' . $state['last_data'],
            'Sumber RAG terakhir: ' . $state['kb_sources'],
            'Batasan retry: ' . json_encode($state['retry_constraints']),
            'Zona waktu: ' . ($state['timezone'] ?? 'tidak diketahui'),
            'Pembelajaran terbaru: ' . $this->latestLearnings(),
            'Guardrail terbaru: ' . ($state['recent_guardrails'] ?? 'Belum ada guardrail yang perlu diperhatikan.'),
            'Kesalahan yang harus dihindari: ' . $this->latestMistakes(),
            'Langkah reasoning referensi: ' . (
                empty($state['reasoning_lessons'])
                    ? 'Belum ada lesson khusus intent ini.'
                    : implode(' | ', $state['reasoning_lessons'])
            ),
        ];

        $structuredBlock = implode("\n- ", $structured);

        $reasoningSteps = <<<TXT
Langkah Reasoning yang WAJIB diikuti:
1. Pahami tujuan utama dari pesan user terakhir: "{$latestUserMessage}".
2. Cocokkan dengan konteks terstruktur di atas dan riwayat percakapan yang sudah diberikan.
3. Jika informasi belum cukup, tanyakan klarifikasi singkat tapi spesifik.
4. Jika informasi cukup, buat penjelasan yang natural seperti manusia, sertakan langkah atau arahan nyata.
5. Tutup dengan tawaran bantuan lanjutan yang relevan.
TXT;

        return <<<GUIDANCE
KONTEKS TERSTRUKTUR (gunakan sebagai fakta internal, jangan tampilkan apa adanya):
- {$structuredBlock}

{$reasoningSteps}

Catatan penting:
- Gunakan reasoning internal (tidak perlu ditulis eksplisit) sebelum memberi jawaban akhir.
- Utamakan data internal RT; jika harus berspekulasi, sebutkan keterbatasanmu.
- Jika user meminta ringkasan dari jawaban sebelumnya, rujuk pada konteks yang dirangkum di atas.
GUIDANCE;
    }

    private function latestLearnings(): string
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        if (!Schema::hasTable('assistant_llm_snapshots')) {
            return $cache = 'Belum ada catatan belajar.';
        }

        $snapshots = AssistantLlmSnapshot::query()
            ->whereIn('promotion_status', ['promoted', 'kb_ingested'])
            ->orderByDesc('promoted_at')
            ->limit(2)
            ->get(['intent', 'content']);

        if ($snapshots->isEmpty()) {
            return $cache = 'Belum ada catatan belajar.';
        }

        $cache = $snapshots->map(function (AssistantLlmSnapshot $snapshot, int $index) {
            $label = $snapshot->intent ? '[' . $snapshot->intent . '] ' : '';
            return ($index + 1) . '. ' . Str::limit($label . (string) $snapshot->content, 140);
        })->implode(' ');

        return $cache;
    }

    private function latestMistakes(): string
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        if (!Schema::hasTable('assistant_llm_snapshot_reviews')) {
            return $cache = 'Belum ada catatan kesalahan.';
        }

        $reviews = AssistantLlmSnapshotReview::query()
            ->with('snapshot:id,intent')
            ->whereIn('action', ['regression_warn', 'needs_review'])
            ->orderByDesc('created_at')
            ->limit(2)
            ->get();

        if ($reviews->isEmpty()) {
            return $cache = 'Belum ada catatan kesalahan.';
        }

        $cache = $reviews->map(function (AssistantLlmSnapshotReview $review, int $index) {
            $intent = $review->snapshot?->intent;
            $label = $intent ? '[' . $intent . '] ' : '';
            $note = $review->notes ?? 'Butuh verifikasi ulang karena tidak konsisten.';

            return ($index + 1) . '. ' . $label . Str::limit(Str::squish($note), 140);
        })->implode(' ');

        return $cache;
    }
}
