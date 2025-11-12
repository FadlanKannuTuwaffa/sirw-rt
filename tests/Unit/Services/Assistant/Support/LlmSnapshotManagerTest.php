<?php

namespace Tests\Unit\Services\Assistant\Support;

use App\Models\AssistantInteractionLog;
use App\Models\AssistantLlmSnapshot;
use App\Services\Assistant\Support\LlmSnapshotManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LlmSnapshotManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_feedback_resolves_by_interaction_id(): void
    {
        $log = AssistantInteractionLog::create([
            'user_id' => null,
            'query' => 'Siapa pengurus RT?',
            'classification_type' => 'complex',
            'confidence' => 0.77,
            'intents' => ['residents'],
            'tool_calls' => [],
            'tool_success' => null,
            'responded_via' => 'llm',
            'success' => true,
            'duration_ms' => 1100,
        ]);

        $interactionId = DB::table('assistant_interactions')->insertGetId([
            'user_id' => null,
            'thread_id' => 'user:5',
            'message' => 'Siapa pengurus RT?',
            'intent' => 'residents',
            'response' => 'Berikut pengurus terbaru...',
            'confidence' => 0.77,
            'method' => 'llm',
            'assistant_interaction_log_id' => $log->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $snapshot = AssistantLlmSnapshot::create([
            'assistant_interaction_log_id' => $log->id,
            'assistant_interaction_id' => $interactionId,
            'user_id' => null,
            'thread_id' => 'user:5',
            'intent' => 'residents',
            'confidence' => 0.77,
            'provider' => 'groq',
            'responded_via' => 'llm',
            'is_fallback' => true,
            'content' => 'Berikut pengurus terbaru...',
            'rag_sources' => [],
            'tool_calls' => [],
            'is_helpful' => null,
            'needs_review' => true,
            'auto_promote_ready' => false,
            'promotion_status' => 'pending',
            'metadata' => [],
        ]);

        $manager = app(LlmSnapshotManager::class);
        $manager->markFeedback($interactionId, true, 'interaction_feedback', null);

        $snapshot->refresh();

        $this->assertTrue($snapshot->is_helpful);
        $this->assertEquals(1, $snapshot->positive_feedback_count);
        $this->assertNotNull($snapshot->last_feedback_at);
        $this->assertFalse($snapshot->auto_promote_ready);
    }
}
