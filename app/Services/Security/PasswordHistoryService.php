<?php

namespace App\Services\Security;

use App\Models\User;
use App\Models\UserPasswordHistory;
use Illuminate\Support\Facades\Hash;

class PasswordHistoryService
{
    private const MAX_HISTORY = 10;

    public function hasBeenUsed(User $user, string $plainPassword): bool
    {
        if ($plainPassword === '') {
            return false;
        }

        if ($user->password && Hash::check($plainPassword, $user->password)) {
            return true;
        }

        $histories = $user->passwordHistories()
            ->latest('created_at')
            ->take(self::MAX_HISTORY)
            ->get();

        foreach ($histories as $history) {
            if (Hash::check($plainPassword, $history->password)) {
                return true;
            }
        }

        return false;
    }

    public function record(User $user, ?string $hashedPassword = null): void
    {
        $hashedPassword = $hashedPassword ?? $user->password;

        if (! $hashedPassword) {
            return;
        }

        UserPasswordHistory::query()->create([
            'user_id' => $user->id,
            'password' => $hashedPassword,
        ]);

        $this->prune($user);
    }

    private function prune(User $user): void
    {
        $idsToKeep = $user->passwordHistories()
            ->latest('created_at')
            ->limit(self::MAX_HISTORY)
            ->pluck('id')
            ->all();

        if (empty($idsToKeep)) {
            return;
        }

        $user->passwordHistories()
            ->whereNotIn('id', $idsToKeep)
            ->delete();
    }
}
