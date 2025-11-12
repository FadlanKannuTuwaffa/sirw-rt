<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Event;
use App\Models\Payment;
use App\Models\RTOfficial;
use App\Models\User;
use App\Services\Assistant\AssistantIntentHandler;
use App\Services\Assistant\GeminiClient;
use App\Services\Assistant\GroqClient;
use App\Services\Assistant\HuggingFaceClient;
use App\Services\Assistant\OpenRouterClient;
use App\Services\Assistant\ToolRouter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssistantIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $testUser;
    private ToolRouter $router;
    private AssistantIntentHandler $intentHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testUser = User::factory()->create([
            'role' => 'warga',
            'name' => 'Test Warga',
            'email' => 'test@warga.com',
            'status' => 'aktif',
        ]);

        $this->router = new ToolRouter();
        $this->intentHandler = new AssistantIntentHandler();
    }

    #[Test]
    public function test_intent_handler_responds_to_billing_query()
    {
        Bill::factory()->create([
            'user_id' => $this->testUser->id,
            'title' => 'Iuran Sampah Januari 2025',
            'amount' => 50000,
            'status' => 'unpaid',
            'due_date' => now()->addDays(5),
        ]);

        $response = $this->intentHandler->handle(
            'Tagihanku bulan ini berapa?',
            $this->router,
            $this->testUser->id
        );

        $this->assertNotNull($response);
        $this->assertArrayHasKey('content', $response);
        $this->assertStringContainsString('Iuran Sampah', $response['content']);
        $this->assertStringContainsString('50.000', $response['content']);
    }

    #[Test]
    public function test_intent_handler_responds_to_no_bills()
    {
        $response = $this->intentHandler->handle(
            'Tagihanku bulan ini berapa?',
            $this->router,
            $this->testUser->id
        );

        $this->assertNotNull($response);
        $this->assertStringContainsString('Tidak ada tagihan', $response['content']);
    }

    #[Test]
    public function test_intent_handler_responds_to_agenda_query()
    {
        Event::create([
            'title' => 'Rapat RT',
            'start_at' => now()->addDays(3),
            'end_at' => now()->addDays(3)->addHours(2),
            'location' => 'Balai RT',
            'is_public' => true,
            'created_by' => $this->testUser->id,
        ]);

        $response = $this->intentHandler->handle(
            'Apa agenda minggu ini?',
            $this->router,
            $this->testUser->id
        );

        $this->assertNotNull($response);
        $this->assertStringContainsString('Rapat RT', $response['content']);
        $this->assertStringContainsString('Balai RT', $response['content']);
    }

    #[Test]
    public function test_intent_handler_responds_to_payment_history()
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

        // Test dengan tool router langsung
        $toolResult = $this->router->execute('get_payments_this_month', [
            'resident_id' => $this->testUser->id,
        ]);

        $this->assertTrue($toolResult['success']);
        $this->assertEquals(1, $toolResult['count']);
        $this->assertEquals(100000, $toolResult['total']);
        $this->assertEquals('Iuran Keamanan', $toolResult['items'][0]['title']);
    }

    #[Test]
    public function test_intent_handler_responds_to_total_residents()
    {
        User::factory()->count(5)->create(['role' => 'warga', 'status' => 'aktif']);

        $response = $this->intentHandler->handle(
            'Berapa total warga?',
            $this->router,
            $this->testUser->id
        );

        $this->assertNotNull($response);
        $this->assertStringContainsString('6 warga', $response['content']); // 5 + test user
    }

    #[Test]
    public function test_intent_handler_responds_to_rt_contacts()
    {
        RTOfficial::create([
            'name' => 'Pak RT',
            'position' => 'ketua',
            'phone' => '081234567890',
            'email' => 'ketua@rt.com',
            'is_active' => true,
            'order' => 1,
        ]);

        $response = $this->intentHandler->handle(
            'Kontak ketua RT',
            $this->router,
            $this->testUser->id
        );

        $this->assertNotNull($response);
        $this->assertStringContainsString('Pak RT', $response['content']);
        $this->assertStringContainsString('081234567890', $response['content']);
    }

    #[Test]
    public function test_tool_router_get_outstanding_bills()
    {
        Bill::factory()->create([
            'user_id' => $this->testUser->id,
            'title' => 'Iuran Sampah',
            'amount' => 50000,
            'status' => 'unpaid',
            'due_date' => now()->addDays(5),
        ]);

        $result = $this->router->execute('get_outstanding_bills', [
            'resident_id' => $this->testUser->id,
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['count']);
        $this->assertEquals(50000, $result['total']);
        $this->assertCount(1, $result['items']);
    }

    #[Test]
    public function test_tool_router_get_agenda()
    {
        Event::create([
            'title' => 'Kerja Bakti',
            'start_at' => now()->addDays(2),
            'end_at' => now()->addDays(2)->addHours(3),
            'location' => 'Lingkungan RT',
            'is_public' => true,
            'created_by' => $this->testUser->id,
        ]);

        $result = $this->router->execute('get_agenda', [
            'resident_id' => $this->testUser->id,
            'range' => 'week',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['count']);
        $this->assertEquals('Kerja Bakti', $result['items'][0]['title']);
    }

    #[Test]
    public function test_tool_router_search_directory()
    {
        User::factory()->create([
            'role' => 'warga',
            'name' => 'Budi Santoso',
            'status' => 'aktif',
        ]);

        $result = $this->router->execute('search_directory', [
            'resident_id' => $this->testUser->id,
            'query' => 'Budi',
            'status' => 'all',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['count']);
        $this->assertEquals('Budi Santoso', $result['items'][0]['name']);
    }

    #[Test]
    public function test_tool_router_get_rt_contacts()
    {
        RTOfficial::create([
            'name' => 'Pak Ketua',
            'position' => 'ketua',
            'phone' => '081234567890',
            'email' => 'ketua@rt.com',
            'is_active' => true,
            'order' => 1,
        ]);

        $result = $this->router->execute('get_rt_contacts', [
            'position' => 'ketua',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('Pak Ketua', $result['contact']['name']);
        $this->assertEquals('081234567890', $result['contact']['phone']);
    }

    #[Test]
    public function test_groq_client_basic_chat()
    {
        config(['services.groq.api_key' => 'gsk_test_key']);

        Http::fake([
            'https://api.groq.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Halo dari Groq']],
                ],
            ]),
        ]);

        $client = new GroqClient();
        $response = $client->chat([
            ['role' => 'user', 'content' => 'Jawab dengan satu kata: Halo'],
        ]);

        $this->assertSame('Halo dari Groq', $response['content']);
        $this->assertSame('Groq', $response['provider']);
        Http::assertSentCount(1);
    }

    #[Test]
    public function test_gemini_client_basic_chat()
    {
        config(['services.gemini.api_key' => 'gemini_test_key']);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'Halo dari Gemini']]],
                ]],
            ]),
        ]);

        $client = new GeminiClient();
        $response = $client->chat([
            ['role' => 'user', 'content' => 'Jawab dengan satu kata: Halo'],
        ]);

        $this->assertSame('Halo dari Gemini', $response['content']);
        $this->assertSame('Gemini', $response['provider']);
    }

    #[Test]
    public function test_huggingface_client_basic_chat()
    {
        config([
            'services.huggingface.api_key' => 'hf_test_key',
            'services.huggingface.endpoints' => ['https://router.huggingface.co/v1'],
        ]);

        Http::fake([
            'https://router.huggingface.co/*' => Http::response([
                'choices' => [[
                    'message' => ['content' => 'Halo dari HF'],
                ]],
            ]),
        ]);

        $client = new HuggingFaceClient();
        $response = $client->chat([
            ['role' => 'user', 'content' => 'Jawab dengan satu kata: Halo'],
        ]);

        $this->assertSame('Halo dari HF', $response['content']);
        $this->assertSame('HuggingFace', $response['provider']);
    }

    #[Test]
    public function test_openrouter_client_basic_chat()
    {
        config([
            'services.openrouter.api_key' => 'sk-or-test',
            'app.url' => 'https://example.test',
            'app.name' => 'Test App',
        ]);

        Http::fake([
            'https://openrouter.ai/*' => Http::response([
                'choices' => [[
                    'message' => ['content' => 'Halo dari OpenRouter'],
                ]],
            ]),
        ]);

        $client = new OpenRouterClient();
        $response = $client->chat([
            ['role' => 'user', 'content' => 'Jawab dengan satu kata: Halo'],
        ]);

        $this->assertSame('Halo dari OpenRouter', $response['content']);
        $this->assertSame('OpenRouter', $response['provider']);
    }

    #[Test]
    public function test_assistant_endpoint_requires_authentication()
    {
        $response = $this->postJson('/api/assistant/chat', [
            'message' => 'Halo',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function test_assistant_endpoint_validates_message()
    {
        $this->actingAs($this->testUser);

        $response = $this->postJson('/api/assistant/chat', [
            'message' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['message']);
    }

    #[Test]
    public function test_assistant_endpoint_responds_to_small_talk()
    {
        $this->actingAs($this->testUser);

        $response = $this->post('/api/assistant/chat', [
            'message' => 'Halo',
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/event-stream; charset=UTF-8');
    }

    #[Test]
    public function test_client_responds_correctly_to_billing_context()
    {
        config(['services.groq.api_key' => 'gsk_test_key']);

        Bill::factory()->create([
            'user_id' => $this->testUser->id,
            'title' => 'Iuran Sampah Januari',
            'amount' => 50000,
            'status' => 'unpaid',
            'due_date' => now()->addDays(5),
        ]);

        Http::fake([
            'https://api.groq.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'tool_calls' => [[
                            'type' => 'function',
                            'function' => ['name' => 'get_outstanding_bills'],
                        ]],
                    ],
                ]],
            ]),
        ]);

        $client = new GroqClient();
        $tools = $this->router->getToolDefinitions();

        $messages = [
            ['role' => 'system', 'content' => 'Kamu adalah asisten RT yang membantu warga. Gunakan tool yang tersedia untuk menjawab pertanyaan.'],
            ['role' => 'user', 'content' => 'Tagihanku bulan ini berapa?'],
        ];

        $response = $client->chat($messages, $tools);

        $this->assertArrayHasKey('tool_calls', $response);
        $toolNames = array_column(
            array_column($response['tool_calls'], 'function'),
            'name'
        );
        $this->assertContains('get_outstanding_bills', $toolNames);
    }

    #[Test]
    public function test_client_responds_correctly_to_agenda_context()
    {
        config(['services.gemini.api_key' => 'gemini_test_key']);

        Event::create([
            'title' => 'Rapat RT',
            'start_at' => now()->addDays(3),
            'end_at' => now()->addDays(3)->addHours(2),
            'location' => 'Balai RT',
            'is_public' => true,
            'created_by' => $this->testUser->id,
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'functionCall' => [
                                'name' => 'get_agenda',
                                'args' => ['range' => 'week'],
                            ],
                        ]],
                    ],
                ]],
            ]),
        ]);

        $client = new GeminiClient();
        $tools = $this->router->getToolDefinitions();

        $messages = [
            ['role' => 'system', 'content' => 'Kamu adalah asisten RT yang membantu warga. Gunakan tool yang tersedia untuk menjawab pertanyaan.'],
            ['role' => 'user', 'content' => 'Apa agenda minggu ini?']
        ];

        $response = $client->chat($messages, $tools);

        $this->assertArrayHasKey('tool_calls', $response);
        $toolNames = array_column(
            array_column($response['tool_calls'], 'function'),
            'name'
        );
        $this->assertContains('get_agenda', $toolNames);
    }

    #[Test]
    public function test_multiple_clients_consistency()
    {
        config([
            'services.groq.api_key' => 'gsk_test_key',
            'services.gemini.api_key' => 'gemini_test_key',
            'services.openrouter.api_key' => 'sk-or-test',
            'app.url' => 'https://example.test',
            'app.name' => 'Test App',
        ]);

        Http::fake([
            'https://api.groq.com/*' => Http::response([
                'choices' => [['message' => ['content' => '1']]],
            ]),
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => '1']]],
                ]],
            ]),
            'https://openrouter.ai/*' => Http::response([
                'choices' => [['message' => ['content' => '1']]],
            ]),
        ]);

        $clients = [
            'groq' => new GroqClient(),
            'gemini' => new GeminiClient(),
            'openrouter' => new OpenRouterClient(),
        ];

        $message = [
            ['role' => 'user', 'content' => 'Sebutkan angka 1'],
        ];

        foreach ($clients as $name => $client) {
            $response = $client->chat($message);
            $this->assertSame('1', $response['content'], "Client {$name} harus mengembalikan angka 1");
        }

        Http::assertSentCount(3);
    }
}
