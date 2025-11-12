<?php

namespace App\Services\Telegram;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TelegramClient
{
    private ?string $token;

    public function __construct(
        private readonly TelegramSettings $settings,
        private readonly HttpFactory $http
    ) {
        $this->token = $this->settings->botToken();
    }

    public function ensureToken(): void
    {
        if (empty($this->token)) {
            throw new RuntimeException('Telegram bot token belum dikonfigurasi.');
        }
    }

    public function sendRequest(string $method, array $payload = []): ?array
    {
        try {
            $this->ensureToken();
        } catch (RuntimeException $e) {
            Log::warning('Telegram request aborted: ' . $e->getMessage());
            return null;
        }

        $url = sprintf('https://api.telegram.org/bot%s/%s', $this->token, $method);

        /** @var Response $response */
        $response = $this->http->asJson()->post($url, $payload);

        if (! $response->successful()) {
            Log::warning('Telegram API request failed', [
                'method' => $method,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return $response->json();
    }

    public function sendMessage(int|string $chatId, string $text, array $options = []): ?array
    {
        $payload = array_merge([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $options['parse_mode'] ?? 'HTML',
        ], $options);

        return $this->sendRequest('sendMessage', $payload);
    }

    public function setMyCommands(array $commands, array $scope = []): ?array
    {
        $payload = [
            'commands' => $commands,
        ];

        if (! empty($scope)) {
            $payload['scope'] = $scope;
        }

        return $this->sendRequest('setMyCommands', $payload);
    }

    public function deleteWebhook(): ?array
    {
        return $this->sendRequest('deleteWebhook');
    }

    public function setWebhook(string $url, array $options = []): ?array
    {
        $payload = array_merge(['url' => $url], $options);

        return $this->sendRequest('setWebhook', $payload);
    }
}
