<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Insight Thresholds
    |--------------------------------------------------------------------------
    |
    | Konfigurasi dasar untuk menentukan prioritas insight CoPilot. Nilai-nilai
    | berikut dapat disesuaikan oleh pengembang guna menyesuaikan kebutuhan
    | setiap lingkungan RT.
    |
    */

    'thresholds' => [
        'outstanding_bills_high' => (int) env('COPILOT_OUTSTANDING_BILLS_HIGH', 500000),
        'cashflow_drop_percent' => (float) env('COPILOT_CASHFLOW_DROP_PERCENT', 20.0),
        'inactivity_days' => (int) env('COPILOT_INACTIVITY_DAYS', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Command Palette Integration
    |--------------------------------------------------------------------------
    |
    | Penanda untuk mengaktifkan fitur tertentu yang berhubungan dengan
    | command palette atau integrasi lainnya.
    |
    */
    'features' => [
        'command_palette' => true,
        'timeline_cache_ttl' => 3600,
    ],
];
