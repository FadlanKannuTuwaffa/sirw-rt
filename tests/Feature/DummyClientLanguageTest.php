<?php

namespace Tests\Feature;

use App\Services\Assistant\DummyClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DummyClientLanguageTest extends TestCase
{
    use RefreshDatabase;

    public function test_chat_responds_in_indonesian_when_prompt_is_indonesian(): void
    {
        /** @var DummyClient $client */
        $client = app(DummyClient::class);

        $response = $client->chat([
            ['role' => 'user', 'content' => 'Halo'],
        ]);

        $content = $response['content'] ?? '';

        $this->assertNotSame('', $content);
        $this->assertStringContainsString('Hai', $content);
        $this->assertStringContainsString('bantu', $content);
    }

    public function test_chat_responds_in_english_when_prompt_is_english(): void
    {
        /** @var DummyClient $client */
        $client = app(DummyClient::class);

        $response = $client->chat([
            ['role' => 'user', 'content' => 'Hello there'],
        ]);

        $content = $response['content'] ?? '';

        $this->assertNotSame('', $content);
        $this->assertStringContainsString('Hi', $content);
        $this->assertStringContainsString('bills', $content);
    }
}
