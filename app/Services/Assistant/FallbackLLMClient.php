<?php

namespace App\Services\Assistant;

use App\Services\Assistant\Exceptions\LLMClientException;
use Illuminate\Support\Facades\Log;

class FallbackLLMClient implements LLMClient
{
    /**
     * @var array<int, LLMClient>
     */
    private array $primaryClients;

    private LLMClient $fallbackClient;

    /**
     * @param array<int, LLMClient> $primaryClients
     */
    public function __construct(array $primaryClients, LLMClient $fallbackClient)
    {
        $this->primaryClients = $primaryClients;
        $this->fallbackClient = $fallbackClient;
    }

    public function chat(array $messages, array $tools = []): array
    {
        foreach ($this->primaryClients as $client) {
            try {
                $response = $client->chat($messages, $tools);

                if (!isset($response['provider'])) {
                    $response['provider'] = class_basename($client);
                }

                return $response;
            } catch (LLMClientException $exception) {
                Log::warning('LLM client failed, trying next fallback', [
                    'client' => get_class($client),
                    'error' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                ]);
                continue;
            }
        }

        $response = $this->fallbackClient->chat($messages, $tools);

        if (!isset($response['provider'])) {
            $response['provider'] = class_basename($this->fallbackClient);
        }

        return $response;
    }

    public function supportsStreaming(): bool
    {
        foreach ($this->primaryClients as $client) {
            if ($client->supportsStreaming()) {
                return true;
            }
        }

        return $this->fallbackClient->supportsStreaming();
    }

    public function stream(array $messages, array $tools, callable $onEvent): array
    {
        foreach ($this->primaryClients as $client) {
            try {
                if ($client->supportsStreaming()) {
                    $response = $client->stream($messages, $tools, $onEvent);
                    if (!isset($response['provider'])) {
                        $response['provider'] = class_basename($client);
                    }
                    return $response;
                }

                $response = $client->chat($messages, $tools);

                if (isset($response['content'])) {
                    $onEvent('token', (string) $response['content']);
                }

                if (!isset($response['provider'])) {
                    $response['provider'] = class_basename($client);
                }

                return $response;
            } catch (LLMClientException $exception) {
                Log::warning('LLM client failed during streaming, trying next fallback', [
                    'client' => get_class($client),
                    'error' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                ]);
            }
        }

        $response = $this->fallbackClient->stream($messages, $tools, $onEvent);

        if (!isset($response['provider'])) {
            $response['provider'] = class_basename($this->fallbackClient);
        }

        return $response;
    }

    public function embed(string $text): ?array
    {
        foreach ($this->primaryClients as $client) {
            try {
                $vector = $client->embed($text);
                if ($vector !== null) {
                    return $vector;
                }
            } catch (LLMClientException $exception) {
                Log::warning('LLM embedding failed, trying next fallback', [
                    'client' => get_class($client),
                    'error' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                ]);
            }
        }

        return $this->fallbackClient->embed($text);
    }
}
