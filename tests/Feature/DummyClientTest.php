<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Event;
use App\Models\Payment;
use App\Models\User;
use App\Services\Assistant\DummyClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class DummyClientTest extends TestCase
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
            'name' => 'Test Warga',
        ]);

        Auth::login($this->testUser);
        $this->client = app(DummyClient::class);
    }

    public function test_responds_to_billing_query()
    {
        Bill::factory()->create([
            'user_id' => $this->testUser->id,
            'title' => 'Iuran Sampah',
            'amount' => 50000,
            'status' => 'unpaid',
        ]);

        $response = $this->client->chat([
            ['role' => 'user', 'content' => 'Tagihanku bulan ini berapa?']
        ]);

        $this->assertArrayHasKey('content', $response);
        $this->assertStringContainsString('Iuran Sampah', $response['content']);
        $this->assertStringContainsString('50.000', $response['content']);
    }

    public function test_responds_to_no_bills()
    {
        $response = $this->client->chat([
            ['role' => 'user', 'content' => 'Tagihanku berapa?']
        ]);

        $this->assertStringContainsString('sudah lunas', strtolower($response['content']));
    }

    public function test_responds_to_agenda_query()
    {
        Event::create([
            'title' => 'Rapat RT',
            'start_at' => now()->addDays(3),
            'end_at' => now()->addDays(3)->addHours(2),
            'location' => 'Balai RT',
            'is_public' => true,
            'created_by' => $this->testUser->id,
        ]);

        $response = $this->client->chat([
            ['role' => 'user', 'content' => 'Apa agenda minggu ini?']
        ]);

        $this->assertStringContainsString('Rapat RT', $response['content']);
        $this->assertStringContainsString('Balai RT', $response['content']);
    }

    public function test_responds_to_payment_history()
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

        $response = $this->client->chat([
            ['role' => 'user', 'content' => 'Riwayat pembayaran saya']
        ]);

        $this->assertStringContainsString('Iuran Keamanan', $response['content']);
        $this->assertStringContainsString('100.000', $response['content']);
    }

    public function test_responds_to_total_residents()
    {
        User::factory()->count(5)->create(['role' => 'warga']);

        $response = $this->client->chat([
            ['role' => 'user', 'content' => 'Berapa total warga?']
        ]);

        $this->assertStringContainsString('6 warga', $response['content']); // 5 + test user
    }

    public function test_responds_to_help_query()
    {
        $response = $this->client->chat([
            ['role' => 'user', 'content' => 'Bantuan']
        ]);

        $this->assertStringContainsString('Tagihan', $response['content']);
        $this->assertStringContainsString('Agenda', $response['content']);
    }

    public function test_responds_to_greeting()
    {
        $response = $this->client->chat([
            ['role' => 'user', 'content' => 'Halo']
        ]);

        $this->assertStringContainsString('Hai', $response['content']);
    }

    public function test_responds_to_unknown_query()
    {
        $response = $this->client->chat([
            ['role' => 'user', 'content' => 'Apa cuaca hari ini?']
        ]);

        $content = strtolower($response['content']);
        $this->assertTrue(
            str_contains($content, 'belum bisa') ||
            str_contains($content, 'sumber:') ||
            str_contains($content, 'hai! ada yang bisa kubantu'),
            'DummyClient should return either a knowledge answer or a friendly fallback.'
        );
    }

    public function test_recognition_question_in_indonesian_mentions_user_name()
    {
        $response = $this->client->chat([
            ['role' => 'user', 'content' => 'Kamu kenal aku gak?']
        ]);

        $this->assertStringContainsString('kenal', strtolower($response['content']));
        $this->assertStringContainsString('Test', $response['content']);
    }

    public function test_recognition_question_in_english_mentions_user_name()
    {
        $response = $this->client->chat([
            ['role' => 'user', 'content' => 'Do you remember me?']
        ]);

        $this->assertStringContainsString('know you', strtolower($response['content']));
        $this->assertStringContainsString('Test', $response['content']);
    }

    public function test_handles_tool_calls_parameter()
    {
        $response = $this->client->chat(
            [['role' => 'user', 'content' => 'Tagihan saya']],
            [['type' => 'function', 'function' => ['name' => 'get_outstanding_bills']]]
        );

        $this->assertArrayHasKey('content', $response);
    }
}
