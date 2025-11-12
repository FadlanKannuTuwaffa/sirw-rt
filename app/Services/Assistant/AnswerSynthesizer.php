<?php

namespace App\Services\Assistant;

use Illuminate\Support\Str;

class AnswerSynthesizer
{
    public static function basic(string $userText): string
    {
        $clean = Str::of($userText)->squish()->limit(120, '...')->value();

        return "Aku bisa bantu dengan rangkuman umum meski model utama lagi penuh. "
            . "Jadi sementara aku sarankan langkah praktisnya begini:\n\n"
            . "- Klarifikasi dulu detail pertanyaannya (contoh: periode/tagihan spesifik).\n"
            . "- Cocokkan dengan data terbaru di menu Tagihan, Agenda, atau Direktori.\n"
            . "- Kalau butuh bantuan pengurus, kabari lewat kontak resmi RT.\n\n"
            . "Kalau ada detail tambahan tentang \"{$clean}\", kasih tahu ya biar bisa kucek ulang saat provider utama kembali normal.";
    }
}
