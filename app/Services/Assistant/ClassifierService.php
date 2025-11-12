<?php

namespace App\Services\Assistant;

use App\Services\Assistant\ComplexMultiIntentHandler;
use App\Services\Assistant\Intent\ExternalIntentClient;
use App\Services\Assistant\IntentExampleRepository;
use App\Services\Assistant\MLIntentClassifier;
use App\Services\Assistant\Ner\NerService;
use App\Support\Assistant\TemporalInterpreter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ClassifierService
{
    private TemporalInterpreter $temporal;
    private AssistantMetrics $metrics;
    private ?ExternalIntentClient $mlClient;
    private ?ExternalIntentClient $llmClient;
    private NerService $ner;
    private float $highConfidenceThreshold;
    private float $mlFallbackThreshold;
    private float $llmFallbackThreshold;
    private array $intentBias = [];
    /**
     * Bias snapshots keyed by token so we can restore the previous state.
     *
     * @var array<string, array<string,float>>
     */
    private array $biasSnapshots = [];
    private ?MLIntentClassifier $embeddingClassifier;
    private ?ComplexMultiIntentHandler $multiIntentHandler;
    private ?array $lastEmbeddingScores = null;
    private ?string $lastEmbeddingHash = null;
    private IntentExampleRepository $intentExampleRepository;
    private ?array $learnedIntentExamples = null;

    /**
     * @var array<string, string[]>
     */
    private array $intentKeywords = [
        'bills' => ['tagihan', 'bill', 'iuran', 'tunggakan', 'bayar', 'kebersihan', 'keamanan'],
        'payments' => ['pembayaran', 'riwayat bayar', 'sudah bayar', 'transfer', 'lunas'],
        'agenda' => ['agenda', 'acara', 'kegiatan', 'rapat', 'event', 'jadwal'],
        'finance' => ['rekap', 'keuangan', 'kas', 'laporan', 'saldo'],
        'residents' => ['warga', 'resident', 'direktori', 'kontak', 'pengurus'],
        'residents_new' => ['warga baru', 'pendatang', 'anggota baru', 'baru gabung'],
        'knowledge_base' => ['prosedur', 'cara', 'bagaimana', 'aturan', 'dokumen'],
    ];

    /**
     * Slots that can be inferred from keywords even before slot-filling runs.
     *
     * @var array<string, array<string, string[]>>
     */
    private array $slotKeywords = [
        'bills' => [
            'period' => ['bulan ini' => 'current_month', 'bulan lalu' => 'previous_month'],
        ],
        'payments' => [
            'period' => ['bulan ini' => 'current_month', 'bulan lalu' => 'previous_month'],
        ],
        'agenda' => [
            'range' => ['minggu depan' => 'next_week', 'minggu ini' => 'week', 'besok' => 'tomorrow', 'hari ini' => 'today'],
        ],
    ];

    public function __construct(
        ?TemporalInterpreter $temporal = null,
        ?AssistantMetrics $metrics = null,
        ?ExternalIntentClient $mlClient = null,
        ?ExternalIntentClient $llmClient = null,
        ?NerService $ner = null,
        ?MLIntentClassifier $embeddingClassifier = null,
        ?ComplexMultiIntentHandler $multiIntentHandler = null,
        ?IntentExampleRepository $intentExampleRepository = null
    ) {
        $config = config('assistant.classifier', []);

        $this->temporal = $temporal ?? new TemporalInterpreter(config('app.timezone', 'UTC'));
        $this->metrics = $metrics ?? new AssistantMetrics();
        $this->mlClient = $mlClient;
        $this->llmClient = $llmClient;
        $ducklingEndpoint = data_get($config, 'ner.duckling_endpoint');
        $ducklingTimeout = (float) (data_get($config, 'ner.duckling_timeout') ?? 2.5);
        $this->ner = $ner ?? new NerService($ducklingEndpoint, $ducklingTimeout);
        $this->embeddingClassifier = $embeddingClassifier;
        $this->multiIntentHandler = $multiIntentHandler;
        $this->intentExampleRepository = $intentExampleRepository ?? new IntentExampleRepository();

        $this->highConfidenceThreshold = (float) ($config['high_confidence_threshold'] ?? 0.75);
        $this->mlFallbackThreshold = (float) ($config['ml_fallback_threshold'] ?? 0.6);
        $this->llmFallbackThreshold = (float) ($config['llm_fallback_threshold'] ?? 0.5);
    }

    /**
     * @param  array<string, mixed>  $lexicalContext
     * @return array{intent:string,score:float,slots:array<string,mixed>}|null
     */
    public function classify(string $message, array $lexicalContext = []): ?array
    {
        $decisions = [];
        $candidate = $this->classifyHeuristically($message, $lexicalContext);

        if ($candidate !== null) {
            $decisions[] = ['stage' => 'heuristic', 'result' => $candidate, 'source' => 'heuristic'];
            if (($candidate['score'] ?? 0) >= $this->highConfidenceThreshold) {
                $this->recordDecisions($decisions, $candidate);

                return $this->normalizeResult($candidate);
            }
        }

        $currentScore = $candidate['score'] ?? 0.0;

        if ($this->embeddingClassifier !== null) {
            $embeddingResult = $this->embeddingClassifier->classify($message, $lexicalContext);
            $this->lastEmbeddingScores = $embeddingResult['all_scores'] ?? [];
            $this->lastEmbeddingHash = md5($message);

            if (($embeddingResult['intent'] ?? null) !== null) {
                $decisions[] = ['stage' => 'ml_embedding', 'result' => [
                    'intent' => $embeddingResult['intent'],
                    'score' => $embeddingResult['confidence'] ?? 0.0,
                    'slots' => [],
                    'source' => 'ml_embedding',
                ], 'source' => 'ml_embedding'];

                $candidate = $this->pickBetter($candidate, [
                    'intent' => $embeddingResult['intent'],
                    'score' => $embeddingResult['confidence'] ?? 0.0,
                    'slots' => [],
                    'source' => 'ml_embedding',
                ]);
                $currentScore = $candidate['score'] ?? 0.0;
            }
        }

        if ($this->shouldInvokeMl($currentScore)) {
            $mlResult = $this->mlClient?->classify($message, $lexicalContext);
            if ($mlResult !== null) {
                $decisions[] = ['stage' => 'ml', 'result' => $mlResult, 'source' => $mlResult['source'] ?? 'ml'];
                $candidate = $this->pickBetter($candidate, $mlResult);
                $currentScore = $candidate['score'] ?? 0.0;
            }
        }

        if ($this->shouldInvokeLlm($currentScore)) {
            $llmResult = $this->llmClient?->classify($message, $lexicalContext);
            if ($llmResult !== null) {
                $decisions[] = ['stage' => 'llm', 'result' => $llmResult, 'source' => $llmResult['source'] ?? 'llm'];
                $candidate = $this->pickBetter($candidate, $llmResult);
            }
        }

        $this->recordDecisions($decisions, $candidate);

        return $this->normalizeResult($candidate);
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function extractEntities(string $message): array
    {
        return $this->ner->extract($message);
    }

    /**
     * @param  array<string,float>  $bias
     */
    public function setIntentBias(array $bias): void
    {
        $this->intentBias = [];

        foreach ($bias as $intent => $value) {
            if (!is_numeric($value)) {
                continue;
            }

            $this->intentBias[(string) $intent] = max(0.0, min(1.0, (float) $value));
        }
    }

    public function pushIntentBias(array $bias): string
    {
        $token = (string) Str::uuid();
        $this->biasSnapshots[$token] = $this->intentBias;
        $this->setIntentBias($bias);

        return $token;
    }

    public function popIntentBias(?string $token): void
    {
        if ($token === null) {
            return;
        }

        if (!array_key_exists($token, $this->biasSnapshots)) {
            return;
        }

        $this->intentBias = $this->biasSnapshots[$token];
        unset($this->biasSnapshots[$token]);
    }

    private function classifyHeuristically(string $message, array $lexicalContext): ?array
    {
        $normalized = Str::of($message)->lower()->squish()->value();

        if ($normalized === '') {
            return null;
        }

        $rawTokens = $lexicalContext['tokens'] ?? preg_split('/\s+/', $normalized) ?: [];
        $tokens = $this->normalizeTokenList($rawTokens);
        $scores = [];

        foreach ($this->intentKeywords as $intent => $keywords) {
            $score = 0.0;

            foreach ($keywords as $keyword) {
                if (Str::contains($normalized, $keyword)) {
                    $score += 1.0;
                }
            }

            foreach ($tokens as $token) {
                if (in_array($token, $keywords, true)) {
                    $score += 0.5;
                }
            }

            $scores[$intent] = $score;
        }

        foreach ($scores as $intent => &$score) {
            if (isset($this->intentBias[$intent])) {
                $score += (float) $this->intentBias[$intent];
            }

            $score += $this->scoreFromLearnedExamples($intent, $tokens);
        }
        unset($score);

        arsort($scores);
        $bestIntent = array_key_first($scores);
        $bestScore = $bestIntent ? $scores[$bestIntent] : 0.0;

        if ($bestScore <= 0) {
            return null;
        }

        $slots = $this->inferSlots($bestIntent, $normalized, $lexicalContext);

        return [
            'intent' => $bestIntent,
            'score' => min(1.0, round($bestScore / 4, 2)),
            'slots' => $slots,
            'source' => 'heuristic',
        ];
    }

    /**
     * @param  array<int,string>  $tokens
     * @return array<int,string>
     */
    private function normalizeTokenList(array $tokens): array
    {
        $normalized = [];

        foreach ($tokens as $token) {
            $value = Str::of($token ?? '')
                ->lower()
                ->replaceMatches('/[^\pL\pN]+/u', '')
                ->value();

            if ($value === '') {
                continue;
            }

            $normalized[] = $value;
        }

        return array_values(array_unique($normalized));
    }

    private function learnedExamples(): array
    {
        if ($this->learnedIntentExamples === null) {
            $this->learnedIntentExamples = $this->intentExampleRepository->all();
        }

        return $this->learnedIntentExamples;
    }

    /**
     * @param  array<int,string>  $tokens
     */
    private function scoreFromLearnedExamples(string $intent, array $tokens): float
    {
        if ($tokens === []) {
            return 0.0;
        }

        $examples = $this->learnedExamples()[$intent] ?? [];

        if ($examples === []) {
            return 0.0;
        }

        $score = 0.0;

        foreach ($examples as $example) {
            $exampleTokens = $example['tokens'] ?? [];

            if ($exampleTokens === []) {
                continue;
            }

            $overlap = count(array_intersect($tokens, $exampleTokens));

            if ($overlap < 2) {
                continue;
            }

            $coverage = $overlap / min(4, max(count($exampleTokens), 1));
            $weight = (float) ($example['weight'] ?? 1.0);
            $score += min(1.5, $coverage * 1.5 * $weight);

            if ($score >= 4.0) {
                break;
            }
        }

        return round($score, 3);
    }

    private function shouldInvokeMl(float $currentScore): bool
    {
        return $this->mlClient !== null && $currentScore < $this->mlFallbackThreshold;
    }

    private function shouldInvokeLlm(float $currentScore): bool
    {
        return $this->llmClient !== null && $currentScore < $this->llmFallbackThreshold;
    }

    private function pickBetter(?array $current, array $candidate): array
    {
        if ($current === null) {
            return $candidate;
        }

        $currentScore = (float) ($current['score'] ?? 0);
        $candidateScore = (float) ($candidate['score'] ?? 0);

        if ($candidateScore > $currentScore + 0.05) {
            return $candidate;
        }

        if (abs($candidateScore - $currentScore) <= 0.05) {
            $currentSlots = count($current['slots'] ?? []);
            $candidateSlots = count($candidate['slots'] ?? []);

            if ($candidateSlots > $currentSlots) {
                return $candidate;
            }
        }

        return $current;
    }

    /**
     * @param  array<string, mixed>  $candidate
     */
    private function normalizeResult(?array $candidate): ?array
    {
        if ($candidate === null) {
            return null;
        }

        $candidate['slots'] = is_array($candidate['slots'] ?? null) ? $candidate['slots'] : [];

        return $candidate;
    }

    /**
     * @param  array<int, array{stage:string,result:array|null,source:string}>  $decisions
     */
    private function recordDecisions(array $decisions, ?array $final): void
    {
        if ($this->metrics === null || $decisions === []) {
            return;
        }

        foreach ($decisions as $decision) {
            $result = $decision['result'] ?? null;
            if (!is_array($result)) {
                continue;
            }

            $this->metrics->recordClassifierDecision([
                'stage' => $decision['stage'],
                'intent' => $result['intent'] ?? null,
                'score' => $result['score'] ?? null,
                'slots' => array_keys($result['slots'] ?? []),
                'selected' => $final !== null && $this->isSamePrediction($result, $final),
                'source' => $decision['source'] ?? $decision['stage'],
            ]);
        }
    }

    private function isSamePrediction(array $left, array $right): bool
    {
        return ($left['intent'] ?? null) === ($right['intent'] ?? null)
            && abs(($left['score'] ?? 0) - ($right['score'] ?? 0)) < 0.0001;
    }

    /**
     * @return array<string, mixed>
     */
    private function inferSlots(string $intent, string $message, array $lexicalContext): array
    {
        $slots = [];

        if (!isset($this->slotKeywords[$intent])) {
            return $slots;
        }

        foreach ($this->slotKeywords[$intent] as $slot => $hints) {
            foreach ($hints as $needle => $value) {
                if (Str::contains($message, $needle)) {
                    $slots[$slot] = $this->coerceSlotValue($slot, $value);
                    break;
                }
            }
        }

        if (!isset($slots['period']) && $intent === 'bills') {
            $slots['period'] = $lexicalContext['entities']['period'] ?? null;
        }

        return array_filter($slots, fn ($value) => $value !== null);
    }

    private function coerceSlotValue(string $slot, mixed $value): mixed
    {
        if ($slot === 'period' && is_string($value)) {
            return match ($value) {
                'current_month' => $this->temporal->monthRange(Carbon::now()),
                'previous_month' => $this->temporal->monthRange(Carbon::now()->subMonth()),
                default => null,
            };
        }

        return $value;
    }

    /**
     * @return array<int,array{intent:string,dependencies:array<int,string>,order:int}>
     */
    public function multiIntentPlan(string $message, array $lexicalContext = []): array
    {
        if ($this->multiIntentHandler === null) {
            return [];
        }

        $scores = $this->embeddingScores($message, $lexicalContext);

        if ($scores === []) {
            return [];
        }

        $intents = $this->multiIntentHandler->detectIntents($message, $scores);

        if ($intents === []) {
            return [];
        }

        return $this->multiIntentHandler->buildExecutionPlan($intents);
    }

    /**
     * @return array<string,float>
     */
    private function embeddingScores(string $message, array $lexicalContext = []): array
    {
        if ($this->embeddingClassifier === null) {
            return [];
        }

        if ($this->lastEmbeddingScores !== null && $this->lastEmbeddingHash === md5($message)) {
            return $this->lastEmbeddingScores;
        }

        $result = $this->embeddingClassifier->classify($message, $lexicalContext);
        $scores = $result['all_scores'] ?? [];
        $this->lastEmbeddingScores = $scores;
        $this->lastEmbeddingHash = md5($message);

        if ($scores === [] && ($result['intent'] ?? null) !== null) {
            $scores[$result['intent']] = $result['confidence'] ?? 0.0;
        }

        if ($scores === [] && ($heuristic = $this->classifyHeuristically($message, $lexicalContext)) !== null) {
            $scores[$heuristic['intent']] = $heuristic['score'];
        }

        return $scores;
    }
}
