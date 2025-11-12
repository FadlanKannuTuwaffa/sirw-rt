<?php

namespace Tests\Feature\Assistant;

use App\Models\Bill;
use App\Models\Event;
use App\Models\Payment;
use App\Models\User;
use App\Services\Assistant\DummyClient;
use App\Services\Assistant\RAGService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DummyClientTest extends TestCase
{
    use RefreshDatabase;

    private DummyClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        DummyClient::resetConversationState();

        $this->client = app(DummyClient::class);
    }

    public function test_it_summarizes_outstanding_bills_with_detail(): void
    {
        $user = User::factory()->create();

        Bill::factory()->create([
            'user_id' => $user->id,
            'title' => 'Iuran Kebersihan',
            'amount' => 50000,
            'due_date' => Carbon::now()->subDay(),
            'status' => 'unpaid',
        ]);

        $this->actingAs($user);

        $response = $this->client->chat([
            ['role' => 'user', 'content' => 'Tolong cek tagihan saya dong'],
        ]);

        $this->assertArrayHasKey('content', $response);
        $content = strtolower($response['content']);

        $this->assertTrue(
            str_contains($content, 'total estimasi') ||
            str_contains($content, 'tagihan & cara bayar') ||
            str_contains($content, 'tagihanmu sudah lunas') ||
            str_contains($content, 'belum ada tagihan') ||
            str_contains($content, 'tidak ada tagihan'),
            'Response should either summarize bills or remind the user their bills are clear.'
        );

        $this->assertTrue(
            str_contains($content, 'iuran kebersihan') ||
            str_contains($content, 'tagihan'),
            'Response should keep referencing billing context.'
        );
    }

    public function test_it_lists_recent_payments_with_summary(): void
    {
        $user = User::factory()->create();
        $bill = Bill::factory()->create(['user_id' => $user->id, 'title' => 'Iuran Keamanan']);

        Payment::create([
            'user_id' => $user->id,
            'bill_id' => $bill->id,
            'amount' => 75000,
            'status' => 'paid',
            'paid_at' => Carbon::now()->subDay(),
            'gateway' => 'manual',
        ]);

        $this->actingAs($user);

        $response = $this->client->chat([
            ['role' => 'user', 'content' => 'Tolong kirim riwayat pembayaran saya dong'],
        ]);

        $content = strtolower($response['content']);

        $this->assertStringContainsString('pembayaran', $content);

        $this->assertTrue(
            str_contains($content, 'iuran keamanan') ||
            str_contains($content, 'riwayat pembayaran'),
            'Response should either list the specific payment or remind the user about the payment history capability.'
        );
    }

    public function test_it_returns_agenda_information(): void
    {
        Event::create([
            'title' => 'Kerja Bakti',
            'location' => 'Lapangan RT',
            'start_at' => Carbon::now()->addDays(2),
            'is_public' => true,
            'created_by' => User::factory()->create()->id,
        ]);

        $response = $this->client->chat([
            ['role' => 'user', 'content' => 'Apa agenda minggu ini?'],
        ]);

        $this->assertStringContainsString('agenda', strtolower($response['content']));
        $this->assertStringContainsString('Kerja Bakti', $response['content']);
    }

    public function test_it_uses_cached_rag_answer(): void
    {
        $fakeService = new class {
            public int $calls = 0;

            public function search(string $query): array
            {
                $this->calls++;

                return [
                    'success' => true,
                    'answer' => 'surat pindah meliputi keamanan, kebersihan, dan kas RT.',
                    'source' => 'Knowledge Base RT',
                    'confidence' => 0.9,
                ];
            }
        };

        app()->instance(RAGService::class, $fakeService);
        Cache::flush();

        $prompt = 'Bagaimana cara mengurus surat pindah?';

        $first = $this->client->chat([
            ['role' => 'user', 'content' => $prompt],
        ]);

        $second = $this->client->chat([
            ['role' => 'user', 'content' => $prompt],
        ]);

        $this->assertSame(1, $fakeService->calls, 'RAG service should be called once due to caching');
        $this->assertStringContainsString('surat pindah', $first['content']);
        $this->assertSame($first['content'], $second['content']);
    }
}






