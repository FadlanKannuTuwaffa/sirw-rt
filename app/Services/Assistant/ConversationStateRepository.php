<?php

namespace App\Services\Assistant;

use App\Models\ConversationState;

class ConversationStateRepository
{
    private const DEFAULT_STATE = [
        'last_intent' => null,
        'last_topic' => null,
        'last_data' => null,
        'slots' => [],
        'pending_slots' => null,
        'corrections' => [],
        'pending_confirmation' => null,
        'pending_correction' => null,
        'kb_sources' => [],
        'metadata' => [
            'timezone' => null,
            'clarification_turns' => 0,
            'clarification_history' => [],
            'last_kb_answer' => null,
            'last_kb_question' => null,
            'last_kb_confidence' => null,
            'retry_constraints' => [
                'include' => [],
                'exclude' => [],
            ],
            'last_correction_hint' => null,
            'language_override' => null,
            'language_override_locked' => false,
            'last_interaction_id' => null,
        ],
    ];

    public function get(?int $userId, string $threadId): array
    {
        $record = ConversationState::query()
            ->where('owner_hash', $this->ownerHash($userId, $threadId))
            ->first();

        if ($record === null) {
            return $this->defaults($threadId);
        }

        return $this->mergeDefaults($record->state ?? [], $threadId);
    }

    public function put(?int $userId, string $threadId, array $state): array
    {
        $payload = $this->mergeDefaults($state, $threadId);

        ConversationState::query()->updateOrCreate(
            ['owner_hash' => $this->ownerHash($userId, $threadId)],
            [
                'user_id' => $userId,
                'thread_id' => $threadId,
                'state' => $payload,
            ]
        );

        return $payload;
    }

    public function merge(?int $userId, string $threadId, array $fragment): array
    {
        $current = $this->get($userId, $threadId);
        $merged = $this->mergeDefaults(array_replace_recursive($current, $fragment), $threadId);

        return $this->put($userId, $threadId, $merged);
    }

    public function forget(?int $userId, string $threadId): void
    {
        ConversationState::query()
            ->where('owner_hash', $this->ownerHash($userId, $threadId))
            ->delete();
    }

    public function ownerHash(?int $userId, string $threadId): string
    {
        return sha1(($userId ?? 'guest') . '|' . $threadId);
    }

    private function defaults(string $threadId): array
    {
        $defaults = self::DEFAULT_STATE;
        $defaults['thread_id'] = $threadId;

        return $defaults;
    }

    private function mergeDefaults(array $state, string $threadId): array
    {
        $defaults = $this->defaults($threadId);

        return array_replace_recursive($defaults, $state);
    }
}
