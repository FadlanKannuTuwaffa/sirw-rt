<?php

namespace App\Services\Telegram;

use App\Models\TelegramCommand;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class TelegramCommandSynchronizer
{
    public function __construct(
        private readonly TelegramSettings $settings,
        private readonly TelegramClient $client,
        private readonly TelegramBotService $botService
    ) {
    }

    public function sync(bool $includeAdmin = true): void
    {
        $this->botService->ensureSystemCommandsSeeded();

        $commands = TelegramCommand::query()
            ->active()
            ->orderBy('command')
            ->get();

        if ($commands->isEmpty()) {
            return;
        }

        $this->syncPublicCommands($commands->where('is_admin_only', false));

        if ($includeAdmin) {
            $this->syncAdminCommands($commands->where('is_admin_only', true));
        }
    }

    private function syncPublicCommands(Collection $commands): void
    {
        if ($commands->isEmpty()) {
            return;
        }

        $payload = $commands->map(fn (TelegramCommand $command) => [
            'command' => $command->command,
            'description' => mb_strimwidth($command->description, 0, 256),
        ])->values()->all();

        $this->client->setMyCommands($payload, [
            'type' => 'default',
        ]);
    }

    private function syncAdminCommands(Collection $commands): void
    {
        if ($commands->isEmpty()) {
            return;
        }

        $payload = $commands->map(fn (TelegramCommand $command) => [
            'command' => $command->command,
            'description' => mb_strimwidth($command->description, 0, 256),
        ])->values()->all();

        // Telegram tidak menyediakan scope khusus untuk chat privat admin.
        // Simpan daftar untuk referensi internal (misal ditampilkan ketika admin meminta bantuan).
        Log::info('Telegram admin commands registered', ['commands' => $payload]);
    }
}
