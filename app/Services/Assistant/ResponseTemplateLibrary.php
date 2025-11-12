<?php

namespace App\Services\Assistant;

class ResponseTemplateLibrary
{
    private array $slotIntros = [
        '*' => [
            '*' => [
                'id' => 'Biar jawabannya presisi, aku butuh sedikit detail tambahan ya.',
                'en' => 'To stay precise I just need a bit more detail from you.',
            ],
        ],
        'bills' => [
            'period' => [
                'id' => 'Biar bisa cek tagihanmu dengan tepat, sebut periode yang kamu maksud dulu ya.',
                'en' => 'To inspect the right bills, tell me which period you have in mind.',
            ],
        ],
        'payments' => [
            'period' => [
                'id' => 'Pilih periode pembayaran supaya bisa kutampilkan riwayat yang pas.',
                'en' => 'Pick the payment period so I can surface the right history.',
            ],
        ],
        'agenda' => [
            'range' => [
                'id' => 'Agenda cukup banyak, jadi pilih rentang waktu yang kamu mau ya.',
                'en' => 'There are quite a few events, so choose the time range you need.',
            ],
        ],
        'finance' => [
            'period' => [
                'id' => 'Sebut periode laporan supaya rekapnya nggak meleset.',
                'en' => 'Name the reporting period so the recap stays accurate.',
            ],
            'format' => [
                'id' => 'Pilih formatnya biar langsung siap diunduh.',
                'en' => 'Pick the format so it is ready to download.',
            ],
        ],
        'residents' => [
            'target' => [
                'id' => 'Data warga cukup luas, mau fokus ke jumlah, warga baru, atau cari nama tertentu?',
                'en' => 'Resident data is broad, should I focus on totals, new residents, or look up a specific name?',
            ],
        ],
    ];

    private array $slotAcknowledgements = [
        '*' => [
            '*' => [
                'id' => 'Siap, kutandai informasinya ya.',
                'en' => 'Noted, I have that detail captured.',
            ],
        ],
        'bills' => [
            'period' => [
                'id' => 'Siap, periode tagihannya sudah jelas. Lanjut kucek ya.',
                'en' => 'Noted, the billing period is clear. Let me check it now.',
            ],
        ],
        'payments' => [
            'period' => [
                'id' => 'Baik, periode pembayarannya sudah kutandai.',
                'en' => 'Got it, I noted the payment period.',
            ],
        ],
        'agenda' => [
            'range' => [
                'id' => 'Oke, rentang waktunya sudah siap.',
                'en' => 'Great, the time range is set.',
            ],
        ],
        'finance' => [
            'period' => [
                'id' => 'Siap, rekapnya akan fokus ke periode itu.',
                'en' => 'All set, I will focus the recap on that period.',
            ],
            'format' => [
                'id' => 'Baik, formatnya sudah kuganti sesuai pilihanmu.',
                'en' => 'All right, I will use that export format.',
            ],
        ],
        'residents' => [
            'target' => [
                'id' => 'Noted, aku tahu data warga mana yang kamu maksud.',
                'en' => 'Noted, I know which resident data to surface.',
            ],
        ],
    ];

    public function slotIntro(string $intent, string $slot, string $language = 'id'): string
    {
        $entry = $this->slotIntros[$intent][$slot]
            ?? $this->slotIntros[$intent]['*']
            ?? $this->slotIntros['*']['*']
            ?? null;

        return $this->translate($entry, $language);
    }

    public function slotAcknowledgement(string $intent, string $slot, string $language = 'id'): ?string
    {
        $entry = $this->slotAcknowledgements[$intent][$slot]
            ?? $this->slotAcknowledgements[$intent]['*']
            ?? $this->slotAcknowledgements['*']['*']
            ?? null;

        $text = $this->translate($entry, $language);

        return $text === '' ? null : $text;
    }

    private function translate(?array $entry, string $language): string
    {
        if ($entry === null) {
            return '';
        }

        $text = $language === 'en'
            ? ($entry['en'] ?? $entry['id'] ?? '')
            : ($entry['id'] ?? $entry['en'] ?? '');

        return trim((string) $text);
    }
}

