<?php

namespace Tests\Feature;

use App\Services\Assistant\Exceptions\LLMClientException;
use App\Services\Assistant\CohereClient;
use App\Services\Assistant\GroqClient;
use App\Services\Assistant\HuggingFaceClient;
use App\Services\Assistant\LangDBClient;
use App\Services\Assistant\MistralClient;
use App\Services\Assistant\OpenRouterClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LLMClientTest extends TestCase
{
    public function test_groq_client_returns_content_without_hitting_real_api(): void
    {
        config(['services.groq.api_key' => 'gsk_test_key']);

        Http::fake([
            'https://api.groq.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'halo']],
                ],
            ]),
        ]);

        $client = new GroqClient();
        $response = $client->chat([['role' => 'user', 'content' => 'Halo?']]);

        $this->assertSame('halo', $response['content']);
        $this->assertSame('Groq', $response['provider']);
        Http::assertSentCount(1);
    }

    public function test_openrouter_client_parses_tool_calls(): void
    {
        config([
            'services.openrouter.api_key' => 'sk-or-test',
            'app.url' => 'https://example.test',
            'app.name' => 'Test App',
        ]);

        Http::fake([
            'https://openrouter.ai/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'tool_calls' => [[
                            'type' => 'function',
                            'function' => [
                                'name' => 'get_outstanding_bills',
                                'arguments' => json_encode(['resident_id' => 1]),
                            ],
                        ]],
                    ],
                ]],
            ]),
        ]);

        $client = new OpenRouterClient();
        $response = $client->chat([['role' => 'user', 'content' => 'Tagihan saya?']]);

        $this->assertArrayHasKey('tool_calls', $response);
        $this->assertEquals('OpenRouter', $response['provider']);
        $this->assertSame('get_outstanding_bills', $response['tool_calls'][0]['function']['name']);
    }

    public function test_huggingface_client_uses_configured_endpoint(): void
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
        $response = $client->chat([['role' => 'user', 'content' => 'Halo?']]);

        $this->assertSame('Halo dari HF', $response['content']);
        $this->assertSame('HuggingFace', $response['provider']);
    }

    public function test_openrouter_client_requires_api_key(): void
    {
        config(['services.openrouter.api_key' => null]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OPENROUTER_API_KEY is not configured.');

        (new OpenRouterClient())->chat([['role' => 'user', 'content' => 'Halo']]);
    }

    public function test_huggingface_client_throws_when_all_endpoints_fail(): void
    {
        config([
            'services.huggingface.api_key' => 'hf_test_key',
            'services.huggingface.endpoints' => ['https://router.huggingface.co/v1'],
        ]);

        Http::fake([
            'https://router.huggingface.co/*' => Http::response([], 500),
        ]);

        $this->expectException(LLMClientException::class);
        (new HuggingFaceClient())->chat([['role' => 'user', 'content' => 'Test']]);
    }

    public function test_langdb_client_returns_message_content(): void
    {
        config([
            'services.langdb.api_key' => 'ld_test_key',
            'services.langdb.endpoint' => 'https://api.langdb.test/v1/chat/completions',
            'services.langdb.model' => 'deepinfra/llama-3.1-8b-instruct',
            'services.langdb.allowed_models' => ['deepinfra/llama-3.1-8b-instruct'],
        ]);

        Http::fake([
            'https://api.langdb.test/*' => Http::response([
                'choices' => [[
                    'message' => ['content' => 'Hello from LangDB'],
                ]],
            ]),
        ]);

        $client = new LangDBClient();
        $response = $client->chat([['role' => 'user', 'content' => 'Hi?']]);

        $this->assertSame('Hello from LangDB', $response['content']);
        $this->assertSame('LangDB', $response['provider']);
    }

    public function test_mistral_client_handles_tool_calls(): void
    {
        config([
            'services.mistral.api_key' => 'mistral_test_key',
            'services.mistral.endpoint' => 'https://api.mistral.test/v1/chat/completions',
        ]);

        Http::fake([
            'https://api.mistral.test/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'tool_calls' => [[
                            'type' => 'function',
                            'function' => ['name' => 'get_agenda'],
                        ]],
                    ],
                ]],
            ]),
        ]);

        $client = new MistralClient();
        $tools = [['type' => 'function', 'function' => ['name' => 'get_agenda']]];
        $response = $client->chat([['role' => 'user', 'content' => 'Agenda?']], $tools);

        $this->assertArrayHasKey('tool_calls', $response);
        $this->assertSame('Mistral', $response['provider']);
    }

    public function test_cohere_client_parses_text_field(): void
    {
        config([
            'services.cohere.api_key' => 'cohere_test_key',
            'services.cohere.endpoint' => 'https://api.cohere.test/v1/chat',
        ]);

        Http::fake([
            'https://api.cohere.test/*' => Http::response([
                'text' => 'Cohere says hello',
            ]),
        ]);

        $client = new CohereClient();
        $response = $client->chat([['role' => 'user', 'content' => 'Hello?']]);

        $this->assertSame('Cohere says hello', $response['content']);
        $this->assertSame('Cohere', $response['provider']);
    }
}
