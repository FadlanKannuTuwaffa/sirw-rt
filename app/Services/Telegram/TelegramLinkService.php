<?php

namespace App\Services\Telegram;

use App\Models\TelegramAccount;
use App\Models\TelegramLinkToken;
use App\Models\User;
use App\Services\Telegram\TelegramBotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use RuntimeException;

class TelegramLinkService
{
    public function __construct(
        private readonly TelegramSettings $settings
    ) {
    }

    public function generateToken(User $user, int $expiresInMinutes = 30): TelegramLinkToken
    {
        $metaDefaults = [
            'role' => $user->role,
            'generated_user_id' => $user->id,
            'generated_via' => $user->isAdmin() ? 'admin_profile' : 'resident_portal',
        ];

        $existing = $user->telegramLinkTokens()
            ->active()
            ->latest()
            ->first();

        if ($existing) {
            $existing->forceFill([
                'meta' => array_merge($existing->meta ?? [], $metaDefaults),
            ])->save();

            return $existing->refresh();
        }

        do {
            $token = 'LINK-' . Str::upper(Str::random(6));
        } while (TelegramLinkToken::where('token', $token)->exists());

        return $user->telegramLinkTokens()->create([
            'token' => $token,
            'expires_at' => now()->addMinutes($expiresInMinutes),
            'meta' => array_merge($metaDefaults, [
                'generated_at' => now()->toIso8601String(),
            ]),
        ]);
    }

    public function findActiveToken(string $token): ?TelegramLinkToken
    {
        return TelegramLinkToken::query()
            ->where('token', strtoupper($token))
            ->active()
            ->first();
    }

    public function linkFromToken(TelegramLinkToken $linkToken, array $telegramPayload): TelegramAccount
    {
        $user = $linkToken->user;

        if (empty($telegramPayload['telegram_user_id']) || empty($telegramPayload['telegram_chat_id'])) {
            throw new RuntimeException('Payload Telegram tidak lengkap untuk proses penautan.');
        }

        $telegramUserId = (int) $telegramPayload['telegram_user_id'];
        $telegramChatId = (int) $telegramPayload['telegram_chat_id'];

        $existing = TelegramAccount::query()
            ->where('telegram_user_id', $telegramUserId)
            ->where('user_id', '!=', $user->id)
            ->whereNull('unlinked_at')
            ->first();

        if ($existing) {
            throw new RuntimeException('Akun Telegram ini sudah terhubung dengan pengguna lain.');
        }

        return DB::transaction(function () use ($user, $linkToken, $telegramPayload, $telegramUserId, $telegramChatId) {
            $previousUser = null;

            $account = TelegramAccount::query()
                ->where('user_id', $user->id)
                ->first();

            if (! $account) {
                $account = TelegramAccount::query()
                    ->where('telegram_user_id', $telegramUserId)
                    ->first();

                if ($account && $account->user_id !== $user->id) {
                    $previousUser = $account->user;
                }
            }

            if (! $account) {
                $account = new TelegramAccount();
            }

            $account->user_id = $user->id;

            $account->fill([
                'telegram_user_id' => $telegramUserId,
                'telegram_chat_id' => $telegramChatId,
                'username' => $telegramPayload['username'] ?? null,
                'first_name' => $telegramPayload['first_name'] ?? null,
                'last_name' => $telegramPayload['last_name'] ?? null,
                'language_code' => $telegramPayload['language_code']
                    ?? $account->language_code
                    ?? $this->settings->defaultLanguage(),
                'last_interaction_at' => now(),
            ]);

            $account->linked_at = now();
            $account->unlinked_at = null;

            $account->receive_notifications = true;
            $account->preferences = [
                'role' => $user->role,
                'scopes' => $user->isAdmin()
                    ? ['admin', 'resident']
                    : ['resident'],
            ];
            $account->save();
            $account->setRelation('user', $user);

            $linkToken->markUsed([
                'linked_user_id' => $user->id,
                'linked_role' => $user->role,
                'linked_at' => now()->toIso8601String(),
            ]);

            if ($previousUser && $previousUser->id !== $user->id) {
                $previousUser->forceFill(['telegram_prompt_enabled' => true])->save();
            }

            $user->forceFill(['telegram_prompt_enabled' => false])->save();

            return $account;
        });
    }

    public function unlink(User $user): void
    {
        $account = $user->telegramAccount;

        if (! $account) {
            return;
        }

        $account->forceFill([
            'unlinked_at' => now(),
            'receive_notifications' => false,
            'last_interaction_at' => null,
            'preferences' => [],
        ])->save();

        $user->forceFill(['telegram_prompt_enabled' => true])->save();

        if ($account->telegram_chat_id) {
            RateLimiter::clear(sprintf('tg:%s', $account->telegram_chat_id));

            app(TelegramBotService::class)->syncChatCommandMenu(
                (string) $account->telegram_chat_id,
                null,
                null
            );
        }
    }

    public function toggleSubscription(User $user, bool $subscribe): void
    {
        $account = $user->telegramAccount;

        if (! $account) {
            throw new RuntimeException('Akun Telegram belum terhubung.');
        }

        $account->forceFill([
            'receive_notifications' => $subscribe,
            'last_interaction_at' => now(),
        ])->save();
    }
}
