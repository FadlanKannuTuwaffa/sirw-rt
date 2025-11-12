<?php

namespace App\Livewire\Admin\Pengaturan;

use App\Models\TelegramCommand;
use App\Services\Telegram\TelegramClient;
use App\Services\Telegram\TelegramCommandSynchronizer;
use App\Services\Telegram\TelegramBotService;
use App\Services\Telegram\TelegramSettings;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use RuntimeException;

#[Layout('layouts.admin')]
class TelegramBot extends Component
{
    protected array $layoutData = [
        'title' => 'Pengaturan',
        'titleClass' => 'text-white',
    ];

    public string $bot_token = '';
    public ?string $webhook_url = null;
    public ?string $webhook_secret = null;
    public string $default_language = 'id';
    public ?string $contact_email = null;
    public ?string $contact_whatsapp = null;

    public array $commandOptions = [];
    public $selectedCommandId = 'new';
    public array $commandEditor = [
        'command' => '',
        'description' => '',
        'response_template' => '',
        'is_admin_only' => false,
        'is_active' => true,
        'is_system' => false,
        'type' => TelegramCommand::TYPE_CUSTOM,
    ];

    protected array $systemTemplates = [];

    public function mount(TelegramSettings $settings, TelegramBotService $botService): void
    {
        $botService->ensureSystemCommandsSeeded();
        $this->systemTemplates = TelegramBotService::defaultTemplates();

        $config = $settings->toArray();

        $this->bot_token = (string) ($config['bot_token'] ?? '');
        $this->webhook_url = $config['webhook_url'] ?? route('telegram.webhook');
        $this->webhook_secret = $config['webhook_secret'] ?? null;
        $this->default_language = $config['default_language'] ?? 'id';
        $this->contact_email = $config['contact_email'] ?? null;
        $this->contact_whatsapp = $config['contact_whatsapp'] ?? null;

        $this->refreshCommands();
    }

    public function saveSettings(TelegramSettings $settings): void
    {
        $data = $this->validate([
            'bot_token' => ['nullable', 'string', 'max:255'],
            'webhook_url' => ['nullable', 'url'],
            'webhook_secret' => ['nullable', 'string', 'max:64'],
            'default_language' => ['required', Rule::in(['id', 'en'])],
            'contact_email' => ['nullable', 'email'],
            'contact_whatsapp' => ['nullable', 'string', 'max:40'],
        ]);

        $payload = collect($data)
            ->map(fn ($value) => is_string($value) ? trim($value) : $value)
            ->map(fn ($value) => $value === '' ? null : $value)
            ->toArray();

        if (empty($payload['webhook_url'])) {
            $payload['webhook_url'] = route('telegram.webhook');
        }

        $settings->store($payload);

        session()->flash('status', 'Pengaturan bot Telegram berhasil disimpan.');
    }

    public function syncCommands(TelegramCommandSynchronizer $synchronizer): void
    {
        $synchronizer->sync();
        session()->flash('status', 'Daftar perintah bot berhasil disinkronkan ke Telegram.');
    }

    public function applyWebhook(TelegramClient $client, TelegramSettings $settings): void
    {
        $url = $this->webhook_url ?: route('telegram.webhook');
        $secret = $this->webhook_secret ?: $settings->webhookSecret();

        $options = [];
        if ($secret) {
            $options['secret_token'] = $secret;
        }

        try {
            $response = $client->setWebhook($url, $options);
        } catch (RuntimeException $e) {
            session()->flash('status', 'Gagal mengatur webhook: ' . $e->getMessage());
            return;
        }

        if (! $response || ! Arr::get($response, 'ok')) {
            session()->flash('status', 'Gagal mengatur webhook Telegram. Periksa token dan URL Anda.');
            return;
        }

        $settings->store([
            'webhook_url' => $url,
            'webhook_secret' => $secret,
        ]);

        session()->flash('status', 'Webhook Telegram berhasil diperbarui.');
    }

    public function selectCommand($commandId): void
    {
        if ($commandId === 'new' || empty($commandId)) {
            $this->resetCommandEditor();
            return;
        }

        $command = TelegramCommand::find($commandId);
        if (! $command) {
            $this->resetCommandEditor();
            return;
        }

        $this->hydrateEditorFromCommand($command);
    }

    public function saveCommand(TelegramCommandSynchronizer $synchronizer): void
    {
        if ($this->selectedCommandId === 'new') {
            $data = $this->validate([
                'commandEditor.command' => ['required', 'regex:/^[a-z0-9_]{2,32}$/', 'unique:telegram_commands,command'],
                'commandEditor.description' => ['required', 'string', 'max:160'],
                'commandEditor.response_template' => ['required', 'string'],
                'commandEditor.is_admin_only' => ['boolean'],
            ])['commandEditor'];

            $command = TelegramCommand::create([
                'command' => strtolower(trim($data['command'])),
                'description' => trim($data['description']),
                'response_template' => $data['response_template'] !== null ? trim($data['response_template']) : null,
                'is_admin_only' => (bool) ($data['is_admin_only'] ?? false),
                'is_active' => true,
                'type' => TelegramCommand::TYPE_CUSTOM,
                'is_system' => false,
            ]);

            $this->refreshCommands($command->id);
            $synchronizer->sync();

            session()->flash('status', 'Perintah baru berhasil ditambahkan.');
            return;
        }

        $command = TelegramCommand::findOrFail($this->selectedCommandId);

        $rules = [
            'commandEditor.description' => ['required', 'string', 'max:160'],
            'commandEditor.response_template' => ['nullable', 'string'],
        ];

        if (! $command->is_system) {
            $rules['commandEditor.command'] = [
                'required',
                'regex:/^[a-z0-9_]{2,32}$/',
                Rule::unique('telegram_commands', 'command')->ignore($command->id),
            ];
            $rules['commandEditor.is_admin_only'] = ['boolean'];
            $rules['commandEditor.is_active'] = ['boolean'];
        }

        $data = $this->validate($rules)['commandEditor'];

        $command->description = trim($data['description']);
        $command->response_template = $data['response_template'] !== null ? trim($data['response_template']) : null;

        if ($command->is_system) {
            $command->is_active = true;
            $command->is_admin_only = false;
        } else {
            $command->command = strtolower(trim($data['command']));
            $command->is_admin_only = (bool) ($data['is_admin_only'] ?? false);
            $command->is_active = (bool) ($data['is_active'] ?? false);
        }

        $command->save();

        $this->refreshCommands($command->id);
        $synchronizer->sync();

        session()->flash('status', "Perintah /{$command->command} berhasil diperbarui.");
    }

    public function deleteCommand(TelegramCommandSynchronizer $synchronizer): void
    {
        if ($this->selectedCommandId === 'new') {
            return;
        }

        $command = TelegramCommand::findOrFail($this->selectedCommandId);

        if ($command->is_system) {
            session()->flash('status', 'Perintah bawaan tidak dapat dihapus.');
            return;
        }

        $command->delete();

        $this->refreshCommands();
        $synchronizer->sync();

        session()->flash('status', 'Perintah bot berhasil dihapus.');
    }

    public function render()
    {
        return view('livewire.admin.pengaturan.telegram-bot', [
            'commandOptions' => $this->commandOptions,
            'selectedCommandId' => $this->selectedCommandId,
            'editor' => $this->commandEditor,
        ]);
    }

    private function refreshCommands($preferredId = null): void
    {
        $botService = app(TelegramBotService::class);
        $botService->ensureSystemCommandsSeeded();
        $this->systemTemplates = TelegramBotService::defaultTemplates();

        $commands = TelegramCommand::query()
            ->orderBy('command')
            ->get();

        $this->commandOptions = $commands->map(function (TelegramCommand $command) {
            return [
                'id' => $command->id,
                'command' => $command->command,
                'is_system' => (bool) $command->is_system,
                'is_admin_only' => (bool) $command->is_admin_only,
                'is_active' => (bool) $command->is_active,
            ];
        })->toArray();

        if ($preferredId === 'new') {
            $this->resetCommandEditor();
            return;
        }

        if ($preferredId && $commands->contains('id', $preferredId)) {
            $this->hydrateEditorFromCommand($commands->firstWhere('id', $preferredId));
            return;
        }

        if ($this->selectedCommandId !== 'new') {
            $current = $commands->firstWhere('id', $this->selectedCommandId);
            if ($current) {
                $this->hydrateEditorFromCommand($current);
                return;
            }
        }

        if ($commands->isNotEmpty()) {
            $this->hydrateEditorFromCommand($commands->first());
        } else {
            $this->resetCommandEditor();
        }
    }

    private function hydrateEditorFromCommand(TelegramCommand $command): void
    {
        $this->selectedCommandId = $command->id;
        $template = $command->response_template ?? '';

        if ($command->is_system && ($template === '' || $template === null)) {
            $template = $this->systemTemplates[$command->command] ?? '';
        }

        $this->commandEditor = [
            'command' => $command->command,
            'description' => $command->description ?? '',
            'response_template' => $template,
            'is_admin_only' => (bool) $command->is_admin_only,
            'is_active' => (bool) $command->is_active,
            'is_system' => (bool) $command->is_system,
            'type' => $command->type ?? TelegramCommand::TYPE_SYSTEM,
        ];
    }

    private function resetCommandEditor(): void
    {
        $this->selectedCommandId = 'new';
        $this->commandEditor = [
            'command' => '',
            'description' => '',
            'response_template' => '',
            'is_admin_only' => false,
            'is_active' => true,
            'is_system' => false,
            'type' => TelegramCommand::TYPE_CUSTOM,
        ];
    }
}
