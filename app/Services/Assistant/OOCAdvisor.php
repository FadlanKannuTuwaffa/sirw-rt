<?php

namespace App\Services\Assistant;

class OOCAdvisor
{
    public function reply(string $text): string
    {
        return "Aku nggak punya data real-time soal kondisi lingkungan di sini, tapi kamu bisa cek aplikasi cuaca atau portal resmi setempat. "
            . "Saran umum: siapin payung kalau langit mendung, minum cukup air saat panas, dan ikuti rilis BPBD/kominfo untuk info darurat. "
            . "Kalau mau, aku bisa bantu buat checklist singkat biar kamu lebih siap.";
    }
}
