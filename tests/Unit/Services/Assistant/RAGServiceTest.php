<?php

namespace Tests\Unit\Services\Assistant;

use App\Services\Assistant\RAGService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RAGServiceTest extends TestCase
{
    use RefreshDatabase;

    private RAGService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->service = app(RAGService::class);
    }

    public function test_search_returns_answer_for_known_query(): void
    {
        $result = $this->service->search('cara mengurus surat pindah');
        
        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['answer']);
        $this->assertGreaterThan(0, $result['confidence']);
    }

    public function test_search_returns_fallback_for_unknown_query(): void
    {
        $result = $this->service->search('zzzxxyy qqqq');
        
        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['confidence']);
    }

    public function test_search_uses_cache(): void
    {
        $query = 'cara mengurus surat pengantar';
        
        $result1 = $this->service->search($query);
        $result2 = $this->service->search($query);

        $this->assertEquals($result1, $result2);
    }
}
