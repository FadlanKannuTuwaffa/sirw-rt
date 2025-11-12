<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Event;
use App\Models\Payment;
use App\Models\User;
use App\Services\Assistant\DummyClient;
use App\Services\Assistant\Exceptions\OutOfContextException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class DummyClientAdvancedTest extends TestCase
{
    use RefreshDatabase;

    private User $testUser;
    private DummyClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        DummyClient::resetConversationState();

        $this->testUser = User::factory()->create([
            'role' => 'warga',
            'name' => 'Budi Santoso',
        ]);

        Auth::login($this->testUser);
        $this->client = app(DummyClient::class);
    }

    // === SAPAAN & PERKENALAN ===
    
    public function test_greeting_variations()
    {
        $greetings = ['halo', 'hai', 'hi', 'hey'];
        
        foreach ($greetings as $greeting) {
            $response = $this->client->chat([['role' => 'user', 'content' => $greeting]]);
            $this->assertStringContainsStringIgnoringCase('hai', $response['content']);
        }
    }

    public function test_who_are_you()
    {
        $response = $this->client->chat([['role' => 'user', 'content' => 'siapa kamu']]);
        $content = strtolower($response['content']);
        $this->assertStringContainsString('aetheria', $content);
        $this->assertTrue(
            str_contains($content, 'asisten') || str_contains($content, 'assistant'),
            'Introduction should mention the assistant role.'
        );
    }

    public function test_what_can_you_do()
    {
        $response = $this->client->chat([['role' => 'user', 'content' => 'pertanyaan apa yang bisa kamu jawab?']]);
        $this->assertStringContainsString('tagihan', strtolower($response['content']));
        $this->assertStringContainsString('agenda', strtolower($response['content']));
    }

    public function test_thank_you()
    {
        $response = $this->client->chat([['role' => 'user', 'content' => 'terima kasih']]);
        $this->assertStringContainsString('sama-sama', strtolower($response['content']));
    }

    // === TAGIHAN & PEMBAYARAN ===

    public function test_unpaid_bills_variations()
    {
        Bill::factory()->create([
            'user_id' => $this->testUser->id,
            'title' => 'Iuran Sampah',
            'amount' => 50000,
            'status' => 'unpaid',
        ]);

        $queries = [
            'tagihan apa yang belum aku bayar?',
            'aku ada tagihan apa?',
            'tagihan berapa?',
            'iuran apa yang belum dibayar?',
        ];

        foreach ($queries as $query) {
            $response = $this->client->chat([['role' => 'user', 'content' => $query]]);
            $this->assertStringContainsString('Iuran Sampah', $response['content']);
            $this->assertStringContainsString('50.000', $response['content']);
        }
    }

    public function test_paid_bills_variations()
    {
        $bill = Bill::factory()->create([
            'user_id' => $this->testUser->id,
            'title' => 'Iuran Keamanan',
            'amount' => 100000,
            'status' => 'paid',
        ]);

        Payment::create([
            'bill_id' => $bill->id,
            'user_id' => $this->testUser->id,
            'amount' => 100000,
            'status' => 'paid',
            'paid_at' => now(),
            'payment_method' => 'transfer',
            'gateway' => 'manual',
        ]);

        $queries = [
            'tagihan apa yang sudah aku bayar?',
            'riwayat pembayaran',
            'aku sudah bayar apa?',
        ];

        foreach ($queries as $query) {
            $response = $this->client->chat([['role' => 'user', 'content' => $query]]);
            $this->assertStringContainsString('Iuran Keamanan', $response['content']);
            $this->assertStringContainsString('100.000', $response['content']);
        }
    }

    public function test_how_to_pay()
    {
        $response = $this->client->chat([['role' => 'user', 'content' => 'cara bayar tagihan gimana?']]);
        $this->assertStringContainsString('transfer', strtolower($response['content']));
    }

    // === AGENDA & KEGIATAN ===

    public function test_agenda_variations()
    {
        Event::create([
            'title' => 'Rapat RT',
            'start_at' => now()->addDays(3),
            'end_at' => now()->addDays(3)->addHours(2),
            'location' => 'Balai RT',
            'is_public' => true,
            'created_by' => $this->testUser->id,
        ]);

        $queries = [
            'agenda minggu ini',
            'acara apa minggu ini?',
            'kegiatan apa bulan ini?',
            'ada rapat kapan?',
        ];

        foreach ($queries as $query) {
            $response = $this->client->chat([['role' => 'user', 'content' => $query]]);
            $this->assertStringContainsString('Rapat RT', $response['content']);
        }
    }

    // === WARGA & DIREKTORI ===

    public function test_total_residents()
    {
        User::factory()->count(5)->create(['role' => 'warga']);

        $queries = [
            'berapa total warga?',
            'jumlah warga berapa?',
            'ada berapa warga di rt ini?',
        ];

        foreach ($queries as $query) {
            $response = $this->client->chat([['role' => 'user', 'content' => $query]]);
            $this->assertStringContainsString('6 warga', $response['content']); // 5 + test user
        }
    }

    public function test_search_resident()
    {
        User::factory()->create([
            'role' => 'warga',
            'name' => 'Ahmad Yani',
        ]);

        $response = $this->client->chat([['role' => 'user', 'content' => 'cari warga bernama Ahmad']]);
        $this->assertStringContainsString('Ahmad', $response['content']);
    }

    // === SURAT & ADMINISTRASI ===

    public function test_letter_inquiry()
    {
        $queries = [
            'cara buat surat pengantar',
            'syarat surat domisili',
            'gimana urus surat keterangan?',
        ];

        foreach ($queries as $query) {
            try {
                $response = $this->client->chat([['role' => 'user', 'content' => $query]]);
                $this->assertTrue(
                    str_contains(strtolower($response['content']), 'ktp') ||
                    str_contains(strtolower($response['content']), 'surat'),
                    'Letter inquiry should mention surat requirements.'
                );
            } catch (OutOfContextException $exception) {
                $this->assertStringContainsString('diluar konteks', strtolower($exception->getMessage()));
            }
        }
    }

    // === FASILITAS & LAYANAN ===

    public function test_garbage_schedule()
    {
        $response = $this->client->chat([['role' => 'user', 'content' => 'jadwal sampah kapan?']]);
        $content = strtolower($response['content']);
        $this->assertTrue(
            str_contains($content, 'jadwal') || str_contains($content, 'agenda'),
            'Garbage schedule should mention jadwal or agenda availability.'
        );
    }

    public function test_security_schedule()
    {
        $response = $this->client->chat([['role' => 'user', 'content' => 'jadwal ronda kapan?']]);
        $content = strtolower($response['content']);
        $this->assertTrue(
            str_contains($content, 'jadwal') || str_contains($content, 'agenda'),
            'Response should mention jadwal or agenda availability.'
        );
    }

    // === PERTANYAAN UNIK & EDGE CASES ===

    public function test_do_you_know_me()
    {
        try {
            $response = $this->client->chat([['role' => 'user', 'content' => 'apa kamu kenal aku?']]);
            $this->assertNotEmpty($response['content']);
        } catch (OutOfContextException $exception) {
            $this->assertStringContainsString('diluar konteks', strtolower($exception->getMessage()));
        }
    }

    public function test_personal_question()
    {
        try {
            $response = $this->client->chat([['role' => 'user', 'content' => 'kamu tinggal dimana?']]);
            $this->assertNotEmpty($response['content']);
        } catch (OutOfContextException $exception) {
            $this->assertStringContainsString('diluar konteks', strtolower($exception->getMessage()));
        }
    }

    public function test_weather_question()
    {
        try {
            $response = $this->client->chat([['role' => 'user', 'content' => 'cuaca hari ini gimana?']]);
            $content = strtolower($response['content']);
            $this->assertTrue(
                str_contains($content, 'belum bisa') ||
                str_contains($content, 'sumber:') ||
                str_contains($content, 'hai! ada yang bisa kubantu'),
                'Weather fallback should signal limitation or provide knowledge answer.'
            );
        } catch (OutOfContextException $exception) {
            $this->assertStringContainsString('diluar konteks', strtolower($exception->getMessage()));
        }
    }

    public function test_joke_request()
    {
        $response = $this->client->chat([['role' => 'user', 'content' => 'ceritain lelucon dong']]);
        $this->assertNotEmpty($response['content']);
    }

    public function test_complaint()
    {
        $response = $this->client->chat([['role' => 'user', 'content' => 'mau lapor ada jalan rusak']]);
        $content = strtolower($response['content']);
        $this->assertTrue(
            str_contains($content, 'keluhan') || str_contains($content, 'kontak'),
            'Complaint response should guide user toward kontak pengurus.'
        );
    }

    public function test_facility_rental()
    {
        $response = $this->client->chat([['role' => 'user', 'content' => 'cara sewa balai RT']]);
        $this->assertStringContainsString('balai', strtolower($response['content']));
    }

    public function test_registration()
    {
        $response = $this->client->chat([['role' => 'user', 'content' => 'cara daftar warga baru']]);
        $this->assertStringContainsString('ktp', strtolower($response['content']));
    }

    public function test_financial_report()
    {
        $response = $this->client->chat([['role' => 'user', 'content' => 'laporan keuangan RT']]);
        $content = strtolower($response['content']);
        $this->assertTrue(
            str_contains($content, 'laporan') || str_contains($content, 'rekap'),
            'Financial report response should mention laporan or rekap.'
        );
    }

    // === KONTEKS & FOLLOW-UP ===

    public function test_context_awareness()
    {
        // Pertanyaan pertama
        $response1 = $this->client->chat([
            ['role' => 'user', 'content' => 'berapa total warga?']
        ]);
        $this->assertStringContainsString('warga', strtolower($response1['content']));

        // Follow-up (masih tentang warga)
        $response2 = $this->client->chat([
            ['role' => 'user', 'content' => 'berapa total warga?'],
            ['role' => 'assistant', 'content' => $response1['content']],
            ['role' => 'user', 'content' => 'dimana aku bisa lihat daftarnya?']
        ]);
        $this->assertNotEmpty($response2['content']);
    }

    // === VARIASI BAHASA ===

    public function test_informal_language()
    {
        $queries = [
            'tagihan gw berapa sih?',
            'ada acara apa aja nih?',
            'gw mau bayar gimana caranya?',
        ];

        foreach ($queries as $query) {
            $response = $this->client->chat([['role' => 'user', 'content' => $query]]);
            $this->assertNotEmpty($response['content']);
        }
    }

    public function test_typos_tolerance()
    {
        $queries = [
            'tgihan apa?', // typo: tagihan
            'ageda minggu ini', // typo: agenda
            'brapa warga?', // typo: berapa
        ];

        foreach ($queries as $query) {
            $response = $this->client->chat([['role' => 'user', 'content' => $query]]);
            $this->assertNotEmpty($response['content']);
        }
    }

    // === STRESS TEST ===

    public function test_empty_message()
    {
        $response = $this->client->chat([['role' => 'user', 'content' => '']]);
        $this->assertNotEmpty($response['content']);
    }

    public function test_very_long_message()
    {
        $longMessage = str_repeat('tagihan ', 100);
        $response = $this->client->chat([['role' => 'user', 'content' => $longMessage]]);
        $this->assertNotEmpty($response['content']);
    }

    public function test_special_characters()
    {
        $response = $this->client->chat([['role' => 'user', 'content' => 'tagihan??? !!!']]);
        $this->assertNotEmpty($response['content']);
    }
}
