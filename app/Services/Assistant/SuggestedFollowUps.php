<?php

namespace App\Services\Assistant;

class SuggestedFollowUps
{
    /**
     * @var array<string, array<int, array{id:string,en:string}>>
     */
    private array $fallbacks = [
        'bills' => [
            ['id' => 'Mau dapat pengingat jatuh tempo otomatis?', 'en' => 'Want automatic due reminders?'],
            ['id' => 'Butuh panduan metode pembayaran?', 'en' => 'Need a quick payment guide?'],
            ['id' => 'Ingin ekspor daftar tagihan ke PDF?', 'en' => 'Want to export the bill list to PDF?'],
        ],
        'payments' => [
            ['id' => 'Perlu detail pembayaran per tagihan?', 'en' => 'Need per-bill payment details?'],
            ['id' => 'Butuh unggah bukti transfer?', 'en' => 'Need to upload transfer receipts?'],
            ['id' => 'Mau ringkasan pembayaran dikirim ke email?', 'en' => 'Want the payment summary emailed to you?'],
        ],
        'agenda' => [
            ['id' => 'Mau kuingatkan jelang acara?', 'en' => 'Want me to remind you before the event?'],
            ['id' => 'Butuh kirim agenda ke keluarga?', 'en' => 'Need to share the agenda with family?'],
            ['id' => 'Perlu kontak panitia atau lokasi detail?', 'en' => 'Need organizer contacts or detailed venue info?'],
        ],
        'finance' => [
            ['id' => 'Ingin ekspor rekap ke PDF/XLSX?', 'en' => 'Want to export the recap to PDF/XLSX?'],
            ['id' => 'Butuh detail pemasukan/pengeluaran?', 'en' => 'Need detailed income/expense logs?'],
            ['id' => 'Perlu cek tunggakan tertentu?', 'en' => 'Need to check a specific outstanding item?'],
        ],
        'residents' => [
            ['id' => 'Mau lihat kontak pengurus RT?', 'en' => 'Want the committee contacts?'],
            ['id' => 'Butuh filter warga berdasarkan blok?', 'en' => 'Need to filter residents by block?'],
            ['id' => 'Ada warga baru yang ingin kamu kenal?', 'en' => 'Want an intro to new residents?'],
        ],
        'residents_new' => [
            ['id' => 'Ingin hubungi warga baru?', 'en' => 'Want to reach out to the new residents?'],
            ['id' => 'Butuh jadwalkan sesi perkenalan?', 'en' => 'Need to schedule a welcome session?'],
            ['id' => 'Perlu bantuan buat paket selamat datang?', 'en' => 'Need help preparing a welcome kit?'],
        ],
        'knowledge_base' => [
            ['id' => 'Perlu prosedur RT lainnya?', 'en' => 'Need another RT procedure?'],
            ['id' => 'Butuh contoh dokumen resmi?', 'en' => 'Need sample official documents?'],
            ['id' => 'Mau aku bantu isi formulirnya?', 'en' => 'Want help filling the form?'],
        ],
        'general' => [
            ['id' => 'Mau cek agenda terdekat?', 'en' => 'Want to check the upcoming agenda?'],
            ['id' => 'Perlu lihat tagihan atau pembayaran?', 'en' => 'Need to see bills or payments?'],
            ['id' => 'Ada prosedur RT lain yang ingin kamu tanyakan?', 'en' => 'Any other RT procedure you want to ask about?'],
        ],
    ];

    /**
     * Build follow-up suggestions tailored to an intent + contextual state.
     *
     * @param  array<string,mixed>  $context
     * @return array<int,string>
     */
    public function forIntent(string $intent, array $context = []): array
    {
        $language = $context['language'] ?? 'id';
        $state = $context['state'] ?? 'summary';

        $suggestions = match ($intent) {
            'bills' => $this->billsSuggestions($language, $state, $context),
            'payments' => $this->paymentsSuggestions($language, $state, $context),
            'agenda' => $this->agendaSuggestions($language, $state, $context),
            'finance' => $this->financeSuggestions($language, $state, $context),
            'residents' => $this->residentsSuggestions($language, $state, $context),
            'residents_new' => $this->newResidentsSuggestions($language, $state, $context),
            'knowledge_base' => $this->knowledgeSuggestions($language, $state, $context),
            default => [],
        };

        $unique = $this->uniqueList($suggestions);

        if (count($unique) < 3) {
            $fallbacks = $this->fallbackFor($intent, $language);
            $unique = $this->uniqueList(array_merge($unique, $fallbacks));
        }

        return array_slice($unique, 0, 3);
    }

    private function billsSuggestions(string $language, string $state, array $context): array
    {
        $list = [];

        if ($state === 'slot_missing') {
            $list[] = $this->t(
                'Tentukan periode tagihan dulu ya supaya bisa kucek.',
                'Let me know the bill period first so I can fetch it.',
                $language
            );
        }

        if (!empty($context['has_unpaid'])) {
            $list[] = $this->t(
                'Mau kuingatkan otomatis sebelum jatuh tempo?',
                'Want me to remind you automatically before it is due?',
                $language
            );
        }

        if (($context['overdue_count'] ?? 0) > 0) {
            $list[] = $this->t(
                'Butuh aku bantu hubungi admin soal tunggakan yang lewat tempo?',
                'Need me to alert the admins about the overdue bills?',
                $language
            );
        }

        if ($state === 'empty') {
            $list[] = $this->t(
                'Perlu cek riwayat pembayaran untuk memastikan semua sudah lunas?',
                'Want to review the payment history to double-check everything is settled?',
                $language
            );
        }

        return $list;
    }

    private function paymentsSuggestions(string $language, string $state, array $context): array
    {
        $list = [];

        if (($context['recent_payment_count'] ?? 0) > 0) {
            $list[] = $this->t(
                'Perlu kukirim bukti bayar terbaru ke email/WhatsApp?',
                'Want me to send the latest receipt to your email or WhatsApp?',
                $language
            );
        }

        if ($state === 'empty') {
            $list[] = $this->t(
                'Mau aku cek lagi tagihan yang belum dibayar?',
                'Should I check which bills are still unpaid?',
                $language
            );
        }

        if ($state === 'slot_missing') {
            $list[] = $this->t(
                'Sebut periode pembayarannya dulu ya supaya pas.',
                'Share the payment period you mean so I can be precise.',
                $language
            );
        }

        return $list;
    }

    private function agendaSuggestions(string $language, string $state, array $context): array
    {
        $list = [];
        $label = $context['agenda_range_label'] ?? null;

        if (($context['agenda_count'] ?? 0) > 0) {
            $list[] = $this->t(
                'Mau kuingatkan H-1 sebelum acaranya mulai?',
                'Want me to ping you a day before the event?',
                $language
            );
            $list[] = $this->t(
                'Butuh aku bantu broadcast agenda ke warga lain?',
                'Need help broadcasting this agenda to other residents?',
                $language
            );
        }

        if ($state === 'empty') {
            $list[] = $this->t(
                'Mau cek rentang tanggal lain supaya nggak kelewat acara?',
                'Want me to check a different date range so nothing is missed?',
                $language
            );
        }

        if ($state === 'slot_missing') {
            $list[] = $this->t(
                'Sebut periode agendanya dulu ya supaya tepat.',
                'Tell me the agenda range first so I can be accurate.',
                $language
            );
        }

        if ($label && $state === 'summary') {
            $list[] = $this->t(
                "Perlu undangan digital untuk {$label}?",
                "Need a digital invite for {$label}?",
                $language
            );
        }

        return $list;
    }

    private function financeSuggestions(string $language, string $state, array $context): array
    {
        $list = [];

        if (!empty($context['has_outstanding'])) {
            $list[] = $this->t(
                'Mau aku buat reminder bayar tunggakannya?',
                'Want me to set a reminder to settle the outstanding amount?',
                $language
            );
        }

        if (isset($context['format'])) {
            $format = strtoupper((string) $context['format']);
            $list[] = $this->t(
                "Perlu kugenerate file {$format} dan kirim ke email?",
                "Should I generate the {$format} file and email it to you?",
                $language
            );
        }

        if ($state === 'slot_missing') {
            $list[] = $this->t(
                'Sebutkan periode rekap yang diinginkan dulu ya.',
                'Let me know which period you want me to recap first.',
                $language
            );
        }

        return $list;
    }

    private function residentsSuggestions(string $language, string $state, array $context): array
    {
        $list = [];

        if (($context['resident_count'] ?? 0) > 0) {
            $list[] = $this->t(
                'Mau difilter berdasarkan blok atau status kepengurusan?',
                'Need me to filter them by block or committee role?',
                $language
            );
        }

        if ($state === 'empty') {
            $list[] = $this->t(
                'Boleh cek lagi ejaannya atau kasih nama panggilan yang biasa dipakai?',
                'Could you double-check the spelling or share their nickname?',
                $language
            );
        }

        if (($context['state'] ?? '') === 'slot_missing') {
            $list[] = $this->t(
                'Pilih dulu jenis data warga yang kamu butuhkan ya.',
                'Pick the type of resident data you need first.',
                $language
            );
        }

        return $list;
    }

    private function newResidentsSuggestions(string $language, string $state, array $context): array
    {
        $list = [];

        if (($context['new_resident_count'] ?? 0) > 0) {
            $list[] = $this->t(
                'Perlu aku bantu kirim pesan sambutan ke mereka?',
                'Want me to help send a welcome note to them?',
                $language
            );
            $list[] = $this->t(
                'Mau dijadwalkan sesi kenalan bareng pengurus?',
                'Need me to schedule an introduction session with the committee?',
                $language
            );
        }

        if ($state === 'empty') {
            $list[] = $this->t(
                'Mau kucek rentang tanggal lain barangkali ada yang baru masuk?',
                'Should I check another date range in case someone new joined?',
                $language
            );
        }

        return $list;
    }

    private function knowledgeSuggestions(string $language, string $state, array $context): array
    {
        $list = [];

        if (!empty($context['kb_sources'])) {
            $list[] = $this->t(
                'Perlu aku rangkum dokumen lainnya dari sumber yang sama?',
                'Want me to summarize another document from the same sources?',
                $language
            );
        }

        if ($state === 'low_confidence') {
            $list[] = $this->t(
                'Mau aku cari referensi tambahan supaya jawabannya lebih yakin?',
                'Should I look for an extra reference to boost confidence?',
                $language
            );
        }

        return $list;
    }

    /**
     * @return array<int,string>
     */
    private function fallbackFor(string $intent, string $language): array
    {
        $entries = $this->fallbacks[$intent] ?? $this->fallbacks['general'];

        return array_map(function (array $entry) use ($language) {
            return $this->t($entry['id'] ?? '', $entry['en'] ?? '', $language);
        }, $entries);
    }

    private function t(string $idText, string $enText, string $language): string
    {
        $text = $language === 'en' ? ($enText ?: $idText) : ($idText ?: $enText);

        return trim($text);
    }

    /**
     * @param  array<int,string>  $items
     * @return array<int,string>
     */
    private function uniqueList(array $items): array
    {
        $clean = array_values(array_filter(array_map('trim', $items)));

        return array_values(array_unique($clean));
    }
}

