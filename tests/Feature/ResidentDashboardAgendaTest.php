<?php

namespace Tests\Feature;

use App\Livewire\Resident\Dashboard;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ResidentDashboardAgendaTest extends TestCase
{
    use RefreshDatabase;

    public function test_resident_can_open_agenda_panel(): void
    {
        $resident = User::factory()->create();
        $creator = User::factory()->create(['role' => 'admin']);

        $event = Event::create([
            'title' => 'Kerja Bakti',
            'description' => 'Membersihkan lingkungan bersama.',
            'location' => 'Balai Warga',
            'start_at' => now()->addDays(2),
            'end_at' => now()->addDays(2)->addHours(2),
            'status' => 'scheduled',
            'created_by' => $creator->id,
        ]);

        $this->actingAs($resident);

        Livewire::test(Dashboard::class)
            ->call('openAgendaPanel')
            ->assertSet('showAgendaPanel', true)
            ->assertSet('selectedEventId', $event->id);
    }

    public function test_resident_can_select_specific_agenda_item(): void
    {
        $resident = User::factory()->create();
        $creator = User::factory()->create(['role' => 'admin']);

        $earlierEvent = Event::create([
            'title' => 'Ronda Malam',
            'description' => 'Jadwal ronda lingkungan.',
            'location' => 'Pos Kamling',
            'start_at' => now()->addDay(),
            'status' => 'scheduled',
            'created_by' => $creator->id,
        ]);

        $focusedEvent = Event::create([
            'title' => 'Kerja Bakti',
            'description' => 'Membersihkan lingkungan bersama.',
            'location' => 'Balai Warga',
            'start_at' => now()->addDays(3),
            'status' => 'scheduled',
            'created_by' => $creator->id,
        ]);

        $this->actingAs($resident);

        Livewire::test(Dashboard::class)
            ->call('viewEvent', $focusedEvent->id)
            ->assertSet('showAgendaPanel', true)
            ->assertSet('selectedEventId', $focusedEvent->id);
    }
}
