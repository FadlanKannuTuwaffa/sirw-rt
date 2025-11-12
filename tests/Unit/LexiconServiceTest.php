<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\Assistant\LexiconService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LexiconServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_process_normalizes_slang_and_typos(): void
    {
        $service = new LexiconService();

        $result = $service->process('taghan smph bulan kmrn udh blm?');

        $this->assertSame(
            'tagihan iuran_kebersihan bulan kmrn sudah belum',
            $result['normalized']
        );
        $this->assertContains('tagihan', $result['tokens']);
        $this->assertContains('iuran_kebersihan', $result['tokens']);
        $this->assertContains('sudah', $result['tokens']);
        $this->assertContains('belum', $result['tokens']);
    }

    public function test_process_extracts_amount_month_and_name_entities(): void
    {
        $service = new LexiconService();

        $result = $service->process('tolong siapin budget 300k buat agenda oktober dan hubungi pak budi ya');
        $entities = $result['entities'];

        $this->assertSame(300000, $entities['amounts'][0]['value'] ?? 0);
        $this->assertSame(10, $entities['months'][0]['month'] ?? null);
        $this->assertSame('Budi', $entities['names'][0]['name'] ?? null);
    }

    public function test_database_names_detected_as_entities(): void
    {
        User::factory()->create(['name' => 'Siti Andini']);

        $service = new LexiconService();
        $entities = $service->extractEntities('tolong carikan kontak siti andini dong');

        $this->assertNotEmpty($entities['names']);
        $this->assertTrue(
            collect($entities['names'])->contains(fn ($entry) => str_contains(strtolower($entry['name']), 'siti'))
        );
    }

    public function test_oov_promotions_are_thread_scoped(): void
    {
        $service = new LexiconService();
        $reflector = new \ReflectionMethod(LexiconService::class, 'recordOovMapping');
        $reflector->setAccessible(true);

        $service->setThreadContext(1, 'alpha');
        for ($i = 0; $i < 3; $i++) {
            $reflector->invoke($service, 'taghan', 'tagihan');
        }

        $map = Cache::get('assistant.lexicon.oov.map', []);
        $alphaKey = 'thread:' . sha1('1|alpha');
        $this->assertArrayHasKey($alphaKey, $map);

        $service->setThreadContext(2, 'beta');
        $reflector->invoke($service, 'taghan', 'tagihan');
        $map = Cache::get('assistant.lexicon.oov.map', []);
        $betaKey = 'thread:' . sha1('2|beta');
        $this->assertArrayNotHasKey($betaKey, $map);
    }
}
