<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use App\Models\Bill;
use App\Models\Event;
use App\Models\TelegramAccount;
use App\Models\TelegramCommand;
use App\Models\TelegramLinkToken;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Throwable;

/**
 * Telegram bot orchestration service.
 *
 * Requires: composer require irazasyed/telegram-bot-sdk
 */
final class TelegramBotService
{
    private const RATE_LIMIT_ATTEMPTS = 20;
    private const RATE_LIMIT_DECAY_SECONDS = 60;

    /**
     * Default Indonesian responses for the built-in system commands.
     */
    private const DEFAULT_TEMPLATES = [
        'start' => "👋 Halo :user_name!\nSelamat datang di layanan RT untuk :audience_label. Ketik /help untuk melihat daftar perintah yang tersedia.\n\n📄 Status akun: :status\n🆔 ID :role_label: :user_id",
        'help' => "Gunakan perintah ini untuk menampilkan daftar perintah terbaru di chat ini.",
        'bills' => "🧾 Ringkasan Tagihan Aktif\nTotal tagihan: :count\nTotal terutang: :total_amount\nJatuh tempo terdekat: :next_due_date\n\n:bills_list\n\nGunakan /bill <ID> untuk melihat salah satu tagihan.",
        'bill' => "🧾 Detail Tagihan :bill_id\nJudul: :bill_title\nStatus: :status\nNominal: :amount\nJatuh tempo: :due_date\nDeskripsi: :bill_description",
        'stats' => "Statistik Warga Aktif\nTotal warga aktif saat ini: :count",
        'billstats' => "Ringkasan Tagihan Pending\nTotal tagihan belum lunas: :billstats_count\nTotal nominal tertunggak: :billstats_total_amount\nJatuh tempo terdekat: :billstats_next_due\n\n:billstats_list",
        'status' => "ℹ️ Status Akun\nStatus: :status\nBergabung sejak: :join_date\nNotifikasi Telegram: :notification_status",
        'lang' => "🌐 Bahasa bot saat ini: :language_code\nGunakan /lang id atau /lang en untuk mengganti bahasa.",
        'link' => "🔗 Akun berhasil ditautkan.\nHalo :user_name! Selamat menggunakan layanan RT bagi :audience_label. Ketik /help untuk memulai.",
        'unlink' => "🔌 Akun Telegram kamu telah diputuskan dari sistem. Gunakan /start LINK-XXXX untuk menghubungkan kembali.",
        'unsubscribe' => "🔕 Notifikasi Telegram dimatikan. Gunakan /subscribe untuk menyalakannya kembali.",
        'subscribe' => "🔔 Notifikasi Telegram diaktifkan kembali. Terima kasih, :user_name!",
        'contact' => "☎️ Kontak Pengurus\nEmail: :contact_email\nWhatsApp: :contact_whatsapp",
        'broadcast' => "📣 Mode broadcast siap.\nKirimkan pesan Anda, bot akan meneruskan ke warga yang berlangganan. Gunakan dengan bijak.",
        'linkinfo' => "🔗 Info Penautan\nStatus: :status\nChat ID: :chat_id\nNotifikasi: :notification_status\nBahasa: :language_code",
        'events' => "🗓️ Agenda Terdekat\nJudul: :event_title\nWaktu: :event_date\nLokasi: :location",
        'unknown' => "Perintah tidak dikenal. Ketik /help untuk melihat daftar perintah yang tersedia.",
    ];

    /**
     * Default metadata for the system commands displayed in the admin panel.
     */
    private const DEFAULT_COMMAND_DEFINITIONS = [
        'start' => ['description' => 'Mulai percakapan dengan bot'],
        'help' => ['description' => 'Daftar perintah bot'],
        'bills' => ['description' => 'Ringkasan tagihan aktif'],
        'bill' => ['description' => 'Detail satu tagihan milik warga'],
        'stats' => ['description' => 'Statistik singkat warga aktif', 'is_admin_only' => true],
        'billstats' => ['description' => 'Ringkasan tagihan warga (pending)', 'is_admin_only' => true],
        'status' => ['description' => 'Status akun dan langganan notifikasi'],
        'lang' => ['description' => 'Ganti bahasa bot'],
        'link' => ['description' => 'Tautkan akun warga dengan bot'],
        'unlink' => ['description' => 'Putuskan tautan akun Telegram'],
        'unsubscribe' => ['description' => 'Matikan notifikasi Telegram'],
        'subscribe' => ['description' => 'Aktifkan notifikasi Telegram'],
        'contact' => ['description' => 'Informasi kontak pengurus'],
        'broadcast' => ['description' => 'Mode siaran (khusus admin)', 'is_admin_only' => true],
        'linkinfo' => ['description' => 'Informasi status penautan'],
        'events' => ['description' => 'Agenda terdekat'],
    ];

    private const GUEST_COMMAND_MENU = [
        [
            'command' => 'start',
            'description' => 'Mulai & tautkan akun (pakai LINK-XXXX)',
        ],
        [
            'command' => 'help',
            'description' => 'Panduan menautkan akun dan penggunaan bot',
        ],
    ];

    private const RESIDENT_COMMAND_MENU = [
        'start',
        'help',
        'bills',
        'bill',
        'status',
        'events',
        'lang',
        'contact',
        'linkinfo',
        'subscribe',
        'unsubscribe',
        'unlink',
    ];

    private const RESIDENT_COMMAND_FALLBACK = [
        ['command' => 'start', 'description' => 'Mulai ulang sesi bot'],
        ['command' => 'help', 'description' => 'Panduan penggunaan bot'],
        ['command' => 'bills', 'description' => 'Ringkasan tagihan aktif'],
        ['command' => 'status', 'description' => 'Status akun dan notifikasi'],
    ];

    private const ADMIN_COMMAND_MENU = [
        'help',
        'broadcast',
        'lang',
        'stats',
        'billstats',
        'linkinfo',
        'unlink',
    ];

    private const ADMIN_COMMAND_FALLBACK = [
        ['command' => 'help', 'description' => 'Panduan penggunaan bot'],
        ['command' => 'broadcast', 'description' => 'Kirim pengumuman ke seluruh pengguna'],
        ['command' => 'lang', 'description' => 'Ganti bahasa bot'],
        ['command' => 'stats', 'description' => 'Lihat statistik warga aktif'],
        ['command' => 'billstats', 'description' => 'Ringkasan tagihan warga (pending)'],
        ['command' => 'linkinfo', 'description' => 'Informasi status penautan'],
        ['command' => 'unlink', 'description' => 'Putuskan tautan akun Telegram'],
    ];

    private ?Api $bot = null;

    private string $timezone;

    public function __construct(
        private readonly TelegramSettings $settings,
        private readonly LoggerInterface $log
    ) {
        $this->timezone = config('app.timezone', 'Asia/Jakarta');
    }
    public function getBot(): Api
    {
        if ($this->bot === null) {
            $token = trim((string) ($this->settings->botToken() ?? ''));

            if ($token === '') {
                throw new RuntimeException('Telegram bot token belum dikonfigurasi.');
            }

            $this->bot = new Api($token);
        }

        return $this->bot;
    }

    public function handleUpdate(array $payload): void
    {
        $updateId = Arr::get($payload, 'update_id');
        $chatId = $this->extractChatId($payload);

        if ($chatId === null) {
            $this->log->warning('Telegram update diabaikan: chat_id tidak ditemukan.', [
                'update_id' => $updateId,
            ]);

            return;
        }

        $this->touchInteraction((string) $chatId);

        $text = $this->extractInputText($payload);

        if ($text === null || $text === '') {
            return;
        }

        $parsed = $this->parseCommand($text);
        $command = $parsed['command'];

        if ($command === null) {
            return;
        }

        $rateKey = sprintf('tg:%s', $chatId);
        if (RateLimiter::tooManyAttempts($rateKey, self::RATE_LIMIT_ATTEMPTS)) {
            $retryAfter = max(RateLimiter::availableIn($rateKey), 1);
            $this->sendText((string) $chatId, sprintf('⚠️ Kamu terlalu sering mengirim perintah. Coba lagi dalam %d detik.', $retryAfter));

            return;
        }

        RateLimiter::hit($rateKey, self::RATE_LIMIT_DECAY_SECONDS);

        $user = $this->findLinkedUserByChatId($chatId);
        $account = $user?->telegramAccount;
        $context = $this->baseContext((string) $chatId, $user, $account);
        $commandRecord = $this->findCommandRecord($command);

        $this->log->info('Telegram command received', [
            'command' => $command,
            'args' => $parsed['args'],
            'chat_id' => $chatId,
            'update_id' => $updateId,
            'user_id' => $user?->id,
        ]);

        $this->dispatchCommand(
            $command,
            $parsed['args'],
            (string) $chatId,
            $payload,
            $context,
            $commandRecord,
            $user,
            $account
        );
    }

    /**
     * @param array<int, string> $args
     * @param array<string, mixed> $payload
     */
    private function dispatchCommand(
        string $command,
        array $args,
        string $chatId,
        array $payload,
        array $context,
        ?TelegramCommand $commandRecord,
        ?User $user,
        ?TelegramAccount $account
    ): void {
        $requiresAdmin = false;

        if ($commandRecord) {
            $requiresAdmin = (bool) $commandRecord->is_admin_only;
        } elseif (in_array($command, ['stats', 'broadcast', 'billstats'], true)) {
            $requiresAdmin = true;
        }

        if ($requiresAdmin && ! $this->isAdmin($user)) {
            $this->sendText($chatId, 'Perintah ini hanya untuk admin.');

            return;
        }

        if (! $user) {
            if ($command === 'link') {
                $this->sendText($chatId, 'Perintah /link tidak tersedia untuk pengguna umum. Gunakan /start LINK-XXXX untuk menautkan akun kamu.');

                return;
            }

            if (! in_array($command, ['start', 'help'], true)) {
                $this->sendText($chatId, 'Silakan tautkan akun kamu terlebih dahulu menggunakan /start LINK-XXXX.');

                return;
            }
        }

        switch ($command) {
            case 'start':
            case 'link':
                $linkedUser = $this->maybeLinkAccount($chatId, $args, $payload);

                if ($linkedUser instanceof User) {
                    $linkedUser->load('telegramAccount');
                    $user = $linkedUser;
                    $account = $linkedUser->telegramAccount;
                }

                $context = $this->baseContext($chatId, $user, $account);
                $this->syncChatCommandMenu($chatId, $user, $account);
                $this->respondWithTemplate($chatId, $command, $commandRecord, $context);

                return;

            case 'help':
                $this->syncChatCommandMenu($chatId, $user, $account);
                $message = $this->buildHelpMessage($user, $account);

                $this->sendText($chatId, $message);

                return;

            case 'bills':
                if (! $user) {
                    $this->sendText($chatId, '🔒 Silakan tautkan akun kamu terlebih dahulu menggunakan /start LINK-XXXX.');

                    return;
                }

                $context = array_merge($context, $this->userBillsSummary((int) $user->id));
                $this->respondWithTemplate($chatId, $command, $commandRecord, $context);

                return;

            case 'bill':
                if (! $user) {
                    $this->sendText($chatId, '🔒 Silakan tautkan akun kamu terlebih dahulu menggunakan /start LINK-XXXX.');

                    return;
                }

                $billId = $args[0] ?? null;

                if ($billId === null || trim($billId) === '') {
                    $this->sendHTML($chatId, $this->buildBillSelectionMessage($user));

                    return;
                }

                $billKey = trim($billId);
                $numericId = ltrim($billKey, '#');

                $bill = Bill::query()
                    ->where('user_id', $user->id)
                    ->where(function ($query) use ($billKey, $numericId) {
                        if ($numericId !== '' && ctype_digit($numericId)) {
                            $query->orWhere('id', (int) $numericId);
                        }

                        $query->orWhereRaw('LOWER(invoice_number) = ?', [strtolower($billKey)]);

                        if ($numericId !== $billKey) {
                            $query->orWhereRaw('LOWER(invoice_number) = ?', [strtolower($numericId)]);
                        }
                    })
                    ->first();

                if (! $bill) {
                    $this->sendText($chatId, 'Tagihan tidak ditemukan atau bukan milik kamu.');

                    return;
                }

                $context['bill_id'] = (string) ($bill->invoice_number ?? $bill->id);
                $context['bill_title'] = $bill->title ?? '-';
                $description = $bill->description ? strip_tags($bill->description) : '-';
                $description = $description !== '-' ? Str::limit(preg_replace("/[\r\n]+/", "\n", trim($description)), 600) : '-';
                $context['bill_description'] = $description === '' ? '-' : $description;
                $context['status'] = ucfirst((string) ($bill->status ?? ''));
                $context['amount'] = $this->formatCurrency((float) $bill->amount);
                $context['due_date'] = $this->formatDate($bill->due_date);

                $detailTemplate = $commandRecord;
                if (! $detailTemplate || ! $detailTemplate->response_template) {
                    $detailTemplate = $this->findCommandRecord('bill');
                }

                $this->respondWithTemplate($chatId, 'bill', $detailTemplate, $context);

                return;

            case 'event':
            case 'events':
                $event = $this->firstUpcomingEvent();

                if (! $event) {
                    $this->sendText($chatId, 'Belum ada agenda terjadwal dalam waktu dekat.');

                    return;
                }

                $record = $commandRecord;

                if ($command === 'event' && $record === null) {
                    $record = $this->findCommandRecord('events');
                }

                $this->respondWithTemplate(
                    $chatId,
                    'events',
                    $record,
                    array_merge($context, $event)
                );

                return;

            case 'stats':
                $context['count'] = (string) User::query()->where('status', 'aktif')->count();
                $this->respondWithTemplate($chatId, $command, $commandRecord, $context);

                return;

            case 'billstats':
                $context = array_merge($context, $this->adminBillStatsSummary());
                $this->respondWithTemplate($chatId, $command, $commandRecord, $context);

                return;

            case 'status':
                $context['status'] = $user?->status ?? 'belum tertaut';
                $context['join_date'] = $user?->created_at ?: $context['join_date'];
                $this->respondWithTemplate($chatId, $command, $commandRecord, $context);

                return;
            case 'lang':
                $language = strtolower((string) ($args[0] ?? ''));

                if ($language === '') {
                    $this->respondWithTemplate($chatId, $command, $commandRecord, $context);

                    return;
                }

                if (! in_array($language, ['id', 'en'], true)) {
                    $this->sendText($chatId, 'Bahasa tidak didukung. Gunakan kode id atau en.');

                    return;
                }

                if ($account) {
                    $account->forceFill(['language_code' => $language])->save();
                    $context['language_code'] = $language;
                }

                $this->respondWithTemplate($chatId, $command, $commandRecord, $context);

                return;

            case 'unlink':
                if (! $user || ! $account) {
                    $this->sendText($chatId, 'Belum ada akun yang tertaut.');

                    return;
                }

                app(TelegramLinkService::class)->unlink($user);
                $this->syncChatCommandMenu($chatId, null, null);
                $this->respondWithTemplate($chatId, $command, $commandRecord, $this->baseContext($chatId, null, null));

                return;

            case 'unsubscribe':
                if (! $user || ! $account) {
                    $this->sendText($chatId, 'Belum ada akun yang tertaut.');

                    return;
                }

                $account->forceFill(['receive_notifications' => false])->save();
                $context['notification_status'] = 'nonaktif';
                $this->respondWithTemplate($chatId, $command, $commandRecord, $context);

                return;

            case 'subscribe':
                if (! $user || ! $account) {
                    $this->sendText($chatId, 'Belum ada akun yang tertaut.');

                    return;
                }

                $account->forceFill(['receive_notifications' => true])->save();
                $context['notification_status'] = 'aktif';
                $this->respondWithTemplate($chatId, $command, $commandRecord, $context);

                return;

            case 'contact':
                $this->respondWithTemplate($chatId, $command, $commandRecord, $context);

                return;

            case 'broadcast':
                $this->respondWithTemplate($chatId, $command, $commandRecord, $context);

                return;

            case 'linkinfo':
                if (! $user || ! $account) {
                    $this->sendText($chatId, 'Belum ada akun yang tertaut.');

                    return;
                }

                $context['status'] = $user->status ?? 'aktif';
                $context['notification_status'] = $account->receive_notifications ? 'aktif' : 'nonaktif';
                $this->respondWithTemplate($chatId, $command, $commandRecord, $context);

                return;

            default:
                if ($commandRecord && $commandRecord->response_template) {
                    $this->sendTemplate($chatId, $commandRecord->response_template, $context);

                    return;
                }

                $this->sendTemplate($chatId, self::DEFAULT_TEMPLATES['unknown'], $context);

                return;
        }
    }
    /**
     * @return array{command: string|null, args: array<int, string>}
     */
    public function parseCommand(string $text): array
    {
        $clean = trim(preg_replace('/\s+/', ' ', $text) ?? '');

        if ($clean === '' || ! str_starts_with($clean, '/')) {
            return [
                'command' => null,
                'args' => [],
            ];
        }

        $parts = explode(' ', $clean);
        $command = array_shift($parts) ?? '';

        $command = ltrim($command, '/');
        $command = strtolower(strtok($command, '@') ?: $command);

        $args = array_values(array_filter($parts, static fn ($part) => $part !== ''));

        return [
            'command' => $command !== '' ? $command : null,
            'args' => $args,
        ];
    }

    public function sendText(string $chatId, string $text, array $options = []): void
    {
        $this->dispatchMessage($chatId, $text, array_merge([
            'disable_web_page_preview' => true,
        ], $options));
    }

    public function sendHTML(string $chatId, string $html, array $options = []): void
    {
        $this->dispatchMessage($chatId, $html, array_merge([
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ], $options));
    }

    public function sendTemplate(string $chatId, string $template, array $context = [], array $options = []): void
    {
        $html = $this->renderTemplate($template, $context);
        $this->sendHTML($chatId, $html, $options);
    }

    public function setWebhook(?string $url = null, ?string $secret = null): array
    {
        $url ??= $this->settings->webhookUrl();
        $secret ??= $this->settings->webhookSecret();

        if ($secret !== null) {
            $secret = $this->sanitizeSecretToken($secret);
        }

        if (! $url) {
            throw new RuntimeException('Webhook URL tidak tersedia.');
        }

        $options = [];
        if ($secret !== null && $secret !== '') {
            $options['secret_token'] = $secret;
        }

        $params = array_merge(['url' => $url], $options);

        try {
            $response = $this->getBot()->setWebhook($params);

            return $this->responseToArray($response);
        } catch (Throwable $e) {
            $this->log->error('Gagal mengatur webhook Telegram', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function deleteWebhook(): array
    {
        try {
            $response = $this->getBot()->deleteWebhook();

            return $this->responseToArray($response);
        } catch (Throwable $e) {
            $this->log->error('Gagal menghapus webhook Telegram', [
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function getWebhookInfo(): array
    {
        try {
            $response = $this->getBot()->getWebhookInfo();

            return $this->responseToArray($response);
        } catch (Throwable $e) {
            $this->log->error('Gagal mengambil informasi webhook Telegram', [
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function listLocalCommands(?string $languageCode = null, string $scope = 'default'): Collection
    {
        $commands = TelegramCommand::query()
            ->where(function ($query) {
                $query->where('is_active', true)->orWhere('is_system', true);
            })
            ->orderBy('command')
            ->get()
            ->filter(function (TelegramCommand $command) use ($languageCode) {
                if (! $languageCode) {
                    return true;
                }

                $languages = Arr::get($command->meta ?? [], 'languages');

                return ! is_array($languages) || in_array($languageCode, $languages, true);
            })
            ->map(function (TelegramCommand $command) {
                return [
                    'command' => $command->command,
                    'description' => mb_strimwidth((string) ($command->description ?? ''), 0, 256),
                    'is_admin_only' => (bool) $command->is_admin_only,
                    'is_system' => (bool) $command->is_system,
                ];
            });

        if ($scope === 'default') {
            $commands = $commands
                ->filter(static fn (array $command) => in_array($command['command'], ['start', 'help'], true))
                ->values();
        }

        return $commands->values();
    }

    public function pushLocalCommandsToTelegram(?string $languageCode = null, string $scope = 'default'): void
    {
        $commands = $this->listLocalCommands($languageCode, $scope);

        if ($commands->isEmpty()) {
            $this->log->info('Tidak ada perintah aktif yang perlu dikirim ke Telegram.');

            return;
        }

        $commands = $commands
            ->sortByDesc(fn (array $command) => $command['is_admin_only'])
            ->values();

        $adminCommands = $commands->filter(static fn (array $command) => $command['is_admin_only']);
        $selectedCommands = $adminCommands->isNotEmpty() ? $adminCommands : $commands;

        if ($adminCommands->isNotEmpty()) {
            $helpCommand = $commands->firstWhere('command', 'help');

            if ($helpCommand) {
                $selectedCommands = $selectedCommands->push($helpCommand);
            }
        }

        $selectedCommands = $selectedCommands
            ->unique('command')
            ->sortBy(fn (array $command) => $command['command'])
            ->values();

        $payload = $selectedCommands
            ->map(fn (array $command) => Arr::only($command, ['command', 'description']))
            ->values()
            ->all();

        $options = [
            'scope' => ['type' => $scope],
        ];

        if ($languageCode) {
            $options['language_code'] = $languageCode;
        }

        try {
            $this->getBot()->setMyCommands(array_merge([
                'commands' => $payload,
            ], $options));
        } catch (Throwable $e) {
            $this->log->error('Gagal mengirim daftar perintah ke Telegram', [
                'payload' => $payload,
                'options' => $options,
                'message' => $e->getMessage(),
            ]);
        }
    }
    public function pullCommandsFromTelegram(?string $languageCode = null, string $scope = 'default'): array
    {
        $options = [
            'scope' => ['type' => $scope],
        ];

        if ($languageCode) {
            $options['language_code'] = $languageCode;
        }

        try {
            $response = $this->getBot()->getMyCommands($options);

            return $this->responseToArray($response);
        } catch (Throwable $e) {
            $this->log->error('Gagal mengambil daftar perintah dari Telegram', [
                'options' => $options,
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function reconcileCommands(?string $languageCode = null, string $scope = 'default'): array
    {
        $local = $this->listLocalCommands($languageCode, $scope);
        $remote = collect($this->pullCommandsFromTelegram($languageCode, $scope));

        $localMap = $local->mapWithKeys(fn (array $command) => [$command['command'] => $command]);
        $remoteMap = $remote
            ->filter(fn ($command) => is_array($command) && isset($command['command']))
            ->mapWithKeys(fn ($command) => [$command['command'] => $command]);

        $toAdd = $localMap->keys()
            ->diff($remoteMap->keys())
            ->map(fn ($name) => $localMap->get($name))
            ->values()
            ->all();

        $toDelete = $remoteMap->keys()
            ->diff($localMap->keys())
            ->map(fn ($name) => $remoteMap->get($name))
            ->values()
            ->all();

        $toUpdate = $localMap->keys()
            ->intersect($remoteMap->keys())
            ->filter(function ($name) use ($localMap, $remoteMap) {
                return trim((string) ($localMap[$name]['description'] ?? '')) !== trim((string) ($remoteMap[$name]['description'] ?? ''));
            })
            ->map(fn ($name) => [
                'local' => $localMap->get($name),
                'remote' => $remoteMap->get($name),
                ])
            ->values()
            ->all();

        return [
            'toAdd' => $toAdd,
            'toUpdate' => $toUpdate,
            'toDelete' => $toDelete,
        ];
    }

    public function notifyReminder(User $user, string $subject, string $body, array $metadata = []): void
    {
        $account = $user->telegramAccount;

        if (! $account || $account->unlinked_at || ! $account->receive_notifications) {
            return;
        }

        $chatId = (string) ($account->telegram_chat_id ?? '');

        if ($chatId === '') {
            return;
        }

        $lines = [
            sprintf('<b>%s</b>', $this->escapeValue($subject)),
            $this->escapeValue($body),
        ];

        foreach ($metadata as $key => $value) {
            $lines[] = sprintf(
                '• <b>%s:</b> %s',
                $this->escapeValue((string) $key),
                $this->escapeValue((string) $value)
            );
        }

        $this->sendHTML($chatId, implode("\n", $lines));
    }

    private function dispatchMessage(string $chatId, string $text, array $options): void
    {
        try {
            $payload = array_merge([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ], $options);

            $this->getBot()->sendMessage($payload);
        } catch (RuntimeException $e) {
            $this->log->warning('Telegram bot belum siap mengirim pesan', [
                'reason' => $e->getMessage(),
            ]);
        } catch (TelegramSDKException|Throwable $e) {
            $this->log->error('Gagal mengirim pesan Telegram', [
                'chat_id' => $chatId,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function syncChatCommandMenu(string $chatId, ?User $user, ?TelegramAccount $account): void
    {
        $commands = $this->commandSetForUser($user, $account);

        if ($commands->isEmpty()) {
            $this->clearChatCommandMenu($chatId);

            return;
        }

        $payload = $commands
            ->map(fn (array $command) => Arr::only($command, ['command', 'description']))
            ->values()
            ->all();

        $this->clearChatCommandMenu($chatId);

        $scopes = [
            [
                'commands' => $payload,
                'scope' => [
                    'type' => 'chat',
                    'chat_id' => (int) $chatId,
                ],
            ],
        ];

        if ($account?->language_code) {
            $scopes[] = [
                'commands' => $payload,
                'scope' => [
                    'type' => 'chat',
                    'chat_id' => (int) $chatId,
                ],
                'language_code' => $account->language_code,
            ];
        }

        foreach ($scopes as $options) {
            try {
                $this->getBot()->setMyCommands($options);
            } catch (Throwable $e) {
                $this->log->warning('Gagal memperbarui daftar perintah chat', [
                    'chat_id' => $chatId,
                    'message' => $e->getMessage(),
                    'options' => Arr::except($options, 'commands'),
                ]);
            }
        }
    }

    private function clearChatCommandMenu(string $chatId): void
    {
        try {
            $this->getBot()->deleteMyCommands([
                'scope' => [
                    'type' => 'chat',
                    'chat_id' => (int) $chatId,
                ],
            ]);
        } catch (Throwable $e) {
            $this->log->warning('Gagal menghapus daftar perintah khusus chat', [
                'chat_id' => $chatId,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function commandSetForUser(?User $user, ?TelegramAccount $account): Collection
    {
        if (! $user) {
            return collect(self::GUEST_COMMAND_MENU);
        }

        $allCommands = $this->listLocalCommands($account?->language_code, 'chat');
        $isAdmin = $this->isAdmin($user);

        $available = $allCommands->filter(static function (array $command) use ($isAdmin) {
            if ($isAdmin) {
                return true;
            }

            return ! $command['is_admin_only'];
        });

        if ($isAdmin) {
            return $this->buildCommandMenu(
                $available,
                self::ADMIN_COMMAND_MENU,
                self::ADMIN_COMMAND_FALLBACK,
                false
            );
        }

        return $this->buildCommandMenu(
            $available,
            self::RESIDENT_COMMAND_MENU,
            self::RESIDENT_COMMAND_FALLBACK,
            true
        );
    }

    private function buildCommandMenu(
        Collection $commands,
        array $preferredOrder,
        array $fallback,
        bool $appendUnmatched
    ): Collection {
        $normalized = $commands
            ->map(static function (array $command) {
                return [
                    'command' => $command['command'],
                    'description' => $command['description'] ?? '',
                ];
            })
            ->keyBy('command');

        $ordered = collect($preferredOrder)
            ->map(static function (string $command) use ($normalized) {
                return $normalized->get($command);
            })
            ->filter()
            ->values();

        if ($appendUnmatched) {
            $ordered = $ordered
                ->concat($normalized->except($preferredOrder)->values())
                ->unique('command')
                ->values();
        }

        if ($ordered->isNotEmpty()) {
            return $ordered->unique('command')->values();
        }

        return collect($fallback)
            ->map(static function (array $command) {
                return [
                    'command' => $command['command'],
                    'description' => $command['description'] ?? '',
                ];
            })
            ->unique('command')
            ->values();
    }

    private function buildHelpMessage(?User $user, ?TelegramAccount $account): string
    {
        if (! $user) {
            $lines = [
                '<b>Bot belum tertaut.</b>',
                $this->escapeValue('Ikuti langkah berikut untuk menghubungkan bot:'),
                $this->escapeValue('1. Login ke portal warga melalui website.'),
                $this->escapeValue('2. Buka menu Profil lalu pilih "Buat Kode LINK".'),
                '3. Kirim perintah <code>/start LINK-XXXX</code> ke bot ini dalam 30 menit.',
                $this->escapeValue('Setelah tersambung, ketik /help lagi untuk melihat perintah sesuai peran.'),
            ];

            return implode("\n", $lines);
        }

        $commands = $this->commandSetForUser($user, $account);

        $title = $this->isAdmin($user)
            ? '<b>Perintah admin yang tersedia:</b>'
            : '<b>Perintah yang bisa kamu gunakan:</b>';

        $lines = array_filter([
            $title,
            $this->formatCommandList($commands),
            $this->escapeValue('Gunakan /start kapan saja untuk memperbarui daftar perintah.'),
        ]);

        return implode("\n\n", $lines);
    }

    private function buildAvailableCommandsText(?User $user, ?TelegramAccount $account): string
    {
        $commands = $this->commandSetForUser($user, $account);

        return $this->formatCommandList($commands);
    }

    private function formatCommandList(Collection $commands): string
    {
        if ($commands->isEmpty()) {
            return $this->escapeValue('-');
        }

        return $commands
            ->map(function (array $command) {
                $description = $command['description'] ?? '';
                $description = $description !== '' ? $description : 'Tidak ada deskripsi.';

                $commandLabel = sprintf('/%s', $command['command']);

                return sprintf(
                    '<code>%s</code> — %s',
                    $this->escapeValue($commandLabel),
                    $this->escapeValue($description)
                );
            })
            ->implode("\n");
    }

    private function respondWithTemplate(string $chatId, string $command, ?TelegramCommand $commandRecord, array $context): void
    {
        if ($commandRecord && $commandRecord->response_template) {
            $this->sendTemplate($chatId, $commandRecord->response_template, $context);

            return;
        }

        $template = self::DEFAULT_TEMPLATES[$command] ?? self::DEFAULT_TEMPLATES['unknown'];
        $this->sendTemplate($chatId, $template, $context);
    }
    private function renderTemplate(string $template, array $context): string
    {
        $normalized = preg_replace('/<br\s*\/?\>/i', "\n", $template) ?? $template;
        $data = array_merge($this->templateDefaults(), $context);

        $filled = preg_replace_callback(
            '/(?<!\w):\s*([A-Z0-9_]+)/i',
            function (array $matches) use ($data) {
                $key = Str::snake(strtolower($matches[1]));

                if (array_key_exists($key, $data)) {
                    return $this->stringifyTemplateValue($data[$key]);
                }

                return $matches[0];
            },
            $normalized
        );

        $escaped = htmlspecialchars($filled, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $converted = $this->convertMarkdownToHtml($escaped);

        return str_replace(["\r\n", "\r"], "\n", $converted);
    }

    private function templateDefaults(): array
    {
        return [
            'user_name' => 'Warga',
            'user_id' => '-',
            'status' => 'aktif',
            'amount' => 'Rp 0',
            'total_amount' => 'Rp 0',
            'due_date' => Carbon::now($this->timezone),
            'next_due_date' => Carbon::now($this->timezone),
            'count' => '0',
            'event_title' => '-',
            'event_date' => '-',
            'location' => '-',
            'bill_id' => '-',
            'bills_list' => '-',
            'bill_title' => '-',
            'bill_description' => '-',
            'join_date' => Carbon::now($this->timezone),
            'chat_id' => '-',
            'language_code' => $this->settings->defaultLanguage(),
            'notification_status' => 'aktif',
            'contact_email' => $this->settings->contactEmail() ?? '-',
            'contact_whatsapp' => $this->settings->contactWhatsapp() ?? '-',
        ];
    }

    private function stringifyTemplateValue(mixed $value): string
    {
        if ($value instanceof Carbon) {
            return $value->copy()->setTimezone($this->timezone)->format('d M Y H:i');
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->setTimezone($this->timezone)->format('d M Y H:i');
        }

        if (is_array($value)) {
            return implode(', ', array_map(fn ($item) => $this->stringifyTemplateValue($item), $value));
        }

        return (string) $value;
    }

    private function convertMarkdownToHtml(string $text): string
    {
        $converted = preg_replace('/\*\*(.+?)\*\*/s', '<b>$1</b>', $text) ?? $text;
        $converted = preg_replace('/\*(.+?)\*/s', '<i>$1</i>', $converted) ?? $converted;
        $converted = preg_replace('/`(.+?)`/s', '<code>$1</code>', $converted) ?? $converted;

        return $converted;
    }

    private function escapeValue(mixed $value): string
    {
        return htmlspecialchars($this->stringifyTemplateValue($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function extractChatId(array $payload): int|string|null
    {
        foreach ([
            'message.chat.id',
            'edited_message.chat.id',
            'channel_post.chat.id',
            'callback_query.message.chat.id',
        ] as $path) {
            $value = Arr::get($payload, $path);

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    private function extractInputText(array $payload): ?string
    {
        foreach ([
            'message.text',
            'message.caption',
            'edited_message.text',
            'callback_query.data',
            'channel_post.text',
        ] as $path) {
            $value = Arr::get($payload, $path);

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    private function touchInteraction(string $chatId): void
    {
        TelegramAccount::query()
            ->where('telegram_chat_id', $chatId)
            ->update(['last_interaction_at' => now()]);
    }

    private function findCommandRecord(string $command): ?TelegramCommand
    {
        return TelegramCommand::query()
            ->where('command', $command)
            ->where(function ($query) {
                $query->where('is_active', true)->orWhere('is_system', true);
            })
            ->first();
    }

    private function countActiveCommands(): int
    {
        return TelegramCommand::query()
            ->where('is_active', true)
            ->count();
    }
    /**
     * @param array<int, string> $args
     * @param array<string, mixed> $payload
     */
    private function maybeLinkAccount(string $chatId, array $args, array $payload): ?User
    {
        $tokenArgument = $args[0] ?? null;

        if (! $tokenArgument || ! Str::startsWith(Str::upper($tokenArgument), 'LINK-')) {
            return null;
        }

        $linkToken = TelegramLinkToken::query()
            ->whereRaw('UPPER(token) = ?', [Str::upper($tokenArgument)])
            ->active()
            ->first();

        if (! $linkToken) {
            $this->sendText($chatId, 'Token penautan tidak ditemukan atau sudah kedaluwarsa.');

            return null;
        }

        $profile = $this->extractUserProfile($payload, $chatId);

        if (! $profile['telegram_user_id']) {
            $this->sendText($chatId, 'Gagal membaca data pengguna Telegram. Coba lagi.');

            return null;
        }

        try {
            $account = app(TelegramLinkService::class)->linkFromToken($linkToken, $profile);
        } catch (RuntimeException $e) {
            $this->sendText($chatId, 'Gagal menautkan akun: ' . $e->getMessage());

            return null;
        }

        return $account->user;
    }

    /**
     * @return array{
     *     telegram_user_id: int|null,
     *     telegram_chat_id: string,
     *     username: string|null,
     *     first_name: string|null,
     *     last_name: string|null,
     *     language_code: string|null
     * }
     */
    private function extractUserProfile(array $payload, string $chatId): array
    {
        $from = Arr::get($payload, 'message.from')
            ?? Arr::get($payload, 'edited_message.from')
            ?? Arr::get($payload, 'callback_query.from')
            ?? [];

        $telegramUserId = Arr::get($from, 'id');

        return [
            'telegram_user_id' => $telegramUserId ? (int) $telegramUserId : null,
            'telegram_chat_id' => $chatId,
            'username' => Arr::get($from, 'username'),
            'first_name' => Arr::get($from, 'first_name'),
            'last_name' => Arr::get($from, 'last_name'),
            'language_code' => Arr::get($from, 'language_code'),
        ];
    }

    private function findLinkedUserByChatId(int|string $chatId): ?User
    {
        return User::query()
            ->select('users.*')
            ->join('telegram_accounts', 'telegram_accounts.user_id', '=', 'users.id')
            ->where('telegram_accounts.telegram_chat_id', $chatId)
            ->whereNull('telegram_accounts.unlinked_at')
            ->first();
    }

    private function isAdmin(?User $user): bool
    {
        return $user ? $user->isAdmin() : false;
    }

    private function firstUpcomingEvent(): ?array
    {
        $event = Event::query()
            ->where('status', 'scheduled')
            ->where('start_at', '>=', Carbon::now($this->timezone))
            ->orderBy('start_at')
            ->first();

        if (! $event) {
            return null;
        }

        return [
            'event_title' => $event->title ?? '-',
            'event_date' => $event->start_at ? $this->formatDate($event->start_at) : '-',
            'location' => $event->location ?? '-',
        ];
    }

    private function userBillsSummary(int $userId): array
    {
        $bills = Bill::query()
            ->where('user_id', $userId)
            ->where('status', '!=', 'paid')
            ->orderBy('due_date')
            ->get();

        $count = $bills->count();
        $total = $bills->sum('amount');
        $nearestDue = $bills->firstWhere('due_date', '!=', null)?->due_date;

        $list = $bills->map(function (Bill $bill, int $index) {
            $identifier = $bill->invoice_number ?: '#' . $bill->id;
            $amount = $this->formatCurrency((float) $bill->amount);
            $due = $bill->due_date ? $this->formatDate($bill->due_date) : '-';
            $status = ucfirst((string) ($bill->status ?? 'menunggak'));

            return sprintf('%d. %s — %s — Jatuh tempo %s (%s)', $index + 1, $identifier, $amount, $due, $status);
        })->implode("\n");

        if ($list === '') {
            $list = 'Tidak ada tagihan aktif. Terima kasih sudah membayar tepat waktu!';
        }

        return [
            'count' => (string) $count,
            'amount' => $this->formatCurrency((float) $total),
            'total_amount' => $this->formatCurrency((float) $total),
            'due_date' => $nearestDue ? $this->formatDate($nearestDue) : '-',
            'next_due_date' => $nearestDue ? $this->formatDate($nearestDue) : '-',
            'status' => $count > 0 ? 'menunggak' : 'lunas',
            'bills_list' => $list,
        ];
    }

    private function adminBillStatsSummary(): array
    {
        $pendingQuery = Bill::query()->where('status', '!=', 'paid');

        $totalCount = (clone $pendingQuery)->count();
        $totalAmount = (clone $pendingQuery)->sum('amount');
        $nextDueDate = (clone $pendingQuery)
            ->whereNotNull('due_date')
            ->orderBy('due_date')
            ->value('due_date');

        $topBills = (clone $pendingQuery)
            ->with('user')
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_date')
            ->orderByDesc('amount')
            ->limit(10)
            ->get();

        $list = $topBills->map(function (Bill $bill, int $index) {
            $code = $bill->invoice_number ?: '#' . $bill->id;
            $amount = $this->formatCurrency((float) $bill->amount);
            $due = $bill->due_date ? $this->formatDate($bill->due_date) : '-';
            $status = ucfirst((string) ($bill->status ?? 'pending'));
            $owner = $bill->user?->name ?? 'Tidak diketahui';

            return sprintf(
                '%d. %s • %s • Jatuh tempo %s • %s (%s)',
                $index + 1,
                $code,
                $amount,
                $due,
                $owner,
                $status
            );
        })->implode("\n");

        if ($list === '') {
            $list = 'Tidak ada tagihan pending saat ini.';
        }

        return [
            'billstats_count' => (string) $totalCount,
            'billstats_total_amount' => $this->formatCurrency((float) $totalAmount),
            'billstats_next_due' => $nextDueDate ? $this->formatDate($nextDueDate) : '-',
            'billstats_list' => $list,
        ];
    }

    private function baseContext(string $chatId, ?User $user, ?TelegramAccount $account): array
    {
        $account ??= $user?->telegramAccount;
        $role = $user?->role;
        $status = $user?->status ?? 'belum tertaut';

        if ($role === 'admin') {
            $status = 'admin';
        }

        $roleLabel = 'Pengguna';
        $audienceLabel = 'warga';

        if ($role === 'admin') {
            $roleLabel = 'Admin';
            $audienceLabel = 'pengurus';
        } elseif ($role === 'warga') {
            $roleLabel = 'Warga';
            $audienceLabel = 'warga';
        }

        return [
            'chat_id' => $chatId,
            'user_name' => $user?->name ?? 'Pengguna',
            'user_id' => $user?->id ? (string) $user->id : '-',
            'status' => $status,
            'role' => $role ?? 'guest',
            'is_admin' => $role === 'admin' ? 'ya' : 'tidak',
            'role_label' => $roleLabel,
            'audience_label' => $audienceLabel,
            'join_date' => $user?->created_at ?: Carbon::now($this->timezone),
            'language_code' => $account?->language_code ?? $this->settings->defaultLanguage(),
            'notification_status' => $account && $account->receive_notifications ? 'aktif' : 'nonaktif',
            'contact_email' => $this->settings->contactEmail() ?? '-',
            'contact_whatsapp' => $this->settings->contactWhatsapp() ?? '-',
            'amount' => 'Rp 0',
            'total_amount' => 'Rp 0',
            'due_date' => Carbon::now($this->timezone),
            'next_due_date' => Carbon::now($this->timezone),
            'count' => '0',
            'event_title' => '-',
            'event_date' => '-',
            'location' => '-',
            'bill_id' => '-',
            'bills_list' => '-',
            'bill_title' => '-',
            'bill_description' => '-',
        ];
    }

    private function formatDate(mixed $value, string $format = 'd M Y H:i'): string
    {
        if ($value instanceof Carbon) {
            return $value->copy()->setTimezone($this->timezone)->format($format);
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->setTimezone($this->timezone)->format($format);
        }

        if ($value === null || $value === '') {
            return '-';
        }

        try {
            return Carbon::parse($value)->setTimezone($this->timezone)->format($format);
        } catch (Throwable) {
            return (string) $value;
        }
    }

    private function formatCurrency(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    private function sanitizeSecretToken(string $secret): string
    {
        $secret = trim($secret);

        if ($secret === '') {
            return '';
        }

        if (! preg_match('/^[A-Za-z0-9_.-]{1,256}$/', $secret)) {
            throw new RuntimeException('Webhook secret hanya boleh mengandung huruf, angka, titik (.), strip (-), dan underscore (_) dengan panjang maksimal 256 karakter.');
        }

        return $secret;
    }

    private function buildBillSelectionMessage(User $user): string
    {
        $bills = Bill::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        if ($bills->isEmpty()) {
            return "✅ Tidak ada tagihan yang perlu ditampilkan saat ini.\nTerima kasih sudah menjaga kewajiban pembayaran tepat waktu!";
        }

        $lines = $bills->map(function (Bill $bill) {
            $code = $bill->invoice_number ?: ('#' . $bill->id);
            $amount = $this->formatCurrency((float) $bill->amount);
            $due = $bill->due_date ? $this->formatDate($bill->due_date) : '-';

            return sprintf(
                "• %s — %s — Jatuh tempo %s",
                htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($amount, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($due, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            );
        })->implode("\n");

        $example = htmlspecialchars($bills->first()->invoice_number ?: ('#' . $bills->first()->id), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return sprintf(
            "Silakan pilih tagihan yang ingin kamu lihat detailnya:\n%s\n\nContoh: <code>/bill %s</code>",
            $lines,
            $example
        );
    }

    private function shouldRefreshSystemTemplate(string $commandName, ?string $currentTemplate): bool
    {
        if ($currentTemplate === null || trim($currentTemplate) === '') {
            return true;
        }

        $commandName = strtolower($commandName);

        if ($commandName === 'bills') {
            return ! Str::contains($currentTemplate, ':bills_list') || ! Str::contains($currentTemplate, ':total_amount');
        }

        if ($commandName === 'bill') {
            return ! Str::contains($currentTemplate, ':bill_id')
                || ! Str::contains($currentTemplate, ':amount')
                || ! Str::contains($currentTemplate, ':bill_description');
        }

        if (in_array($commandName, ['start', 'link'], true)) {
            return Str::contains($currentTemplate, 'layanan warga');
        }

        return false;
    }

    private function responseToArray(mixed $response): array
    {
        if (is_array($response)) {
            return $response;
        }

        if (is_object($response)) {
            if (method_exists($response, 'toArray')) {
                /** @var array<string, mixed> $array */
                $array = $response->toArray();

                return $array;
            }

            if (method_exists($response, 'getResult')) {
                $result = $response->getResult();

                return is_array($result) ? $result : (array) $result;
            }

            return (array) $response;
        }

        return [];
    }

    public static function defaultTemplates(): array
    {
        return self::DEFAULT_TEMPLATES;
    }

    public static function defaultCommandDefinitions(): array
    {
        return self::DEFAULT_COMMAND_DEFINITIONS;
    }

    public function ensureSystemCommandsSeeded(): Collection
    {
        $commands = collect();

        foreach (self::DEFAULT_COMMAND_DEFINITIONS as $name => $definition) {
            $command = TelegramCommand::query()->firstOrNew(['command' => $name]);
            $isNew = ! $command->exists;

            $command->type = TelegramCommand::TYPE_SYSTEM;
            $command->is_system = true;
            $command->is_active = true;
            $command->is_admin_only = (bool) ($definition['is_admin_only'] ?? false);

            $description = trim((string) ($definition['description'] ?? ''));
            if ($description !== '' && ($isNew || ! $command->description)) {
                $command->description = $description;
            }

            $defaultTemplate = self::DEFAULT_TEMPLATES[$name] ?? null;
            if ($defaultTemplate !== null && $this->shouldRefreshSystemTemplate($name, $command->response_template)) {
                $command->response_template = $defaultTemplate;
            }

            if ($isNew && isset($definition['meta'])) {
                $command->meta = $definition['meta'];
            }

            $command->save();
            $commands->push($command);
        }

        return $commands;
    }
}

