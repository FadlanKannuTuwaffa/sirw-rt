<?php

namespace App\Services\Security;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class AdminSensitiveAttemptService
{
    private const MAX_ATTEMPTS = 5;
    private const DECAY_SECONDS = 600;
    private const HISTORY_TTL = 600;

    public function record(User $user, string $action, array $meta = []): bool
    {
        $key = $this->key($user->id);
        RateLimiter::hit($key, self::DECAY_SECONDS);

        $historyKey = $this->historyKey($user->id);
        $history = Cache::get($historyKey, []);

        $history[] = [
            'action' => $action,
            'meta' => $meta,
            'attempted_at' => now()->toIso8601String(),
        ];

        $history = array_slice($history, -10);
        Cache::put($historyKey, $history, self::HISTORY_TTL);

        return RateLimiter::attempts($key) > self::MAX_ATTEMPTS;
    }

    public function clear(User $user): void
    {
        RateLimiter::clear($this->key($user->id));
        Cache::forget($this->historyKey($user->id));
    }

    public function attempts(User $user): int
    {
        return RateLimiter::attempts($this->key($user->id));
    }

    public function history(User $user): array
    {
        return Cache::get($this->historyKey($user->id), []);
    }

    private function key(int $userId): string
    {
        return 'admin-sensitive-attempts:' . $userId;
    }

    private function historyKey(int $userId): string
    {
        return 'admin-sensitive-history:' . $userId;
    }
}
