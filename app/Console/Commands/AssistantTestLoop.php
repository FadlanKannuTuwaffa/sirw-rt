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
    // --- Added for v3 scenario support ---
    protected array $v3Settings = [];
    protected array $v3Lexicon  = [];
    protected array $v3Fuzzing  = [];
    // -------------------------------------

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

        // --- Added: normalize v3 structure (meta/settings/lexicon/fuzzing/scenarios) ---
        if (isset($scenarios['scenarios']) && is_array($scenarios['scenarios'])) {
            $this->v3Settings = $scenarios['settings'] ?? [];
            $this->v3Lexicon  = $scenarios['lexicon']  ?? [];
            $this->v3Fuzzing  = $scenarios['fuzzing']  ?? [];
            $scenarios        = $scenarios['scenarios'];
        }
        // -------------------------------------------------------------------------------


        try {
            $defaultUser = $this->resolveUser($this->option('user'));
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
        $report = [];

        foreach ($scenarios as $scenarioIndex => $scenario) {
            if (!isset($scenario['steps']) || !is_array($scenario['steps'])) {
                // Try to expand from generator (v3)
                if (isset($scenario['generator'])) {
                    $scenario = $this->expandGeneratorToSteps($scenario);
                }
                // Re-check after expansion; if still missing steps, skip
                if (!isset($scenario['steps']) || !is_array($scenario['steps'])) {
                    $this->warn("Scenario index {$scenarioIndex} dilewati karena tidak memiliki steps.");
                    continue;
                }
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

    // ===== v3 Scenario Helpers =====

    protected function expandGeneratorToSteps(array $scenario): array
    {
        if (!isset($scenario['generator'])) {
            return $scenario;
        }
        $gen = $scenario['generator'];
        $scenarioOut = $scenario;
        $scenarioOut['steps'] = $scenarioOut['steps'] ?? [];
        $ctx = ['__values' => []]; // store resolved placeholders

        // Single-step via templates
        if (isset($gen['templates']) && is_array($gen['templates'])) {
            $tpl = $this->pickOne($gen['templates']);
            $prompt = $this->substituteTemplate($tpl, $gen['placeholders'] ?? [], $ctx);
            $prompt = $this->maybeMutatePrompt($prompt);
            $scenarioOut['steps'][] = [
                'prompt'  => $prompt,
                'expects' => $scenario['expects'] ?? []
            ];
        }

        // Multi-step generator
        if (isset($gen['steps']) && is_array($gen['steps'])) {
            foreach ($gen['steps'] as $stepIndex => $step) {
                $tpl = $step['prompt_template'] ?? '';
                $placeholders = $step['placeholders'] ?? [];
                $prompt = $this->substituteTemplate($tpl, $placeholders, $ctx);
                $prompt = $this->maybeMutatePrompt($prompt);
                $expects = $step['expects'] ?? [];
                $scenarioOut['steps'][] = [
                    'prompt'  => $prompt,
                    'expects' => $expects
                ];
            }
        }

        unset($scenarioOut['generator']);
        return $scenarioOut;
    }

    protected function substituteTemplate(string $tpl, array $ph, array &$ctx): string
    {
        foreach ($ph as $key => $rule) {
            $val = $this->resolvePlaceholder($rule, $ctx);
            $ctx['__values'][$key] = $val;
            $tpl = str_replace('{{'.$key.'}}', $val, $tpl);
        }
        return $tpl;
    }

    protected function resolvePlaceholder($rule, array &$ctx)
    {
        $type = is_array($rule) ? ($rule['type'] ?? 'choice') : 'choice';

        switch ($type) {
            case 'list_choice':
                $pool = $this->resolvePool($rule);
                $min = max(1, (int)($rule['min'] ?? 1));
                $max = max($min, (int)($rule['max'] ?? 3));
                $n = random_int($min, min($max, count($pool)));
                shuffle($pool);
                $items = array_slice($pool, 0, $n);
                // optional slang
                if (!empty($rule['allow_slang'])) {
                    $items = array_map(function($x){ return $this->maybeSlang($x); }, $items);
                }
                return $this->joinWithOxfordComma($items);

            case 'choice':
                if (isset($rule['ref']) && ($rule['type'] ?? '') === 'reuse') {
                    return $this->reuseValue($rule['ref'], $ctx);
                }
                $pool = $this->resolvePool($rule);
                // handle not_equal_to
                if (isset($rule['not_equal_to'])) {
                    $ne = $this->reuseValue($rule['not_equal_to'], $ctx);
                    $pool = array_values(array_filter($pool, fn($v) => $v !== $ne));
                    if (empty($pool)) { $pool = $this->resolvePool($rule); }
                }
                $val = $this->pickOne($pool);
                if (!empty($rule['allow_slang'])) {
                    $val = $this->maybeSlang($val);
                }
                return $val;

            case 'reuse':
                return $this->reuseValue($rule['ref'] ?? '', $ctx);

            case 'money_range':
                $min = (int)($rule['min'] ?? 50000);
                $max = (int)($rule['max'] ?? 250000);
                $num = random_int($min, $max);
                return $this->formatMoneyIDR($num);

            case 'jitter_money_from':
                $ref = $this->reuseValue($rule['ref'] ?? '', $ctx);
                $base = $this->parseMoney($ref);
                $range = $rule['delta_pct_range'] ?? [0.05, 0.10];
                $pct = $this->randFloat($range[0], $range[1]);
                $sign = random_int(0,1) ? 1 : -1;
                $num = max(0, (int)round($base * (1 + $sign*$pct)));
                return $this->formatMoneyIDR($num);

            default:
                // fallback: treat as literal or choice values
                if (isset($rule['values']) && is_array($rule['values'])) {
                    return $this->pickOne($rule['values']);
                }
                return (string)($rule['value'] ?? '');
        }
    }

    protected function resolvePool(array $rule): array
    {
        if (isset($rule['values']) && is_array($rule['values'])) {
            return $rule['values'];
        }
        if (isset($rule['from'])) {
            $pool = $this->getByPath($rule['from']);
            if (is_array($pool)) return array_values($pool);
        }
        return [];
    }

    protected function getByPath(string $path)
    {
        // supports 'lexicon.bills.categories'
        $root = ['lexicon' => $this->v3Lexicon, 'settings' => $this->v3Settings, 'fuzzing' => $this->v3Fuzzing];
        $node = $root;
        foreach (explode('.', $path) as $seg) {
            if (is_array($node) && array_key_exists($seg, $node)) {
                $node = $node[$seg];
            } else {
                return null;
            }
        }
        return $node;
    }

    protected function reuseValue(string $ref, array $ctx): string
    {
        // e.g. 'steps[0].CATEGORY' or 'UNIT_FROM'
        if (isset($ctx['__values'][$ref])) {
            return (string)$ctx['__values'][$ref];
        }
        if (preg_match('/steps\[(\d+)\]\.(\w+)/', $ref, $m)) {
            $key = $m[2];
            if (isset($ctx['__values'][$key])) {
                return (string)$ctx['__values'][$key];
            }
        }
        return '';
    }

    protected function joinWithOxfordComma(array $items): string
    {
        $items = array_values(array_filter(array_map('strval', $items), fn($x)=>$x!==''));
        $c = count($items);
        if ($c === 0) return '';
        if ($c === 1) return $items[0];
        if ($c === 2) return $items[0].' dan '.$items[1];
        return implode(', ', array_slice($items, 0, -1)).' dan '.end($items);
    }

    protected function pickOne(array $arr)
    {
        if (empty($arr)) return '';
        return $arr[array_rand($arr)];
    }

    protected function maybeSlang(string $val): string
    {
        // map via lexicon.bills.slang_alias if exists
        $aliases = $this->getByPath('lexicon.bills.slang_alias') ?? [];
        if (isset($aliases[$val]) && is_array($aliases[$val])) {
            // 40% chance use slang
            if (mt_rand(0, 99) < 40) {
                return $this->pickOne($aliases[$val]);
            }
        }
        return $val;
    }

    protected function formatMoneyIDR(int $num): string
    {
        return number_format($num, 0, ',', '.');
    }

    protected function parseMoney(string $s): int
    {
        $digits = preg_replace('/[^0-9]/', '', $s);
        return (int)$digits;
    }

    protected function randFloat(float $a, float $b): float
    {
        return $a + (mt_rand() / mt_getrandmax()) * ($b - $a);
    }

    protected function maybeMutatePrompt(string $prompt): string
    {
        $rand = $this->v3Settings['randomization'] ?? [];
        $fuzz = $this->v3Fuzzing ?? [];

        // code-switch
        if (!empty($rand['code_switch_probability']) && mt_rand(0,99) < (int)($rand['code_switch_probability']*100)) {
            $fragPool = $fuzz['code_switch_fragments'] ?? ['btw','plz','FYI'];
            $prompt .= ' ' . $this->pickOne($fragPool);
        }
        // emoji
        if (!empty($rand['emoji_probability']) && mt_rand(0,99) < (int)($rand['emoji_probability']*100)) {
            $pool = $fuzz['emoji_pool'] ?? ['🙂','✅','🙏'];
            $prompt .= ' ' . $this->pickOne($pool);
        }
        // simple typos: swap vowels randomly
        if (!empty($rand['typo_probability']) && mt_rand(0,99) < (int)($rand['typo_probability']*100)) {
            $pairs = $fuzz['typos']['swap_pairs'] ?? [['a','e'],['i','e']];
            foreach ($pairs as $p) {
                if (!is_array($p) || count($p) < 2) { continue; }
                $from = '/' . preg_quote((string)$p[0], '/') . '/u';
                $to   = (string)$p[1];
                $prompt = preg_replace($from, $to, $prompt, 1);
                break;
            }
        }
        return $prompt;
    }
    // ===== end helpers =====
}
