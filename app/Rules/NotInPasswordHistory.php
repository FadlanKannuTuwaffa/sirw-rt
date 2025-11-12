<?php

namespace App\Rules;

use App\Models\User;
use App\Services\Security\PasswordHistoryService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NotInPasswordHistory implements ValidationRule
{
    public function __construct(private readonly ?User $user)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '' || ! $this->user) {
            return;
        }

        /** @var PasswordHistoryService $history */
        $history = app(PasswordHistoryService::class);

        if ($history->hasBeenUsed($this->user, (string) $value)) {
            $fail('Password ini sudah pernah digunakan. Silakan pilih password lain.');
        }
    }
}
