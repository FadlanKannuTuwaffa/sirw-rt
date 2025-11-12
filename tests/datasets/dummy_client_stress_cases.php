<?php

/**
 * Data set untuk stress test DummyClient.
 * Digunakan oleh skrip manual di tests/run_stress_test*.php.
 */

return [
    // === KATEGORI 1: REASONING MULTI-STEP ===
    'reasoning' => [
        ['input' => 'Aku mau bayar tagihan yang paling urgent', 'expect' => 'Identifikasi tagihan terdekat jatuh tempo'],
        ['input' => 'Tagihan mana yang harus aku prioritaskan?', 'expect' => 'Urutkan berdasarkan urgency'],
        ['input' => 'Aku punya budget 500rb, tagihan mana yang bisa aku bayar?', 'expect' => 'Filter tagihan <= 500rb'],
    ],

    // === KATEGORI 2: KONTEKS IMPLISIT ===
    'implicit_context' => [
        ['input' => 'Kemarin aku sudah transfer, kok belum masuk?', 'expect' => 'Pahami transfer = pembayaran pending'],
        ['input' => 'Uang bulanan bulan ini berapa?', 'expect' => 'Pahami uang bulanan = iuran/tagihan'],
        ['input' => 'Aku lupa bayar bulan lalu', 'expect' => 'Cek tunggakan bulan lalu'],
    ],

    // === KATEGORI 3: KOMPARASI DATA ===
    'comparison' => [
        ['input' => 'Tagihan bulan ini lebih mahal dari bulan lalu?', 'expect' => 'Bandingkan total tagihan 2 bulan'],
        ['input' => 'Aku bayar lebih banyak atau lebih sedikit dari bulan kemarin?', 'expect' => 'Komparasi pembayaran'],
        ['input' => 'Siapa yang paling banyak tunggakan?', 'expect' => 'Ranking tunggakan (jika admin)'],
    ],

    // === KATEGORI 4: EKSTRAKSI ENTITAS KOMPLEKS ===
    'entity_extraction' => [
        ['input' => 'Cari warga yang tinggal di Jalan Mawar nomor 5', 'expect' => 'Parse alamat lengkap'],
        ['input' => 'Ada agenda tanggal 25 Desember?', 'expect' => 'Parse tanggal spesifik'],
        ['input' => 'Tagihan listrik bulan Januari berapa?', 'expect' => 'Parse jenis tagihan + bulan'],
    ],

    // === KATEGORI 5: HANDLE AMBIGUITAS ===
    'ambiguity' => [
        ['input' => 'Bayar', 'expect' => 'Tanya: "Bayar tagihan yang mana?"'],
        ['input' => 'Agenda', 'expect' => 'Tanya: "Agenda kapan? Hari ini/besok/minggu ini?"'],
        ['input' => 'Cari warga', 'expect' => 'Tanya: "Cari warga siapa?"'],
    ],

    // === KATEGORI 6: MULTI-INTENT ===
    'multi_intent' => [
        ['input' => 'Tagihan aku berapa dan agenda besok apa?', 'expect' => 'Jawab 2 pertanyaan sekaligus'],
        ['input' => 'Cek tunggakan aku dan kasih tau cara bayar', 'expect' => 'Tampilkan tagihan + metode bayar'],
        ['input' => 'Siapa ketua RT dan nomornya berapa?', 'expect' => 'Info ketua + kontak'],
    ],

    // === KATEGORI 7: PERSONALISASI PROAKTIF ===
    'proactive' => [
        ['input' => 'Halo', 'expect' => 'Cek tagihan jatuh tempo & beri notif proaktif'],
        ['input' => 'Ada info penting?', 'expect' => 'Rangkum: tagihan urgent + agenda terdekat'],
    ],

    // === KATEGORI 8: NEGASI & KOREKSI ===
    'negation' => [
        ['input' => 'Bukan itu, aku tanya agenda besok', 'expect' => 'Koreksi topik ke agenda besok'],
        ['input' => 'Salah, maksud aku pembayaran bukan tagihan', 'expect' => 'Switch dari bills ke payments'],
    ],

    // === KATEGORI 9: KONTEKS TEMPORAL ===
    'temporal' => [
        ['input' => 'Tagihan 3 bulan terakhir', 'expect' => 'Filter tagihan 3 bulan'],
        ['input' => 'Agenda minggu depan', 'expect' => 'Agenda next week (bukan this week)'],
        ['input' => 'Pembayaran tahun ini', 'expect' => 'Filter pembayaran year-to-date'],
    ],

    // === KATEGORI 10: SINONIM DINAMIS ===
    'dynamic_synonym' => [
        ['input' => 'Uang kas RT', 'expect' => 'Pahami = iuran/tagihan'],
        ['input' => 'Duit sampah', 'expect' => 'Pahami = tagihan sampah'],
        ['input' => 'Kumpul warga', 'expect' => 'Pahami = agenda/rapat'],
    ],
];
