<?php

namespace Tests\Unit\Services\Assistant;

use App\Models\Bill;
use App\Models\Event;
use App\Models\RTOfficial;
use App\Models\User;
use App\Services\Assistant\ToolRouter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ToolRouterTest extends TestCase
{
    use RefreshDatabase;

    private ToolRouter $router;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->router = new ToolRouter();
        $this->user = User::factory()->create(['role' => 'warga']);
    }

    public function test_get_outstanding_bills_returns_empty_when_no_bills(): void
    {
        $result = $this->router->execute('get_outstanding_bills', ['resident_id' => $this->user->id]);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['count']);
    }

    public function test_get_outstanding_bills_returns_unpaid_bills(): void
    {
        Bill::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
            'amount' => 50000,
        ]);
        
        $result = $this->router->execute('get_outstanding_bills', ['resident_id' => $this->user->id]);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['count']);
        $this->assertEquals(50000, $result['total']);
    }

    public function test_get_rt_contacts_returns_all_officials(): void
    {
        RTOfficial::factory()->create(['position' => 'ketua', 'is_active' => true]);
        RTOfficial::factory()->create(['position' => 'sekretaris', 'is_active' => true]);
        
        $result = $this->router->execute('get_rt_contacts', ['position' => 'all']);
        
        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['contacts']);
    }

    public function test_execute_handles_unknown_tool(): void
    {
        $result = $this->router->execute('unknown_tool', []);
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_execute_handles_exceptions_gracefully(): void
    {
        $result = $this->router->execute('get_outstanding_bills', ['resident_id' => 999999]);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }
}
