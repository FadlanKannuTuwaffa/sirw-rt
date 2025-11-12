<?php

namespace App\Http\Controllers;

use App\Services\Telegram\TelegramBotService;
use App\Services\Telegram\TelegramSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, TelegramBotService $bot, TelegramSettings $settings): JsonResponse
    {
        $secret = trim((string) ($settings->webhookSecret() ?? ''));

        if ($secret === '') {
            Log::error('Telegram webhook rejected: secret token not configured', [
                'path' => $request->path(),
                'ip' => $request->ip(),
            ]);

            return response()->json(['ok' => false, 'reason' => 'Secret token not configured'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $headerSecret = $request->header('X-Telegram-Bot-Api-Secret-Token');
        $headerSecret = $headerSecret !== null ? trim($headerSecret) : null;

        if ($headerSecret === null) {
            Log::warning('Telegram webhook rejected: secret header missing', [
                'path' => $request->path(),
                'ip' => $request->ip(),
            ]);

            return response()->json(['ok' => false, 'reason' => 'Missing secret token'], Response::HTTP_FORBIDDEN);
        }

        if (! hash_equals($secret, $headerSecret)) {
            Log::warning('Telegram webhook rejected: invalid secret token', [
                'path' => $request->path(),
                'ip' => $request->ip(),
            ]);

            return response()->json(['ok' => false, 'reason' => 'Invalid secret token'], Response::HTTP_FORBIDDEN);
        }

        if ($request->isMethod('get')) {
            return response()->json(['ok' => true, 'message' => 'Webhook endpoint healthy']);
        }

        if (! $request->isJson()) {
            Log::warning('Telegram webhook rejected: non JSON payload', [
                'content_type' => $request->headers->get('content-type'),
            ]);

            return response()->json(['ok' => false, 'reason' => 'Invalid secret token'], 403);
        }

        $payload = $request->json()->all();

        if (config('app.debug')) {
            Log::debug('Telegram webhook update received', [
                'path' => $request->path(),
                'update_id' => $payload['update_id'] ?? null,
                'keys' => array_keys($payload),
            ]);
        }

        $bot->handleUpdate($payload);

        return response()->json(['ok' => true]);
    }
}
