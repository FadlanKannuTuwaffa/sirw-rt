<?php

namespace App\Http\Controllers;

use App\Models\AssistantKnowledgeFeedback;
use App\Services\Assistant\AnswerSynthesizer;
use App\Services\Assistant\AssistantIntentHandler;
use App\Services\Assistant\DummyClient;
use App\Services\Assistant\GuidedLLMReasoner;
use App\Services\Assistant\InteractionLearner;
use App\Services\Assistant\InteractionLogger;
use App\Services\Assistant\LLMClient;
use App\Services\Assistant\ProviderRouter;
use App\Services\Assistant\QueryClassifier;
use App\Services\Assistant\Support\AutoLearnSnapshotService;
use App\Services\Assistant\Support\LlmSnapshotManager;
use App\Services\Assistant\SystemPrompt;
use App\Services\Assistant\ToolRouter;
use App\Services\Assistant\Exceptions\OutOfContextException;
use App\Support\Assistant\LanguageDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Acceptance Criteria:
 * - Small talk "gimana kabarmu" → balasan 1–2 kalimat + tawarkan bantuan.
 * - "Tagihanku bulan ini berapa?" → panggil get_outstanding_bills, ringkas + tautan aksi.
 * - "Apa agenda minggu ini?" → get_agenda('week').
 * - "Apa beda iuran sampah dan keamanan?" → rag_search menampilkan jawaban + sumber.
 * - Streaming berjalan, tidak kena 60s timeout.
 */
class AssistantController extends Controller
{
    public function chat(Request $request, LLMClient $llm, ToolRouter $router, AssistantIntentHandler $intentHandler, QueryClassifier $classifier, InteractionLogger $interactionLogger)
    {
        $request->validate(['message' => 'required|string|max:2000']);
        
        $user = Auth::user();
        $message = $request->input('message');
        $language = LanguageDetector::detect($message);
        $start = microtime(true);
        $threadId = $request->attributes->get('assistant_thread_id');
        if (!is_string($threadId) || $threadId === '') {
            $threadId = 'user:' . ($user->id ?? 'guest');
        }
        /** @var \App\Services\Assistant\Support\LlmSnapshotManager $llmSnapshotManager */
        $llmSnapshotManager = app(LlmSnapshotManager::class);
        $interactionRecorder = app(InteractionLearner::class);
        
        Log::info('Assistant chat started', [
            'user_id' => $user->id,
            'message_length' => strlen($message),
            'language' => $language,
        ]);

        return new StreamedResponse(function () use ($message, $user, $llm, $router, $intentHandler, $classifier, $interactionLogger, $language, $start, $llmSnapshotManager, $threadId, $interactionRecorder): void {
            if (ob_get_level()) ob_end_clean();
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('X-Accel-Buffering: no');
            header('Connection: keep-alive');

            $sendEvent = static function (array $payload): void {
                echo 'data: ' . json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n\n";
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            };
            $llmProvider = null;
            $providerPlan = ProviderRouter::choose([
                'primary' => config('assistant.llm.primary', 'groq'),
                'chain' => config('assistant.llm.chain', ['groq', 'gemini', 'openrouter', 'small_lm', 'synth']),
            ]);
            $providerPrimary = $providerPlan['primary'] ?? 'groq';
            $providerFinal = null;
            $providerFallbackFrom = null;
            
            $classification = $classifier->classify($message);
            Log::info('Query classified', $classification);

            $predictedIntents = [];
            if (!empty($classification['intents']) && is_array($classification['intents'])) {
                $predictedIntents = array_values(array_filter($classification['intents'], 'is_string'));
            } elseif (!empty($classification['category']) && is_string($classification['category'])) {
                $predictedIntents = [$classification['category']];
            }
            $router->resetExecutionLog();

            $logInteraction = function (array $extra = []) use ($interactionLogger, $user, $message, $classification, &$predictedIntents, $start, &$providerPrimary, &$providerFinal, &$providerFallbackFrom) {
                $payload = array_merge([
                    'user_id' => $user->id,
                    'query' => $message,
                    'classification_type' => $classification['type'],
                    'confidence' => $classification['confidence'] ?? null,
                    'intents' => $predictedIntents,
                    'tool_calls' => [],
                    'tool_success' => null,
                    'responded_via' => 'unknown',
                    'llm_provider' => null,
                    'provider_primary' => $providerPrimary,
                    'provider_final' => $providerFinal,
                    'provider_fallback_from' => $providerFallbackFrom,
                    'repetition_score' => null,
                    'correction_event_id' => null,
                    'smalltalk_kind' => null,
                    'success' => true,
                    'duration_ms' => round((microtime(true) - $start) * 1000),
                ], $extra);

                return $interactionLogger->record($payload);
            };

            $normalizedMessage = Str::of($message)->lower()->squish()->value();

            if ($this->isIdentityQuestion($normalizedMessage)) {
                $intro = $this->identityIntroduction($message);
                $sendEvent(['type' => 'token', 'content' => $intro]);
                $sendEvent(['type' => 'done']);

                $duration = round((microtime(true) - $start) * 1000);
                $providerFinal = 'identity_guard';
                $providerFallbackFrom = null;
                $logInteraction([
                    'responded_via' => 'identity_guard',
                    'duration_ms' => $duration,
                    'intents' => ['identity'],
                    'llm_provider' => null,
                ]);

                return;
            }

        $history = session()->get('assistant_history_' . $user->id, []);
        $userName = explode(' ', $user->name)[0] ?? 'Warga';

        if ($classification['type'] === 'small_talk') {
            $response = $this->handleSmallTalk($message, $language);
            $sendEvent(['type' => 'token', 'content' => $response]);
            $sendEvent(['type' => 'done']);

            $history[] = ['role' => 'user', 'content' => $message];
            $history[] = ['role' => 'assistant', 'content' => $response];
            session()->put('assistant_history_' . $user->id, array_slice($history, -20));
            $repetitionScore = $this->calculateRepetitionScore($history);

            $duration = round((microtime(true) - $start) * 1000);
                Log::info('Assistant chat completed (small talk)', ['duration_ms' => $duration]);
                $providerFinal = 'small_talk_classifier';
                $providerFallbackFrom = null;
                $logInteraction([
                    'intents' => [],
                    'tool_calls' => [],
                    'tool_success' => null,
                    'responded_via' => 'small_talk',
                    'duration_ms' => $duration,
                    'llm_provider' => null,
                    'smalltalk_kind' => 'classifier',
                    'repetition_score' => $repetitionScore,
                ]);
                return;
            }

        if ($this->shouldRouteToDummy($normalizedMessage)) {
            $this->respondViaDummy(
                $history,
                $message,
                $router,
                $sendEvent,
                $logInteraction,
                $start,
                $user->id,
                $predictedIntents,
                'dummy_feedback'
            );

            return;
        }

        if ($classification['type'] === 'simple' && $classification['confidence'] >= 0.45) {
            Log::info('Using IntentHandler + DummyClient', [
                'confidence' => $classification['confidence'],
                'intents' => $predictedIntents,
            ]);
            
            try {
                $intentResult = $predictedIntents !== []
                    ? $intentHandler->handle($message, $router, $user->id, $predictedIntents, $language)
                    : null;
                
                if ($intentResult) {
                    $content = $intentResult['content'];
                    Log::info('Intent handled directly', ['length' => strlen($content)]);
                    $providerFinal = 'intent_handler';
                    $providerFallbackFrom = null;
                } else {
                    $this->respondViaDummy(
                        $history,
                        $message,
                        $router,
                        $sendEvent,
                        $logInteraction,
                        $start,
                        $user->id,
                        $predictedIntents
                    );

                    return;
                }
                
                $history[] = ['role' => 'user', 'content' => $message];
                $history[] = ['role' => 'assistant', 'content' => $content];
                session()->put('assistant_history_' . $user->id, array_slice($history, -20));
                $repetitionScore = $this->calculateRepetitionScore($history);

                $sendEvent(['type' => 'token', 'content' => $content]);
                $sendEvent(['type' => 'done']);

                $duration = round((microtime(true) - $start) * 1000);
                Log::info('Assistant chat completed (simple)', ['duration_ms' => $duration, 'cost' => 0]);
                $toolLog = $router->pullExecutionLog();
                $logInteraction([
                    'responded_via' => $intentResult ? 'intent_handler' : 'dummy_client',
                    'tool_calls' => $toolLog,
                    'tool_success' => $toolLog === [] ? null : collect($toolLog)->every(fn ($item) => (bool) ($item['success'] ?? false)),
                    'duration_ms' => $duration,
                    'intents' => $predictedIntents,
                    'llm_provider' => null,
                    'repetition_score' => $repetitionScore,
                ]);
                return;
                
            } catch (OutOfContextException $e) {
                Log::info('Out of context detected, fallback to LLM', ['message' => $message]);
                
                $router->resetExecutionLog();
                // Send "sebentar saya cari tahu dulu ya..."
                $sendEvent(['type' => 'token', 'content' => "Sebentar saya cari tahu dulu ya... 🔍"]);
                
                // Continue to LLM processing below
            }
        }
        
        Log::info('Using LLM (complex query)', ['confidence' => $classification['confidence']]);
            
            $systemPrompt = SystemPrompt::get([
                'user_name' => $userName,
                'language' => $language,
            ]);

            $reasoner = app(GuidedLLMReasoner::class);
            $messages = $reasoner->buildMessages(
                $user,
                $history,
                $message,
                $systemPrompt,
                $classification,
                $predictedIntents
            );
            $llmContext = $reasoner->context();
            
            $history[] = ['role' => 'user', 'content' => $message];
            $history = array_slice($history, -20);
            $maxIterations = 5;
            $iteration = 0;

            while ($iteration < $maxIterations) {
                $iteration++;
                
                $collectedContent = '';

                try {
                    if ($llm->supportsStreaming()) {
                        $response = $llm->stream(
                            $messages,
                            $router->getToolDefinitions(),
                            function (string $event, $payload) use (&$collectedContent, $sendEvent) {
                                if ($event !== 'token') {
                                    return;
                                }

                                $token = (string) $payload;

                                if ($token === '') {
                                    return;
                                }

                                $collectedContent .= $token;
                                $sendEvent(['type' => 'token', 'content' => $token]);
                            }
                        );
                    } else {
                        $response = $llm->chat($messages, $router->getToolDefinitions());
                    }
                } catch (\Throwable $e) {
                    Log::error('LLM chat failed', ['error' => $e->getMessage(), 'iteration' => $iteration]);
                    
                    if ($iteration >= 2) {
                        Log::warning('LLM failed twice, invoking synthesizer fallback');
                        $providerFallbackFrom ??= $providerPrimary;
                        
                        $intentResult = $intentHandler->handle($message, $router, $user->id, $predictedIntents, $language);
                        
                        if ($intentResult) {
                            $content = $intentResult['content'];
                            $providerFinal = 'intent_handler';
                            
                            $sendEvent(['type' => 'token', 'content' => $content]);
                            $sendEvent(['type' => 'done']);

                            $history[] = ['role' => 'user', 'content' => $message];
                            $history[] = ['role' => 'assistant', 'content' => $content];
                            session()->put('assistant_history_' . $user->id, array_slice($history, -20));

                            $toolLog = $router->pullExecutionLog();
                            $durationFallback = round((microtime(true) - $start) * 1000);
                            $logInteraction([
                                'responded_via' => 'intent_handler',
                                'tool_calls' => $toolLog,
                                'tool_success' => $toolLog === [] ? null : collect($toolLog)->every(fn ($item) => (bool) ($item['success'] ?? false)),
                                'duration_ms' => $durationFallback,
                                'llm_provider' => null,
                            ]);
                            return;
                        }

                        $synthContent = AnswerSynthesizer::basic($message);
                        $providerFinal = 'synthesizer';
                        $sendEvent(['type' => 'token', 'content' => $synthContent]);
                        $sendEvent(['type' => 'done']);
                        $history[] = ['role' => 'assistant', 'content' => $synthContent];
                        session()->put('assistant_history_' . $user->id, array_slice($history, -20));
                        $repetitionScore = $this->calculateRepetitionScore($history);
                        $toolLog = $router->pullExecutionLog();
                        $durationSynth = round((microtime(true) - $start) * 1000);
                        $logInteraction([
                            'responded_via' => 'synthesizer',
                            'tool_calls' => $toolLog,
                            'tool_success' => $toolLog === [] ? null : collect($toolLog)->every(fn ($item) => (bool) ($item['success'] ?? false)),
                            'duration_ms' => $durationSynth,
                            'llm_provider' => null,
                            'repetition_score' => $repetitionScore,
                        ]);

                        return;
                    }
                    
                    sleep(1);
                    continue;
                }
                
                $response = $response ?? [];
                if (!$llm->supportsStreaming() && isset($response['content'])) {
                    $collectedContent = (string) $response['content'];
                }

                if (isset($response['provider'])) {
                    $llmProvider = $response['provider'];
                    $providerFinal = $llmProvider;
                    if ($llmProvider !== null && $llmProvider !== $providerPrimary) {
                        $providerFallbackFrom ??= $providerPrimary;
                    }
                } elseif ($providerFinal === null) {
                    $providerFinal = $providerPlan['final'] ?? $providerPrimary;
                }
                
                if (isset($response['tool_calls']) && !empty($response['tool_calls'])) {
                    $messages[] = ['role' => 'assistant', 'content' => '', 'tool_calls' => $response['tool_calls']];
                    
                    foreach ($response['tool_calls'] as $toolCall) {
                        $toolName = $toolCall['function']['name'] ?? '';
                        $toolArgs = json_decode($toolCall['function']['arguments'] ?? '{}', true) ?? [];
                        $toolArgs['resident_id'] = $user->id;
                        $validation = $router->validateAndCoerce($toolName, $toolArgs, $message);

                        if (!($validation['valid'] ?? true)) {
                            $toolResult = [
                                'success' => false,
                                'error' => 'validation_failed',
                                'clarification' => $validation['clarification'] ?? 'Parameter tool belum lengkap.',
                                'details' => $validation['errors'] ?? [],
                            ];
                        } else {
                            $toolResult = $router->execute($toolName, $validation['parameters'] ?? $toolArgs);
                        }
                        
                        $messages[] = [
                            'role' => 'tool',
                            'tool_call_id' => $toolCall['id'] ?? '',
                            'content' => json_encode($toolResult, JSON_UNESCAPED_UNICODE)
                        ];
                    }
                } else {
                    $content = $collectedContent !== ''
                        ? $collectedContent
                        : (string) ($response['content'] ?? '');

                    if (trim($content) === '') {
                        $content = 'Maaf, tidak ada respons dari sistem.';
                        Log::warning('Empty content from LLM', ['response' => $response]);
                    }

                    Log::info('Sending content', ['length' => strlen($content)]);

                    $shouldEmitToken = !$llm->supportsStreaming() || trim($collectedContent) === '';

                    if ($shouldEmitToken) {
                        $sendEvent(['type' => 'token', 'content' => $content]);
                    }

                    $resolvedIntent = $predictedIntents[0] ?? Arr::get($llmContext, 'state.last_intent');
                    $interactionSampleId = null;

                    try {
                        $interactionSampleId = $interactionRecorder->recordInteraction([
                            'user_id' => $user->id,
                            'thread_id' => $threadId,
                            'message' => $message,
                            'intent' => $resolvedIntent,
                            'response' => $content,
                            'confidence' => $classification['confidence'] ?? null,
                            'method' => 'llm',
                        ]);
                    } catch (\Throwable $e) {
                        Log::warning('Failed to record LLM interaction sample', [
                            'error' => $e->getMessage(),
                        ]);
                    }

                    if ($interactionSampleId !== null) {
                        $sendEvent(['type' => 'meta', 'content' => [
                            'interaction_id' => $interactionSampleId,
                            'intent' => $resolvedIntent,
                            'confidence' => $classification['confidence'] ?? null,
                        ]]);
                    }

                    $sendEvent(['type' => 'done']);
                    
                    $history[] = ['role' => 'assistant', 'content' => $content];
                    session()->put('assistant_history_' . $user->id, array_slice($history, -20));
                    $repetitionScore = $this->calculateRepetitionScore($history);

                    $toolLog = $router->pullExecutionLog();
                    $respondedVia = $toolLog === [] ? 'llm' : 'llm_with_tools';
                    $interactionLog = $logInteraction([
                        'responded_via' => $respondedVia,
                        'tool_calls' => $toolLog,
                        'tool_success' => $toolLog === [] ? null : collect($toolLog)->every(fn ($item) => (bool) ($item['success'] ?? false)),
                        'intents' => $predictedIntents,
                        'llm_provider' => $llmProvider,
                        'repetition_score' => $repetitionScore,
                    ]);

                    if ($interactionLog) {
                        if ($interactionSampleId !== null) {
                            try {
                                $interactionRecorder->attachInteractionLog($interactionSampleId, $interactionLog->id);
                            } catch (\Throwable $e) {
                                Log::warning('Failed to attach interaction log', [
                                    'error' => $e->getMessage(),
                                    'interaction_id' => $interactionSampleId,
                                    'log_id' => $interactionLog->id,
                                ]);
                            }
                        }

                        $llmSnapshotManager->record([
                            'assistant_interaction_log_id' => $interactionLog->id,
                            'assistant_interaction_id' => $interactionSampleId,
                            'user_id' => $user->id,
                            'thread_id' => $threadId,
                            'intent' => $resolvedIntent,
                            'confidence' => $classification['confidence'] ?? null,
                            'provider' => $llmProvider,
                            'responded_via' => $respondedVia,
                            'is_fallback' => $providerFallbackFrom !== null,
                            'content' => $content,
                            'rag_sources' => Arr::get($llmContext, 'state.kb_sources'),
                            'tool_calls' => $toolLog,
                            'metadata' => $llmContext,
                        ]);
                    }

                    break;
                }
            }

            if ($iteration >= $maxIterations) {
                $timeoutContent = 'Maaf, saya butuh lebih banyak waktu untuk memproses ini. Coba pertanyaan yang lebih spesifik?';
                $sendEvent(['type' => 'token', 'content' => $timeoutContent]);
                $sendEvent(['type' => 'done']);
                $toolLog = $router->pullExecutionLog();
                $duration = round((microtime(true) - $start) * 1000);
                $history[] = ['role' => 'assistant', 'content' => $timeoutContent];
                session()->put('assistant_history_' . $user->id, array_slice($history, -20));
                $repetitionScore = $this->calculateRepetitionScore($history);
                $providerFinal = $providerFinal ?? 'timeout';
                $providerFallbackFrom ??= $providerPrimary;
                $logInteraction([
                    'responded_via' => 'timeout',
                    'tool_calls' => $toolLog,
                    'tool_success' => $toolLog === [] ? null : collect($toolLog)->every(fn ($item) => (bool) ($item['success'] ?? false)),
                    'success' => false,
                    'duration_ms' => $duration,
                    'llm_provider' => $llmProvider,
                    'repetition_score' => $repetitionScore,
                ]);
            }

            $duration = round((microtime(true) - $start) * 1000);
            Log::info('Assistant chat completed', ['iterations' => $iteration, 'duration_ms' => $duration, 'used_llm' => true]);
        }, 200, ['Content-Type' => 'text/event-stream', 'Cache-Control' => 'no-cache', 'X-Accel-Buffering' => 'no']);
    }
    
    private function handleSmallTalk(string $message, string $language): string
    {
        return \App\Support\Assistant\SmallTalk::respond($message, $language);
    }

    private function isIdentityQuestion(string $normalized): bool
    {
        return Str::of($normalized)->contains([
            'who are you',
            'what is your name',
            'tell me about yourself',
            'siapa kamu',
            'kamu siapa',
            'nama kamu siapa',
            'siapa dirimu',
            'dirimu siapa',
            'perkenalkan diri',
            'perkenalkan dirimu',
            'perkenalkan kamu',
            'perkenalkan dong',
            'tolong perkenalkan',
            'bisa perkenalkan',
            'introduce yourself',
            'introduce your self',
        ]);
    }

    private function identityIntroduction(string $message): string
    {
        $normalized = Str::of($message)->lower()->squish()->value();

        if ($this->containsAny($normalized, [
            'siapa',
            'perkenalkan',
            'dirimu',
            'diri mu',
            'kamu',
            'namamu',
            'nama kamu',
        ])) {
            return "Namaku Aetheria, asisten virtual RT yang siap bantu cek tagihan, pembayaran, agenda, direktori warga, dan informasi seputar RT. Ada yang bisa kubantu sekarang?";
        }

        if ($this->containsAny($normalized, [
            'who are you',
            'what is your name',
            'introduce yourself',
            'introduce your self',
            'tell me about yourself',
        ])) {
            return "I'm Aetheria, your neighborhood assistant. I can help with bills, payments, agendas, resident info, and general RT guidance. How can I support you today?";
        }

        $detected = LanguageDetector::detect($message);

        if ($detected === 'en') {
            return "I'm Aetheria, your neighborhood assistant. I can help with bills, payments, agendas, resident info, and general RT guidance. How can I support you today?";
        }

        return "Namaku Aetheria, asisten virtual RT yang siap bantu cek tagihan, pembayaran, agenda, direktori warga, dan informasi seputar RT. Ada yang bisa kubantu sekarang?";
    }

    private function shouldRouteToDummy(string $normalized): bool
    {
        $identityKeywords = [
            'tugas',
            'peran',
            'fungsi',
            'jobdesc',
            'kerja kamu',
            'kerjamu',
        ];

        $correctionKeywords = [
            'kamu salah',
            'kamu menjawab salah',
            'kamu jawab salah',
            'jawabanmu salah',
            'jawabannya salah',
            'jawaban salah',
            'jawab salah',
            'itu salah',
            'bukan itu',
            'jawab yang benar',
            'jangan jawab begitu',
            'jangan jawab terus',
            'tolong perbaiki',
            'tolong benerin',
            'perbaiki jawaban',
            'wrong answer',
            'you are wrong',
            'your answer is wrong',
        ];

        return $this->containsAny($normalized, array_merge($identityKeywords, $correctionKeywords));
    }

    private function respondViaDummy(
        array &$history,
        string $message,
        ToolRouter $router,
        callable $sendEvent,
        callable $logInteraction,
        float $start,
        int $userId,
        array $predictedIntents,
        string $respondedViaLabel = 'dummy_client'
    ): void {
        $dummyClient = app(DummyClient::class);
        $dummyResponse = $dummyClient->chat(
            array_merge($history, [['role' => 'user', 'content' => $message]])
        );
        $correctionEventId = $dummyClient->getLastCorrectionEventId();
        $smalltalkKind = $dummyClient->getSmalltalkKind();
        $content = $dummyResponse['content'] ?? 'Maaf, saya tidak mengerti.';

        $history[] = ['role' => 'user', 'content' => $message];
        $history[] = ['role' => 'assistant', 'content' => $content];
        session()->put('assistant_history_' . $userId, array_slice($history, -20));

        $sendEvent(['type' => 'token', 'content' => $content]);

        if (!empty($dummyResponse['meta'])) {
            $sendEvent(['type' => 'meta', 'content' => $dummyResponse['meta']]);
        }

        $sendEvent(['type' => 'done']);

        $duration = round((microtime(true) - $start) * 1000);
        $toolLog = $router->pullExecutionLog();
        $repetitionScore = $this->calculateRepetitionScore($history);

        $log = $logInteraction([
            'responded_via' => $respondedViaLabel,
            'tool_calls' => $toolLog,
            'tool_success' => $toolLog === [] ? null : collect($toolLog)->every(fn ($item) => (bool) ($item['success'] ?? false)),
            'duration_ms' => $duration,
            'intents' => $predictedIntents,
            'llm_provider' => 'Dummy',
            'provider_primary' => 'dummy_client',
            'provider_final' => 'dummy_client',
            'provider_fallback_from' => null,
            'correction_event_id' => $correctionEventId,
            'smalltalk_kind' => $smalltalkKind,
            'repetition_score' => $repetitionScore,
        ]);

        $dummyClient->finalizeKnowledgeFeedback($log?->id);
        $dummyClient->finalizeLlmSnapshot($log?->id);

        if ($log && !empty($dummyResponse['meta']['interaction_id'] ?? null)) {
            try {
                app(InteractionLearner::class)->attachInteractionLog(
                    (int) $dummyResponse['meta']['interaction_id'],
                    (int) $log->id
                );
            } catch (\Throwable $e) {
                Log::warning('Failed to attach dummy interaction log', [
                    'error' => $e->getMessage(),
                    'interaction_id' => $dummyResponse['meta']['interaction_id'],
                    'log_id' => $log->id,
                ]);
            }
        }
    }

    public function submitKnowledgeFeedback(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string',
            'helpful' => 'required|boolean',
            'note' => 'nullable|string|max:500',
        ]);

        $feedback = AssistantKnowledgeFeedback::where('token', $data['token'])->firstOrFail();

        if ($feedback->responded_at !== null) {
            return response()->json(['message' => 'Feedback sudah tercatat.'], 200);
        }

        $feedback->fill([
            'helpful' => $data['helpful'],
            'note' => $data['helpful'] ? null : ($data['note'] ?? null),
            'responded_at' => now(),
        ]);

        if ($feedback->user_id === null && $request->user()) {
            $feedback->user_id = $request->user()->id;
        }

        $feedback->save();

        if ($feedback->assistant_interaction_id) {
            try {
                $reasonSource = $feedback->note ?: Str::limit($feedback->question ?? '', 160);
                app(InteractionLearner::class)->recordFeedback(
                    (int) $feedback->assistant_interaction_id,
                    (bool) $data['helpful'],
                    (($data['helpful'] ?? false) ? 'kb_helpful:' : 'kb_not_helpful:') . $reasonSource
                );
            } catch (\Throwable $e) {
                Log::warning('Failed to record KB feedback label', [
                    'error' => $e->getMessage(),
                    'feedback_id' => $feedback->id,
                ]);
            }

            app(LlmSnapshotManager::class)->markFeedback(
                (int) $feedback->assistant_interaction_id,
                (bool) $data['helpful'],
                'kb_feedback',
                $feedback->note
            );

            app(AutoLearnSnapshotService::class)->scheduleFromInteraction(
                (int) $feedback->assistant_interaction_id,
                (bool) $data['helpful']
            );
        }

        return response()->json(['message' => 'Terima kasih atas feedbacknya!']);
    }

    public function submitInteractionFeedback(Request $request, InteractionLearner $interactionLearner, AutoLearnSnapshotService $autoLearn)
    {
        $data = $request->validate([
            'interaction_id' => 'required|integer',
            'helpful' => 'required|boolean',
            'reason' => 'nullable|string|max:480',
        ]);

        $user = $request->user();

        $interaction = DB::table('assistant_interactions')
            ->where('id', $data['interaction_id'])
            ->first();

        if (!$interaction) {
            return response()->json(['message' => 'Interaksi tidak ditemukan.'], 404);
        }

        if (!$user || (int) ($interaction->user_id ?? 0) !== (int) $user->id) {
            abort(403, 'Tidak boleh memberi feedback untuk percakapan pengguna lain.');
        }

        $reason = Str::of($data['reason'] ?? '')
            ->squish()
            ->limit(480, '...')
            ->value();

        $label = $data['helpful'] ? 'user_feedback:positive' : 'user_feedback:negative';
        if ($reason !== '') {
            $label .= ' | ' . $reason;
        }

        $interactionLearner->recordFeedback(
            (int) $data['interaction_id'],
            (bool) $data['helpful'],
            $label
        );

        app(LlmSnapshotManager::class)->markFeedback(
            (int) $data['interaction_id'],
            (bool) $data['helpful'],
            'interaction_feedback',
            $reason === '' ? null : $reason
        );

        $autoLearn->scheduleFromInteraction(
            (int) $data['interaction_id'],
            (bool) $data['helpful']
        );

        return response()->json(['message' => 'Terima kasih atas feedbacknya!']);
    }

    /**
     * @param array<int, string> $needles
     */
    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (Str::contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function calculateRepetitionScore(array $history): float
    {
        $messages = [];

        foreach ($history as $entry) {
            if (($entry['role'] ?? '') === 'assistant' && isset($entry['content'])) {
                $text = trim((string) $entry['content']);
                if ($text !== '') {
                    $messages[] = $text;
                }
            }
        }

        if (count($messages) < 2) {
            return 0.0;
        }

        $window = array_slice($messages, -5);
        $latest = array_pop($window);

        if ($latest === null || trim($latest) === '') {
            return 0.0;
        }

        $currentNgrams = array_unique($this->extractNgrams($latest));
        if ($currentNgrams === []) {
            return 0.0;
        }

        $otherNgrams = [];
        foreach ($window as $item) {
            $otherNgrams = array_merge($otherNgrams, $this->extractNgrams($item));
        }
        $otherNgrams = array_unique($otherNgrams);

        if ($otherNgrams === []) {
            return 0.0;
        }

        $overlap = count(array_intersect($currentNgrams, $otherNgrams));

        return round($overlap / max(count($currentNgrams), 1), 3);
    }

    /**
     * @return array<int,string>
     */
    private function extractNgrams(string $text): array
    {
        $normalized = Str::of($text)
            ->lower()
            ->replaceMatches('/[^a-z0-9áéíóúàèìòùäëïöüâêîôûçñ ]/iu', ' ')
            ->squish()
            ->value();

        if ($normalized === '') {
            return [];
        }

        $tokens = preg_split('/\s+/u', $normalized) ?: [];
        $ngrams = [];

        foreach ([3, 4, 5] as $size) {
            if (count($tokens) < $size) {
                continue;
            }

            for ($i = 0; $i <= count($tokens) - $size; $i++) {
                $segment = array_slice($tokens, $i, $size);
                $ngrams[] = implode(' ', $segment);
            }
        }

        return $ngrams;
    }
}
