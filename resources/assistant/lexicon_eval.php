<?php

return [
    [
        'input' => 'tgihan apa aja yang belum lunas?',
        'expected' => ['tagihan'],
        'unexpected' => ['tgihan'],
    ],
    [
        'input' => 'ageda minggu ini apa?',
        'expected' => ['agenda'],
        'unexpected' => ['ageda'],
    ],
    [
        'input' => 'brapa total warga aktif sekarang?',
        'expected' => ['berapa', 'warga'],
        'unexpected' => ['brapa'],
    ],
    [
        'input' => 'duit sampah bln ini udh kebayar belum?',
        'expected' => ['iuran_kebersihan'],
        'unexpected' => ['duit', 'sampah'],
    ],
    [
        'input' => 'iuran keamnan kok belum muncul ya?',
        'expected' => ['iuran_keamanan'],
        'unexpected' => ['keamnan'],
    ],
    [
        'input' => 'laporan keuangn terbaru dong',
        'expected' => ['laporan', 'keuangan'],
        'unexpected' => ['keuangn'],
    ],
];
