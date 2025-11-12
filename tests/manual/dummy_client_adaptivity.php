<?php

use App\Models\User;
use App\Services\Assistant\DummyClient;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../../bootstrap/app.php';

/** @var Kernel $kernel */
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$userId = User::query()->value('id');

if (!$userId) {
    echo "Tidak ada user terdaftar untuk menjalankan tes.\n";
    exit(1);
}

Auth::loginUsingId($userId);

DummyClient::resetConversationState();

/** @var DummyClient $client */
$client = app(DummyClient::class);
$history = [];

$talk = function (string $message) use (&$history, $client): string {
    $history[] = ['role' => 'user', 'content' => $message];
    $response = $client->chat($history);
    $content = trim((string) ($response['content'] ?? ''));
    $history[] = ['role' => 'assistant', 'content' => $content];

    return $content;
};

$baselineGreeting = $talk('Hai, bisa bantu cek tagihan?');
$talk('kamu salah menjawab');
$styleAck = $talk('Jawab santai aja ya, pakai emoji biar enak dibaca.');
$postCorrectionGreeting = $talk('Hai lagi, bantu cek info terbaru dong.');

$latestEvent = DB::table('assistant_correction_events')
    ->orderByDesc('id')
    ->first();

$stylePref = DB::table('user_style_prefs')
    ->where('user_id', $userId)
    ->orderByDesc('updated_at')
    ->first();

echo "=== DummyClient Adaptivity Test ===\n";
echo "User ID         : {$userId}\n\n";

echo "[1] Baseline greeting (sebelum koreksi gaya):\n{$baselineGreeting}\n\n";
echo "[2] Bot ack setelah koreksi gaya:\n{$styleAck}\n\n";
echo "[3] Greeting setelah koreksi (harus terasa santai + emoji):\n{$postCorrectionGreeting}\n\n";

if ($latestEvent) {
    echo "--- Recorded Correction Event ---\n";
    echo "Event ID        : {$latestEvent->id}\n";
    echo "Scope/Type      : {$latestEvent->scope} / {$latestEvent->correction_type}\n";
    echo "Tone Preference : {$latestEvent->tone_preference}\n";
    echo "Patch Rules     : {$latestEvent->patch_rules}\n";
    echo "Created At      : {$latestEvent->created_at}\n\n";
} else {
    echo "Tidak menemukan event koreksi di database.\n\n";
}

if ($stylePref) {
    echo "--- user_style_prefs Snapshot ---\n";
    echo "Formality       : {$stylePref->formality}\n";
    echo "Humor Enabled   : " . ((int) $stylePref->humor ? 'ya' : 'tidak') . "\n";
    echo "Emoji Policy    : {$stylePref->emoji_policy}\n";
    echo "Updated At      : {$stylePref->updated_at}\n";
} else {
    echo "Belum ada entry user_style_prefs untuk user ini.\n";
}

echo "\nSelesai.\n";
