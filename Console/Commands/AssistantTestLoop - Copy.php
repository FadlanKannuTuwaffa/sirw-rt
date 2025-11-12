<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Assistant\DummyClient;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AssistantTestLoop extends Command
{
    protected $signature = 'assistant:test-loop
        {--scenario=tests/assistant_scenarios.json : Path ke berkas skenario JSON}
        {--user= : ID user yang digunakan saat simulasi}
        {--json : Keluarkan laporan dalam format JSON mentah}';

    protected $description = 'Loop percakapan ter-skenario untuk mengevaluasi penalaran & adaptasi DummyClient';

    public function handle(DummyClient $dummyClient): int
    {
        try {
            $scenarioFile = $this->resolveScenarioPath(trim((string) $this->option('scenario')));
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if (!File::exists($scenarioFile)) {
            $this->error("Berkas skenario tidak ditemukan: {$scenarioFile}");

            return self::FAILURE;
        }

        $scenarios = json_decode(File::get($scenarioFile), true);
        if (!is_array($scenarios)) {
            $this->error('Format skenario tidak valid. Pastikan berkas berupa JSON array.');

            return self::FAILURE;
        }

        try {
            $defaultUser = $this->resolveUser($this->option('user'));
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
        $report = [];

        foreach ($scenarios as $scenarioIndex => $scenario) {
            if (!isset($scenario['steps']) || !is_array($scenario['steps'])) {
                $this->warn("Scenario index {$scenarioIndex} dilewati karena tidak memiliki steps.");
                continue;
            }

            $activeUser = $this->resolveUser($scenario['user_id'] ?? $defaultUser->id);
            $this->prepareRequestContext($activeUser);
            Auth::setUser($activeUser);

            $history = [];
            $rows = [];
            $passes = 0;
            $total = 0;

            foreach ($scenario['steps'] as $stepIndex => $step) {
                $prompt = (string) ($step['prompt'] ?? $step['message'] ?? '');
                if ($prompt === '') {
                    continue;
                }

                $history[] = ['role' => 'user', 'content' => $prompt];
                $response = $dummyClient->chat($history);
                $reply = (string) ($response['content'] ?? '');
                $history[] = ['role' => 'assistant', 'content' => $reply];

                $intent = $dummyClient->getLastIntent();
                $confidence = Arr::get($response, 'style.confidence');
                [$stepPass, $notes] = $this->evaluateExpectations($reply, $intent, $confidence, $step['expects'] ?? []);

                $passes += $stepPass ? 1 : 0;
                $total++;

                $rows[] = [
                    '#' => $stepIndex + 1,
                    'Prompt' => Str::limit($prompt, 70),
                    'Intent' => $intent ?? '-',
                    'Conf' => $confidence !== null ? number_format((float) $confidence, 2) : '-',
                    'Status' => $stepPass ? 'PASS' : 'CHECK',
                    'Catatan' => implode('; ', $notes),
                ];
            }

            $scenarioReport = [
                'name' => $scenario['name'] ?? ("Scenario #{$scenarioIndex}"),
                'description' => $scenario['description'] ?? null,
                'user_id' => $activeUser->id,
                'steps_total' => $total,
                'steps_passed' => $passes,
                'rows' => $rows,
            ];

            $report[] = $scenarioReport;

            if (!$this->option('json')) {
                $this->info("\nScenario: {$scenarioReport['name']} (User #{$activeUser->id})");
                if ($scenarioReport['description']) {
                    $this->line($scenarioReport['description']);
                }
                $this->table(array_keys($rows[0] ?? ['#', 'Prompt', 'Intent', 'Conf', 'Status', 'Catatan']), $rows);
                $this->line("Summary: {$passes}/{$total} langkah memenuhi ekspektasi.\n");
            }
        }

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        return self::SUCCESS;
    }

    private function resolveScenarioPath(string $path): string
    {
        if ($path === '') {
            $path = 'tests/assistant_scenarios.json';
        }

        return Str::startsWith($path, DIRECTORY_SEPARATOR)
            ? $path
            : base_path($path);
    }

    private function resolveUser(?string $userId): User
    {
        if ($userId !== null) {
            return User::findOrFail((int) $userId);
        }

        $user = User::query()->first();
        if ($user === null) {
            throw new \RuntimeException('Tidak ada user di database. Tambahkan minimal satu user terlebih dahulu.');
        }

        return $user;
    }

    private function prepareRequestContext(User $user): void
    {
        $request = Request::create('/', 'POST');
        $request->setUserResolver(fn () => $user);
        $request->attributes->set('assistant_thread_id', 'cli:' . Str::uuid()->toString());
        app()->instance('request', $request);
    }

    /**
     * @param array<string, mixed> $expects
     * @return array{bool,array<int,string>}
     */
    private function evaluateExpectations(string $reply, ?string $intent, ?float $confidence, array $expects): array
    {
        if ($expects === []) {
            return [true, ['(tidak ada ekspektasi khusus)']];
        }

        $status = true;
        $notes = [];

        $contains = Arr::get($expects, 'contains', []);
        foreach ($contains as $needle) {
            $ok = Str::contains(Str::lower($reply), Str::lower($needle));
            $status = $status && $ok;
            $notes[] = $ok
                ? "contains:\"{$needle}\""
                : "missing:\"{$needle}\"";
        }

        $notContains = Arr::get($expects, 'not_contains', []);
        foreach ($notContains as $needle) {
            $ok = !Str::contains(Str::lower($reply), Str::lower($needle));
            $status = $status && $ok;
            $notes[] = $ok
                ? "clean:\"{$needle}\""
                : "should-not:\"{$needle}\"";
        }

        if (isset($expects['intent'])) {
            $ok = (string) $expects['intent'] === (string) $intent;
            $status = $status && $ok;
            $notes[] = $ok
                ? "intent:{$intent}"
                : "intent-mismatch (actual {$intent})";
        }

        if (isset($expects['min_confidence'])) {
            $threshold = (float) $expects['min_confidence'];
            $ok = $confidence !== null && $confidence >= $threshold;
            $status = $status && $ok;
            $notes[] = $ok
                ? "confidence {$confidence}"
                : "confidence<{$threshold}";
        }

        return [$status, $notes];
    }
}
