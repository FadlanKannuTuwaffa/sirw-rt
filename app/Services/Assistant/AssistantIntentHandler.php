<?php

namespace App\Services\Assistant;

use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

class AssistantIntentHandler
{
    private const FIELD_SEPARATOR = " \u{0007} ";

    private string $language = 'id';

    /**
     * Coba tangani pesan sebelum diteruskan ke LLM.
     *
     * @return array{content:string}|null
     */
    public function handle(string $message, ToolRouter $router, int $residentId, array $predictedIntents = [], string $language = 'id'): ?array
    {
        $this->setLanguage($language);

        $keywordIntents = $this->resolveIntentsFromMessage($message);
        $intents = $predictedIntents !== []
            ? $this->filterIntentsByKeywords($predictedIntents, $keywordIntents)
            : $keywordIntents;

        if ($intents === []) {
            return null;
        }

        return $this->handleIntents($intents, $message, $router, $residentId);
    }

    /**
     * @param array<int, string> $predicted
     * @param array<int, string> $keywordIntents
     * @return array<int, string>
     */
    private function filterIntentsByKeywords(array $predicted, array $keywordIntents): array
    {
        $predicted = array_values(array_unique(array_filter($predicted, 'is_string')));

        if ($predicted === []) {
            return $keywordIntents;
        }

        if ($keywordIntents === []) {
            return [];
        }

        $supported = array_values(array_intersect($predicted, $keywordIntents));

        return $supported !== [] ? $supported : $keywordIntents;
    }

    /**
     * Tangani daftar intent yang sudah diklasifikasi sebelumnya.
     *
     * @param array<int, string> $intents
     * @return array{content:string}|null
     */
    public function handleIntents(array $intents, string $message, ToolRouter $router, int $residentId, ?string $language = null): ?array
    {
        if ($language !== null) {
            $this->setLanguage($language);
        }

        $responses = [];

        foreach (array_values(array_unique($intents)) as $intent) {
            $response = $this->handleSingleIntent($intent, $message, $router, $residentId);

            if ($response) {
                $responses[] = $response['content'];
            }
        }

        if ($responses === []) {
            return null;
        }

        $combined = implode("\n\n", array_values(array_unique($responses)));

        return ['content' => $combined];
    }

    /**
     * @return array{content:string}|null
     */
    private function handleSingleIntent(string $intent, string $message, ToolRouter $router, int $residentId): ?array
    {
        $lower = Str::of($message)->lower()->value();

        return match ($intent) {
            'tagihan' => $this->respondValidated(
                $router,
                'get_outstanding_bills',
                ['resident_id' => $residentId],
                $message,
                fn ($payload) => $this->formatOutstandingBills($payload)
            ),
            'pembayaran' => $this->respondValidated(
                $router,
                'get_payments_this_month',
                ['resident_id' => $residentId],
                $message,
                fn ($payload) => $this->formatPayments($payload)
            ),
            'agenda' => $this->respondValidated(
                $router,
                'get_agenda',
                [
                    'resident_id' => $residentId,
                    'range' => $this->resolveAgendaRange($lower),
                ],
                $message,
                fn ($payload) => $this->formatAgenda($payload, $this->resolveAgendaRange($lower))
            ),
            'keuangan' => $this->respondValidated(
                $router,
                'export_financial_recap',
                [
                    'resident_id' => $residentId,
                    'period' => $this->resolveFinancialPeriod($lower),
                ],
                $message,
                fn ($payload) => $this->formatFinancialRecap($payload)
            ),
            'warga' => $this->respondValidated(
                $router,
                'search_directory',
                [
                    'resident_id' => $residentId,
                    'query' => '*',
                    'status' => 'all',
                ],
                $message,
                fn ($payload) => $this->formatDirectorySummary($payload)
            ),
            'cari_warga' => $this->handleDirectorySearchIntent($lower, $router, $residentId, $message),
            'kontak' => $this->respondValidated(
                $router,
                'get_rt_contacts',
                ['position' => $this->resolveContactPosition($lower)],
                $message,
                fn ($payload) => $this->formatContacts($payload)
            ),
            'surat' => ['content' => $this->isEnglish()
                ? 'To request official RT letters (introductions, domicile letters, SKCK), prepare your ID card and family card, fill out the form at the secretariat or message the admin, then follow the committee’s guidance. Processing usually takes 1-2 working days.'
                : 'Untuk mengurus surat RT (pengantar, domisili, SKCK): siapkan KTP & KK, isi formulir di sekretariat/WA admin, lalu ikuti arahan pengurus. Biasanya selesai 1-2 hari kerja.'],
            'fasilitas' => ['content' => $this->isEnglish()
                ? 'Facility info: the patrol and trash collection schedule is in the Agenda menu. To book the hall, contact the committee at least 3 days beforehand.'
                : 'Info fasilitas RT: jadwal ronda & pengambilan sampah ada di menu Agenda. Untuk pinjam balai/aula, hubungi pengurus minimal 3 hari sebelumnya ya.'],
            'bantuan' => ['content' => $this->isEnglish()
                ? "I can help you with:\n- Checking outstanding bills\n- Seeing this month’s payments\n- Listing upcoming events\n- Looking up resident contacts\n- Exporting the financial recap\n\nTry asking things like “How much are my bills this month?” or “Who is the RT head?”"
                : "Aku bisa bantu cek tagihan, riwayat bayar, agenda, kontak pengurus, sampai direktori warga. Coba tanya: 'Tagihanku bulan ini berapa?' atau 'Kontak ketua RT siapa?'."],
            default => null,
        };
    }

    /**
     * Deteksi intent dari pesan mentah (fallback ketika tidak ada hasil klasifikasi).
     *
     * @return array<int, string>
     */
    private function resolveIntentsFromMessage(string $message): array
    {
        $text = Str::of($message)->lower();
        $intents = [];

        if ($this->matches($text, ['tagihan', 'iuran', 'tunggak', 'tunggakan', 'bill', 'bills', 'outstanding bill', 'unpaid bill', 'invoice'])) {
            $intents[] = 'tagihan';
        }

        if ($this->matches($text, ['pembayaran', 'riwayat bayar', 'riwayat pembayaran', 'sudah bayar', 'payment', 'paid', 'pay', 'transfer', 'bukti bayar'])) {
            $intents[] = 'pembayaran';
        }

        if ($this->matches($text, ['agenda', 'acara', 'kegiatan', 'event', 'events', 'rapat', 'jadwal', 'schedule', 'meeting'])) {
            $intents[] = 'agenda';
        }

        $hasFinanceKeyword = $this->matches($text, ['keuangan', 'finansial', 'finance', 'financial', 'kas', 'saldo', 'dana']);
        $hasRecapKeyword = $this->matches($text, ['rekap', 'ringkasan', 'laporan', 'report']);

        if ($hasFinanceKeyword || ($hasRecapKeyword && $this->matches($text, ['keuangan', 'kas', 'dana', 'finance', 'financial']))) {
            $intents[] = 'keuangan';
        }

        if ($this->matches($text, ['berapa', 'jumlah', 'total', 'how many', 'count']) && $this->matches($text, ['warga', 'penduduk', 'resident', 'residents'])) {
            $intents[] = 'warga';
        }

        if ($this->matches($text, ['cari', 'search', 'find', 'lookup']) && $this->matches($text, ['warga', 'orang', 'resident'])) {
            $intents[] = 'cari_warga';
        }

        if ($this->matches($text, ['kontak', 'nomor', 'hubungi', 'ketua', 'sekretaris', 'bendahara', 'contact', 'phone', 'chairman', 'leader', 'secretary', 'treasurer'])) {
            $intents[] = 'kontak';
        }

        if ($this->matches($text, ['surat', 'pengantar', 'domisili', 'skck', 'letter'])) {
            $intents[] = 'surat';
        }

        if ($this->matches($text, ['fasilitas', 'sampah', 'ronda', 'balai', 'aula', 'lapangan', 'keamanan', 'facility', 'hall', 'trash', 'patrol'])) {
            $intents[] = 'fasilitas';
        }

        if ($this->matches($text, ['bantuan', 'bisa apa', 'tolong', 'panduan', 'help', 'assist', 'support'])) {
            $intents[] = 'bantuan';
        }

        return array_values(array_unique($intents));
    }

    /**
     * @param callable(array):array{content:string} $formatter
     */
    private function respondValidated(
        ToolRouter $router,
        string $toolName,
        array $args,
        string $message,
        callable $formatter
    ): ?array {
        $validated = $this->validateToolCall($router, $toolName, $args, $message);

        if (isset($validated['clarification'])) {
            return ['content' => $validated['clarification']];
        }

        return $formatter($validated['result']);
    }

    /**
     * @param  array<string,mixed>  $args
     * @return array{result?:array,clarification?:string}
     */
    private function validateToolCall(ToolRouter $router, string $toolName, array $args, string $message): array
    {
        $validation = $router->validateAndCoerce($toolName, $args, $message);

        if (!($validation['valid'] ?? true)) {
            $clarification = $validation['clarification'] ?? ($this->isEnglish()
                ? 'I need one more detail to run that tool. Could you specify it?'
                : 'Parameter tool-nya belum pas. Bisa sebut detail yang kamu maksud?');

            return ['clarification' => $clarification];
        }

        $parameters = $validation['parameters'] ?? $args;

        return [
            'result' => $router->execute($toolName, $parameters),
        ];
    }

    private function matchNameFromMessage(string $lower): ?string
    {
        $patterns = [
            '/bernama\s+([\p{L}\']+)/u',
            '/atas nama\s+([\p{L}\']+)/u',
            '/nama\s+([\p{L}\']+)/u',
            '/named\s+([\p{L}\']+)/u',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $lower, $matches)) {
                return Str::of($matches[1] ?? '')->studly()->value();
            }
        }

        $tokens = preg_split('/[^a-z\p{L}]+/u', $lower) ?: [];
        $stopWords = ['cari', 'warga', 'resident', 'nama', 'siapa', 'yang', 'find', 'search'];

        foreach ($tokens as $token) {
            if (Str::length($token) >= 3 && !in_array($token, $stopWords, true)) {
                return Str::of($token)->studly()->value();
            }
        }

        return null;
    }

    private function handleDirectorySearchIntent(string $lower, ToolRouter $router, int $residentId, string $message): ?array
    {
        $name = $this->matchNameFromMessage($lower);

        $validated = $this->validateToolCall($router, 'search_directory', [
            'resident_id' => $residentId,
            'query' => $name ?? '*',
            'status' => 'all',
        ], $message);

        if (isset($validated['clarification'])) {
            return ['content' => $validated['clarification']];
        }

        return $this->formatDirectorySearch($validated['result'], $name);
    }

    private function resolveAgendaRange(string $message): string
    {
        if (Str::contains($message, ['minggu', 'week', 'pekan'])) {
            return 'week';
        }

        return 'month';
    }

    private function resolveFinancialPeriod(string $message): string
    {
        return Str::contains($message, ['bulan lalu', 'last month', 'sebelumnya', 'previous']) ? 'last_month' : 'this_month';
    }

    private function resolveContactPosition(string $message): string
    {
        if (Str::contains($message, ['ketua', 'chairman', 'leader', 'head'])) {
            return 'ketua';
        }

        if (Str::contains($message, ['sekretaris', 'secretary'])) {
            return 'sekretaris';
        }

        if (Str::contains($message, ['bendahara', 'treasurer'])) {
            return 'bendahara';
        }

        return 'all';
    }

    private function matches(Stringable $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($text->contains($needle)) {
                return true;
            }
        }

        return false;
    }

    private function formatOutstandingBills(array $result): array
    {
        if (!($result['success'] ?? false)) {
            $fallback = $this->isEnglish() ? 'Bill data is currently unavailable.' : 'Data tagihan belum tersedia.';
            return ['content' => $result['error'] ?? $fallback];
        }

        if (($result['count'] ?? 0) === 0) {
            return ['content' => $this->isEnglish()
                ? 'Great news! You have no outstanding bills.'
                : 'Selamat! Tidak ada tagihan tertunggak.'];
        }

        $items = collect($result['items'] ?? [])
            ->map(function ($item) {
                $dueLabel = $this->isEnglish()
                    ? 'Due ' . ($item['due_date'] ?? '-')
                    : 'Jatuh tempo ' . ($item['due_date'] ?? '-');

                return "- {$item['title']}" . self::FIELD_SEPARATOR . "{$item['amount']}" . self::FIELD_SEPARATOR . $dueLabel;
            })
            ->implode("\n");

        $total = $this->formatRupiah((int) ($result['total'] ?? 0));

        $message = $this->isEnglish()
            ? "Here are your active bills:\n{$items}\n\nTotal: {$total}. You can settle them via the Bills menu."
            : "Tagihan aktif kamu:\n{$items}\n\nTotal: {$total}. Kamu bisa menyelesaikannya lewat menu Tagihan.";

        return ['content' => $message];
    }

    private function formatPayments(array $result): array
    {
        if (!($result['success'] ?? false)) {
            $fallback = $this->isEnglish() ? 'Payment history is currently unavailable.' : 'Riwayat pembayaran sementara tidak tersedia.';
            return ['content' => $result['error'] ?? $fallback];
        }

        if (($result['count'] ?? 0) === 0) {
            return ['content' => $this->isEnglish()
                ? 'No payments have been recorded this month.'
                : 'Belum ada pembayaran yang tercatat bulan ini.'];
        }

        $items = collect($result['items'] ?? [])
            ->map(fn ($item) => "- {$item['title']}" . self::FIELD_SEPARATOR . "{$item['amount']}" . self::FIELD_SEPARATOR . ($item['paid_at'] ?? '-'))
            ->implode("\n");

        $total = $this->formatRupiah((int) ($result['total'] ?? 0));

        $message = $this->isEnglish()
            ? "Payments recorded this month:\n{$items}\n\nTotal paid: {$total}. Thanks for staying on top of things!"
            : "Riwayat bayar bulan ini:\n{$items}\n\nTotal dibayar: {$total}. Terima kasih sudah membayar tepat waktu.";

        return ['content' => $message];
    }

    private function formatAgenda(array $result, string $range): array
    {
        if (!($result['success'] ?? false)) {
            $fallback = $this->isEnglish() ? 'Agenda data is not available right now.' : 'Agenda belum tersedia.';
            return ['content' => $result['error'] ?? $fallback];
        }

        if (($result['count'] ?? 0) === 0) {
            if ($range === 'week') {
                return ['content' => $this->isEnglish()
                    ? 'No agenda items scheduled this week. Enjoy the break!'
                    : 'Belum ada agenda minggu ini. Santai dulu, ya.'];
            }

            return ['content' => $this->isEnglish()
                ? 'There are no upcoming agenda items this month yet.'
                : 'Belum ada agenda bulan ini. Santai dulu, ya.'];
        }

        $items = collect($result['items'] ?? [])
            ->map(fn ($item) => "- {$item['title']}" . self::FIELD_SEPARATOR . "{$item['start_at']}" . self::FIELD_SEPARATOR . "{$item['location']}")
            ->implode("\n");

        $periodLabel = $range === 'week'
            ? ($this->isEnglish() ? 'Agenda for the next 7 days' : 'Agenda 7 hari ke depan')
            : ($this->isEnglish() ? 'Agenda for the next 30 days' : 'Agenda 30 hari ke depan');

        $footer = $this->isEnglish()
            ? 'Check the Agenda menu for full details.'
            : 'Detail lengkap ada di menu Agenda.';

        return ['content' => "{$periodLabel}:\n{$items}\n\n{$footer}"];
    }

    private function formatFinancialRecap(array $result): array
    {
        $period = $result['period'] ?? ($this->isEnglish() ? 'this period' : 'periode ini');
        $totalTagihan = $this->formatRupiah((int) ($result['total_tagihan'] ?? 0));
        $totalBayar = $this->formatRupiah((int) ($result['total_dibayar'] ?? 0));
        $totalTunggakan = $this->formatRupiah((int) ($result['total_tunggakan'] ?? 0));

        if ($this->isEnglish()) {
            return ['content' => "Financial recap ({$period}):\n- Bills issued: {$totalTagihan}\n- Paid: {$totalBayar}\n- Outstanding: {$totalTunggakan}\n\nDownload the detailed report from the Reports menu."];
        }

        return ['content' => "Rekap keuangan ({$period}):\n- Tagihan diterbitkan: {$totalTagihan}\n- Sudah dibayar: {$totalBayar}\n- Tunggakan: {$totalTunggakan}\n\nUnduh detail di menu Laporan ya!"];
    }

    private function formatDirectorySummary(array $result): array
    {
        if (!($result['success'] ?? false)) {
            $fallback = $this->isEnglish() ? 'Resident data is unavailable right now.' : 'Data warga tidak tersedia.';
            return ['content' => $result['error'] ?? $fallback];
        }

        $total = $result['total_warga'] ?? 0;

        return ['content' => $this->isEnglish()
            ? "There are currently {$total} residents registered. Check the Resident Directory menu for details."
            : "Saat ini ada {$total} warga terdaftar di RT. Lihat detailnya di menu Direktori Warga."];
    }

    private function formatDirectorySearch(array $result, ?string $name): array
    {
        if (!($result['success'] ?? false)) {
            $fallback = $this->isEnglish() ? 'Resident search failed.' : 'Pencarian warga gagal.';
            return ['content' => $result['error'] ?? $fallback];
        }

        if (($result['count'] ?? 0) === 0) {
            if ($name) {
                return ['content' => $this->isEnglish()
                    ? "No resident found with the name '{$name}'. Try another keyword or open the Resident Directory."
                    : "Tidak ditemukan warga dengan nama '{$name}'. Coba cek lagi atau buka menu Direktori."];
            }

            return ['content' => $this->isEnglish()
                ? 'No matching resident data. Open the Resident Directory for more information.'
                : 'Tidak ada data warga yang cocok. Buka menu Direktori untuk info lengkap.'];
        }

        $items = collect($result['items'] ?? [])
            ->map(function ($item) {
                $statusLabel = $this->isEnglish()
                    ? 'Status ' . ($item['status'] ?? '-')
                    : 'Status ' . ($item['status'] ?? '-');

                $contactLabel = $this->isEnglish()
                    ? 'Contact ' . ($item['phone'] ?? '-')
                    : 'Kontak ' . ($item['phone'] ?? '-');

                return "- {$item['name']}" . self::FIELD_SEPARATOR . $statusLabel . self::FIELD_SEPARATOR . $contactLabel;
            })
            ->implode("\n");

        $footer = $this->isEnglish()
            ? 'Open the Resident Directory for more details.'
            : 'Untuk detail lebih lengkap, buka menu Direktori Warga.';

        return ['content' => "Resident search results:\n{$items}\n\n{$footer}"];
    }

    private function formatContacts(array $result): array
    {
        if (!($result['success'] ?? false)) {
            $fallback = $this->isEnglish() ? 'RT contact information is not available yet.' : 'Kontak pengurus belum tersedia.';
            return ['content' => $result['message'] ?? $fallback];
        }

        if (isset($result['contact'])) {
            $c = $result['contact'];

            if ($this->isEnglish()) {
                return ['content' => "Contact {$c['position']}: {$c['name']}" . self::FIELD_SEPARATOR . "Phone {$c['phone']}" . self::FIELD_SEPARATOR . "Email {$c['email']}"];
            }

            return ['content' => "Kontak {$c['position']}: {$c['name']}" . self::FIELD_SEPARATOR . "Telp {$c['phone']}" . self::FIELD_SEPARATOR . "Email {$c['email']}"];
        }

        $items = collect($result['contacts'] ?? [])
            ->map(function ($item) {
                if ($this->isEnglish()) {
                    return "- {$item['position']}: {$item['name']}" . self::FIELD_SEPARATOR . "Phone {$item['phone']}";
                }

                return "- {$item['position']}: {$item['name']}" . self::FIELD_SEPARATOR . "{$item['phone']}";
            })
            ->implode("\n");

        $header = $this->isEnglish()
            ? 'RT management contacts:'
            : 'Kontak pengurus RT:';

        return ['content' => "{$header}\n{$items}"];
    }

    private function setLanguage(string $language): void
    {
        $language = strtolower($language);
        $this->language = $language === 'en' ? 'en' : 'id';
    }

    private function isEnglish(): bool
    {
        return $this->language === 'en';
    }

    private function formatRupiah(int $value): string
    {
        if ($this->isEnglish()) {
            return 'Rp ' . number_format($value, 0, '.', ',');
        }

        return 'Rp' . number_format($value, 0, ',', '.');
    }
}
