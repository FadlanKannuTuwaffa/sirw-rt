<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Arr;

/**
 * Service untuk mengelola bahasa antarmuka resident
 * Menyediakan terjemahan dinamis berdasarkan preferensi pengguna
 */
final class ResidentLanguageService
{
    /**
     * Mendapatkan bahasa preferensi pengguna
     */
    public static function getPreferredLanguage(?User $user = null): string
    {
        $user ??= auth()->user();
        $preferences = $user?->experience_preferences ?? [];

        return Arr::get($preferences, 'language', 'id');
    }

    /**
     * Terjemahkan teks berdasarkan bahasa preferensi
     */
    public static function translate(string $indonesian, ?string $english = null, ?User $user = null): string
    {
        $language = self::getPreferredLanguage($user);

        return $language === 'en' ? ($english ?? $indonesian) : $indonesian;
    }

    /**
     * Terjemahkan dengan placeholder replacement
     */
    public static function translateWithReplacements(
        string $indonesian,
        ?string $english = null,
        array $replacements = [],
        ?User $user = null
    ): string {
        $text = self::translate($indonesian, $english, $user);

        foreach ($replacements as $key => $value) {
            $text = str_replace(':' . $key, (string) $value, $text);
        }

        return $text;
    }

    /**
     * Mendapatkan dictionary terjemahan untuk komponen
     */
    public static function getDictionary(string $component, ?User $user = null): array
    {
        $language = self::getPreferredLanguage($user);

        return match ($component) {
            'dashboard' => self::dashboardDictionary($language),
            'bills' => self::billsDictionary($language),
            'payments' => self::paymentsDictionary($language),
            'directory' => self::directoryDictionary($language),
            'reports' => self::reportsDictionary($language),
            'profile' => self::profileDictionary($language),
            'assistant' => self::assistantDictionary($language),
            'search' => self::searchDictionary($language),
            default => [],
        };
    }

    private static function dashboardDictionary(string $language): array
    {
        return [
            'id' => [
                'stat_synced' => 'Diperbarui',
                'stat_labels' => [
                    'outstanding' => 'Tagihan aktif',
                    'paid_month' => 'Pembayaran bulan ini',
                    'total_bills' => 'Total tagihan',
                    'paid_bills' => 'Tagihan lunas',
                ],
                'stat_captions' => [
                    'outstanding' => 'Nominal menunggu penyelesaian',
                    'paid_month' => 'Total masuk per bulan berjalan',
                    'total_bills' => 'Unit tagihan yang diterbitkan',
                    'paid_bills' => 'Jumlah tagihan terselesaikan',
                ],
                'insights_title' => 'Insight Keuangan',
                'insights_subtitle' => 'Prediksi dan rekomendasi otomatis dari pola pembayaran Anda.',
                'insights_badge' => 'Real-time',
                'slider_summary' => 'Ringkasan konten slider',
                'slider_select_prefix' => 'Pilih slide',
                'slider_pause' => 'Jeda',
                'slider_play' => 'Putar',
                'outstanding_title' => 'Tagihan belum dibayar',
                'outstanding_subtitle' => 'Urut berdasarkan prioritas dan jatuh tempo terdekat.',
                'manage_bills' => 'Kelola tagihan',
                'all_paid' => 'Semua tagihan sudah lunas.',
                'recent_title' => 'Pembayaran terbaru',
                'recent_subtitle' => 'Konfirmasi terakhir yang diterima dari warga.',
                'history' => 'Riwayat lengkap',
                'no_payments' => 'Belum ada pembayaran.',
                'paid_badge' => 'Lunas',
                'bill_label' => 'Tagihan',
                'admin_fee_label' => 'Biaya admin',
                'agenda_title' => 'Agenda Mendatang',
                'agenda_subtitle' => 'Klik salah satu agenda untuk melihat detail lengkap.',
                'agenda_button' => 'Lihat detail',
                'agenda_empty' => 'Belum ada agenda terdekat.',
                'modal_badge' => 'Detail Agenda',
                'modal_status_heading' => 'Status kehadiran',
                'modal_reminders_heading' => 'Pengingat cepat',
                'modal_close' => 'Selesai',
                'agenda_modal_select_prompt' => 'Pilih agenda untuk melihat detailnya.',
                'download_ics' => 'Unduh ICS',
                'share_whatsapp' => 'WhatsApp',
                'share_telegram' => 'Telegram',
                'share_heading' => 'Bagikan',
                'share_title_prefix' => 'Agenda',
                'share_time_prefix' => 'Waktu',
                'share_location_prefix' => 'Lokasi',
                'rsvp_reset' => 'Reset',
                'rsvp_labels' => [
                    'going' => 'Hadir',
                    'maybe' => 'Dipertimbangkan',
                    'declined' => 'Tidak hadir',
                    'pending' => 'Belum konfirmasi',
                ],
                'rsvp_buttons' => [
                    'going' => 'Hadir',
                    'maybe' => 'Pertimbangkan',
                    'declined' => 'Tidak hadir',
                ],
                'agenda_time_label' => 'Waktu',
                'agenda_location_label' => 'Lokasi',
                'agenda_description_label' => 'Deskripsi',
                'agenda_default_location' => 'Lokasi akan diinformasikan',
                'agenda_default_description' => 'Belum ada detail tambahan.',
            ],
            'en' => [
                'stat_synced' => 'Updated',
                'stat_labels' => [
                    'outstanding' => 'Active bills',
                    'paid_month' => 'Payments this month',
                    'total_bills' => 'Bills issued',
                    'paid_bills' => 'Bills settled',
                ],
                'stat_captions' => [
                    'outstanding' => 'Amount awaiting settlement',
                    'paid_month' => 'Total collected this month',
                    'total_bills' => 'Bills published so far',
                    'paid_bills' => 'Bills marked as paid',
                ],
                'insights_title' => 'Financial insights',
                'insights_subtitle' => 'Automated predictions and tips based on your payment patterns.',
                'insights_badge' => 'Real-time',
                'slider_summary' => 'Slider content summary',
                'slider_select_prefix' => 'Select slide',
                'slider_pause' => 'Pause',
                'slider_play' => 'Play',
                'outstanding_title' => 'Outstanding bills',
                'outstanding_subtitle' => 'Sorted by priority and upcoming due dates.',
                'manage_bills' => 'Manage bills',
                'all_paid' => 'All bills are settled.',
                'recent_title' => 'Recent payments',
                'recent_subtitle' => 'Latest confirmations received from residents.',
                'history' => 'Full history',
                'no_payments' => 'No payments yet.',
                'paid_badge' => 'Paid',
                'bill_label' => 'Bill',
                'admin_fee_label' => 'Service fee',
                'agenda_title' => 'Upcoming agenda',
                'agenda_subtitle' => 'Select an agenda to view full details.',
                'agenda_button' => 'View details',
                'agenda_empty' => 'No upcoming agenda yet.',
                'modal_badge' => 'Agenda details',
                'modal_status_heading' => 'Attendance status',
                'modal_reminders_heading' => 'Quick reminders',
                'modal_close' => 'Done',
                'agenda_modal_select_prompt' => 'Select an agenda to view its details.',
                'download_ics' => 'Download ICS',
                'share_whatsapp' => 'WhatsApp',
                'share_telegram' => 'Telegram',
                'share_heading' => 'Share via',
                'share_title_prefix' => 'Event',
                'share_time_prefix' => 'Time',
                'share_location_prefix' => 'Location',
                'rsvp_reset' => 'Reset',
                'rsvp_labels' => [
                    'going' => 'Attending',
                    'maybe' => 'Considering',
                    'declined' => 'Not attending',
                    'pending' => 'Not confirmed',
                ],
                'rsvp_buttons' => [
                    'going' => 'Attend',
                    'maybe' => 'Maybe',
                    'declined' => 'Decline',
                ],
                'agenda_time_label' => 'Time',
                'agenda_location_label' => 'Location',
                'agenda_description_label' => 'Description',
                'agenda_default_location' => 'Location will be announced',
                'agenda_default_description' => 'No additional details yet.',
            ],
        ][$language] ?? [];
    }

    private static function billsDictionary(string $language): array
    {
        return [
            'id' => [
                'title' => 'Tagihan & Pembayaran',
                'subtitle' => 'Kelola kewajiban keluarga Anda',
                'outstanding_section' => 'Tagihan Aktif',
                'outstanding_subtitle' => 'Tagihan yang belum dibayar',
                'paid_section' => 'Riwayat Pembayaran',
                'paid_subtitle' => 'Pembayaran yang telah dikonfirmasi',
                'no_outstanding' => 'Tidak ada tagihan aktif',
                'no_paid' => 'Tidak ada riwayat pembayaran',
                'due_date' => 'Jatuh tempo',
                'amount' => 'Nominal',
                'status' => 'Status',
                'paid' => 'Lunas',
                'pending' => 'Menunggu',
                'pay_button' => 'Bayar sekarang',
                'view_details' => 'Lihat detail',
                'filter_label' => 'Filter',
                'sort_label' => 'Urutkan',
                'search_placeholder' => 'Cari tagihan...',
            ],
            'en' => [
                'title' => 'Bills & Payments',
                'subtitle' => 'Manage your household obligations',
                'outstanding_section' => 'Active Bills',
                'outstanding_subtitle' => 'Unpaid bills',
                'paid_section' => 'Payment History',
                'paid_subtitle' => 'Confirmed payments',
                'no_outstanding' => 'No active bills',
                'no_paid' => 'No payment history',
                'due_date' => 'Due date',
                'amount' => 'Amount',
                'status' => 'Status',
                'paid' => 'Paid',
                'pending' => 'Pending',
                'pay_button' => 'Pay now',
                'view_details' => 'View details',
                'filter_label' => 'Filter',
                'sort_label' => 'Sort',
                'search_placeholder' => 'Search bills...',
            ],
        ][$language] ?? [];
    }

    private static function paymentsDictionary(string $language): array
    {
        return [
            'id' => [
                'title' => 'Pembayaran',
                'subtitle' => 'Riwayat dan status pembayaran',
                'recent' => 'Pembayaran Terbaru',
                'history' => 'Riwayat Lengkap',
                'no_payments' => 'Belum ada pembayaran',
                'paid_at' => 'Dibayar pada',
                'amount' => 'Nominal',
                'method' => 'Metode',
                'reference' => 'Referensi',
            ],
            'en' => [
                'title' => 'Payments',
                'subtitle' => 'Payment history and status',
                'recent' => 'Recent Payments',
                'history' => 'Full History',
                'no_payments' => 'No payments yet',
                'paid_at' => 'Paid on',
                'amount' => 'Amount',
                'method' => 'Method',
                'reference' => 'Reference',
            ],
        ][$language] ?? [];
    }

    private static function directoryDictionary(string $language): array
    {
        return [
            'id' => [
                'title' => 'Data Warga',
                'subtitle' => 'Direktori tetangga',
                'search_placeholder' => 'Cari nama atau nomor rumah...',
                'no_results' => 'Tidak ada hasil pencarian',
                'name' => 'Nama',
                'address' => 'Alamat',
                'phone' => 'Telepon',
                'email' => 'Email',
                'view_profile' => 'Lihat profil',
            ],
            'en' => [
                'title' => 'Resident Directory',
                'subtitle' => 'Find your neighbours',
                'search_placeholder' => 'Search by name or house number...',
                'no_results' => 'No results found',
                'name' => 'Name',
                'address' => 'Address',
                'phone' => 'Phone',
                'email' => 'Email',
                'view_profile' => 'View profile',
            ],
        ][$language] ?? [];
    }

    private static function reportsDictionary(string $language): array
    {
        return [
            'id' => [
                'title' => 'Rekap Keuangan',
                'subtitle' => 'Transparansi kas RT',
                'income' => 'Pemasukan',
                'expense' => 'Pengeluaran',
                'balance' => 'Saldo',
                'period' => 'Periode',
                'total' => 'Total',
                'no_data' => 'Tidak ada data',
            ],
            'en' => [
                'title' => 'Financial Recap',
                'subtitle' => 'Community finance transparency',
                'income' => 'Income',
                'expense' => 'Expense',
                'balance' => 'Balance',
                'period' => 'Period',
                'total' => 'Total',
                'no_data' => 'No data available',
            ],
        ][$language] ?? [];
    }

    private static function profileDictionary(string $language): array
    {
        return [
            'id' => [
                'title' => 'Profil Saya',
                'subtitle' => 'Kelola data pribadi',
                'personal_info' => 'Informasi Pribadi',
                'contact_info' => 'Informasi Kontak',
                'preferences' => 'Preferensi',
                'security' => 'Keamanan',
                'edit' => 'Edit',
                'save' => 'Simpan',
                'cancel' => 'Batal',
                'name' => 'Nama',
                'email' => 'Email',
                'phone' => 'Telepon',
                'address' => 'Alamat',
            ],
            'en' => [
                'title' => 'My Profile',
                'subtitle' => 'Manage personal details',
                'personal_info' => 'Personal Information',
                'contact_info' => 'Contact Information',
                'preferences' => 'Preferences',
                'security' => 'Security',
                'edit' => 'Edit',
                'save' => 'Save',
                'cancel' => 'Cancel',
                'name' => 'Name',
                'email' => 'Email',
                'phone' => 'Phone',
                'address' => 'Address',
            ],
        ][$language] ?? [];
    }

    private static function assistantDictionary(string $language): array
    {
        return [
            'id' => [
                'title' => 'Asisten Warga',
                'subtitle' => 'Panduan cepat & ringkasan',
                'guides' => 'Panduan',
                'faqs' => 'Pertanyaan Umum',
                'help' => 'Bantuan',
            ],
            'en' => [
                'title' => 'Resident Assistant',
                'subtitle' => 'Guides & summaries',
                'guides' => 'Guides',
                'faqs' => 'FAQs',
                'help' => 'Help',
            ],
        ][$language] ?? [];
    }

    private static function searchDictionary(string $language): array
    {
        return [
            'id' => [
                'title' => 'Pencarian',
                'subtitle' => 'Temukan info cepat',
                'search_placeholder' => 'Cari di semua menu...',
                'no_results' => 'Tidak ada hasil',
                'recent_searches' => 'Pencarian Terbaru',
                'clear_history' => 'Hapus riwayat',
            ],
            'en' => [
                'title' => 'Search',
                'subtitle' => 'Find info quickly',
                'search_placeholder' => 'Search across all menus...',
                'no_results' => 'No results found',
                'recent_searches' => 'Recent Searches',
                'clear_history' => 'Clear history',
            ],
        ][$language] ?? [];
    }
}
