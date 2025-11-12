<?php

namespace Tests\Unit\Services\Assistant;

use App\Jobs\PromoteLlmSnapshot;
use App\Models\AssistantInteractionLog;
use App\Models\AssistantLlmSnapshot;
use App\Services\Assistant\Support\AutoLearnSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AutoLearnSnapshotServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_queues_snapshot_when_feedback_positive(): void
    {
        Queue::fake();
        config(['assistant.features.llm_promotion' => true]);

        $log = AssistantInteractionLog::create([
            'user_id' => null,
            'query' => 'Apa agenda minggu ini?',
            'classification_type' => 'complex',
            'confidence' => 0.9,
            'intents' => ['agenda'],
            'tool_calls' => [],
            'tool_success' => null,
            'responded_via' => 'llm',
            'success' => true,
            'duration_ms' => 1500,
        ]);

        $interactionId = DB::table('assistant_interactions')->insertGetId([
            'user_id' => null,
            'thread_id' => 'user:1',
            'message' => 'Apa agenda minggu ini?',
            'intent' => 'agenda',
            'response' => 'Agenda terbaru tersedia.',
            'confidence' => 0.9,
            'method' => 'llm',
            'assistant_interaction_log_id' => $log->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $snapshot = AssistantLlmSnapshot::create([
            'assistant_interaction_log_id' => $log->id,
            'user_id' => null,
            'thread_id' => 'user:1',
            'intent' => 'agenda',
            'confidence' => 0.82,
            'provider' => 'groq',
            'responded_via' => 'llm',
            'is_fallback' => true,
            'content' => 'Agenda terbaru.',
            'rag_sources' => [],
            'tool_calls' => [],
            'is_helpful' => true,
            'needs_review' => false,
            'auto_promote_ready' => false,
            'promotion_status' => 'pending',
            'metadata' => [],
        ]);

        app(AutoLearnSnapshotService::class)->scheduleFromInteraction($interactionId, true);

        $snapshot->refresh();

        $this->assertEquals('queued', $snapshot->promotion_status);
        $this->assertTrue($snapshot->auto_promote_ready);
        $this->assertSame($interactionId, $snapshot->assistant_interaction_id);

        Queue::assertPushed(PromoteLlmSnapshot::class, function ($job) use ($snapshot) {
            $ref = new \ReflectionClass($job);
            $prop = $ref->getProperty('snapshotId');
            $prop->setAccessible(true);

            return $prop->getValue($job) === $snapshot->id;
        });

        $this->assertDatabaseHas('assistant_llm_snapshot_reviews', [
            'assistant_llm_snapshot_id' => $snapshot->id,
            'action' => 'auto_learn_feedback',
        ]);
    }

    public function test_it_skips_when_guardrail_detected(): void
    {
        Queue::fake();
        config(['assistant.features.llm_promotion' => true]);

        $log = AssistantInteractionLog::create([
            'user_id' => null,
            'query' => 'Beritahu aturan RT',
            'classification_type' => 'complex',
            'confidence' => 0.66,
            'intents' => ['knowledge_base'],
            'tool_calls' => [],
            'tool_success' => null,
            'responded_via' => 'llm',
            'success' => true,
            'duration_ms' => 900,
        ]);

        $interactionId = DB::table('assistant_interactions')->insertGetId([
            'user_id' => null,
            'thread_id' => 'user:2',
            'message' => 'Beritahu aturan RT',
            'intent' => 'knowledge_base',
            'response' => 'Aturan...',
            'confidence' => 0.66,
            'method' => 'llm',
            'assistant_interaction_log_id' => $log->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $snapshot = AssistantLlmSnapshot::create([
            'assistant_interaction_log_id' => $log->id,
            'user_id' => null,
            'thread_id' => 'user:2',
            'intent' => 'knowledge_base',
            'confidence' => 0.7,
            'provider' => 'groq',
            'responded_via' => 'llm',
            'is_fallback' => true,
            'content' => 'Aturan...',
            'rag_sources' => [],
            'tool_calls' => [],
            'is_helpful' => true,
            'needs_review' => false,
            'auto_promote_ready' => false,
            'promotion_status' => 'pending',
            'metadata' => [
                'recent_guardrails' => ['ooc_block'],
            ],
        ]);

        app(AutoLearnSnapshotService::class)->scheduleFromInteraction($interactionId, true);

        $snapshot->refresh();

        $this->assertEquals('pending', $snapshot->promotion_status);
        Queue::assertNothingPushed();
        $this->assertDatabaseMissing('assistant_llm_snapshot_reviews', [
            'assistant_llm_snapshot_id' => $snapshot->id,
            'action' => 'auto_learn_feedback',
        ]);
    }
}
