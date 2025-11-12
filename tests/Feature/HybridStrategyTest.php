<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HybridStrategyTest extends TestCase
{
    use RefreshDatabase;

    private User $testUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testUser = User::factory()->create([
            'role' => 'warga',
            'name' => 'Test Warga',
        ]);
    }

    public function test_simple_billing_query_returns_response()
    {
        $this->actingAs($this->testUser);

        Bill::factory()->create([
            'user_id' => $this->testUser->id,
            'title' => 'Iuran Sampah',
            'amount' => 50000,
            'status' => 'unpaid',
        ]);

        $response = $this->post('/api/assistant/chat', [
            'message' => 'Tagihanku berapa?'
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/event-stream; charset=UTF-8');
    }

    public function test_complex_query_returns_response()
    {
        $this->actingAs($this->testUser);

        $response = $this->post('/api/assistant/chat', [
            'message' => 'Jelaskan perbedaan antara iuran sampah dan iuran keamanan'
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/event-stream; charset=UTF-8');
    }

    public function test_agenda_query_returns_response()
    {
        $this->actingAs($this->testUser);

        Event::create([
            'title' => 'Rapat RT',
            'start_at' => now()->addDays(3),
            'end_at' => now()->addDays(3)->addHours(2),
            'location' => 'Balai RT',
            'is_public' => true,
            'created_by' => $this->testUser->id,
        ]);

        $response = $this->post('/api/assistant/chat', [
            'message' => 'Agenda minggu ini'
        ]);

        $response->assertStatus(200);
    }

    public function test_short_query_returns_response()
    {
        $this->actingAs($this->testUser);

        $response = $this->post('/api/assistant/chat', [
            'message' => 'Bantuan'
        ]);

        $response->assertStatus(200);
    }

    public function test_why_question_returns_response()
    {
        $this->actingAs($this->testUser);

        $response = $this->post('/api/assistant/chat', [
            'message' => 'Kenapa iuran sampah harus dibayar setiap bulan?'
        ]);

        $response->assertStatus(200);
    }
    
    public function test_hybrid_strategy_handles_various_queries()
    {
        $this->actingAs($this->testUser);
        
        // Test berbagai jenis query
        $queries = [
            'Tagihanku berapa?',
            'Agenda bulan ini',
            'Berapa total warga?',
            'Kontak ketua RT',
            'Bantuan',
        ];
        
        foreach ($queries as $query) {
            $response = $this->post('/api/assistant/chat', [
                'message' => $query
            ]);
            
            $response->assertStatus(200);
        }
    }
}
