<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminCreatePagesTest extends TestCase
{
    use RefreshDatabase;

    protected function createAdmin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'status' => 'aktif',
        ]);
    }

    #[Test]
    public function admin_can_view_create_tagihan_page(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get('/admin/tagihan/create')
            ->assertOk();
    }

    #[Test]
    public function admin_can_view_create_warga_page(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get('/admin/warga/create')
            ->assertOk();
    }

    #[Test]
    public function admin_can_view_create_agenda_page(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get('/admin/agenda/create')
            ->assertOk();
    }
}
