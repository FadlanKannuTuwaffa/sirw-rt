<?php

namespace App\Services\Assistant;

use App\Models\AssistantKnowledgeFeedback;
use App\Models\Bill;
use App\Models\Event;
use App\Models\Payment;
use App\Models\User;
use App\Models\ConversationState;
use App\Models\RTOfficial;
use App\Services\AutoTranslator;
use App\Services\Assistant\AdaptiveRAGThreshold;
use App\Services\Assistant\AssistantMetrics;
use App\Services\Assistant\ClassifierService;
use App\Services\Assistant\ConversationStateRepository;
use App\Services\Assistant\Exceptions\OutOfContextException;
use App\Services\Assistant\GenerativeResponseEngine;
use App\Services\Assistant\InteractionLearner;
use App\Services\Assistant\RAGService;
use App\Services\Assistant\Reasoning\ReasoningDraft;
use App\Services\Assistant\Reasoning\ReasoningEngine;
use App\Services\Assistant\Reasoning\RuleLibrary;
use App\Services\Assistant\ResponseRewriter;
use App\Services\Assistant\ResponseStyle;
use App\Services\Assistant\ToolSchemaRegistry;
use App\Services\Assistant\Support\FactCorrectionQueue;
use App\Support\Assistant\LanguageDetector;
use App\Support\Assistant\TemporalInterpreter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class DummyClient implements LLMClient
{
    private const CORRECTION_TTL_MINUTES = 4320;
    private const MAX_KB_SOURCES = 3;
    private const RAG_LOW_CONFIDENCE_THRESHOLD = 0.45;
    private array $antonymMap = [
        'sudah' => 'belum',
        'belum' => 'sudah',
        'ada' => 'tidak ada',
        'tidak ada' => 'ada',
        'lunas' => 'belum bayar',
        'belum bayar' => 'lunas',
        'baru' => 'lama',
        'lama' => 'baru',
    ];

    private array $topicKeywords = [
        'payments' => ['pembayaran', 'bayar', 'lunas', 'riwayat', 'transaksi', 'dibayar'],
        'bills' => ['tagihan', 'tunggakan', 'iuran', 'bill'],
        'agenda' => ['agenda', 'event', 'kegiatan', 'rapat', 'jadwal', 'acara'],
        'agenda_time' => ['kapan', 'jam', 'mulai', 'dimulai', 'waktu'],
        'agenda_location' => ['lokasi', 'tempat', 'dimana', 'di mana', 'venue'],
        'agenda_tomorrow' => ['besok', 'tomorrow', 'esok'],
        'agenda_today' => ['hari ini', 'today', 'malam ini'],
        'residents_new' => ['warga baru', 'pendatang', 'anggota baru', 'resident baru'],
        'residents' => ['warga', 'resident', 'direktori', 'kontak', 'pengurus'],
        'finance' => ['rekap', 'keuangan', 'kas', 'dana'],
        'knowledge_base' => ['surat', 'pengantar', 'domisili', 'skck', 'sktm', 'prosedur rt', 'panduan rt', 'syarat rt', 'aturan rt'],
    ];
    private array $domainSignalKeywords = [
        'bills' => ['tagihan', 'iuran', 'tunggakan', 'bill'],
        'payments' => ['pembayaran', 'bayar', 'lunas', 'riwayat bayar', 'transaksi', 'dibayar'],
        'agenda' => ['agenda', 'acara', 'kegiatan', 'rapat', 'jadwal', 'event'],
        'finance' => ['rekap', 'keuangan', 'kas', 'laporan', 'dana'],
        'residents' => ['warga', 'resident', 'pengurus', 'kontak', 'direktori'],
        'residents_new' => ['warga baru', 'pendatang', 'anggota baru'],
        'knowledge_base' => ['surat', 'pengantar', 'domisili', 'skck', 'sktm', 'prosedur', 'panduan', 'aturan'],
    ];

    private array $negationWords = ['tidak', 'tak', 'nggak', 'gak', 'ga', 'bukan', 'negatif', 'no', 'tidak ada', 'ga ada', 'gak ada'];
    private array $suggestionMatrix = [
        'bills' => ['tagihan', 'tunggakan', 'iuran', 'bill', 'outstanding'],
        'payments' => ['pembayaran', 'riwayat bayar', 'sudah bayar', 'payment'],
        'agenda' => ['agenda', 'acara', 'kegiatan', 'rapat', 'event', 'schedule'],
        'facilities' => ['fasilitas', 'balai', 'aula', 'lapangan', 'pinjam', 'sewa'],
        'complaint' => ['keluhan', 'komplain', 'lapor', 'pengaduan'],
        'contacts' => ['kontak', 'hubungi', 'nomor', 'telepon', 'contact'],
        'residents' => ['warga', 'penduduk', 'direktori', 'resident'],
        'residents_new' => ['warga baru', 'pendatang', 'anggota baru'],
        'finance' => ['rekap', 'keuangan', 'kas', 'laporan keuangan'],
    ];

    private array $intentDescriptions = [
        'bills' => [
            'label_id' => 'Informasi tagihan & tunggakan',
            'label_en' => 'Outstanding bill information',
            'sample_id' => 'Contoh: "Tagihanku bulan ini apa?"',
            'sample_en' => 'Example: "What bills do I owe this month?"',
        ],
        'payments' => [
            'label_id' => 'Riwayat & status pembayaran',
            'label_en' => 'Payment history & status',
            'sample_id' => 'Contoh: "Pembayaran mana yang sudah lunas?"',
            'sample_en' => 'Example: "Which payments are already settled?"',
        ],
        'agenda' => [
            'label_id' => 'Agenda & kegiatan RT',
            'label_en' => 'RT agendas & events',
            'sample_id' => 'Contoh: "Agenda minggu ini apa?"',
            'sample_en' => 'Example: "What events are scheduled this week?"',
        ],
        'residents' => [
            'label_id' => 'Info warga & kontak pengurus',
            'label_en' => 'Resident info & committee contacts',
            'sample_id' => 'Contoh: "Berapa jumlah warga terdaftar?"',
            'sample_en' => 'Example: "How many residents are registered?"',
        ],
        'residents_new' => [
            'label_id' => 'Daftar warga baru',
            'label_en' => 'Recent new residents',
            'sample_id' => 'Contoh: "Siapa saja warga baru bulan ini?"',
            'sample_en' => 'Example: "Who moved in this month?"',
        ],
        'finance' => [
            'label_id' => 'Rekap dan laporan keuangan',
            'label_en' => 'Financial recap & reports',
            'sample_id' => 'Contoh: "Tolong buatkan rekap keuangan bulan ini."',
            'sample_en' => 'Example: "Generate this month\'s finance recap."',
        ],
        'summary' => [
            'label_id' => 'Ringkasan aktivitas penting',
            'label_en' => 'Important activity summary',
            'sample_id' => 'Contoh: "Apa info penting hari ini?"',
            'sample_en' => 'Example: "What important updates should I know?"',
        ],
        'knowledge_base' => [
            'label_id' => 'Pengetahuan & panduan RT',
            'label_en' => 'Neighborhood knowledge base',
            'sample_id' => 'Contoh: "Apa prosedur pengajuan surat domisili?"',
            'sample_en' => 'Example: "What is the procedure to request a domicile letter?"',
        ],
    ];

    private ?string $lastTopic = null;
    private ?array $lastData = null;
    private ?string $lastIntent = null;
    private string $language = 'id';
    private ?string $languageOverride = null;
    private bool $languageOverrideLocked = false;
    private ?AutoTranslator $translator = null;
    private ?ReasoningEngine $reasoning = null;
    private array $multiIntentPlanContext = [
        'plan' => [],
        'intents' => [],
    ];
    private ResponseStyle $responseStyle;
    private SuggestedFollowUps $suggestedFollowUps;
    private AssistantMetrics $metrics;
    private ClassifierService $classifier;
    private ResponseRewriter $responseRewriter;
    private AdaptiveRAGThreshold $ragThreshold;
    private GenerativeResponseEngine $generativeEngine;
    private string $currentRawMessage = '';
    private string $currentNormalizedMessage = '';
    private string $currentCorrectedMessage = '';
    private bool $inputHasNegation = false;
    private array $corrections = [];
    private ?array $pendingConfirmation = null;
    private array $conversationState = [];
    private ?int $stateUserId = null;
    private ?string $threadId = null;
    private ?string $pendingKnowledgeFeedbackToken = null;
    private ConversationStateRepository $stateRepository;
    private LexiconService $lexicon;
    private array $slotState = [];
    private ?array $pendingSlot = null;
    private ?TemporalInterpreter $temporal = null;
    private ?string $temporalTimezone = null;
    private array $kbSources = [];
    private ?string $timezonePreference = null;
    private ?string $lastKnowledgeAnswer = null;
    private ?string $lastKnowledgeQuestion = null;
    private ?float $lastKnowledgeConfidence = null;
    private ?array $pendingLlmSnapshot = null;
    private array $clarificationHistory = [];
    private array $recentGuardrails = [];
    private array $retryConstraints = [
        'include' => [],
        'exclude' => [],
    ];
    private array $lexicalContext = [
        'tokens' => [],
        'entities' => [],
        'oov' => [],
    ];
    private ?string $lastCorrectionHint = null;
    private ?string $nextCorrectionHint = null;
    private bool $lexiconCorrectionsHydrated = false;
    private array $riskyToolActions = [
        'export_financial_recap',
    ];
    private ToolSchemaRegistry $toolSchema;
    private int $clarificationTurns = 0;
    private ?string $userAddressColumn = null;
    private array $messageHistory = [];
    private array $recentOpeners = [];
    private ?int $lastInteractionSampleId = null;
    private SlangNormalizer $slangNormalizer;
    private ChitChatClassifier $chitChatClassifier;
    private OOCAdvisor $oocAdvisor;
    private array $slangHits = [];
    private ?string $smalltalkKind = null;
    private array $slotSchema = [
        'bills' => [
            'period' => [
                'required' => false,
                'question' => [
                    'id' => 'Tagihan periode mana yang ingin kamu cek?',
                    'en' => 'Which billing period would you like me to check?',
                ],
                'options' => [
                    ['value' => 'current_month', 'label_id' => 'Bulan ini', 'label_en' => 'This month', 'hints' => ['bulan ini', 'sekarang', 'this month', 'now']],
                    ['value' => 'previous_month', 'label_id' => 'Bulan lalu', 'label_en' => 'Last month', 'hints' => ['bulan lalu', 'kemarin', 'last month']],
                    ['value' => 'custom', 'label_id' => 'Sebut bulan tertentu', 'label_en' => 'Specify another month', 'hints' => []],
                ],
            ],
        ],
        'payments' => [
            'period' => [
                'required' => false,
                'question' => [
                    'id' => 'Pembayaran untuk periode apa yang ingin kamu lihat?',
                    'en' => 'Which payment period do you want to review?',
                ],
                'options' => [
                    ['value' => 'current_month', 'label_id' => 'Bulan ini', 'label_en' => 'This month', 'hints' => ['bulan ini', 'this month']],
                    ['value' => 'previous_month', 'label_id' => 'Bulan lalu', 'label_en' => 'Last month', 'hints' => ['bulan lalu', 'last month']],
                    ['value' => 'custom', 'label_id' => 'Sebut bulan tertentu', 'label_en' => 'Specify another month', 'hints' => []],
                ],
            ],
        ],
        'agenda' => [
            'range' => [
                'required' => true,
                'question' => [
                    'id' => 'Agenda untuk kapan yang ingin kamu lihat?',
                    'en' => 'Which time range would you like to check?',
                ],
                'options' => [
                    ['value' => 'today', 'label_id' => 'Hari ini', 'label_en' => 'Today', 'hints' => ['hari ini', 'today']],
                    ['value' => 'tomorrow', 'label_id' => 'Besok', 'label_en' => 'Tomorrow', 'hints' => ['besok', 'tomorrow', 'esok']],
                    ['value' => 'week', 'label_id' => '7 hari ke depan', 'label_en' => 'Next 7 days', 'hints' => ['minggu ini', 'this week', '7 hari']],
                    ['value' => 'month', 'label_id' => '30 hari ke depan', 'label_en' => 'Next 30 days', 'hints' => ['bulan ini', 'this month']],
                ],
            ],
        ],
        'finance' => [
            'period' => [
                'required' => true,
                'question' => [
                    'id' => 'Rekap periode apa yang kamu butuhkan?',
                    'en' => 'Which period do you need the recap for?',
                ],
                'options' => [
                    ['value' => 'current_month', 'label_id' => 'Bulan ini', 'label_en' => 'This month', 'hints' => ['bulan ini', 'this month']],
                    ['value' => 'previous_month', 'label_id' => 'Bulan lalu', 'label_en' => 'Last month', 'hints' => ['bulan lalu', 'last month']],
                    ['value' => 'custom', 'label_id' => 'Sebut bulan tertentu', 'label_en' => 'Specify another month', 'hints' => []],
                ],
            ],
            'format' => [
                'required' => false,
                'question' => [
                    'id' => 'Format ekspor apa yang kamu mau?',
                    'en' => 'Which export format would you like?',
                ],
                'options' => [
                    ['value' => 'pdf', 'label_id' => 'PDF', 'label_en' => 'PDF', 'hints' => ['pdf']],
                    ['value' => 'xlsx', 'label_id' => 'Excel/XLSX', 'label_en' => 'Excel/XLSX', 'hints' => ['excel', 'xlsx']],
                ],
            ],
        ],
        'residents' => [
            'target' => [
                'required' => true,
                'question' => [
                    'id' => 'Data warga seperti apa yang kamu butuhkan?',
                    'en' => 'What kind of resident info do you need?',
                ],
                'options' => [
                    ['value' => 'count', 'label_id' => 'Jumlah warga', 'label_en' => 'Total residents', 'hints' => ['berapa', 'jumlah', 'total']],
                    ['value' => 'new', 'label_id' => 'Warga baru', 'label_en' => 'New residents', 'hints' => ['baru', 'pendatang']],
                    ['value' => 'search', 'label_id' => 'Cari nama tertentu', 'label_en' => 'Look up a name', 'hints' => ['cari', 'nama', 'search']],
                ],
            ],
        ],
    ];

    private array $intentResponseTemplates = [
        'bills' => [
            'slot_filled' => [
                'id' => 'Siap, ini update tagihan yang kamu minta.',
                'en' => 'Alright, here is the bill update you asked for.',
            ],
            'slot_missing' => [
                'id' => 'Biar bisa cek tagihan, jawab pilihan berikut dulu ya.',
                'en' => 'Help me with the options first so I can pull the bills.',
            ],
        ],
        'payments' => [
            'slot_filled' => [
                'id' => 'Sip, ini ringkasan pembayaran terbarumu.',
                'en' => 'Here is the latest payment summary.',
            ],
            'slot_missing' => [
                'id' => 'Sebut periode pembayarannya dulu ya supaya pas.',
                'en' => 'Tell me the payment period first so we stay precise.',
            ],
        ],
        'agenda' => [
            'slot_filled' => [
                'id' => 'Siap, ini agenda terdekat yang sudah kucek.',
                'en' => 'All good, these are the upcoming agendas.',
            ],
            'slot_missing' => [
                'id' => 'Pilih rentang waktunya dulu ya supaya daftar agendanya tepat.',
                'en' => 'Pick the time range first so I can narrow the agenda list.',
            ],
        ],
        'finance' => [
            'slot_filled' => [
                'id' => 'Siap, rekap keuangannya beres. Berikut detailnya.',
                'en' => 'Finance recap ready. Here are the details.',
            ],
            'slot_missing' => [
                'id' => 'Sebut periode rekap atau format yang kamu perlukan dulu ya.',
                'en' => 'Let me know the recap period or format you need first.',
            ],
        ],
        'residents' => [
            'slot_filled' => [
                'id' => 'Oke, ini data warga yang kamu minta.',
                'en' => 'Here is the resident data you asked for.',
            ],
            'slot_missing' => [
                'id' => 'Pilih dulu jenis data warga (jumlah, pencarian, atau warga baru).',
                'en' => 'Pick the resident data type (count, search, or new arrivals) first.',
            ],
        ],
        'residents_new' => [
            'slot_filled' => [
                'id' => 'Berikut daftar warga baru terbaru.',
                'en' => 'Here is the latest list of new residents.',
            ],
            'slot_missing' => [
                'id' => 'Kuharus tahu rentang waktunya dulu untuk daftar warga baru.',
                'en' => 'I will need the time range before I can list the new residents.',
            ],
        ],
        'knowledge_base' => [
            'slot_filled' => [
                'id' => 'Ini ringkasan dari dokumen yang relevan.',
                'en' => 'Here is the summary from the relevant documents.',
            ],
            'slot_missing' => [
                'id' => 'Kasih tahu nama prosedur atau dokumennya dulu ya.',
                'en' => 'Let me know the procedure or document name first.',
            ],
        ],
        'general' => [
            'slot_filled' => [
                'id' => 'Siap, berikut jawabannya.',
                'en' => 'Got it, here is the answer.',
            ],
            'slot_missing' => [
                'id' => 'Butuh satu detail lagi sebelum lanjut ya.',
                'en' => 'I just need one more detail before we continue.',
            ],
        ],
        'default' => [
            'slot_filled' => [
                'id' => 'Siap, ini detailnya.',
                'en' => 'All set, here are the details.',
            ],
            'slot_missing' => [
                'id' => 'Butuh satu detail lagi sebelum lanjut ya.',
                'en' => 'I just need one more detail before continuing.',
            ],
        ],
    ];
    private array $evaluationEvents = [
        'guardrails' => [],
        'tools' => [],
    ];
    private string $lastResponseContent = '';
    private ?float $lastResponseConfidence = null;
    /** @var callable|null */
    private $lastIntentReplayHandler = null;
    private array $lastIntentReplayMeta = [];
    private CorrectionMemoryService $correctionMemory;
    private array $memoryBias = [];
    private array $memoryStyle = [];
    private array $memoryFewshot = [];
    private array $memoryFacts = [];
    private ?string $fewshotClosing = null;
    private ?string $classifierBiasToken = null;
    private array $forbiddenPhrases = [];
    private ?array $pendingCorrectionFeedback = null;
    private ?int $lastCorrectionEventId = null;
    private ?int $userOrgId = null;
    private bool $introduceSelfPreference = true;
    private CorrectionPromoter $correctionPromoter;
    private FactCorrectionQueue $factCorrectionQueue;

    public function __construct(
        ConversationStateRepository $stateRepository,
        LexiconService $lexicon,
        ToolSchemaRegistry $toolSchema,
        ResponseStyle $responseStyle,
        ResponseRewriter $responseRewriter,
        SuggestedFollowUps $suggestedFollowUps,
        AssistantMetrics $metrics,
        ClassifierService $classifier,
        CorrectionMemoryService $correctionMemory,
        SlangNormalizer $slangNormalizer,
        ChitChatClassifier $chitChatClassifier,
        OOCAdvisor $oocAdvisor,
        AdaptiveRAGThreshold $ragThreshold,
        GenerativeResponseEngine $generativeEngine,
        FactCorrectionQueue $factCorrectionQueue,
        ?CorrectionPromoter $correctionPromoter = null
    ) {
        $this->stateRepository = $stateRepository;
        $this->lexicon = $lexicon;
        $this->toolSchema = $toolSchema;
        $this->responseStyle = $responseStyle;
        $this->responseRewriter = $responseRewriter;
        $this->suggestedFollowUps = $suggestedFollowUps;
        $this->metrics = $metrics;
        $this->classifier = $classifier;
        $this->correctionMemory = $correctionMemory;
        $this->slangNormalizer = $slangNormalizer;
        $this->chitChatClassifier = $chitChatClassifier;
        $this->oocAdvisor = $oocAdvisor;
        $this->ragThreshold = $ragThreshold;
        $this->generativeEngine = $generativeEngine;
        $this->factCorrectionQueue = $factCorrectionQueue;
        $this->correctionPromoter = $correctionPromoter ?? app(CorrectionPromoter::class);
    }

    public function __destruct()
    {
        $this->releaseClassifierBias();
    }

    public static function resetConversationState(): void
    {
        if (Schema::hasTable('conversation_states')) {
            ConversationState::query()->delete();
        }
    }

    private function bootstrapState(?int $userId): void
    {
        $this->stateUserId = $userId;
        $this->threadId = $this->resolveThreadId($userId);
        $this->conversationState = $this->stateRepository->get($userId, $this->threadId);
        $this->lastIntent = $this->conversationState['last_intent'] ?? null;
        $this->lastTopic = $this->conversationState['last_topic'] ?? null;
        $this->lastData = $this->conversationState['last_data'] ?? [];
        $this->slotState = $this->conversationState['slots'] ?? [];
        $this->pendingSlot = $this->conversationState['pending_slots'] ?? null;
        $this->corrections = $this->conversationState['corrections'] ?? [];
        $this->pendingConfirmation = $this->conversationState['pending_confirmation'] ?? null;
        $this->kbSources = $this->conversationState['kb_sources'] ?? [];
        $this->pendingCorrectionFeedback = $this->conversationState['pending_correction'] ?? null;
        $this->userOrgId = $this->resolveUserOrgId($userId);
        $metadata = $this->conversationState['metadata'] ?? [];
        $interactionId = $metadata['last_interaction_id'] ?? null;
        $this->lastInteractionSampleId = is_numeric($interactionId) ? (int) $interactionId : null;
        $this->lastCorrectionHint = is_string($metadata['last_correction_hint'] ?? null)
            ? $metadata['last_correction_hint']
            : null;
        $this->clarificationTurns = (int) ($metadata['clarification_turns'] ?? 0);
        $this->clarificationHistory = is_array($metadata['clarification_history'] ?? null)
            ? $metadata['clarification_history']
            : [];
        $this->timezonePreference = $this->normalizeTimezone($metadata['timezone'] ?? null);
        $this->pruneExpiredCorrections(true);
        $this->lastKnowledgeAnswer = is_string($metadata['last_kb_answer'] ?? null)
            ? $metadata['last_kb_answer']
            : null;
        $this->lastKnowledgeQuestion = is_string($metadata['last_kb_question'] ?? null)
            ? $metadata['last_kb_question']
            : null;
        $confidence = $metadata['last_kb_confidence'] ?? null;
        $this->lastKnowledgeConfidence = is_numeric($confidence) ? (float) $confidence : null;
        $this->retryConstraints = $this->normalizeRetryConstraints($metadata['retry_constraints'] ?? []);
        $storedLanguageOverride = $this->normalizeLanguageCode($metadata['language_override'] ?? null);
        $lockedFlag = $metadata['language_override_locked'] ?? null;
        $this->languageOverrideLocked = $lockedFlag !== null
            ? (bool) $lockedFlag
            : ($storedLanguageOverride !== null);
        $this->languageOverride = $storedLanguageOverride;
        $this->recentOpeners = is_array($metadata['recent_openers'] ?? null)
            ? $metadata['recent_openers']
            : [];
        $recentGuardrails = $metadata['recent_guardrails'] ?? [];
        $this->recentGuardrails = is_array($recentGuardrails)
            ? array_values(array_filter($recentGuardrails, fn($value) => is_string($value) && $value !== ''))
            : [];

        $this->lexicon->setThreadContext($this->stateUserId, $this->threadId);
        $this->hydrateLexiconCorrectionsFromState();
        $this->applyCorrectionMemoryAdjustments($userId);
    }

    private function resolveThreadId(?int $userId): string
    {
        try {
            $request = request();
        } catch (\Throwable) {
            $request = null;
        }

        if ($request && $request->attributes->has('assistant_thread_id')) {
            return (string) $request->attributes->get('assistant_thread_id');
        }

        return 'user:' . ($userId ?? 'guest');
    }

    private function resolveUserOrgId(?int $userId): ?int
    {
        if ($userId === null) {
            return null;
        }

        static $hasOrgColumn;

        if ($hasOrgColumn === null) {
            $hasOrgColumn = Schema::hasTable('users') && Schema::hasColumn('users', 'org_id');
        }

        if (!$hasOrgColumn) {
            return null;
        }

        return User::query()
            ->whereKey($userId)
            ->value('org_id');
    }

    private function finalizeResponse(array $response): array
    {
        if ($this->threadId === null) {
            $this->threadId = $this->resolveThreadId($this->stateUserId);
        }

        $this->conversationState['last_intent'] = $this->lastIntent;
        $this->conversationState['last_topic'] = $this->lastTopic;
        $this->conversationState['last_data'] = $this->lastData;
        $this->conversationState['slots'] = $this->slotState;
        $this->conversationState['pending_slots'] = $this->pendingSlot;
        $this->conversationState['corrections'] = $this->corrections;
        $this->conversationState['pending_confirmation'] = $this->pendingConfirmation;
        $this->conversationState['kb_sources'] = $this->kbSources;
        $metadata = $this->conversationState['metadata'] ?? [];
        $metadata['clarification_turns'] = $this->clarificationTurns;
        $metadata['clarification_history'] = $this->clarificationHistory;
        $metadata['timezone'] = $this->timezonePreference;
        $metadata['last_kb_answer'] = $this->lastKnowledgeAnswer;
        $metadata['last_kb_question'] = $this->lastKnowledgeQuestion;
        $metadata['last_kb_confidence'] = $this->lastKnowledgeConfidence;
        $metadata['retry_constraints'] = $this->retryConstraints;
        $metadata['language_override'] = $this->languageOverrideLocked ? $this->languageOverride : null;
        $metadata['language_override_locked'] = $this->languageOverrideLocked;
        $nextHint = $this->nextCorrectionHint ?? $this->lastCorrectionHint;
        $metadata['last_correction_hint'] = $nextHint;
        $this->lastCorrectionHint = $nextHint;
        $this->nextCorrectionHint = null;
        $metadata['recent_openers'] = array_values(array_slice($this->recentOpeners, -5));
        $metadata['recent_guardrails'] = $this->recentGuardrails;
        $metadata['last_interaction_id'] = $this->lastInteractionSampleId;
        $this->conversationState['metadata'] = $metadata;
        $this->conversationState['pending_correction'] = $this->pendingCorrectionFeedback;

        $this->stateRepository->put($this->stateUserId, $this->threadId, $this->conversationState);

        return $response;
    }

    private function applyCorrectionMemoryAdjustments(?int $userId): void
    {
        $memory = $this->correctionMemory->apply($userId, $this->userOrgId, $this->threadId);
        $this->memoryBias = $memory['bias'] ?? [];
        $this->memoryStyle = $memory['style'] ?? [];
        $this->memoryFewshot = $memory['fewshot'] ?? [];
        $this->memoryFacts = $memory['facts'] ?? [];
        $this->fewshotClosing = null;
        $this->forbiddenPhrases = $this->normalizeForbiddenPhrases($memory['forbidden'] ?? []);

        foreach ($memory['syn'] ?? [] as $entry) {
            $alias = $entry['alias'] ?? null;
            $canonical = $entry['canonical'] ?? null;

            if (!is_string($alias) || $alias === '' || !is_string($canonical) || $canonical === '') {
                continue;
            }

            $ttl = isset($entry['ttl']) ? (int) $entry['ttl'] : 3600;
            $this->lexicon->addCorrectionAlias($alias, $canonical, max($ttl, 60));
        }

        $this->releaseClassifierBias();
        if ($this->memoryBias !== []) {
            $this->classifierBiasToken = $this->classifier->pushIntentBias($this->memoryBias);
        }

        if (isset($this->memoryStyle['language'])) {
            $this->setLanguageOverride($this->memoryStyle['language'], true);
            $this->language = $this->languageOverride ?? $this->language;
        }

        if (array_key_exists('introduce_self', $this->memoryStyle)) {
            $this->introduceSelfPreference = (bool) $this->memoryStyle['introduce_self'];
        }

        $this->deriveFewshotHints();
    }

    private function releaseClassifierBias(): void
    {
        if ($this->classifierBiasToken === null) {
            return;
        }

        $this->classifier->popIntentBias($this->classifierBiasToken);
        $this->classifierBiasToken = null;
    }

    /**
     * @param  array<int,mixed>  $entries
     * @return array<int,string>
     */
    private function normalizeForbiddenPhrases(array $entries): array
    {
        $clean = [];

        foreach ($entries as $entry) {
            if (!is_string($entry)) {
                continue;
            }

            $value = Str::of($entry)
                ->squish()
                ->trim(" \"'“”‘’.?!")
                ->value();

            if ($value === '') {
                continue;
            }

            $clean[] = $value;
        }

        return array_values(array_unique($clean));
    }

    private function applyRequestTimezone(): void
    {
        try {
            $request = request();
        } catch (\Throwable) {
            $request = null;
        }

        $candidates = [];

        if ($request) {
            $candidates[] = $request->attributes->get('assistant_timezone');
            $candidates[] = $request->input('timezone');
            $candidates[] = $request->header('X-Assistant-Timezone');

            $user = $request->user();
            if ($user && isset($user->experience_preferences['timezone'])) {
                $candidates[] = $user->experience_preferences['timezone'];
            }
        }

        $candidates[] = $this->timezonePreference;

        foreach ($candidates as $candidate) {
            $normalized = $this->normalizeTimezone($candidate);
            if ($normalized === null) {
                continue;
            }

            if ($normalized !== $this->timezonePreference) {
                $this->timezonePreference = $normalized;
                $this->persistStateFragment([
                    'metadata' => [
                        'timezone' => $normalized,
                    ],
                ]);
            }

            return;
        }

        if ($this->timezonePreference === null) {
            $this->timezonePreference = config('app.timezone', 'UTC');
        }
    }

    private function normalizeTimezone(mixed $timezone): ?string
    {
        if (!is_string($timezone)) {
            return null;
        }

        $candidate = trim($timezone);

        if ($candidate === '') {
            return null;
        }

        try {
            new \DateTimeZone($candidate);

            return $candidate;
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeLanguageCode(mixed $language): ?string
    {
        if (!is_string($language)) {
            return null;
        }

        $candidate = Str::of($language)->lower()->squish()->value();
        if ($candidate === '') {
            return null;
        }

        $indonesian = ['id', 'id-id', 'indonesia', 'bahasa indonesia', 'indo'];
        $english = ['en', 'en-us', 'english', 'eng', 'bahasa inggris', 'inggris'];
        $javanese = ['jv', 'jw', 'jawa', 'bahasa jawa', 'basa jawa', 'javanese', 'jowo'];
        $sundanese = ['su', 'sun', 'snd', 'sunda', 'bahasa sunda', 'basa sunda', 'sundanese'];

        if (in_array($candidate, $indonesian, true)) {
            return 'id';
        }

        if (in_array($candidate, $english, true)) {
            return 'en';
        }

        if (in_array($candidate, $javanese, true)) {
            return 'jv';
        }

        if (in_array($candidate, $sundanese, true)) {
            return 'su';
        }

        return null;
    }

    private function applyRequestLanguagePreference(): void
    {
        if ($this->languageOverrideLocked) {
            return;
        }

        try {
            $request = request();
        } catch (\Throwable) {
            $request = null;
        }

        if (!$request) {
            return;
        }

        $user = $request->user();
        if (!$user) {
            return;
        }

        $preferences = $user->experience_preferences ?? [];
        $preferred = $this->normalizeLanguageCode($preferences['language'] ?? null);

        if ($preferred === null) {
            return;
        }

        $this->setLanguageOverride($preferred, false);
    }

    private function setLanguageOverride(?string $language, bool $locked): void
    {
        $normalized = $this->normalizeLanguageCode($language);
        $this->languageOverride = $normalized;
        $this->languageOverrideLocked = $locked;
    }

    /**
     * @return array{code:?string,pure:bool}|null
     */
    private function detectLanguageInstruction(string $rawMessage, string $normalizedMessage): ?array
    {
        $normalized = Str::of($normalizedMessage)->lower()->squish()->value();
        if ($normalized === '') {
            return null;
        }

        $instructionTokens = [
            'jawab',
            'balas',
            'balasin',
            'jawabin',
            'tolong',
            'please',
            'pls',
            'pake',
            'pakai',
            'gunakan',
            'use',
            'switch',
            'reply',
            'respond',
            'hanya',
            'only',
            'aja',
            'dong',
            'ya',
        ];

        $hasInstructionToken = false;
        foreach ($instructionTokens as $token) {
            if (Str::contains($normalized, $token)) {
                $hasInstructionToken = true;
                break;
            }
        }

        $shortLanguageOnly = in_array($normalized, [
            'english',
            'inggris',
            'bahasa inggris',
            'indonesia',
            'bahasa indonesia',
            'indo',
            'indonesian',
            'bahasa jawa',
            'jawa',
            'basa jawa',
            'javanese',
            'bahasa sunda',
            'basa sunda',
            'sunda',
            'sundanese',
        ], true);

        $resetIndicators = [
            'ikut bahasa',
            'ikuti bahasa',
            'sesuaikan bahasa',
            'ikutin bahasaku',
            'ikut bahasaku',
            'sesuaikan saja bahasanya',
            'bebas bahasanya',
            'pakai bahasa yang sama',
            'gunakan bahasa yang sama',
            'default language',
            'reset language',
            'balik ke bahasa semula',
        ];

        foreach ($resetIndicators as $indicator) {
            if (Str::contains($normalized, $indicator)) {
                return [
                    'code' => null,
                    'pure' => $this->isPureLanguagePreferenceMessage($normalized),
                ];
            }
        }

        $negationPattern = '/\b(jangan|jgn|don\'?t|do\s+not|gak\s+usah|tidak\s+usah)\b[^\n]{0,40}?\b(bahasa\s+inggris|english|bahasa\s+indonesia|indonesia|indo|inggris|bahasa\s+jawa|basa\s+jawa|javanese|jawa|jowo|bahasa\s+sunda|basa\s+sunda|sunda|sundanese)\b/u';
        if (preg_match($negationPattern, $normalized, $match)) {
            $target = $this->languageCodeFromToken($match[2] ?? '');
            if ($target !== null) {
                $fallback = [
                    'en' => 'id',
                    'jv' => 'id',
                    'su' => 'id',
                    'id' => 'en',
                ][$target] ?? 'id';

                return [
                    'code' => $fallback,
                    'pure' => $this->isPureLanguagePreferenceMessage($normalized),
                ];
            }
        }

        $languageKeywords = [
            'id' => ['bahasa indonesia', 'indonesia', 'indo', 'indonesian'],
            'en' => ['bahasa inggris', 'english', 'inggris'],
            'jv' => ['bahasa jawa', 'basa jawa', 'jawa', 'javanese', 'jowo'],
            'su' => ['bahasa sunda', 'basa sunda', 'sunda', 'sundanese'],
        ];

        foreach ($languageKeywords as $code => $keywords) {
            foreach ($keywords as $keyword) {
                if (Str::contains($normalized, $keyword) && ($hasInstructionToken || $shortLanguageOnly)) {
                    return [
                        'code' => $code,
                        'pure' => $this->isPureLanguagePreferenceMessage($normalized),
                    ];
                }
            }
        }

        return null;
    }

    private function languageCodeFromToken(?string $token): ?string
    {
        $value = Str::of($token ?? '')->lower()->squish()->value();
        if ($value === '') {
            return null;
        }

        if (Str::contains($value, ['inggris', 'english'])) {
            return 'en';
        }

        if (Str::contains($value, ['indo', 'indonesia', 'indonesian'])) {
            return 'id';
        }

        if (Str::contains($value, ['jawa', 'javanese', 'basa jawa', 'jowo'])) {
            return 'jv';
        }

        if (Str::contains($value, ['sunda', 'sundanese', 'basa sunda'])) {
            return 'su';
        }

        return null;
    }

    private function isPureLanguagePreferenceMessage(string $normalized): bool
    {
        if ($normalized === '') {
            return false;
        }

        if (Str::length($normalized) > 80) {
            return false;
        }

        $intentKeywords = [
            'tagihan',
            'tunggakan',
            'pembayaran',
            'riwayat',
            'agenda',
            'kegiatan',
            'acara',
            'warga',
            'resident',
            'laporan',
            'rekap',
            'keluhan',
            'complaint',
            'surat',
            'prosedur',
            'event',
            'info',
            'informasi',
            'jadwal',
            '?',
        ];

        foreach ($intentKeywords as $keyword) {
            if ($keyword === '?' && Str::contains($normalized, '?')) {
                return false;
            }

            if ($keyword !== '?' && Str::contains($normalized, $keyword)) {
                return false;
            }
        }

        return true;
    }

    private function languagePreferenceAcknowledgement(?string $code): string
    {
        if ($code === 'en') {
            return $this->text(
                'Siap, selanjutnya aku balas dalam Bahasa Inggris ya.',
                'Got it, I will switch to English.'
            );
        }

        if ($code === 'id') {
            return $this->text(
                'Oke, aku akan jawab pakai Bahasa Indonesia ya.',
                'Sure, I will answer in Indonesian.'
            );
        }

        if ($code === 'jv') {
            return $this->text(
                'Oke, aku bakal jawab nganggo Bahasa Jawa ya.',
                'Sure, I will answer in Javanese.'
            );
        }

        if ($code === 'su') {
            return $this->text(
                'Mangga, ayeuna aku bakal ngajawab nganggo Basa Sunda.',
                'Alright, I will answer in Sundanese.'
            );
        }

        return $this->text(
            'Baik, aku ikuti saja bahasa yang kamu pakai.',
            'Alright, I will follow whichever language you use.'
        );
    }
    public function chat(array $messages, array $tools = []): array
    {
        $this->messageHistory = $messages;
        $this->lastCorrectionEventId = null;
        $this->smalltalkKind = null;
        $this->slangHits = [];
        $userId = Auth::id();
        $this->bootstrapState($userId);
        $this->resetEvaluationEvents();
        $this->applyRequestTimezone();
        $this->applyRequestLanguagePreference();
        $lastMessage = end($messages);
        $rawOriginal = (string) ($lastMessage['content'] ?? '');

        $this->currentRawMessage = $rawOriginal;
        [$rawMessage, $slangHits] = $this->slangNormalizer->normalize($rawOriginal);
        $this->slangHits = $slangHits;

        $detectedLanguage = trim($rawMessage) !== ''
            ? LanguageDetector::detect($rawMessage)
            : 'id';
        $this->language = $this->languageOverride ?? $detectedLanguage;

        $rawLower = Str::of($rawMessage)->lower()->squish()->value();
        if ($languageDirective = $this->detectLanguageInstruction($rawMessage, $rawLower)) {
            $this->setLanguageOverride($languageDirective['code'], true);
            $this->language = $this->languageOverride ?? $detectedLanguage;

            if ($languageDirective['pure']) {
                return $this->respond($this->languagePreferenceAcknowledgement($languageDirective['code']));
            }
        }

        $correctedMessage = $this->applyCorrectionsToInput($rawMessage);
        $this->currentCorrectedMessage = $correctedMessage;
        $normalizedMessage = $this->normalizeWithSynonyms($correctedMessage);
        $this->currentNormalizedMessage = $normalizedMessage;
        $this->nextCorrectionHint = $this->guessCorrectionHint($rawMessage, $correctedMessage);
        $this->inputHasNegation = $this->detectInputNegation($normalizedMessage);
        $recentMessages = array_slice($messages, -6);
        $context = Str::of(implode(' ', array_map(fn($m) => $m['content'] ?? '', $recentMessages)))
            ->lower()
            ->value();
        $isQuestion = (bool) preg_match(
            '/(\?|apa|gimana|bagaimana|kapan|dimana|di mana|berapa|siapa|kenapa|mengapa|bisakah|boleh|yang mana|itu apa|detail|lanjut)/i',
            $rawMessage
        );
        if ($this->contains($normalizedMessage, ['cuaca', 'weather'])) {
            return $this->respond($this->text(
                'Aku belum bisa cek info cuaca secara real-time. Coba aplikasi BMKG atau layanan cuaca favoritmu ya.',
                'I am not able to check live weather info yet. Please use your preferred weather service for the latest update.'
            ));
        }
        if ($chitChatResponse = $this->handleChitChatKind($normalizedMessage, $rawOriginal, $userId)) {
            return $chitChatResponse;
        }

        if ($this->shouldForceOffTopicEarly($normalizedMessage, $rawOriginal)) {
            $this->escalateOffTopic($rawOriginal, 'early_guard');
        }

        if (!empty($tools)) {
            return $this->handleToolBasedQuery($normalizedMessage, $tools, $userId);
        }
        if ($confirmationResponse = $this->handlePendingConfirmation($normalizedMessage, $rawMessage, $userId)) {
            return $confirmationResponse;
        }
        if ($pendingFeedbackResponse = $this->handlePendingCorrectionFeedback($rawMessage, $normalizedMessage, $userId)) {
            return $pendingFeedbackResponse;
        }
        if ($directStyleResponse = $this->handleDirectStyleInstruction($rawMessage, $normalizedMessage, $userId)) {
            return $directStyleResponse;
        }
        if ($this->isGeneralCorrectionMessage($normalizedMessage, $rawLower)) {
            return $this->handleGeneralCorrectionMessage($normalizedMessage, $rawMessage, $userId);
        }
        if ($correctionResponse = $this->handleCorrectionMessage($rawMessage, $normalizedMessage, $userId)) {
            return $correctionResponse;
        }
        if ($retryResponse = $this->handleRetryRequest($normalizedMessage, $rawMessage, $userId)) {
            return $retryResponse;
        }
        if ($multiIntentResponse = $this->attemptMultiIntentPlan($normalizedMessage, $userId)) {
            return $multiIntentResponse;
        }
        if ($this->pendingSlot !== null && $this->shouldBypassPendingSlot($normalizedMessage, $rawMessage)) {
            $this->pendingSlot = null;
        }
        if ($pendingResponse = $this->handlePendingSlotAnswer($normalizedMessage, $rawMessage, $userId)) {
            return $pendingResponse;
        }
        if ($this->matches($normalizedMessage, ['^halo$', '^hai$', '^hi$', '^hey$', '^hello$'])) {
            return $this->proactiveGreeting($userId);
        }
        if ($this->isIdentityIntroductionQuestion($normalizedMessage)) {
            return $this->respondIdentityIntroduction();
        }
        if ($this->isIdentityScopeQuestion($normalizedMessage)) {
            return $this->respondIdentityScope();
        }
        if ($this->isRecognitionQuestion($normalizedMessage)) {
            $this->lastIntent = 'identity_check';
            $this->lastTopic = 'smalltalk';
            $this->lastData = [];

            return $this->respondRecognition($userId);
        }
        if (
            $this->contains($normalizedMessage, ['terima kasih', 'makasih', 'thanks', 'thank you', 'thx', 'tq']) ||
            ($rawLower !== '' && $this->contains($rawLower, ['terima kasih', 'makasih', 'thanks', 'thank you', 'thx', 'tq'])) ||
            $this->matches($normalizedMessage, ['(oke|ok|okay).*(makasih|thanks|thx|terima)', '(makasih|thanks|thx|thank).*(bro|gan|boss|ya)'])
        ) {
            $this->lastIntent = 'gratitude_reply';
            $this->lastTopic = 'smalltalk';
            $this->lastData = [];

            $this->recordInteractionFeedback(true, 'user_gratitude_autolabel');

            $responses = $this->isEnglish()
                ? [
                    "You're welcome! Happy to help. ??",
                    'Glad I could assist-need anything else?',
                    'No worries at all. Just let me know if you need anything else.',
                    'Anytime! Hope the info helps.',
                ]
                : [
                    'Sama-sama! Senang bisa membantu Anda. ??',
                    'Sama-sama! Senang bisa membantu Anda. Ada lagi yang bisa dibantu?',
                    'Sama-sama! Jangan ragu untuk bertanya lagi jika ada yang perlu dibantu.',
                    'Iya, sama-sama! Semoga informasinya bermanfaat.',
                ];

            return $this->respond($this->randomChoice($responses));
        }
        if ($this->isSmallTalk($normalizedMessage, $rawMessage)) {
            $this->lastIntent = 'smalltalk';
            $this->lastTopic = 'smalltalk';
            $this->lastData = [];

            return $this->respond($this->text(
                'Aku baik-baik saja dan siap bantu. Ada yang ingin kamu tanyakan soal urusan RT?',
                "I'm doing well and ready to help. What would you like to ask about the neighborhood?"
            ));
        }
        if ($this->isFollowUpQuestion($normalizedMessage)) {
            return $this->handleFollowUp($normalizedMessage, $context, $userId);
        }
        if ($this->matches($normalizedMessage, ['jadwal.*(ronda|jaga|keamanan)'])) {
            return $this->respond($this->text(
                'Jadwal ronda ada di menu **Agenda** atau bisa konfirmasi ke koordinator keamanan RT.',
                'The patrol schedule is listed in the **Agenda** menu, or you can confirm it with the neighborhood security coordinator.'
            ));
        }
        if (
            $this->contains($normalizedMessage, ['agenda', 'acara', 'kegiatan', 'event', 'rapat', 'pertemuan', 'schedule', 'jadwal']) ||
            $this->matches($normalizedMessage, ['(agenda|acara|kegiatan|jadwal).*(apa|bulan|minggu|kapan)'])
        ) {
            if ($this->contains($normalizedMessage, ['besok', 'tomorrow'])) {
                $this->rememberSlotValue('agenda', 'range', 'tomorrow');
                return $this->respondAgenda($normalizedMessage, $rawMessage, $userId, 'tomorrow');
            }
            if ($this->contains($normalizedMessage, ['hari ini', 'today'])) {
                $this->rememberSlotValue('agenda', 'range', 'today');
                return $this->respondAgenda($normalizedMessage, $rawMessage, $userId, 'today');
            }
            if ($this->contains($normalizedMessage, ['minggu ini', 'week', 'seminggu'])) {
                $this->rememberSlotValue('agenda', 'range', 'week');
                return $this->respondAgenda($normalizedMessage, $rawMessage, $userId, 'week');
            }

            return $this->respondAgenda($normalizedMessage, $rawMessage, $userId, 'month');
        }
        if ($this->contains($normalizedMessage, ['cuaca', 'weather'])) {
            return $this->respond($this->text(
                'Aku belum bisa cek info cuaca secara real-time. Coba aplikasi BMKG atau layanan cuaca favoritmu ya.',
                'I am not able to check live weather info yet. Please use your preferred weather service for the latest update.'
            ));
        }
        if ($this->contains($normalizedMessage, ['urgent', 'penting', 'prioritas'])) {
            return $this->getUrgentBills($userId);
        }
        if ($this->matches($normalizedMessage, ['(budget|punya|ada).*(\d+)'])) {
            return $this->filterBillsByBudget($normalizedMessage, $userId);
        }
        if ($this->contains($normalizedMessage, ['info penting', 'ada apa', 'update'])) {
            return $this->getImportantInfo($userId);
        }
        if (
            $this->matches($normalizedMessage, ['(belum|tidak|nggak|gak).*(bayar|lunas)', '(bayar|lunas).*(belum|tidak|nggak|gak)']) ||
            $this->contains($normalizedMessage, ['belum bayar', 'tunggakan', 'outstanding', 'not paid', 'hutang'])
        ) {
            return $this->respondBills($userId, $normalizedMessage, $rawMessage);
        }
        if ($this->matches($normalizedMessage, ['(cara|bagaimana|metode).*(bayar|pembayaran|pay)'])) {
            return $this->respond('Kamu bisa bayar melalui transfer bank, setor langsung ke bendahara, atau payment gateway. Detail lengkap ada di menu **Tagihan**.');
        }
        if (
            $this->matches($normalizedMessage, ['(pembayaran|bayar).*(bulan ini|this month|bulan sekarang)']) ||
            ($this->contains($normalizedMessage, ['pembayaran', 'bayar']) && $this->contains($normalizedMessage, ['bulan ini', 'berapa']))
        ) {
            return $this->respondPayments($userId, $normalizedMessage, $rawMessage);
        }
        if ($this->contains($normalizedMessage, ['tagihan', 'iuran']) && !$this->contains($normalizedMessage, ['sudah', 'udah', 'riwayat', 'history'])) {
            return $this->respondBills($userId, $normalizedMessage, $rawMessage);
        }
        if ($this->contains($normalizedMessage, ['bayar', 'pembayaran']) && $this->contains($normalizedMessage, ['sudah', 'udah', 'riwayat', 'history'])) {
            $this->lastTopic = 'payments';
            return $this->getRiwayatPembayaran($userId);
        }
        if ($this->contains($normalizedMessage, ['bayar', 'pembayaran', 'transaksi'])) {
            return $this->respondBills($userId, $normalizedMessage, $rawMessage);
        }
        if (
            preg_match('/\blapor\b/', $normalizedMessage) ||
            $this->contains($normalizedMessage, ['pengaduan', 'keluhan', 'komplain', 'masalah', 'rusak', 'report', 'issue'])
        ) {
            if ($this->contains($normalizedMessage, ['cara', 'bagaimana', 'how'])) {
                return $this->respondComplaintGuidance(true);
            }

            return $this->respondComplaintGuidance();
        }
        if ($this->contains($normalizedMessage, ['bantuan', 'tolong', 'pertanyaan apa', 'help', 'panduan'])) {
            $items = [
                $this->text('Tagihan & cara bayar', 'Bills & how to pay'),
                $this->text('Riwayat pembayaran', 'Payment history'),
                $this->text('Agenda & kegiatan RT', 'RT agendas & events'),
                $this->text('Info warga & kontak pengurus', 'Resident info & committee contacts'),
                $this->text('FAQ dan prosedur RT', 'RT FAQs and procedures'),
            ];

            $header = $this->text('Aku bisa bantu kamu dengan:', 'I can help you with:');
            $footer = $this->text('Tulis pertanyaanmu aja ya!', 'Just drop your question anytime!');
            $list = implode("\n", array_map(static fn($line) => chr(7) . ' ' . $line, $items));

            return $this->respond("{$header}\n{$list}\n\n{$footer}");
        }
        if ($this->contains($normalizedMessage, ['cari warga', 'search resident', 'find resident', 'bernama'])) {
            $this->rememberSlotValue('residents', 'target', 'search');
            if (preg_match('/bernama\s+([\p{L}\']+)/u', $normalizedMessage, $match)) {
                return $this->cariWarga($match[1]);
            }
            if ($nameEntity = $this->inferResidentNameFromLexicon()) {
                return $this->cariWarga($nameEntity);
            }
            return $this->respond($this->text(
                'Mau cari warga siapa? Coba tulis: "Cari warga bernama [nama]" atau buka menu **Direktori**.',
                'Who are you looking for? Try typing "Find resident named [name]" or open the **Directory** menu.'
            ));
        }
        if ($this->contains($normalizedMessage, ['jumlah warga', 'total warga', 'berapa warga', 'berapa penduduk'])) {
            return $this->respondResidentsIntent($userId, $normalizedMessage, $rawMessage, 'count');
        }

        if ($this->contains($normalizedMessage, ['rekap keuangan', 'laporan keuangan', 'financial recap'])) {
            return $this->respondFinance($userId, $normalizedMessage, $rawMessage);
        }
        if ($this->contains($normalizedMessage, ['kas', 'keuangan', 'dana', 'uang', 'anggaran'])) {
            return $this->respond($this->text(
                'Laporan kas dan keuangan RT bisa kamu lihat di menu **Laporan**. Update dilakukan secara berkala biar transparan.',
                'You can review the RT cash and finance report from the **Reports** menu. It gets updated regularly to keep things transparent.'
            ));
        }
        if (
            $this->matches($normalizedMessage, ['(daftar|registrasi|pendaftaran|cara).*(warga baru)']) ||
            $this->contains($normalizedMessage, ['registrasi', 'pendaftaran'])
        ) {
            return $this->respond($this->text(
                'Untuk registrasi warga baru, siapkan KTP & KK, lalu hubungi admin RT. Admin akan bantu prosesnya sampai aktif.',
                'To register a new resident, prepare the ID card and family card, then contact the RT admin. They will help you complete the process.'
            ));
        }
        if ($this->contains($normalizedMessage, ['warga baru', 'pendatang baru', 'anggota baru'])) {
            $this->lastTopic = 'residents_new';
            return $this->getWargaBaru();
        }
        if ($this->contains($normalizedMessage, ['fasilitas', 'balai', 'aula', 'lapangan', 'pinjam'])) {
            if ($this->contains($normalizedMessage, ['sewa', 'pinjam', 'pakai', 'booking', 'cara'])) {
                return $this->respond($this->text(
                    'Untuk memakai fasilitas RT (balai, aula, lapangan), hubungi admin RT untuk cek jadwal dan prosedur peminjaman ya.',
                    'To use RT facilities (balai/community hall, field, etc.), contact the RT admin to check availability and the borrowing procedure.'
                ));
            }
            return $this->respond('Info fasilitas umum RT bisa ditanyakan ke admin RT. Biasanya perlu isi formulir dan konfirmasi jadwal.');
        }

        $attemptedKnowledge = false;
        $knowledgeAnswer = null;
        if ($this->shouldInvokeKnowledgeBase($normalizedMessage, $rawMessage)) {
            $attemptedKnowledge = true;
            $knowledgeAnswer = $this->answerFromKnowledgeBase($normalizedMessage);
            if ($knowledgeAnswer !== null) {
                if ($this->isLetterInquiry($normalizedMessage, $rawLower)) {
                    $content = Str::lower((string) ($knowledgeAnswer['message'] ?? ''));
                    if (!Str::contains($content, 'ktp') && !Str::contains($content, 'surat')) {
                        return $this->respondLetterGuidance();
                    }
                }

                $style = $knowledgeAnswer['style'] ?? [];
                $style['rewrite_opening'] = false;

                $response = $this->respond(
                    (string) ($knowledgeAnswer['message'] ?? ''),
                    $style
                );

                if (isset($knowledgeAnswer['meta'])) {
                    $response['meta'] = array_merge($response['meta'] ?? [], $knowledgeAnswer['meta']);
                }

                return $response;
            }
        }

        if ($knowledgeAnswer === null && $this->isLetterInquiry($normalizedMessage, $rawLower)) {
            return $this->respondLetterGuidance();
        }

        if ($this->shouldForceOffTopic($normalizedMessage, $rawMessage)) {
            $this->escalateOffTopic($rawMessage, 'no_domain_signal');
        }

        if ($classifierResponse = $this->handleClassifierFallback($normalizedMessage, $rawMessage, $userId)) {
            return $classifierResponse;
        }
        // Out of context - throw exception untuk fallback ke LLM
        if ($attemptedKnowledge && $isQuestion) {
            throw new OutOfContextException(
                'Query diluar konteks RT, butuh LLM reasoning'
            );
        }

        $fallbackMessage = $this->text(
            'Hai! Ada yang bisa kubantu? Coba tanya tentang tagihan, agenda, info warga, atau prosedur RT ya.',
            'Hi there! Need anything about bills, events, residents, or RT procedures? Just ask away.'
        );

        $excludeIntents = array_filter([$this->lastIntent, 'knowledge_base', 'greeting']);
        $suggestions = $this->buildSuggestions(array_unique($excludeIntents), 3, $normalizedMessage);

        if ($suggestions !== '') {
            $fallbackMessage .= "\n\n" . $suggestions;
        }

        return $this->respond($fallbackMessage, [
            'tone' => 'friendly',
            'confidence' => 0.6,
            'followups' => $this->followUpsForIntent('general'),
        ]);
    }
    private function matches(string $message, array|string $patterns): bool
    {
        $haystacks = $this->messageVariants($message);

        foreach ((array) $patterns as $pattern) {
            $regex = '/' . $pattern . '/i';

            foreach ($haystacks as $haystack) {
                if (preg_match($regex, $haystack)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function contains(string $message, array $needles): bool
    {
        $haystacks = $this->messageVariants($message);

        foreach ($needles as $needle) {
            foreach ($this->needlePatterns($needle) as $pattern) {
                foreach ($haystacks as $haystack) {
                    if (preg_match($pattern, $haystack)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function messageVariants(string $message): array
    {
        $normalized = Str::of($message)->lower()->value();
        $variants = [$normalized];
        $withSpaces = str_replace('_', ' ', $normalized);

        if ($withSpaces !== $normalized) {
            $variants[] = $withSpaces;
        }

        return array_values(array_unique($variants));
    }

    /**
     * @return array<int, string>
     */
    private function needlePatterns(string $needle): array
    {
        $normalized = Str::of($needle)->lower()->value();

        if ($normalized === '') {
            return [];
        }

        $variants = array_unique([
            $normalized,
            str_replace('_', ' ', $normalized),
            str_replace(' ', '_', $normalized),
        ]);

        $patterns = [];

        foreach ($variants as $variant) {
            if ($variant === '') {
                continue;
            }

            $escaped = preg_quote($variant, '/');
            $escaped = str_replace('\ ', '\s+', $escaped);
            $suffix = preg_match('/^[\p{L}]+$/u', $variant)
                ? '(?:ku|mu|nya|kah|lah|pun|an)?'
                : '';
            $patterns[] = '/\b' . $escaped . $suffix . '\b/u';
        }

        return $patterns;
    }

    private function isIdentityIntroductionQuestion(string $normalizedMessage): bool
    {
        return $this->contains($normalizedMessage, ['siapa kamu', 'who are you', 'kamu siapa', 'nama kamu', 'namamu', 'your name'])
            || $this->matches($normalizedMessage, ['nama.*(kamu|dirimu)', 'what.*(is )?your name', 'who.*are you']);
    }

    private function isIdentityScopeQuestion(string $normalizedMessage): bool
    {
        return $this->contains($normalizedMessage, ['tugas', 'peran', 'fungsi', 'jobdesc', 'kerja kamu', 'kerjamu'])
            || $this->matches($normalizedMessage, ['apa.*(tugas|lakukan|kerja) kamu']);
    }

    private function respondIdentityIntroduction(): array
    {
        $this->lastIntent = 'identity_introduction';
        $this->lastTopic = 'identity';
        $this->lastData = [];
        $this->registerIntentReplay('identity_introduction', function () {
            return $this->respondIdentityIntroduction();
        }, ['intent_label' => 'Perkenalan']);

        return $this->respond($this->text(
            'Namaku Aetheria, asisten virtual RT yang siap bantu cek tagihan, pembayaran, agenda, direktori warga, dan informasi prosedur RT. Mau bahas apa? ??',
            "I'm Aetheria, the neighborhood assistant, ready to help with bills, payments, events, resident directory, and RT procedures. What would you like to chat about?"
        ));
    }

    private function respondIdentityScope(): array
    {
        $this->lastIntent = 'identity_scope';
        $this->lastTopic = 'identity';
        $this->lastData = [];
        $this->registerIntentReplay('identity_scope', function () {
            return $this->respondIdentityScope();
        }, ['intent_label' => 'Lingkup tugas']);

        return $this->respond($this->text(
            'Tugasku bantu warga soal urusan RT: cek tagihan & pembayaran, lihat agenda, cari info warga, dan jelaskan prosedur/FAQ RT. Tinggal sebut aja topik yang kamu butuhkan.',
            "My role is to help residents with RT matters: checking bills and payments, sharing upcoming events, looking up resident info, and explaining RT SOP/FAQ. Just let me know which topic you need."
        ));
    }
    private function isRecognitionQuestion(string $message): bool
    {
        $keywordIndicators = ['kenal', 'ingat', 'tau', 'tahu', 'know', 'remember', 'recognize', 'recognise'];
        $pronounIndicators = ['aku', 'saya', 'diriku', 'kami', 'kita', 'namaku', 'nama aku', 'nama saya', 'namasaya'];
        $englishPronounPatterns = [
            '\\bme\\b',
            '\\bus\\b',
            '\\bmyself\\b',
            '\\bmy\\s+name\\b',
            '\\bwho\\s+am\\s+i\\b',
            '\\bwho\\s+i\\s+am\\b',
        ];

        $hasKeyword = $this->contains($message, $keywordIndicators);
        $hasPronoun = $this->contains($message, $pronounIndicators);

        foreach ($englishPronounPatterns as $pattern) {
            if ($this->matches($message, [$pattern])) {
                $hasPronoun = true;
                break;
            }
        }

        return $hasKeyword && $hasPronoun;
    }
    private function respondRecognition(?int $userId): array
    {
        $firstName = $this->resolveResidentFirstName($userId);

        if ($firstName !== null) {
            return $this->respond($this->text(
                'Tentu aku kenal kamu, {name}! Aku selalu siap bantu cek tagihan, agenda, atau info RT lainnya. Ada yang mau kamu bahas sekarang?',
                'Of course I know you, {name}! I am right here whenever you need RT updates. What should we look at next?',
                ['name' => $firstName]
            ));
        }

        return $this->respond($this->text(
            'Selama ngobrol aku fokus bantu warga yang sedang bertanya, jadi tinggal sebut kebutuhannya dan akan kubantu.',
            'I stay present with the resident I am chatting with, so just tell me what you need and I will help you out.'
        ));
    }
    private function isFollowUpQuestion(string $message): bool
    {
        return $this->matches($message, [
            '^(itu|yang tadi|yang barusan|yang mana|tersebut)',
            '^(detail|lanjut|lanjutnya|jelasin lagi)',
            '(apa|siapa|dimana|kapan|berapa).*(itu|tersebut|nya)',
            '^(lokasi|tempat|waktu|kapan|dimana|berapa|siapa).*(nya)?$',
            '\\b(sumber|source|referensi)\\b',
        ]);
    }

    private function handleFollowUp(string $message, string $context, ?int $userId): array
    {
        if ($this->lastIntent === 'knowledge_base') {
            return $this->handleKnowledgeBaseFollowUp($message);
        }

        // Cek konteks dari percakapan terakhir
        if ($this->contains($context, ['sudah', 'udah', 'bayar']) && $this->contains($context, ['tagihan', 'iuran'])) {
            // Konteks: "tagihan apa yang sudah aku bayar?" -> follow-up harus tentang tagihan belum bayar
            if ($this->contains($message, ['tagihan', 'itu', 'tersebut'])) {
                $this->lastTopic = 'bills';
                return $this->getTagihanInfo($userId, $this->getSlotValue('bills', 'period'));
            }
        }

        if ($this->lastTopic === 'payments' || $this->contains($context, ['pembayaran', 'riwayat'])) {
            if ($this->contains($message, ['berapa', 'jumlah', 'total'])) {
                return $this->getPembayaranInfo($userId, $this->getSlotValue('payments', 'period'));
            }
            return $this->getRiwayatPembayaran($userId);
        }

        if ($this->lastTopic === 'bills' || $this->contains($context, ['tagihan', 'tunggakan', 'iuran'])) {
            if ($this->contains($message, ['berapa', 'jumlah', 'total'])) {
                return $this->getTagihanInfo($userId, $this->getSlotValue('bills', 'period'));
            }
            return $this->getTagihanInfo($userId, $this->getSlotValue('bills', 'period'));
        }

        if ($this->lastTopic === 'agenda' || $this->contains($context, ['agenda', 'acara', 'kegiatan'])) {
            if ($this->contains($message, ['lokasi', 'tempat', 'dimana', 'di mana'])) {
                return $this->getAgendaLocation();
            }
            if ($this->contains($message, ['kapan', 'waktu', 'jam', 'mulai'])) {
                return $this->getAgendaTime();
            }
            return $this->getAgendaInfo('month');
        }

        if ($this->lastTopic === 'residents_new' || $this->contains($context, ['warga baru', 'pendatang'])) {
            if ($this->contains($message, ['siapa', 'nama'])) {
                return $this->getWargaBaru();
            }
            return $this->getWargaBaru();
        }

        if ($this->lastTopic === 'residents' || $this->contains($context, ['warga', 'resident'])) {
            return $this->respondResidentsIntent($userId, $message, $this->currentRawMessage, 'count');
        }

        if ($this->lastTopic === 'summary') {
            if ($this->contains($message, ['tagihan', 'tunggakan', 'bill', 'bills'])) {
                return $this->getUrgentBills($userId);
            }

            if ($this->contains($message, ['agenda', 'acara', 'event', 'kegiatan'])) {
                return $this->getAgendaInfo('week');
            }

            if ($this->contains($message, ['pembayaran', 'bayar', 'payment'])) {
                return $this->getRiwayatPembayaran($userId);
            }
        }

        if ($this->lastTopic === 'finance') {
            if ($this->contains($message, ['detail', 'rinci', 'breakdown', 'per transaksi'])) {
                return $this->getRekapKeuangan(
                    $userId,
                    $this->getSlotValue('finance', 'format') ?? 'pdf',
                    false,
                    $this->getSlotValue('finance', 'period')
                );
            }

            if ($this->contains($message, ['pdf', 'unduh', 'download'])) {
                $link = $this->safeRoute('resident.reports');
                return $this->respond($this->text(
                    $link
                        ? "Kamu bisa unduh rekap PDF dari menu **Laporan** ya. Ini tautannya: {$link}"
                        : 'Kamu bisa unduh rekap PDF langsung dari menu **Laporan** di aplikasi.',
                    $link
                        ? "You can download the finance PDF from the **Reports** menu. Here is the link: {$link}"
                        : 'Feel free to download the finance PDF from the **Reports** menu.'
                ));
            }
        }

        return $this->getTagihanInfo($userId, $this->getSlotValue('bills', 'period'));
    }

    private function handleKnowledgeBaseFollowUp(string $message): array
    {
        $data = $this->lastData ?? [];
        $answer = $data['answer'] ?? $this->lastKnowledgeAnswer;
        $sources = $data['sources'] ?? $this->kbSources ?? [];
        $sourceBlock = $data['source'] ?? ($sources !== [] ? $this->formatKnowledgeSources($sources) : null);
        $confidence = $data['confidence'] ?? $this->lastKnowledgeConfidence;

        if ($sourceBlock && $this->contains($message, ['sumber', 'source', 'referensi', 'asal info', 'asal informasi'])) {
            return $this->respond($sourceBlock);
        }

        if (!$sourceBlock && $this->contains($message, ['sumber', 'source', 'referensi', 'asal info', 'asal informasi'])) {
            return $this->respond($this->text(
                'Aku belum menyimpan sumber detail untuk jawaban barusan. Coba tanya ulang topiknya supaya bisa kutarik dokumennya.',
                'I did not store the source details for that answer. Please mention the topic again so I can pull the document.'
            ));
        }

        if ($confidence !== null && $this->contains($message, ['yakin', 'confidence', 'pasti', 'sure', 'seberapa yakin'])) {
            $percent = max(0, min(100, (int) round($confidence * 100)));
            return $this->respond($this->text(
                "Kira-kira aku yakin {$percent}% berdasarkan sumber yang sama. Kalau mau cek dokumen lain tinggal sebut judulnya ya.",
                "I'm roughly {$percent}% confident based on those sources. Mention another document if you need extra verification."
            ));
        }

        if ($answer && $this->contains($message, ['ulang', 'repeat', 'lagi', 'apa tadi', 'jelasin', 'jelaskan'])) {
            $withSources = $sources !== []
                ? $answer . "\n\n" . $this->formatKnowledgeSources($sources)
                : $answer;

            return $this->respond($withSources);
        }

        if ($this->contains($message, ['detail', 'lanjut', 'lanjutan', 'more detail', 'more info'])) {
            return $this->respond($this->text(
                'Sebut judul atau bagian mana yang mau diperdalam, nanti kucari di sumber yang sama.',
                'Tell me which section or document you want to dive into and I will check the same sources.'
            ));
        }

        return $this->respond($this->text(
            'Kalau mau info tambahan, coba tanya lebih spesifik ya.',
            'If you need more information, try asking a more specific follow-up.'
        ));
    }

    private function handlePendingSlotAnswer(string $normalizedMessage, string $rawMessage, ?int $userId): ?array
    {
        if ($this->pendingSlot === null) {
            return null;
        }

        $intent = $this->pendingSlot['intent'] ?? null;
        $slot = $this->pendingSlot['slot'] ?? null;
        $options = $this->pendingSlot['options'] ?? [];
        $question = $this->pendingSlot['question'] ?? [
            'id' => 'Bisa pilih salah satu opsi?',
            'en' => 'Could you pick one of the options?',
        ];

        if (!$intent || !$slot) {
            $this->pendingSlot = null;
            return null;
        }

        $selected = $this->matchSlotOption($normalizedMessage, $rawMessage, $options, $slot);

        if ($selected === null) {
            return $this->respond($this->formatClarificationPrompt($question, $options));
        }

        $this->rememberSlotValue($intent, $slot, $selected);
        $this->pendingSlot = null;

        return $this->completeIntentWithSlots($intent, $userId);
    }

    private function shouldBypassPendingSlot(string $normalizedMessage, string $rawMessage): bool
    {
        $metaKeywords = [
            'tugas',
            'peran',
            'fungsi',
            'jobdesc',
            'kerja kamu',
            'kerjamu',
            'siapa kamu',
            'kamu siapa',
            'your name',
            'nama kamu',
            'namamu',
        ];

        if ($this->matches($normalizedMessage, ['^halo$', '^hai$', '^hi$', '^hey$', '^hello$'])) {
            return true;
        }

        if ($this->contains($normalizedMessage, $metaKeywords)) {
            return true;
        }

        if (
            $this->contains($normalizedMessage, ['terima kasih', 'makasih', 'thanks', 'thank you', 'thx', 'tq']) ||
            $this->contains(Str::of($rawMessage)->lower()->squish()->value(), ['terima kasih', 'makasih', 'thanks', 'thank you', 'thx', 'tq'])
        ) {
            return true;
        }

        if ($this->isSmallTalk($normalizedMessage, $rawMessage)) {
            return true;
        }

        return false;
    }

    private function ensureSlots(string $intent, string $normalizedMessage, string $rawMessage): ?array
    {
        $this->extractSlotValues($intent, $normalizedMessage, $rawMessage);

        $schema = $this->slotSchema[$intent] ?? null;

        if ($schema === null) {
            return null;
        }

        foreach ($schema as $slotName => $definition) {
            $value = $this->getSlotValue($intent, $slotName);
            $isRequired = (bool) ($definition['required'] ?? false);

            if ($value !== null) {
                continue;
            }

            if (!$isRequired) {
                continue;
            }

            $this->pendingSlot = [
                'intent' => $intent,
                'slot' => $slotName,
                'question' => $definition['question'] ?? [
                    'id' => 'Bisa pilih salah satu opsi?',
                    'en' => 'Could you pick one of the options?',
                ],
                'options' => $definition['options'] ?? [],
            ];

            return $this->respondWithTemplate(
                $intent,
                'slot_missing',
                $this->formatClarificationPrompt(
                    $this->pendingSlot['question'],
                    $definition['options'] ?? []
                ),
                [
                    'tone' => 'cautious',
                    'confidence' => 0.45,
                    'followups' => $this->followUpsForIntent($intent, [
                        'state' => 'slot_missing',
                        'slot' => $slotName,
                    ]),
                ],
                [
                    'slot' => $this->slotPromptLabel($intent, $slotName),
                ]
            );
        }

        return null;
    }

    private function extractSlotValues(string $intent, string $normalizedMessage, string $rawMessage): void
    {
        if (!isset($this->slotSchema[$intent])) {
            return;
        }

        foreach (array_keys($this->slotSchema[$intent]) as $slotName) {
            if ($slotName === 'period') {
                $period = $this->temporal()->parsePeriod($rawMessage) ?? $this->inferPeriodFromLexicon();
                if ($period !== null) {
                    $this->rememberSlotValue($intent, $slotName, $period);
                }
            }

            if ($slotName === 'range') {
                $range = $this->inferAgendaRangeFromLexicon() ?? $this->temporal()->parseAgendaRange($normalizedMessage);
                if ($range !== null) {
                    $this->rememberSlotValue($intent, $slotName, $range);
                }
            }
        }
    }

    private function inferPeriodFromLexicon(): ?array
    {
        $months = $this->lexicalContext['entities']['months'] ?? [];

        foreach ($months as $entity) {
            $period = $this->periodFromMonthEntity($entity);
            if ($period !== null) {
                return $period;
            }
        }

        return null;
    }

    private function inferAgendaRangeFromLexicon(): ?string
    {
        $dates = $this->lexicalContext['entities']['dates'] ?? [];

        foreach ($dates as $entity) {
            $range = $entity['range'] ?? null;
            if (is_string($range) && $range !== '') {
                return $range;
            }
        }

        return null;
    }

    private function periodFromMonthEntity(array $entity): ?array
    {
        $month = isset($entity['month']) ? (int) $entity['month'] : null;
        if ($month === null || $month < 1 || $month > 12) {
            return null;
        }

        $year = isset($entity['year_hint']) ? (int) $entity['year_hint'] : Carbon::now($this->currentTimezone())->year;

        try {
            $reference = Carbon::create($year, $month, 1, 0, 0, 0, $this->currentTimezone());
        } catch (\Throwable) {
            return null;
        }

        return $this->temporal()->monthRange($reference);
    }

    private function extractBudgetValue(string $message): int
    {
        $amounts = $this->lexicalContext['entities']['amounts'] ?? [];

        if (!empty($amounts)) {
            $value = (int) ($amounts[0]['value'] ?? 0);
            if ($value > 0) {
                return $value < 1000 ? $value * 1000 : $value;
            }
        }

        if (preg_match('/(\d+)\s*(jt|juta|rb|ribu|k|m|million)?/i', $message, $matches)) {
            $base = (int) $matches[1];
            $suffix = strtolower($matches[2] ?? '');
            $multiplier = match ($suffix) {
                'jt', 'juta', 'm', 'million' => 1_000_000,
                'rb', 'ribu', 'k' => 1_000,
                default => 1_000,
            };

            return $base * $multiplier;
        }

        return 0;
    }

    private function inferResidentNameFromLexicon(): ?string
    {
        $names = $this->lexicalContext['entities']['names'] ?? [];

        foreach ($names as $entity) {
            $name = $entity['name'] ?? null;
            if (is_string($name) && $name !== '') {
                return $name;
            }
        }

        return null;
    }

    private function rememberSlotValue(string $intent, string $slot, mixed $value): void
    {
        $this->slotState[$intent]['values'][$slot] = $value;
    }

    private function getSlotValue(string $intent, string $slot): mixed
    {
        return $this->slotState[$intent]['values'][$slot] ?? null;
    }

    private function matchSlotOption(string $normalizedMessage, string $rawMessage, array $options, string $slot): mixed
    {
        if ($options === []) {
            return $slot === 'period'
                ? $this->temporal()->parsePeriod($rawMessage)
                : $this->temporal()->parseAgendaRange($normalizedMessage);
        }

        $normalized = Str::of($normalizedMessage)->lower()->value();

        if (preg_match('/\b([1-9])\b/', $normalized, $match)) {
            $index = (int) $match[1] - 1;
            if (isset($options[$index])) {
                return $this->resolveOptionValue($options[$index], $rawMessage, $normalized);
            }
        }

        foreach ($options as $definition) {
            $value = $this->resolveOptionValue($definition, $rawMessage, $normalized);
            if ($value !== null) {
                return $value;
            }
        }

        return $slot === 'period'
            ? $this->temporal()->parsePeriod($rawMessage)
            : $this->temporal()->parseAgendaRange($normalized);
    }

    private function resolveOptionValue(array $option, string $rawMessage, string $normalized): mixed
    {
        $hints = $option['hints'] ?? [];
        foreach ($hints as $hint) {
            if ($hint !== '' && Str::contains($normalized, $hint)) {
                return $option['value'] === 'custom'
                    ? $this->temporal()->parsePeriod($rawMessage)
                    : $option['value'];
            }
        }

        return $option['value'] !== 'custom' ? null : $this->temporal()->parsePeriod($rawMessage);
    }

    private function formatClarificationPrompt(array $question, array $options): string
    {
        $query = $this->isEnglish() ? ($question['en'] ?? '') : ($question['id'] ?? '');

        if ($options === []) {
            return $query !== '' ? $query : ($this->isEnglish() ? 'Could you share more detail?' : 'Bisa dijelaskan sedikit lagi?');
        }

        $lines = [];
        foreach ($options as $index => $option) {
            $label = $this->isEnglish() ? ($option['label_en'] ?? '') : ($option['label_id'] ?? '');
            if ($label === '') {
                continue;
            }
            $lines[] = ($index + 1) . ') ' . $label;
        }

        $choices = implode(', ', $lines);

        if ($query === '') {
            $query = $this->isEnglish() ? 'Could you pick one of these options?' : 'Pilih salah satu opsi berikut ya:';
        }

        return "{$query}\n{$choices}";
    }

    private function slotPromptLabel(string $intent, string $slot): string
    {
        $definition = $this->slotSchema[$intent][$slot] ?? null;
        if (is_array($definition)) {
            $question = $definition['question'] ?? [];
            $candidate = $this->isEnglish() ? ($question['en'] ?? '') : ($question['id'] ?? '');
            if ($candidate !== '') {
                return trim($candidate);
            }
        }

        return Str::title(str_replace('_', ' ', $slot));
    }

    private function completeIntentWithSlots(string $intent, ?int $userId): array
    {
        return match ($intent) {
            'bills' => $this->getTagihanInfo($userId, $this->getSlotValue('bills', 'period')),
            'payments' => $this->getPembayaranInfo($userId, $this->getSlotValue('payments', 'period')),
            'agenda' => $this->getAgendaInfo($this->getSlotValue('agenda', 'range') ?? 'week'),
            'finance' => $this->respondFinance($userId, $this->currentNormalizedMessage, $this->currentRawMessage),
            'residents' => $this->respondResidentsIntent(
                $userId,
                $this->currentNormalizedMessage,
                $this->currentRawMessage
            ),
            default => $this->respond($this->text(
                'Siap, kita lanjut ke topik selanjutnya. Ada yang mau ditanyakan lagi?',
                'Alright, let me know what else you need.'
            )),
        };
    }

    private function respondBills(?int $userId, string $normalizedMessage, string $rawMessage): array
    {
        $this->lastTopic = 'bills';

        if ($clarification = $this->ensureSlots('bills', $normalizedMessage, $rawMessage)) {
            return $clarification;
        }

        return $this->getTagihanInfo($userId, $this->getSlotValue('bills', 'period'));
    }

    private function respondPayments(?int $userId, string $normalizedMessage, string $rawMessage): array
    {
        $this->lastTopic = 'payments';

        if ($clarification = $this->ensureSlots('payments', $normalizedMessage, $rawMessage)) {
            return $clarification;
        }

        return $this->getPembayaranInfo($userId, $this->getSlotValue('payments', 'period'));
    }

    private function respondAgenda(string $normalizedMessage, string $rawMessage, ?int $userId, string $defaultRange = 'month'): array
    {
        $this->lastTopic = 'agenda';
        $this->rememberSlotValue('agenda', 'range', $this->getSlotValue('agenda', 'range') ?? $defaultRange);

        if ($clarification = $this->ensureSlots('agenda', $normalizedMessage, $rawMessage)) {
            return $clarification;
        }

        $range = $this->getSlotValue('agenda', 'range') ?? $defaultRange;

        return $this->getAgendaInfo($range);
    }

    private function respondFinance(?int $userId, string $normalizedMessage, string $rawMessage, ?array $forcedConstraints = null): array
    {
        $this->lastTopic = 'finance';

        if ($clarification = $this->ensureSlots('finance', $normalizedMessage, $rawMessage)) {
            return $clarification;
        }

        $period = $this->getSlotValue('finance', 'period');
        $format = $this->getSlotValue('finance', 'format') ?? 'pdf';

        return $this->getRekapKeuangan($userId, $format, false, $period, $forcedConstraints);
    }

    private function respondResidentsIntent(?int $userId, string $normalizedMessage, string $rawMessage, ?string $forcedTarget = null): array
    {
        $this->lastTopic = 'residents';

        if ($forcedTarget !== null) {
            $this->rememberSlotValue('residents', 'target', $forcedTarget);
        }

        if ($clarification = $this->ensureSlots('residents', $normalizedMessage, $rawMessage)) {
            return $clarification;
        }

        $target = $this->getSlotValue('residents', 'target') ?? 'count';

        return match ($target) {
            'new' => $this->getWargaBaru(),
            'search' => $this->cariWarga($this->inferResidentNameFromLexicon() ?? '*'),
            default => $this->getJumlahWarga(),
        };
    }

    private function respondComplaintGuidance(bool $withSteps = false): array
    {
        $this->lastIntent = 'complaint';
        $this->lastTopic = 'complaint';
        $this->lastData = [];
        $this->registerIntentReplay('complaint', function () use ($withSteps) {
            return $this->respondComplaintGuidance($withSteps);
        }, ['intent_label' => 'Panduan keluhan', 'mode' => $withSteps ? 'steps' : 'summary']);

        $message = $withSteps
            ? $this->text(
                'Untuk membuat laporan: 1) Hubungi admin RT via menu **Kontak**, 2) Datang ke sekretariat RT, atau 3) Kontak ketua RT. Sertakan detail kronologi agar bisa diproses.',
                'To file a report: 1) Contact the RT admin via **Contact**, 2) Visit the RT office, or 3) Reach out to the RT head. Include a short chronology so it can be processed.'
            )
            : $this->text(
                'Kalau ada keluhan, tulis detailnya lalu kirim lewat menu **Kontak Pengurus** atau langsung datang ke sekretariat. Pengurus akan tindak lanjut sesuai prosedur.',
                'If you have a complaint, jot down the details and send them via **Contact**, or visit the office. The committee will follow up per procedure.'
            );

        $safeMessage = $this->text(
            'Supaya bisa bantu lebih cepat, tuliskan keluhanmu beserta lokasi/waktu ya.',
            'Share the issue along with location/time so I can route it quickly.'
        );

        $clarifications = [
            'negation_conflict' => [
                'id' => 'Baik, kubatalkan laporan. Kalau tetap perlu, sebut detail keluhanmu ya.',
                'en' => 'Alright, I will hold off. If you still need help, share the complaint details.',
            ],
            'default' => [
                'id' => 'Ceritakan inti keluhannya (apa, kapan, lokasi) supaya bisa kuarahkan dengan tepat.',
                'en' => 'Let me know what happened, when, and where so I can route it properly.',
            ],
        ];

        $negationContext = [
            'label' => 'complaint',
            'user_negated' => $this->userRequestsNegation(['keluhan', 'komplain', 'lapor', 'pengaduan']),
            'resolved' => false,
        ];

        $tone = $withSteps ? 'informative' : 'friendly';

        return $this->respond(
            $this->reasoning()->run(function () use ($message, $safeMessage, $clarifications, $negationContext) {
                return ReasoningDraft::make(
                    intent: 'complaint',
                    message: $message,
                    numerics: [],
                    dates: [],
                    negation: $negationContext,
                    clarifications: $clarifications,
                    repairCallback: function () use ($safeMessage, $clarifications, $negationContext) {
                        return ReasoningDraft::make(
                            intent: 'complaint',
                            message: $safeMessage,
                            numerics: [],
                            dates: [],
                            negation: $negationContext,
                            clarifications: $clarifications
                        );
                    }
                );
            }, $this->language),
            [
                'tone' => $tone,
                'confidence' => 0.72,
                'followups' => $this->followUpsForIntent('general'),
            ]
        );
    }

    private function normalizeWithSynonyms(string $message): string
    {
        $result = $this->lexicon->process($message);

        $this->lexicalContext = [
            'tokens' => $result['tokens'] ?? [],
            'entities' => $result['entities'] ?? [],
            'oov' => $result['oov'] ?? [],
        ];
        $this->lexicalContext['language'] = $this->language;

        $oovTokens = array_values(array_unique($result['oov'] ?? []));
        if ($oovTokens !== []) {
            $this->persistStateFragment([
                'metadata' => [
                    'lexicon' => [
                        'oov' => $oovTokens,
                        'oov_updated_at' => Carbon::now($this->currentTimezone())->timestamp,
                    ],
                ],
            ]);
        }

        $ner = $this->classifier->extractEntities($message);
        foreach ($ner as $type => $items) {
            if (!isset($this->lexicalContext['entities'][$type])) {
                $this->lexicalContext['entities'][$type] = [];
            }
            $this->lexicalContext['entities'][$type] = array_merge(
                $this->lexicalContext['entities'][$type],
                $items
            );
        }

        $normalized = $result['normalized'] ?? Str::of($message)->lower()->squish()->value();

        return $normalized === '' ? Str::of($message)->lower()->squish()->value() : $normalized;
    }

    public function stream(array $messages, array $tools, callable $onEvent): array
    {
        $response = $this->chat($messages, $tools);

        if (isset($response['content'])) {
            $onEvent('token', (string) $response['content']);
        }

        return $response;
    }
    private function handleToolBasedQuery(string $message, array $tools, ?int $userId): array
    {
        $tool = $tools[0] ?? null;
        if (!$tool) {
            return $this->respond($this->text(
                'Aku belum mengerti tool apa yang perlu dipakai untuk itu.',
                'I am not sure which tool fits that request yet.'
            ));
        }

        $toolName = $tool['function']['name'] ?? '';
        $parameters = $tool['function']['parameters'] ?? [];

        if (isset($tool['function']['arguments']) && is_string($tool['function']['arguments'])) {
            $decoded = json_decode($tool['function']['arguments'], true);
            if (is_array($decoded)) {
                $parameters = $decoded;
            }
        }

        $payload = is_array($parameters) ? $parameters : [];
        $payload = $this->enrichToolParameters($toolName, $payload);

        $validation = $this->toolSchema->validate(
            $toolName,
            $payload,
            $this->currentCorrectedMessage,
            $this->lexicalContext
        );

        if (!($validation['valid'] ?? true)) {
            $this->logToolError($toolName, 422, 'validation_failed', $payload, [
                'errors' => $validation['errors'] ?? [],
            ]);
            $clarification = $validation['clarification'] ?? $this->text(
                'Parameter tool-nya belum lengkap. Bisa pilih opsi yang kamu maksud?',
                'I still need a parameter for that tool. Could you pick the option you meant?'
            );

            return $this->respond($clarification, [
                'tone' => 'cautious',
                'confidence' => 0.4,
                'followups' => $this->followUpsForIntent('general'),
            ]);
        }

        $parameters = $validation['parameters'] ?? [];

        if ($this->requiresConfirmationForTool($toolName)) {
            $this->pendingConfirmation = [
                'type' => 'tool',
                'tool' => $tool,
                'user_id' => $userId,
                'name' => $toolName,
                'created_at' => Carbon::now($this->currentTimezone())->timestamp,
                'parameters' => $parameters,
            ];
            $this->guardrail('risky_action_blocked', ['tool' => $toolName]);
            $this->recordToolCall($toolName, false, ['reason' => 'awaiting_confirmation']);

            return $this->respond($this->text(
                'Ingin melanjutkan tindakan ini? Balas "ya" untuk lanjut atau "tidak" untuk batalkan.',
                'Do you want me to proceed? Reply "yes" to continue or "no" to cancel.'
            ));
        }

        $response = match ($toolName) {
            'get_outstanding_bills' => $this->getTagihanInfo($userId, $parameters['period'] ?? null),
            'get_payments_this_month' => $this->getPembayaranInfo($userId, $parameters['period'] ?? null),
            'get_payment_status' => $this->respondPaymentStatus($userId, $parameters),
            'get_agenda' => $this->getAgendaInfo($parameters['range'] ?? 'month'),
            'search_directory' => $this->cariWarga($parameters['query'] ?? '*', $parameters['status'] ?? 'all'),
            'export_financial_recap' => $this->getRekapKeuangan(
                $userId,
                $parameters['format'] ?? 'pdf',
                false,
                $parameters['period'] ?? null
            ),
            'get_rt_contacts' => $this->respondRtContacts($parameters['position'] ?? 'all'),
            'rag_search' => $this->respondKnowledgeTool($parameters['query'] ?? $message),
            default => null,
        };

        if ($response === null) {
            $this->logToolError($toolName, 400, 'unsupported_tool', $parameters);
            return $this->respond($this->text(
                'Saat ini aku belum bisa pakai tool tersebut.',
                'I cannot use that tool yet.'
            ));
        }

        $this->recordToolCall($toolName, true, ['parameters' => $parameters]);

        return $response;
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    private function enrichToolParameters(string $toolName, array $parameters): array
    {
        return match ($toolName) {
            'get_outstanding_bills' => $this->withPeriodFallback($parameters, 'bills'),
            'get_payments_this_month' => $this->withPeriodFallback($parameters, 'payments'),
            'get_payment_status' => $this->withPaymentStatusFallback($parameters),
            'get_agenda' => $this->withRangeFallback($parameters),
            'export_financial_recap' => $this->withPeriodFallback($parameters, 'finance'),
            'search_directory' => $this->withDirectoryFallback($parameters),
            'get_rt_contacts' => $this->withPositionFallback($parameters),
            'rag_search' => $this->withQueryFallback($parameters),
            default => $parameters,
        };
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    private function withPeriodFallback(array $parameters, string $intent): array
    {
        if (($parameters['period'] ?? null) === null) {
            $slotPeriod = $this->getSlotValue($intent, 'period');
            if ($slotPeriod !== null) {
                $parameters['period'] = $slotPeriod;
            }
        }

        return $parameters;
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    private function withPaymentStatusFallback(array $parameters): array
    {
        if (($parameters['month'] ?? null) === null) {
            $slotPeriod = $this->getSlotValue('payments', 'period');
            if (is_array($slotPeriod) && isset($slotPeriod['start'])) {
                try {
                    $month = Carbon::parse($slotPeriod['start'])->format('Y-m');
                    $parameters['month'] = $month;
                } catch (\Throwable) {
                    // ignore
                }
            }
        }

        if (($parameters['type'] ?? null) === null) {
            $parameters['type'] = 'all';
        }

        return $parameters;
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    private function withRangeFallback(array $parameters): array
    {
        if (($parameters['range'] ?? null) === null) {
            $range = $this->getSlotValue('agenda', 'range');
            if (is_string($range) && $range !== '') {
                $parameters['range'] = $range;
            }
        }

        return $parameters;
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    private function withDirectoryFallback(array $parameters): array
    {
        if (($parameters['query'] ?? null) === null || $parameters['query'] === '') {
            $name = $this->inferResidentNameFromLexicon();
            $parameters['query'] = $name ?? '*';
        }

        if (($parameters['status'] ?? null) === null) {
            $parameters['status'] = 'all';
        }

        return $parameters;
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    private function withPositionFallback(array $parameters): array
    {
        if (($parameters['position'] ?? null) === null || $parameters['position'] === '') {
            $parameters['position'] = 'all';
        }

        return $parameters;
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    private function withQueryFallback(array $parameters): array
    {
        if (($parameters['query'] ?? null) === null || trim((string) $parameters['query']) === '') {
            $parameters['query'] = $this->currentCorrectedMessage !== ''
                ? $this->currentCorrectedMessage
                : $this->currentRawMessage;
        }

        return $parameters;
    }

    private function respondPaymentStatus(?int $userId, array $parameters): array
    {
        if (!$userId) {
            return $this->requireLoginResponse(
                'Untuk cek status pembayaran, kamu harus login dulu ya! 🔐',
                'Please log in so I can check the payment status. 🔐'
            );
        }

        $period = $this->normalizeMonthPeriod($parameters['month'] ?? null);
        $type = $parameters['type'] ?? 'all';

        $constraints = null;
        if (is_string($type) && $type !== '' && $type !== 'all') {
            $constraints = [
                'include' => [$type],
                'exclude' => [],
            ];
        }

        return $this->getPembayaranInfo($userId, $period, false, $constraints);
    }

    private function normalizeMonthPeriod(?string $value): ?array
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            $reference = Carbon::createFromFormat('Y-m-d', $value . '-01', $this->currentTimezone());
        } catch (\Throwable) {
            return null;
        }

        return $this->temporal()->monthRange($reference);
    }

    private function respondRtContacts(string $position): array
    {
        $normalized = Str::lower(Str::squish($position));
        if ($normalized === '') {
            $normalized = 'all';
        }

        $query = RTOfficial::query()
            ->where('is_active', true)
            ->orderBy('order');

        if ($normalized !== 'all') {
            $query->where('position', $normalized);
        }

        $officials = $query->limit(5)->get(['name', 'position', 'phone', 'email']);

        if ($officials->isEmpty()) {
            return $this->respond($this->text(
                'Data kontak pengurus belum tersedia. Coba hubungi admin RT ya.',
                'Committee contacts are not available yet. Please reach out to the admin.'
            ), [
                'tone' => 'cautious',
                'confidence' => 0.45,
                'followups' => $this->followUpsForIntent('residents'),
            ]);
        }

        $lines = $officials->map(function (RTOfficial $official) {
            $position = Str::title($official->position ?? 'Pengurus');
            $phone = $official->phone ?: ($this->isEnglish() ? 'Phone not listed' : 'Telepon belum tercatat');
            $email = $official->email ? (' • ' . $official->email) : '';

            return chr(7) . " {$official->name} ({$position}) — {$phone}{$email}";
        })->join("\n");

        return $this->respond(
            $this->text(
                "Berikut kontak pengurus yang bisa kamu hubungi:\n{$lines}",
                "Here are the committee contacts you can reach:\n{$lines}"
            ),
            [
                'tone' => 'informative',
                'confidence' => 0.78,
                'followups' => $this->followUpsForIntent('residents'),
            ]
        );
    }

    private function respondKnowledgeTool(string $query): array
    {
        $result = $this->answerFromKnowledgeBase($query);

        if ($result === null) {
            return $this->respond($this->text(
                'Belum ada panduan yang cocok. Bisa jelaskan detail prosedur yang kamu maksud?',
                'I could not find the guideline yet. Could you clarify the procedure you need?'
            ), [
                'tone' => 'cautious',
                'confidence' => 0.4,
                'followups' => $this->followUpsForIntent('knowledge_base'),
            ]);
        }

        return $this->respond(
            (string) ($result['message'] ?? ''),
            $result['style'] ?? [
                'tone' => 'informative',
                'confidence' => 0.65,
                'followups' => $this->followUpsForIntent('knowledge_base'),
            ]
        );
    }

    private function logToolError(string $toolName, int $code, string $error, array $parameters = [], array $details = []): void
    {
        $this->metrics->recordToolError([
            'tool' => $toolName,
            'code' => $code,
            'error' => $error,
            'intent' => $this->lastIntent,
            'parameters' => $parameters,
            'details' => $details + [
                'message' => $this->currentRawMessage,
            ],
        ]);

        $this->recordToolCall($toolName, false, [
            'code' => $code,
            'error' => $error,
        ] + $details);
    }

    private function attemptMultiIntentPlan(string $normalizedMessage, ?int $userId): ?array
    {
        if (Str::length($normalizedMessage) < 24) {
            return null;
        }

        if ($this->matches($normalizedMessage, ['(cara|bagaimana|gimana|metode).*(bayar|pembayaran|pay)'])) {
            return null;
        }

        $intents = $this->detectMultiIntentTargets($normalizedMessage);
        $planContext = $this->multiIntentPlanContext;

        if (count($intents) < 2) {
            return null;
        }

        $blocks = [];
        $executedIntents = [];

        $orderedIntents = $planContext['intents'] ?? [];
        if ($orderedIntents === []) {
            $orderedIntents = $intents;
        }

        foreach ($orderedIntents as $intent) {
            $block = $this->renderMultiIntentBlock($intent, $userId);
            if ($block !== null) {
                $blocks[] = $block;
                $executedIntents[] = $intent;
            }
        }

        if (count($blocks) < 2) {
            return null;
        }

        $intro = $this->text(
            'Berikut rangkuman dari beberapa topik yang kamu minta:',
            'Here is the combined summary you asked for:'
        );

        $sections = [];
        $confidenceScores = [];
        $followupPool = [];

        foreach ($blocks as $block) {
            $section = '**' . ($block['title'] ?? $this->text('Info', 'Info')) . "**\n" . trim($block['content']);
            $blockFollowups = $block['followups'] ?? [];

            if ($blockFollowups !== []) {
                $section .= "\n" . $this->text('Tindak lanjut: ', 'Follow-up: ') . ($blockFollowups[0] ?? '');
                $followupPool = array_merge($followupPool, $blockFollowups);
            }

            $sections[] = $section;
            $confidenceScores[] = $block['confidence'] ?? 0.75;
        }

        $body = implode("\n\n" . str_repeat('-', 18) . "\n\n", $sections);
        $fallbackList = $this->text(
            'Topik yang bisa kuambil sekarang:',
            'Here are the topics I could fetch for now:'
        ) . "\n- " . implode("\n- ", array_map(fn($block) => '**' . ($block['title'] ?? 'Info') . '**', $blocks));

        $closing = $this->text(
            'Kalau mau tindakan lanjutan (misal unduh PDF atau hubungi pengurus), tinggal bilang ya.',
            'If you need a follow-up action (download a file or contact the admins), just let me know.'
        );

        $this->lastIntent = 'multi_intent';
        $this->lastTopic = 'combined';
        $this->lastData = ['intents' => $intents];
        $this->registerIntentReplay('multi_intent', function () use ($normalizedMessage, $userId) {
            return $this->attemptMultiIntentPlan($normalizedMessage, $userId);
        }, ['intent_label' => 'Ringkasan multi-intent', 'intents' => $intents]);

        $overallConfidence = $confidenceScores !== [] ? min($confidenceScores) : 0.75;
        $summaryFollowups = array_slice(
            array_values(array_unique($followupPool !== [] ? $followupPool : $this->followUpsForIntent('general'))),
            0,
            3
        );

        $planIntents = array_values(array_unique($planContext['intents'] ?? $intents));
        $coveredIntents = array_values(array_unique($executedIntents));

        $numericRules = [
            RuleLibrary::range('section_count', count($blocks), 2, null),
        ];

        if ($planIntents !== []) {
            $numericRules[] = RuleLibrary::difference(
                'plan_coverage',
                count($planIntents),
                count(array_intersect($planIntents, $coveredIntents)),
                0
            );
        }

        $clarifications = [
            'numeric_difference_mismatch' => [
                'id' => 'Sebagian topik belum bisa kuambil. Mau pilih dua topik utama saja supaya bisa kubahas mendalam?',
                'en' => 'Some requested topics could not be fetched. Could you pick the two most important ones so I can focus?',
            ],
            'numeric_range_invalid' => [
                'id' => 'Aku butuh minimal dua topik berbeda untuk membuat ringkasan gabungan. Mau sebutkan dua topik yang pasti?',
                'en' => 'I need at least two distinct topics to build a combined summary. Which two should I focus on?',
            ],
            'default' => [
                'id' => 'Sebutkan topik mana yang paling penting supaya bisa kucek satu per satu.',
                'en' => 'Let me know which topics matter most so I can dig into them one by one.',
            ],
        ];

        $finalMessage = $this->reasoning()->run(function () use (
            $intro,
            $body,
            $closing,
            $numericRules,
            $clarifications,
            $fallbackList
        ) {
            return ReasoningDraft::make(
                intent: 'multi_intent',
                message: "{$intro}\n\n{$body}\n\n{$closing}",
                numerics: $numericRules,
                clarifications: $clarifications,
                repairCallback: function () use ($fallbackList, $clarifications) {
                    return ReasoningDraft::make(
                        intent: 'multi_intent',
                        message: $fallbackList,
                        numerics: [],
                        clarifications: $clarifications
                    );
                }
            );
        }, $this->language);

        return $this->respond($finalMessage, [
            'tone' => 'summary',
            'confidence' => $overallConfidence,
            'followups' => $summaryFollowups,
        ]);
    }

    private function detectMultiIntentTargets(string $normalizedMessage): array
    {
        $plan = $this->classifier->multiIntentPlan($this->currentCorrectedMessage ?: $normalizedMessage, $this->lexicalContext);

        if ($plan !== []) {
            $ordered = array_values(array_unique(array_map(static fn($item) => $item['intent'] ?? null, $plan)));
            $this->multiIntentPlanContext = [
                'plan' => $plan,
                'intents' => $ordered,
            ];

            return $ordered;
        }

        $map = [
            'bills' => $this->topicKeywords['bills'] ?? [],
            'payments' => $this->topicKeywords['payments'] ?? [],
            'agenda' => $this->topicKeywords['agenda'] ?? [],
            'finance' => $this->topicKeywords['finance'] ?? [],
            'residents' => $this->topicKeywords['residents'] ?? [],
            'residents_new' => $this->topicKeywords['residents_new'] ?? [],
        ];

        $targets = [];

        foreach ($map as $intent => $keywords) {
            if ($keywords !== [] && $this->contains($normalizedMessage, $keywords)) {
                $targets[] = $intent;
            }
        }

        $targets = array_values(array_unique($targets));
        $this->multiIntentPlanContext = [
            'plan' => [],
            'intents' => $targets,
        ];

        return $targets;
    }

    private function renderMultiIntentBlock(string $intent, ?int $userId): ?array
    {
        $result = match ($intent) {
            'bills' => $this->getTagihanInfo($userId, $this->getSlotValue('bills', 'period'), true),
            'payments' => $this->getPembayaranInfo($userId, $this->getSlotValue('payments', 'period'), true),
            'agenda' => $this->getAgendaInfo($this->getSlotValue('agenda', 'range') ?? 'week', true),
            'finance' => $this->getRekapKeuangan(
                $userId,
                $this->getSlotValue('finance', 'format') ?? 'pdf',
                true,
                $this->getSlotValue('finance', 'period')
            ),
            'residents' => $this->residentSnapshotBlock(true),
            'residents_new' => $this->newResidentsBlock(true),
            default => null,
        };

        if ($result === null) {
            return null;
        }

        $content = is_array($result) ? ($result['content'] ?? null) : $result;
        $style = is_array($result) ? ($result['style'] ?? []) : [];

        if (!is_string($content) || trim($content) === '') {
            return null;
        }

        $title = match ($intent) {
            'bills' => $this->text('Tagihan', 'Bills'),
            'payments' => $this->text('Pembayaran', 'Payments'),
            'agenda' => $this->text('Agenda', 'Agenda'),
            'finance' => $this->text('Rekap Keuangan', 'Finance Recap'),
            'residents' => $this->text('Direktori Warga', 'Resident Directory'),
            'residents_new' => $this->text('Warga Baru', 'New Residents'),
            default => $this->text('Info', 'Info'),
        };

        return [
            'intent' => $intent,
            'title' => $title,
            'content' => trim($content),
            'followups' => $style['followups'] ?? $this->followUpsForIntent($intent),
            'confidence' => $style['confidence'] ?? 0.75,
        ];
    }

    private function detectDomainSignals(string $normalizedMessage): array
    {
        $hits = [];

        foreach ($this->domainSignalKeywords as $intent => $keywords) {
            if ($keywords === []) {
                continue;
            }

            if ($this->contains($normalizedMessage, $keywords)) {
                $hits[] = $intent;
            }
        }

        return $hits;
    }

    private function shouldForceOffTopicEarly(string $normalizedMessage, ?string $rawMessage = null): bool
    {
        if ($this->detectDomainSignals($normalizedMessage) !== []) {
            return false;
        }

        return $this->looksLikeGeneralQuestion($normalizedMessage, $rawMessage);
    }

    private function shouldForceOffTopic(string $normalizedMessage, ?string $rawMessage = null): bool
    {
        return $this->shouldForceOffTopicEarly(
            Str::of($normalizedMessage)->lower()->squish()->value(),
            $rawMessage
        );
    }

    private function looksLikeGeneralQuestion(string $normalized, ?string $rawMessage = null): bool
    {
        if ($normalized === '') {
            return false;
        }

        if (Str::length($normalized) < 8) {
            return false;
        }

        $questionHints = [
            'kapan',
            'waktu',
            'siapa',
            'apa itu',
            'apa yang',
            'bagaimana',
            'kenapa',
            'mengapa',
            'berapa',
            'dimana',
            'di mana',
            'berasal',
            'sejarah',
            'tahun berapa',
            'kapan pertama',
            'asal usul',
            'when',
            'what year',
            'who invented',
            'history of',
            'jelaskan',
            'ceritakan',
        ];

        $haystacks = [$normalized];

        if ($rawMessage !== null) {
            $haystacks[] = Str::of($rawMessage)->lower()->squish()->value();
        }

        foreach ($haystacks as $haystack) {
            if ($haystack !== '' && $this->contains($haystack, $questionHints)) {
                return true;
            }
        }

        if ($rawMessage !== null && Str::contains($rawMessage, '?')) {
            return true;
        }

        return Str::contains($normalized, '?');
    }

    private function residentSnapshotBlock(bool $asBlock = false): string|array|null
    {
        $total = User::where('role', 'warga')->count();

        if ($total === 0) {
            $message = $this->text(
                'Belum ada data warga terdaftar.',
                'No registered residents found yet.'
            );

            return $asBlock
                ? [
                    'content' => $message,
                    'style' => [
                        'tone' => 'informative',
                        'confidence' => 0.6,
                        'followups' => $this->followUpsForIntent('residents'),
                    ],
                ]
                : $message;
        }

        $recent = User::where('role', 'warga')
            ->orderByDesc('updated_at')
            ->limit(3)
            ->get($this->userAddressSelectColumns(['name', 'updated_at']));

        $list = $recent->map(function (User $user) {
            $alamat = $this->userAddressValue($user);

            return '• ' . $user->name . ' — ' . $alamat;
        })->join("\n");

        $message = $this->text(
            "Total warga aktif: **{$total}**.\n{$list}",
            "Active residents: **{$total}**.\n{$list}"
        );

        if ($asBlock) {
            return [
                'content' => $message,
                'style' => [
                    'tone' => 'informative',
                    'confidence' => 0.75,
                    'followups' => $this->followUpsForIntent('residents'),
                ],
            ];
        }

        return $message;
    }

    private function newResidentsBlock(bool $asBlock = false): string|array|null
    {
        $newResidents = User::where('role', 'warga')
            ->where('created_at', '>=', Carbon::now($this->currentTimezone())->subMonths(3))
            ->orderByDesc('created_at')
            ->limit(3)
            ->get($this->userAddressSelectColumns(['name', 'created_at']));

        if ($newResidents->isEmpty()) {
            $message = $this->text(
                'Tidak ada warga baru dalam 3 bulan terakhir.',
                'No new residents in the last 3 months.'
            );

            return $asBlock
                ? [
                    'content' => $message,
                    'style' => [
                        'tone' => 'informative',
                        'confidence' => 0.6,
                        'followups' => $this->followUpsForIntent('residents_new'),
                    ],
                ]
                : $message;
        }

        $items = $newResidents->map(function (User $user) {
            $date = Carbon::parse($user->created_at)->translatedFormat('d M Y');
            $alamat = $this->userAddressValue($user);

            return "• {$user->name} ({$date}) — {$alamat}";
        })->join("\n");

        $message = $this->text(
            "Warga baru (3 bulan terakhir):\n{$items}",
            "New residents (last 3 months):\n{$items}"
        );

        if ($asBlock) {
            return [
                'content' => $message,
                'style' => [
                    'tone' => 'informative',
                    'confidence' => 0.72,
                    'followups' => $this->followUpsForIntent('residents_new'),
                ],
            ];
        }

        return $message;
    }

    private function getTagihanInfo(?int $userId, ?array $period = null, bool $asBlock = false, ?array $constraints = null)
    {
        $this->lastIntent = 'bills';
        $this->lastTopic = 'bills';
        if (!$userId) {
            $message = $this->text(
                'Untuk cek tagihan, kamu harus login dulu ya! 🔐',
                'Please log in so I can check your bills for you. 🔐'
            );
            $style = [
                'tone' => 'cautious',
                'confidence' => 0.35,
                'followups' => $this->followUpsForIntent('bills', [
                    'state' => 'auth_missing',
                ]),
            ];

            return $asBlock
                ? ['content' => $message, 'style' => $style]
                : $this->requireLoginResponse(
                    'Untuk cek tagihan, kamu harus login dulu ya! 🔐',
                    'Please log in so I can check your bills for you. 🔐'
                );
        }

        $period = $this->normalizePeriodInput($period);
        $constraints ??= $this->constraintsForIntent('bills');
        $this->registerIntentReplay('bills', function () use ($userId, $period, $asBlock, $constraints) {
            return $this->getTagihanInfo($userId, $period, $asBlock, $constraints);
        }, [
            'intent_label' => $this->intentDescriptions['bills']['label_id'] ?? 'tagihan',
            'mode' => $asBlock ? 'block' : 'full',
        ]);

        $query = Bill::where('user_id', $userId)
            ->where('status', '!=', 'paid')
            ->orderBy('due_date')
            ->limit(5)
            ->when($period !== null, function ($builder) use ($period) {
                $start = Carbon::parse($period['start'])->startOfDay();
                $end = Carbon::parse($period['end'])->endOfDay();
                $builder->whereBetween('created_at', [$start, $end]);
            });

        $bills = $query->get();

        [$bills, $constraintMessage] = $this->filterCollectionByConstraints(
            $bills,
            $constraints,
            fn(Bill $bill) => trim(($bill->title ?? '') . ' ' . ($bill->type ?? ''))
        );

        if ($constraintMessage !== null) {
            return $asBlock
                ? $constraintMessage
                : $this->respond($constraintMessage, [
                    'tone' => 'cautious',
                    'confidence' => 0.55,
                    'followups' => $this->followUpsForIntent('bills', [
                        'state' => 'clarification',
                    ]),
                ]);
        }

        $bills = $this->applyFactOverridesToBills($bills);
        $this->lastData = $bills->toArray();

        if ($bills->isEmpty()) {
            $message = $this->text(
                'Yeay! Semua tagihanmu sudah lunas. 🎉',
                'Nice! You have no outstanding bills.'
            );
            $style = [
                'tone' => 'celebrate',
                'confidence' => 0.95,
                'followups' => $this->followUpsForIntent('bills', [
                    'state' => 'empty',
                    'has_unpaid' => false,
                ]),
            ];

            return $asBlock
                ? ['content' => $message, 'style' => $style]
                : $this->respondWithTemplate('bills', 'slot_filled', $message, $style);
        }

        $periodLabel = $period !== null
            ? ($this->isEnglish() ? ($period['label_en'] ?? '') : ($period['label_id'] ?? ''))
            : '';

        $intro = $this->randomChoice(
            $this->isEnglish()
                ? [
                    'Let me recap your active bills:',
                    'Here are the bills that still need your attention:',
                    'These bills are still open right now:',
                ]
                : [
                    'Aku rangkum tagihan aktifmu ya:',
                    'Berikut tagihan yang masih perlu kamu bereskan:',
                    'Ini daftar tagihan yang masih terbuka saat ini:',
                ]
        );

        $locale = $this->isEnglish() ? 'en' : config('app.locale', 'id');
        $items = $bills->map(function (Bill $bill) use ($locale) {
            $due = $bill->due_date
                ? Carbon::parse($bill->due_date)->locale($locale)->translatedFormat('d M Y')
                : ($this->isEnglish() ? 'Due date not set' : 'Jatuh tempo belum ditentukan');
            $amount = $this->formatCurrency((int) $bill->amount);
            $statusLabel = $bill->due_date && $bill->due_date->isPast()
                ? ($this->isEnglish() ? 'Overdue' : 'Lewat jatuh tempo')
                : ($this->isEnglish() ? 'Active' : 'Aktif');

            return chr(7) . " {$bill->title} - {$amount} ({$due}, {$statusLabel})";
        })->join("\n");

        $amounts = $bills->pluck('amount')->map(fn($amount) => (int) $amount)->all();
        $overdueCount = $bills->filter(function (Bill $bill) {
            $due = $this->asCarbon($bill->due_date);

            return $due !== null && $due->isPast();
        })->count();
        $totalNumeric = array_sum($amounts);
        $total = $this->formatCurrency($totalNumeric);
        $link = $this->safeRoute('resident.bills');
        $cta = $this->randomChoice(
            $this->isEnglish()
                ? [
                    'Settle them soon to stay clear.',
                    'Ready to pay? Go ahead and finish it up.',
                    'Take care of them now so nothing slips.',
                ]
                : [
                    'Segera lunasi biar aman ya.',
                    'Kalau sudah siap bayar, langsung proses aja.',
                    'Bisa langsung diselesaikan biar nggak lupa.',
                ]
        );

        $footer = $link
            ? $this->text(
                "{$cta} ?? {$link}",
                "{$cta} {$link}"
            )
            : $this->text(
                "{$cta} Buka menu **Tagihan** untuk lanjut.",
                "{$cta} Open the **Bills** menu to continue."
            );

        $labelSuffix = $periodLabel !== '' ? ' (' . $periodLabel . ')' : '';

        $summaryTemplate = $this->text(
            "{$intro}{$labelSuffix}\n\n{$items}\n\nTotal estimasi: **{$total}**\n\n{$footer}",
            "{$intro}{$labelSuffix}\n\n{$items}\n\nEstimated total: **{$total}**\n\n{$footer}"
        );

        $safeListTemplate = $this->text(
            "{$intro}{$labelSuffix}\n\n{$items}\n\nTotal estimasi: **{$total}**\n\n{$footer}",
            "{$intro}{$labelSuffix}\n\n{$items}\n\nEstimated total: **{$total}**\n\n{$footer}"
        );

        $numericRules = [
            RuleLibrary::sum('bills_total', $totalNumeric, $amounts, 0.01),
        ];

        if (!empty($amounts)) {
            $numericRules[] = RuleLibrary::range('bill_amount_positive', min($amounts), 0);
        }

        $dateRules = [];
        foreach ($bills as $bill) {
            $due = $this->asCarbon($bill->due_date);
            if ($due !== null) {
                $dateRules[] = [
                    'label' => 'bill_due_' . $bill->id,
                    'value' => $due,
                    'timezone' => $this->currentTimezone(),
                ];
            }
        }

        if ($period !== null) {
            $dateRules[] = [
                'label' => 'bills_period',
                'start' => $this->asCarbon($period['start']),
                'end' => $this->asCarbon($period['end']),
                'timezone' => $this->currentTimezone(),
            ];
        }

        $negationContext = [
            'label' => 'bills',
            'user_negated' => $this->userRequestsNegation(['tagihan', 'bill', 'iuran', 'tunggakan']),
            'resolved' => false,
        ];

        $clarifications = [
            'negation_conflict' => [
                'id' => 'Sepertinya kamu berharap tidak ada tagihan, mau aku cek periode lain atau pastikan lagi?',
                'en' => 'It sounds like you expected no bills, should I double-check another period or confirm the data?',
            ],
            'numeric_sum_mismatch' => [
                'id' => 'Angkanya kurang sinkron, sebutkan periode/tagihan spesifik biar bisa kucek ulang ya?',
                'en' => 'The numbers look inconsistent, could you mention the period so I can re-check it?',
            ],
            'numeric_below_min' => [
                'id' => 'Nilai tagihannya tidak wajar. Mau fokus ke daftar mentah dulu?',
                'en' => 'One of the bill amounts looks odd. Should I show the raw list first?',
            ],
            'date_invalid' => [
                'id' => 'Tanggal tagihannya kurang jelas, boleh tentukan bulan/periode yang kamu maksud?',
                'en' => 'The billing dates are unclear, could you specify the period you mean?',
            ],
            'date_timezone_mismatch' => [
                'id' => 'Format tanggalnya beda zona, kasih tahu zona waktumu biar pas ya?',
                'en' => 'The date zone differs, let me know your timezone so I can align it.',
            ],
            'default' => [
                'id' => 'Biar akurat, bilang periode tagihan mana yang mau dicek ya?',
                'en' => 'To be precise, tell me which billing period you want me to check.',
            ],
        ];

        $finalMessage = $this->reasoning()->run(function () use (
            $summaryTemplate,
            $numericRules,
            $dateRules,
            $negationContext,
            $clarifications,
            $safeListTemplate
        ) {
            return ReasoningDraft::make(
                intent: 'bills',
                message: $summaryTemplate,
                numerics: $numericRules,
                dates: $dateRules,
                negation: $negationContext,
                clarifications: $clarifications,
                repairCallback: function () use ($safeListTemplate, $dateRules, $negationContext, $clarifications) {
                    return ReasoningDraft::make(
                        intent: 'bills',
                        message: $safeListTemplate,
                        numerics: [],
                        dates: $dateRules,
                        negation: $negationContext,
                        clarifications: $clarifications
                    );
                }
            );
        }, $this->language);

        $followupContext = [
            'state' => 'summary',
            'has_unpaid' => true,
            'overdue_count' => $overdueCount,
            'total_amount' => $totalNumeric,
        ];

        $style = [
            'tone' => 'informative',
            'confidence' => 0.9,
            'followups' => $this->followUpsForIntent('bills', $followupContext),
        ];

        if ($asBlock) {
            return [
                'content' => $finalMessage,
                'style' => $style,
            ];
        }

        return $this->respondWithTemplate('bills', 'slot_filled', $finalMessage, $style);
    }

    private function getRiwayatPembayaran(?int $userId): array
    {
        $this->lastIntent = 'payments';
        $this->lastTopic = 'payments';
        $this->registerIntentReplay('payments', function () use ($userId) {
            return $this->getRiwayatPembayaran($userId);
        }, [
            'intent_label' => $this->intentDescriptions['payments']['label_id'] ?? 'pembayaran',
            'mode' => 'history',
        ]);
        if (!$userId) {
            return $this->requireLoginResponse(
                'Untuk cek riwayat pembayaran, kamu harus login dulu ya! 🔐',
                'Please log in so I can show your payment history. 🔐'
            );
        }
        $payments = Payment::where('user_id', $userId)
            ->where('status', 'paid')
            ->orderBy('paid_at', 'desc')
            ->limit(5)
            ->get();
        $payments = $this->applyFactOverridesToPayments($payments);

        $this->lastData = $payments->toArray();
        if ($payments->isEmpty()) {
            return $this->respond('Belum ada riwayat pembayaran nih. Yuk cek menu **Tagihan** untuk memastikan semuanya aman.');
        }
        $intro = $this->randomChoice([
            'Ini pembayaran terbarumu:',
            'Riwayat pembayaran terakhir kamu:',
            'Kamu sudah menyelesaikan pembayaran berikut:',
        ]);
        $items = $payments->map(function (Payment $payment) {
            $date = $payment->paid_at ? Carbon::parse($payment->paid_at)->translatedFormat('d M Y') : 'Tanggal tidak tercatat';
            $amount = $this->formatCurrency((int) $payment->amount);
            $title = optional($payment->bill)->title ?? 'Pembayaran';
            $method = $payment->gateway ? strtoupper($payment->gateway) : 'manual';
            return "• {$title} — {$amount} ({$date}, via {$method})";
        })->join("\n");
        $link = $this->safeRoute('resident.reports');
        $footer = $link
            ? "Detail lengkap ada di laporan kas 👉 {$link}"
            : 'Detail lengkap bisa kamu lihat di menu **Laporan**.';
        return $this->respond("{$intro}\n\n{$items}\n\n{$footer}", [
            'tone' => 'informative',
            'confidence' => 0.8,
            'followups' => $this->followUpsForIntent('payments'),
        ]);
    }
    private function getPembayaranInfo(?int $userId, ?array $period = null, bool $asBlock = false, ?array $constraints = null)
    {
        $this->lastIntent = 'payments';
        if (!$userId) {
            $message = $this->text(
                'Untuk cek pembayaran, kamu harus login dulu ya! 🔐',
                'You need to log in before I can show your payments. 🔐'
            );
            $style = [
                'tone' => 'cautious',
                'confidence' => 0.4,
                'followups' => $this->followUpsForIntent('payments', [
                    'state' => 'auth_missing',
                ]),
            ];

            return $asBlock
                ? ['content' => $message, 'style' => $style]
                : $this->requireLoginResponse(
                    'Untuk cek pembayaran, kamu harus login dulu ya! 🔐',
                    'You need to log in before I can show your payments. 🔐'
                );
        }
        if ($period === null) {
            $period = $this->temporal()->monthRange(Carbon::now($this->currentTimezone()));
        } else {
            $period = $this->normalizePeriodInput($period) ?? $this->temporal()->monthRange(Carbon::now($this->currentTimezone()));
        }
        $constraints ??= $this->constraintsForIntent('payments');
        $this->registerIntentReplay('payments', function () use ($userId, $period, $asBlock, $constraints) {
            return $this->getPembayaranInfo($userId, $period, $asBlock, $constraints);
        }, [
            'intent_label' => $this->intentDescriptions['payments']['label_id'] ?? 'pembayaran',
            'mode' => $asBlock ? 'block' : 'summary',
        ]);

        $start = Carbon::parse($period['start'])->startOfDay();
        $end = Carbon::parse($period['end'])->endOfDay();

        $payments = Payment::where('user_id', $userId)
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$start, $end])
            ->orderByDesc('paid_at')
            ->get();
        [$payments, $constraintMessage] = $this->filterCollectionByConstraints(
            $payments,
            $constraints,
            fn(Payment $payment) => trim((optional($payment->bill)->title ?? '') . ' ' . (optional($payment->bill)->type ?? ''))
        );

        if ($constraintMessage !== null) {
            return $asBlock
                ? $constraintMessage
                : $this->respond($constraintMessage, [
                    'tone' => 'cautious',
                    'confidence' => 0.55,
                    'followups' => $this->followUpsForIntent('payments', [
                        'state' => 'clarification',
                    ]),
                ]);
        }
        $payments = $this->applyFactOverridesToPayments($payments);
        $this->lastData = $payments->toArray();
        if ($payments->isEmpty()) {
            $message = $this->text(
                'Belum ada pembayaran tercatat bulan ini. Tetap cek menu **Tagihan** buat jaga-jaga ya!',
                'No payments recorded this month yet. Keep an eye on the Bills menu just in case!'
            );

            $style = [
                'tone' => 'cautious',
                'confidence' => 0.55,
                'followups' => $this->followUpsForIntent('payments', [
                    'state' => 'empty',
                ]),
            ];

            return $asBlock
                ? ['content' => $message, 'style' => $style]
                : $this->respondWithTemplate('payments', 'slot_filled', $message, $style);
        }
        $count = $payments->count();
        $amounts = $payments->map(fn($payment) => (int) $payment->amount)->all();
        $totalNumeric = array_sum($amounts);
        $total = $this->formatCurrency($totalNumeric);
        $items = $payments->map(function (Payment $payment) {
            $date = $payment->paid_at ? Carbon::parse($payment->paid_at)->translatedFormat('d M Y') : '-';
            $title = optional($payment->bill)->title ?? 'Pembayaran';
            $amount = $this->formatCurrency((int) $payment->amount);
            return " {$title} - {$amount} ({$date})";
        })->join("
");
        $summary = $this->randomChoice([
            "Bulan ini kamu sudah bayar {$count} tagihan.",
            "Progress pembayaran bulan ini mantap: {$count} transaksi selesai.",
            "Catatan bulan ini: {$count} pembayaran sukses tercatat.",
        ]);
        $label = $this->isEnglish() ? ($period['label_en'] ?? '') : ($period['label_id'] ?? '');
        $labelSuffix = $label !== '' ? ' (' . $label . ')' : '';

        $summaryMessage = "{$summary}{$labelSuffix}\n\n{$items}\n\nTotal bayar periode ini: **{$total}**. " .
            ($this->isEnglish() ? 'Thanks for staying on track! :)' : 'Terima kasih sudah tertib! :)');
        $safeListTemplate = "{$summary}{$labelSuffix}\n\n{$items}\n\n" .
            ($this->isEnglish() ? 'Detail lengkap ada di menu Payments, mau cek lagi?' : 'Detail lengkapnya bisa dicek di menu Pembayaran ya?');

        $numericRules = [
            RuleLibrary::sum('payments_total', $totalNumeric, $amounts, 0.01),
        ];

        if (!empty($amounts)) {
            $numericRules[] = RuleLibrary::range('payment_amount', min($amounts), 0);
        }

        $dateRules = [
            [
                'label' => 'payments_period',
                'start' => $start,
                'end' => $end,
                'timezone' => $this->currentTimezone(),
            ],
        ];

        foreach ($payments as $payment) {
            $paidAt = $this->asCarbon($payment->paid_at);
            if ($paidAt !== null) {
                $dateRules[] = [
                    'label' => 'payment_' . $payment->id,
                    'value' => $paidAt,
                    'timezone' => $this->currentTimezone(),
                ];
            }
        }

        $negationContext = [
            'label' => 'payments',
            'user_negated' => $this->userRequestsNegation(['pembayaran', 'bayar', 'lunas']),
            'resolved' => false,
        ];

        $clarifications = [
            'negation_conflict' => [
                'id' => 'Kamu sepertinya mengira belum ada pembayaran, mau tentukan periode lain untuk dipastikan?',
                'en' => 'You hinted there should be no payments; want me to double-check a different period?',
            ],
            'numeric_sum_mismatch' => [
                'id' => 'Total pembayaran terasa janggal. Sebutkan nominal yang kamu harapkan dong biar kucek ulang.',
                'en' => 'The total payment looks off. Tell me the amount you expect so I can re-check it.',
            ],
            'date_range_invalid' => [
                'id' => 'Rentang tanggalnya kurang pas. Periode mana yang perlu kamu lihat?',
                'en' => 'The date range looks off. Which period do you need me to inspect?',
            ],
            'default' => [
                'id' => 'Biar pas, sebut periode atau jumlah pembayaran yang mau dicek ya.',
                'en' => 'To be precise, let me know the payment period or amount you want me to check.',
            ],
        ];

        $finalMessage = $this->reasoning()->run(function () use (
            $summaryMessage,
            $numericRules,
            $dateRules,
            $negationContext,
            $clarifications,
            $safeListTemplate
        ) {
            return ReasoningDraft::make(
                intent: 'payments',
                message: $summaryMessage,
                numerics: $numericRules,
                dates: $dateRules,
                negation: $negationContext,
                clarifications: $clarifications,
                repairCallback: function () use ($safeListTemplate, $dateRules, $negationContext, $clarifications) {
                    return ReasoningDraft::make(
                        intent: 'payments',
                        message: $safeListTemplate,
                        numerics: [],
                        dates: $dateRules,
                        negation: $negationContext,
                        clarifications: $clarifications
                    );
                }
            );
        }, $this->language);

        $followupContext = [
            'state' => 'summary',
            'recent_payment_count' => $count,
        ];

        $style = [
            'tone' => 'informative',
            'confidence' => 0.85,
            'followups' => $this->followUpsForIntent('payments', $followupContext),
        ];

        if ($asBlock) {
            return [
                'content' => $finalMessage,
                'style' => $style,
            ];
        }

        return $this->respondWithTemplate('payments', 'slot_filled', $finalMessage, $style);
    }

    private function getAgendaInfo(string $range = 'month', bool $asBlock = false)
    {
        $this->lastIntent = 'agenda';
        $this->lastTopic = 'agenda';
        $this->registerIntentReplay('agenda', function () use ($range, $asBlock) {
            return $this->getAgendaInfo($range, $asBlock);
        }, [
            'intent_label' => $this->intentDescriptions['agenda']['label_id'] ?? 'agenda',
            'mode' => $range,
        ]);

        $now = Carbon::now($this->currentTimezone());
        if ($range === 'tomorrow') {
            $start = $now->copy()->addDay()->startOfDay();
            $end = $now->copy()->addDay()->endOfDay();
            $label = 'besok';
        } elseif ($range === 'today') {
            $start = $now->copy()->startOfDay();
            $end = $now->copy()->endOfDay();
            $label = 'hari ini';
        } elseif ($range === 'week') {
            $start = $now;
            $end = $now->copy()->addWeek();
            $label = '7 hari ke depan';
        } elseif ($range === 'next_week') {
            $start = $now->copy()->addWeek();
            $end = $now->copy()->addWeeks(2);
            $label = 'minggu depan';
        } else {
            $start = $now;
            $end = $now->copy()->addMonth();
            $label = '30 hari ke depan';
        }
        $events = Event::where(function ($query) {
            $query->where('is_public', true);
        })
            ->whereBetween('start_at', [$start, $end])
            ->orderBy('start_at')
            ->limit(5)
            ->get();

        $events = $this->applyFactOverridesToEvents($events);

        $this->lastData = $events->toArray();
        if ($events->isEmpty()) {
            $message = $this->text(
                "Belum ada agenda {$label}. Santai dulu ya, nanti kalau ada kegiatan baru aku kabari!",
                "There is no agenda for {$label} yet. I'll let you know when something new pops up!"
            );
            $style = [
                'tone' => 'celebrate',
                'confidence' => 0.9,
                'followups' => $this->followUpsForIntent('agenda', [
                    'state' => 'empty',
                    'agenda_range_label' => $label,
                ]),
            ];

            return $asBlock
                ? ['content' => $message, 'style' => $style]
                : $this->respondWithTemplate('agenda', 'slot_filled', $message, $style);
        }
        $intro = $this->randomChoice([
            "Agenda {$label} yang bisa kamu catat:",
            'Catat jadwal kegiatan berikut biar nggak kelewat:',
            'Ini agenda terdekat yang sudah dijadwalkan:',
        ]);
        $items = $events->map(function (Event $event) {
            $date = $event->start_at ? Carbon::parse($event->start_at)->translatedFormat('d M Y, H:i') : '-';
            $location = $event->location ?: 'Lokasi menyusul';
            return "• {$event->title} — {$date} @ {$location}";
        })->join("\n");
        $link = $this->safeRoute('resident.dashboard');
        $footer = $link
            ? "Detail lengkap & RSVP bisa dicek di dashboard 👉 {$link}#agenda"
            : 'Cek menu **Agenda** untuk detail lengkap.';
        $message = "{$intro}\n\n{$items}\n\n{$footer}";

        $dateRules = [
            [
                'label' => 'agenda_range',
                'start' => $start,
                'end' => $end,
                'timezone' => $this->currentTimezone(),
            ],
        ];

        foreach ($events as $event) {
            $eventDate = $this->asCarbon($event->start_at);
            if ($eventDate !== null) {
                $dateRules[] = [
                    'label' => 'event_' . $event->id,
                    'value' => $eventDate,
                    'timezone' => $this->currentTimezone(),
                ];
            }
        }

        $negationContext = [
            'label' => 'agenda',
            'user_negated' => $this->userRequestsNegation(['agenda', 'acara', 'event', 'jadwal']),
            'resolved' => false,
        ];

        $clarifications = [
            'negation_conflict' => [
                'id' => 'Kamu bilang belum ada agenda, mau tentukan tanggal yang ingin dicek?',
                'en' => 'You mentioned there might be no events; should I focus on a specific date?',
            ],
            'date_range_invalid' => [
                'id' => 'Rentang waktunya kurang jelas, sebutkan periode agenda yang kamu mau ya?',
                'en' => 'The requested time range is unclear, let me know which period you mean?',
            ],
            'date_invalid' => [
                'id' => 'Tanggal agenda susah kubaca. Bisa kasih tanggal pastinya?',
                'en' => 'Some dates look invalid. Could you share the exact day?',
            ],
            'default' => [
                'id' => 'Biar tepat, sebutkan tanggal atau rentang agenda yang kamu butuhkan ya.',
                'en' => 'To stay precise, tell me the exact date or range you need.',
            ],
        ];

        $finalMessage = $this->reasoning()->run(function () use ($message, $dateRules, $negationContext, $clarifications) {
            return ReasoningDraft::make(
                intent: 'agenda',
                message: $message,
                dates: $dateRules,
                numerics: [],
                negation: $negationContext,
                clarifications: $clarifications
            );
        }, $this->language);

        $followupContext = [
            'state' => 'summary',
            'agenda_count' => $events->count(),
            'agenda_range_label' => $label,
        ];

        $style = [
            'tone' => 'informative',
            'confidence' => 0.8,
            'followups' => $this->followUpsForIntent('agenda', $followupContext),
        ];

        if ($asBlock) {
            return [
                'content' => $finalMessage,
                'style' => $style,
            ];
        }

        return $this->respondWithTemplate('agenda', 'slot_filled', $finalMessage, $style);
    }
    private function getJumlahWarga(): array
    {
        $this->lastIntent = 'residents';
        $this->lastTopic = 'residents';
        $this->registerIntentReplay('residents', function () {
            return $this->getJumlahWarga();
        }, ['intent_label' => $this->intentDescriptions['residents']['label_id'] ?? 'residents', 'mode' => 'count']);
        $total = max(0, (int) User::where('role', 'warga')->count());
        $this->lastData = ['total' => $total];
        $intro = $this->randomChoice([
            'Data warga terbaru:',
            'Update jumlah warga sekarang:',
            'Catatan registrasi saat ini:',
        ]);
        $message = "{$intro} **{$total} warga** terdaftar aktif di RT ini. Cek menu **Direktori** untuk melihat detail tiap warga.";
        $safeMessage = $this->text(
            'Data warga lengkap ada di menu **Direktori**. Sebutkan nama/RT yang mau kamu cek ya.',
            'The full resident directory is available in the **Directory** menu. Let me know the name or block you need.'
        );

        $numericRules = [
            RuleLibrary::nonNegative('residents_total', $total),
        ];

        $clarifications = [
            'numeric_below_min' => [
                'id' => 'Jumlah warga ini terasa tidak wajar. Ada blok khusus yang mau kamu pastikan?',
                'en' => 'That resident count looks odd. Is there a specific block you want me to verify?',
            ],
            'negation_conflict' => [
                'id' => 'Sepertinya kamu menolak data ini. Mau aku cek ulang berdasarkan filter tertentu?',
                'en' => 'It sounds like you disagree with this count. Should I re-check with a specific filter?',
            ],
            'default' => [
                'id' => 'Sebutkan kriteria (blok/nama) yang mau kamu cek supaya datanya presisi ya.',
                'en' => 'Tell me the block or name you need so I can be precise.',
            ],
        ];

        $negationContext = [
            'label' => 'residents_total',
            'user_negated' => $this->userRequestsNegation(['warga', 'penduduk', 'residents']),
            'resolved' => false,
        ];

        $finalMessage = $this->reasoning()->run(function () use ($message, $safeMessage, $numericRules, $clarifications, $negationContext) {
            return ReasoningDraft::make(
                intent: 'residents',
                message: $message,
                numerics: $numericRules,
                dates: [],
                negation: $negationContext,
                clarifications: $clarifications,
                repairCallback: function () use ($safeMessage, $clarifications, $negationContext) {
                    return ReasoningDraft::make(
                        intent: 'residents',
                        message: $safeMessage,
                        numerics: [],
                        dates: [],
                        negation: $negationContext,
                        clarifications: $clarifications
                    );
                }
            );
        }, $this->language);

        $style = [
            'tone' => 'informative',
            'confidence' => 0.78,
            'followups' => $this->followUpsForIntent('residents', [
                'state' => 'summary',
                'resident_count' => $total,
            ]),
        ];

        return $this->respondWithTemplate('residents', 'slot_filled', $finalMessage, $style);
    }

    private function cariWarga(string $nama, string $status = 'all'): array
    {
        $this->lastIntent = 'residents';
        $this->lastTopic = 'residents';
        $this->lastData = [];
        $this->registerIntentReplay('residents', function () use ($nama, $status) {
            return $this->cariWarga($nama, $status);
        }, ['intent_label' => $this->intentDescriptions['residents']['label_id'] ?? 'residents', 'mode' => 'search']);
        $normalizedStatus = Str::lower(Str::squish($status));
        if ($nama === '*' || $nama === '') {
            $totalQuery = User::where('role', 'warga');
            if ($normalizedStatus !== '' && $normalizedStatus !== 'all') {
                $totalQuery->where('status', $normalizedStatus);
            }
            $total = $totalQuery->count();
            $statusLabel = $normalizedStatus !== '' && $normalizedStatus !== 'all'
                ? ($this->isEnglish() ? " with {$normalizedStatus} status" : " dengan status {$normalizedStatus}")
                : '';

            return $this->respond("Saat ini ada {$total} warga terdaftar{$statusLabel}. Untuk detailnya, kunjungi menu **Direktori** ya!", [
                'tone' => 'informative',
                'confidence' => 0.7,
                'followups' => $this->followUpsForIntent('residents'),
            ]);
        }

        $usersQuery = User::where('role', 'warga')
            ->where('name', 'like', "%{$nama}%");

        if ($normalizedStatus !== '' && $normalizedStatus !== 'all') {
            $usersQuery->where('status', $normalizedStatus);
        }

        $users = $usersQuery
            ->limit(5)
            ->get($this->userAddressSelectColumns(['name', 'status']));

        $users = $this->applyFactOverridesToResidents($users);

        $this->lastData = $users->toArray();

        if ($users->isEmpty()) {
            $this->lastData = [];
            return $this->respond("Belum menemukan warga bernama \"{$nama}\". Coba cek ejaan lain atau lihat langsung di menu **Direktori**.", [
                'tone' => 'cautious',
                'confidence' => 0.5,
                'followups' => $this->followUpsForIntent('residents'),
            ]);
        }

        $items = $users->map(function (User $user) {
            $alamat = $this->userAddressValue($user, 'Alamat belum terdata', 'Address missing');
            $status = $user->status ? ' / ' . Str::title($user->status) : '';

            return chr(7) . " {$user->name}{$status}\n 📍 {$alamat}";
        })->join("\n\n");

        return $this->respond("Ini warga yang cocok dengan namamu:\n\n{$items}\n\nUntuk info lebih detail kamu bisa buka menu **Direktori**.", [
            'tone' => 'informative',
            'confidence' => 0.75,
            'followups' => $this->followUpsForIntent('residents'),
        ]);
    }


    private function getRekapKeuangan(?int $userId, string $format = 'pdf', bool $asBlock = false, ?array $period = null, ?array $constraints = null)
    {
        $this->lastIntent = 'finance';
        $this->lastTopic = 'finance';
        if (!$userId) {
            $message = $this->text(
                'Untuk lihat rekap keuangan, kamu harus login dulu ya! 🔐',
                'Please log in so I can prepare your finance recap. 🔐'
            );

            $style = [
                'tone' => 'cautious',
                'confidence' => 0.5,
                'followups' => $this->followUpsForIntent('finance', [
                    'state' => 'auth_missing',
                ]),
            ];

            return $asBlock
                ? ['content' => $message, 'style' => $style]
                : $this->respond($message, $style);
        }
        $format = Str::lower($format);
        if (!in_array($format, ['pdf', 'xlsx'], true)) {
            $format = 'pdf';
        }
        $formatLabel = Str::upper($format);

        $periodRange = $period ?? $this->getSlotValue('finance', 'period');
        if (!is_array($periodRange) || !isset($periodRange['start'], $periodRange['end'])) {
            $periodRange = $this->temporal()->monthRange(Carbon::now($this->currentTimezone()));
        }
        $constraints ??= $this->constraintsForIntent('finance');
        $this->registerIntentReplay('finance', function () use ($userId, $format, $asBlock, $periodRange, $constraints) {
            return $this->getRekapKeuangan($userId, $format, $asBlock, $periodRange, $constraints);
        }, [
            'intent_label' => $this->intentDescriptions['finance']['label_id'] ?? 'rekap keuangan',
            'mode' => $format,
        ]);

        $start = Carbon::parse($periodRange['start'], $this->currentTimezone())->startOfDay();
        $end = Carbon::parse($periodRange['end'], $this->currentTimezone())->endOfDay();
        $bills = Bill::where('user_id', $userId)
            ->whereBetween('created_at', [$start, $end])
            ->get();
        $payments = Payment::where('user_id', $userId)
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$start, $end])
            ->get();

        [$bills, $billConstraintMessage] = $this->filterCollectionByConstraints(
            $bills,
            $constraints,
            fn(Bill $bill) => trim(($bill->title ?? '') . ' ' . ($bill->type ?? ''))
        );

        if ($billConstraintMessage !== null) {
            $style = [
                'tone' => 'cautious',
                'confidence' => 0.55,
                'followups' => $this->followUpsForIntent('finance', [
                    'state' => 'clarification',
                ]),
            ];

            return $asBlock
                ? ['content' => $billConstraintMessage, 'style' => $style]
                : $this->respond($billConstraintMessage, $style);
        }
        $bills = $this->applyFactOverridesToBills($bills);

        [$payments, $paymentConstraintMessage] = $this->filterCollectionByConstraints(
            $payments,
            $constraints,
            fn(Payment $payment) => trim((optional($payment->bill)->title ?? '') . ' ' . (optional($payment->bill)->type ?? ''))
        );

        if ($paymentConstraintMessage !== null) {
            $style = [
                'tone' => 'cautious',
                'confidence' => 0.55,
                'followups' => $this->followUpsForIntent('finance', [
                    'state' => 'clarification',
                ]),
            ];

            return $asBlock
                ? ['content' => $paymentConstraintMessage, 'style' => $style]
                : $this->respond($paymentConstraintMessage, $style);
        }
        $payments = $this->applyFactOverridesToPayments($payments);
        $this->lastData = [
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'bills' => $bills->toArray(),
            'payments' => $payments->toArray(),
        ];
        $billAmounts = $bills->map(fn($bill) => (int) $bill->amount)->all();
        $paymentAmounts = $payments->map(fn($payment) => (int) $payment->amount)->all();
        $totalBillsNumeric = array_sum($billAmounts);
        $totalPaidNumeric = array_sum($paymentAmounts);
        $outstandingNumeric = (int) $bills->where('status', '!=', 'paid')->sum('amount');
        $totalBills = $this->formatCurrency($totalBillsNumeric);
        $totalPaid = $this->formatCurrency($totalPaidNumeric);
        $outstanding = $this->formatCurrency($outstandingNumeric);
        $summary = $this->randomChoice([
            'Rekap keuangan bulan ini:',
            'Ini ringkasan transaksi bulan berjalan:',
            'Catatan finansial terakhir:',
        ]);
        $exportHint = $this->text(
            "Butuh file {$formatLabel}? Buka menu **Laporan** ya!",
            "Need the {$formatLabel} file? Open the **Reports** menu!"
        );
        $message = "{$summary}\n- Tagihan diterbitkan: {$totalBills}\n- Sudah dibayar: {$totalPaid}\n- Tunggakan: {$outstanding}\n\n{$exportHint}";

        $safeMessage = "{$summary}\n- Tagihan diterbitkan: {$totalBills}\n- Sudah dibayar: {$totalPaid}\n\n" .
            ($this->isEnglish() ? 'Untuk detail tunggakan silakan cek menu Reports supaya pasti.' : 'Detail tunggakan lengkap ada di menu Laporan biar pasti.');

        $numericRules = [];
        if (!empty($billAmounts)) {
            $numericRules[] = RuleLibrary::sum('finance_bills', $totalBillsNumeric, $billAmounts);
        }
        if (!empty($paymentAmounts)) {
            $numericRules[] = RuleLibrary::sum('finance_payments', $totalPaidNumeric, $paymentAmounts);
        }
        $numericRules[] = RuleLibrary::range('finance_outstanding', max(0, $outstandingNumeric), 0);
        $numericRules[] = RuleLibrary::range(
            'finance_paid_not_exceed',
            $totalPaidNumeric,
            null,
            $totalBillsNumeric > 0 ? $totalBillsNumeric : null
        );

        $dateRules = [
            [
                'label' => 'finance_period',
                'start' => $start,
                'end' => $end,
                'timezone' => $this->currentTimezone(),
            ],
        ];

        $clarifications = [
            'numeric_sum_mismatch' => [
                'id' => 'Angka rekapnya nggak sinkron. Mau aku regenerasi berdasarkan transaksi terbaru?',
                'en' => 'The recap numbers look off. Want me to regenerate it with the latest transactions?',
            ],
            'numeric_below_min' => [
                'id' => 'Nilai tunggakannya minus. Mau cek ulang data kasar di laporan?',
                'en' => 'Outstanding looks negative. Should I cross-check the raw report?',
            ],
            'numeric_above_max' => [
                'id' => 'Total bayar melewati total tagihan. Ada transaksi ekstra yang mau kamu jelaskan?',
                'en' => 'Paid total exceeds issued bills. Is there an extra transaction I should know about?',
            ],
            'date_range_invalid' => [
                'id' => 'Rentang rekapnya aneh. Sebut bulan mana yang ingin kamu rekap ya?',
                'en' => 'The recap window seems odd. Which month should I focus on?',
            ],
            'default' => [
                'id' => 'Sebut periode atau detail yang ingin dipastikan supaya rekapnya akurat ya.',
                'en' => 'Tell me the exact period/details you need so I can keep the recap accurate.',
            ],
        ];

        $finalMessage = $this->reasoning()->run(function () use ($message, $safeMessage, $numericRules, $dateRules, $clarifications) {
            return ReasoningDraft::make(
                intent: 'finance',
                message: $message,
                numerics: $numericRules,
                dates: $dateRules,
                negation: [],
                clarifications: $clarifications,
                repairCallback: function () use ($safeMessage, $dateRules, $clarifications) {
                    return ReasoningDraft::make(
                        intent: 'finance',
                        message: $safeMessage,
                        numerics: [],
                        dates: $dateRules,
                        clarifications: $clarifications
                    );
                }
            );
        }, $this->language);

        $followupContext = [
            'state' => 'summary',
            'has_outstanding' => $outstandingNumeric > 0,
            'format' => $formatLabel,
        ];

        $style = [
            'tone' => 'informative',
            'confidence' => 0.8,
            'followups' => $this->followUpsForIntent('finance', $followupContext),
        ];

        if ($asBlock) {
            return [
                'content' => $finalMessage,
                'style' => $style,
            ];
        }

        return $this->respondWithTemplate('finance', 'slot_filled', $finalMessage, $style);
    }

    private function shouldInvokeKnowledgeBase(string $normalizedMessage, string $rawMessage): bool
    {
        if ($this->isSmallTalk($normalizedMessage, $rawMessage)) {
            return false;
        }

        $normalized = Str::of($normalizedMessage)->lower()->squish()->value();
        $raw = Str::of($rawMessage)->lower()->squish()->value();

        if ($normalized === '') {
            return false;
        }

        if (Str::length($normalized) < 10) {
            return false;
        }

        $questionHints = [
            '?',
            'apa',
            'bagaimana',
            'mengapa',
            'kenapa',
            'dimana',
            'di mana',
            'kapan',
            'berapa',
            'siapa',
            'bisakah',
            'bolehkah',
            'langkah',
            'caranya',
            'prosedur',
            'syarat',
            'dokumen',
            'surat',
            'izin',
            'ijin',
            'domisili',
            'panduan',
            'aturan',
            'petunjuk',
            'proses',
            'berkas',
        ];

        $hasQuestionHint = false;

        foreach ($questionHints as $hint) {
            if ($hint === '?' && Str::contains($raw, '?')) {
                $hasQuestionHint = true;
                break;
            }

            if ($hint !== '?' && (Str::contains($normalized, $hint) || Str::contains($raw, $hint))) {
                $hasQuestionHint = true;
                break;
            }
        }

        if (!$hasQuestionHint) {
            return false;
        }

        if ($this->contains($normalized, ['halo', 'hai', 'hey']) && Str::length($normalized) <= 16) {
            return false;
        }

        if ($this->lastIntent === 'knowledge_base') {
            $acknowledgements = ['makasih', 'terima kasih', 'thanks', 'sip', 'ok', 'okay', 'baik', 'siap'];
            foreach ($acknowledgements as $phrase) {
                if (Str::contains($normalized, $phrase) || Str::contains($raw, $phrase)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function answerFromKnowledgeBase(string $message): ?array
    {
        $cacheKey = 'dummy_client_rag:' . md5($message);
        $this->registerIntentReplay('knowledge_base', function () use ($message) {
            return $this->answerFromKnowledgeBase($message);
        }, ['intent_label' => $this->intentDescriptions['knowledge_base']['label_id'] ?? 'Knowledge base']);
        if ($override = $this->factOverrideForKnowledgeQuestion($message)) {
            return $override;
        }
        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached) && isset($cached['message'])) {
                $this->lastIntent = 'knowledge_base';
                $this->lastData = $cached;
                $this->lastTopic = 'knowledge_base';
                $answerText = (string) ($cached['answer'] ?? ($cached['message'] ?? ''));
                $question = (string) ($cached['question'] ?? $message);
                $this->rememberKnowledgeContext(
                    $cached['sources'] ?? [],
                    $answerText,
                    $question,
                    isset($cached['confidence']) ? (float) $cached['confidence'] : null
                );

                if (isset($cached['answer'], $cached['sources'])) {
                    if ($meta = $this->registerKnowledgeFeedback($message, (string) ($cached['answer'] ?? ''), $cached['sources'] ?? [], $cached['confidence'] ?? null)) {
                        $cached['meta']['knowledge_feedback'] = $meta;
                    }
                }

                return $cached;
            }

            $this->lastIntent = 'knowledge_base';
            $this->lastData = ['message' => (string) $cached, 'sources' => []];
            $this->lastTopic = 'knowledge_base';
            $fallbackAnswer = (string) $cached;
            $this->rememberKnowledgeContext([], $fallbackAnswer, $message, 0.65);

            return [
                'message' => $fallbackAnswer,
                'style' => [
                    'tone' => 'informative',
                    'confidence' => 0.65,
                    'followups' => $this->followUpsForIntent('knowledge_base'),
                ],
            ];
        }
        $threshold = $this->ragThreshold->getThreshold($this->stateUserId, $this->threadId);
        $result = app(RAGService::class)->search($message);
        $this->lastIntent = 'knowledge_base';
        $this->lastTopic = 'knowledge_base';

        if (!($result['success'] ?? false)) {
            if (($result['reason'] ?? '') === 'low_confidence') {
                $this->ragThreshold->recordFailure($this->stateUserId, $this->threadId, (float) ($result['confidence'] ?? 0.0));
                $titles = $result['suggested_titles'] ?? [];
                $clarification = $this->text(
                    $this->buildKnowledgeClarification($titles),
                    $this->buildKnowledgeClarification($titles, true)
                );
                $this->lastData = [
                    'reason' => 'low_confidence',
                    'hints' => $titles,
                ];
                $this->guardrail('rag_low_confidence', [
                    'confidence' => $result['confidence'] ?? null,
                    'question' => $message,
                ]);

                return [
                    'message' => $clarification,
                    'style' => [
                        'tone' => 'cautious',
                        'confidence' => 0.35,
                        'followups' => $this->followUpsForIntent('knowledge_base'),
                    ],
                ];
            }

            $this->ragThreshold->recordFailure($this->stateUserId, $this->threadId, (float) ($result['confidence'] ?? 0.0));
            return $this->generateKnowledgeFallback($message);
        }

        $answer = trim($result['answer'] ?? '');
        if ($answer === '') {
            $this->ragThreshold->recordFailure($this->stateUserId, $this->threadId, (float) ($result['confidence'] ?? 0.0));
            return $this->generateKnowledgeFallback($message);
        }

        $sources = $result['sources'] ?? [];
        $sourceBlock = $this->formatKnowledgeSources($sources);
        $confidenceLine = isset($result['confidence'])
            ? ($this->isEnglish()
                ? 'Confidence ~' . (int) round($result['confidence'] * 100) . '%'
                : 'Keyakinan ~' . (int) round($result['confidence'] * 100) . '%')
            : '';

        $finalAnswer = $sourceBlock !== '' ? "{$answer}\n\n{$sourceBlock}" : $answer;
        if ($confidenceLine !== '') {
            $finalAnswer .= "\n\n{$confidenceLine}";
        }

        $payload = [
            'message' => $finalAnswer,
            'answer' => $answer,
            'sources' => $sources,
            'source' => $result['source'] ?? $sourceBlock,
            'confidence' => $result['confidence'] ?? null,
            'question' => $message,
            'style' => [
                'tone' => ($result['confidence'] ?? 0.7) < 0.65 ? 'cautious' : 'informative',
                'confidence' => (float) ($result['confidence'] ?? 0.7),
                'followups' => $this->followUpsForIntent('knowledge_base'),
            ],
        ];

        if ($feedbackMeta = $this->registerKnowledgeFeedback($message, $answer, $sources, $payload['confidence'] ?? null)) {
            $payload['meta']['knowledge_feedback'] = $feedbackMeta;
        }

        $cachePayload = $payload;
        unset($cachePayload['meta']);
        Cache::put($cacheKey, $cachePayload, now()->addMinutes(5));

        $this->lastData = $payload;
        $this->rememberKnowledgeContext(
            array_slice($sources, 0, self::MAX_KB_SOURCES),
            $answer,
            $message,
            isset($result['confidence']) ? (float) $result['confidence'] : null
        );

        if (($payload['confidence'] ?? 1.0) < $threshold) {
            $this->ragThreshold->recordFailure($this->stateUserId, $this->threadId, $payload['confidence'] ?? 0.0);
            $this->guardrail('rag_low_confidence', [
                'confidence' => $payload['confidence'],
                'question' => $message,
                'sources' => $sources,
            ]);

            return [
                'message' => $this->text(
                    'Aku belum cukup yakin dengan referensi yang ada. Bisa sebut judul atau detail dokumen supaya aku cek ulang?',
                    'I am not confident enough with the available references. Could you mention the exact title or detail so I can re-check?'
                ),
                'style' => [
                    'tone' => 'cautious',
                    'confidence' => $payload['confidence'] ?? 0.3,
                    'followups' => $this->followUpsForIntent('knowledge_base'),
                ],
            ];
        }

        $this->ragThreshold->recordSuccess($this->stateUserId, $this->threadId, $payload['confidence'] ?? 0.0);

        return $payload;
    }

    private function generateKnowledgeFallback(string $message): ?array
    {
        $this->pendingLlmSnapshot = null;

        $generated = $this->generativeEngine->generate($message, [
            'language' => $this->language,
            'intent' => 'knowledge_base',
            'data' => [
                'last_intent' => $this->lastIntent,
                'last_topic' => $this->lastTopic,
                'state' => $this->lastData,
            ],
        ]);

        if ($generated === null) {
            return null;
        }

        $this->queueFallbackSnapshot($message, $generated);

        return [
            'message' => $generated['content'],
            'style' => [
                'tone' => 'empathetic',
                'confidence' => $generated['confidence'] ?? 0.55,
                'followups' => $this->followUpsForIntent('knowledge_base'),
            ],
        ];
    }

    private function queueFallbackSnapshot(string $question, array $generated): void
    {
        $this->pendingLlmSnapshot = [
            'question' => $question,
            'answer' => (string) ($generated['content'] ?? ''),
            'confidence' => $generated['confidence'] ?? null,
            'provider' => method_exists($this->generativeEngine, 'provider') ? $this->generativeEngine->provider() : 'unknown',
            'context' => [
                'language' => $this->language,
                'last_intent' => $this->lastIntent,
                'last_topic' => $this->lastTopic,
                'kb_sources' => array_values(array_slice($this->kbSources, 0, self::MAX_KB_SOURCES)),
                'state' => $this->lastData,
            ],
        ];
    }

    private function formatKnowledgeSources(array $sources): string
    {
        if ($sources === []) {
            return $this->isEnglish()
                ? 'Sources: internal RT knowledge base.'
                : 'Sumber: basis pengetahuan internal RT.';
        }

        $intro = $this->isEnglish() ? 'Sources:' : 'Sumber:';
        $lines = [];

        foreach (array_slice($sources, 0, self::MAX_KB_SOURCES) as $source) {
            $title = $source['title'] ?? 'Dokumen';
            $snippet = $source['snippet'] ?? '';
            $score = isset($source['score']) ? (float) $source['score'] : null;
            $scoreLabel = '';
            if ($score !== null && $score > 0) {
                $percent = $score > 1 ? (int) round(min($score, 100)) : (int) round($score * 100);
                $scoreLabel = ' (~' . max(0, min(100, $percent)) . '%)';
            }
            $lines[] = '- ' . $title . $scoreLabel . ($snippet !== '' ? ' - ' . $snippet : '');
        }

        return $intro . "\n" . implode("\n", $lines);
    }

    private function buildKnowledgeClarification(array $titles, bool $english = false): string
    {
        $limited = array_slice($titles, 0, self::MAX_KB_SOURCES);

        if ($limited !== []) {
            $header = $english
                ? 'I found a few related topics:'
                : 'Aku menemukan beberapa topik terkait:';
            $bullets = implode("\n", array_map(static fn($title) => '- ' . $title, $limited));
            $prompt = $english
                ? 'Which one do you mean so I can be precise?'
                : 'Yang mana yang kamu maksud supaya jawabannya tepat?';

            return "{$header}\n{$bullets}\n\n{$prompt}";
        }

        return $english
            ? 'I need a bit more context. Could you mention the exact document or procedure you need?'
            : 'Aku butuh konteks tambahan. Sebutkan dokumen atau prosedur yang kamu cari ya.';
    }

    private function registerKnowledgeFeedback(string $question, string $answer, array $sources, ?float $confidence): ?array
    {
        try {
            $token = (string) Str::uuid();

            AssistantKnowledgeFeedback::create([
                'user_id' => $this->stateUserId,
                'token' => $token,
                'question' => $question,
                'answer_excerpt' => Str::limit(strip_tags($answer), 400),
                'sources' => array_values(array_slice($sources, 0, self::MAX_KB_SOURCES)),
                'confidence' => $confidence,
            ]);

            $this->pendingKnowledgeFeedbackToken = $token;

            return [
                'token' => $token,
                'question' => Str::limit($question, 160),
                'confidence' => $confidence,
                'sources' => array_values(array_slice($sources, 0, 2)),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    public function finalizeKnowledgeFeedback(?int $interactionId): void
    {
        if ($this->pendingKnowledgeFeedbackToken === null) {
            return;
        }

        try {
            $update = [];

            if ($interactionId !== null) {
                $update['assistant_interaction_log_id'] = $interactionId;
            }

            if ($this->lastInteractionSampleId !== null) {
                $update['assistant_interaction_id'] = $this->lastInteractionSampleId;
            }

            if ($update !== []) {
                AssistantKnowledgeFeedback::where('token', $this->pendingKnowledgeFeedbackToken)
                    ->update($update);
            }
        } finally {
            $this->pendingKnowledgeFeedbackToken = null;
        }
    }

    public function finalizeLlmSnapshot(?int $interactionId): void
    {
        if ($this->pendingLlmSnapshot === null) {
            return;
        }

        $threadId = $this->threadId ?? $this->resolveThreadId($this->stateUserId);
        $payload = [
            'user_id' => $this->stateUserId,
            'thread_id' => $threadId,
            'intent' => $this->lastIntent ?? ($this->pendingLlmSnapshot['context']['last_intent'] ?? null),
            'confidence' => $this->pendingLlmSnapshot['confidence'] ?? null,
            'provider' => $this->pendingLlmSnapshot['provider'] ?? (method_exists($this->generativeEngine, 'provider') ? $this->generativeEngine->provider() : 'unknown'),
            'responded_via' => 'dummy_knowledge_fallback',
            'is_fallback' => true,
            'content' => $this->pendingLlmSnapshot['answer'] ?? '',
            'rag_sources' => $this->pendingLlmSnapshot['context']['kb_sources'] ?? [],
            'tool_calls' => [],
            'assistant_interaction_id' => $this->lastInteractionSampleId,
            'metadata' => [
                'reason' => 'knowledge_fallback',
                'question' => $this->pendingLlmSnapshot['question'] ?? null,
                'language' => $this->pendingLlmSnapshot['context']['language'] ?? $this->language,
                'last_topic' => $this->pendingLlmSnapshot['context']['last_topic'] ?? $this->lastTopic,
                'state' => $this->pendingLlmSnapshot['context']['state'] ?? $this->lastData,
            ],
        ];

        if ($interactionId !== null) {
            $payload['assistant_interaction_log_id'] = $interactionId;
        }

        try {
            app(\App\Services\Assistant\Support\LlmSnapshotManager::class)->record($payload);
        } finally {
            $this->pendingLlmSnapshot = null;
        }
    }
    private function randomChoice(array $options): string
    {
        return $options[array_rand($options)];
    }
    private function formatCurrency(int $amount): string
    {
        if ($this->isEnglish()) {
            return 'Rp ' . number_format($amount, 0, '.', ',');
        }

        return 'Rp' . number_format($amount, 0, ',', '.');
    }
    private function safeRoute(string $name): ?string
    {
        return app('router')->has($name) ? route($name) : null;
    }

    private function requireLoginResponse(string $idMessage, string $enMessage, ?string $routeName = 'login'): array
    {
        $link = $routeName ? $this->safeRoute($routeName) : null;

        if ($link) {
            $idMessage .= "\n\nMasuk melalui tautan ini: {$link}";
            $enMessage .= "\n\nYou can log in here: {$link}";
        } else {
            $idMessage .= "\n\nMasuk lewat menu **Beranda** lalu pilih Login ya.";
            $enMessage .= "\n\nOpen the main menu and choose Login.";
        }

        return $this->respond($this->text($idMessage, $enMessage));
    }

    private function getAgendaLocation(): array
    {
        $this->lastIntent = 'agenda';
        $this->lastTopic = 'agenda';
        $this->registerIntentReplay('agenda', function () {
            return $this->getAgendaLocation();
        }, ['intent_label' => $this->intentDescriptions['agenda']['label_id'] ?? 'agenda', 'mode' => 'location']);

        if (empty($this->lastData)) {
            $eventCollection = Event::where('is_public', true)
                ->where('start_at', '>=', now())
                ->orderBy('start_at')
                ->limit(3)
                ->get();

            if ($eventCollection->isEmpty()) {
                $this->lastData = [];
                return $this->respond($this->text(
                    'Belum ada agenda yang dijadwalkan.',
                    'No agenda is scheduled at the moment.'
                ));
            }

            $this->lastData = $eventCollection->toArray();
            $events = $eventCollection;
        } else {
            $events = collect($this->lastData);
        }

        if ($events->isEmpty()) {
            $this->lastData = [];
            return $this->respond($this->text(
                'Belum ada agenda yang dijadwalkan.',
                'No agenda is scheduled at the moment.'
            ));
        }

        $items = $events->take(3)->map(function ($event) {
            $title = $event['title'] ?? (is_object($event) ? ($event->title ?? 'Agenda') : 'Agenda');
            $rawLocation = $event['location'] ?? (is_object($event) ? ($event->location ?? null) : null);
            $location = $rawLocation ?: $this->text('Lokasi belum ditentukan', 'Location to be confirmed');

            return '- ' . $title . ' @ ' . $location;
        })->join("\n");

        return $this->respond($this->text(
            "Lokasi agenda:\n\n{$items}",
            "Agenda locations:\n\n{$items}"
        ));
    }

    private function getAgendaTime(): array
    {
        $this->lastIntent = 'agenda';
        $this->lastTopic = 'agenda';
        $this->registerIntentReplay('agenda', function () {
            return $this->getAgendaTime();
        }, ['intent_label' => $this->intentDescriptions['agenda']['label_id'] ?? 'agenda', 'mode' => 'time']);

        if (empty($this->lastData)) {
            $eventCollection = Event::where('is_public', true)
                ->where('start_at', '>=', now())
                ->orderBy('start_at')
                ->limit(3)
                ->get();

            if ($eventCollection->isEmpty()) {
                $this->lastData = [];
                return $this->respond($this->text(
                    'Belum ada agenda yang dijadwalkan.',
                    'No agenda is scheduled at the moment.'
                ));
            }

            $this->lastData = $eventCollection->toArray();
            $events = $eventCollection;
        } else {
            $events = collect($this->lastData);
        }

        if ($events->isEmpty()) {
            $this->lastData = [];
            return $this->respond($this->text(
                'Belum ada agenda yang dijadwalkan.',
                'No agenda is scheduled at the moment.'
            ));
        }

        $items = $events->take(3)->map(function ($event) {
            $title = $event['title'] ?? (is_object($event) ? ($event->title ?? 'Agenda') : 'Agenda');
            $startAt = $event['start_at'] ?? (is_object($event) ? ($event->start_at ?? null) : null);
            $label = $startAt
                ? Carbon::parse($startAt)->translatedFormat('d M Y, H:i')
                : $this->text('Waktu belum ditentukan', 'Time to be confirmed');

            return '- ' . $title . ' @ ' . $label;
        })->join("\n");

        return $this->respond($this->text(
            "Jadwal agenda:\n\n{$items}",
            "Agenda schedule:\n\n{$items}"
        ));
    }


    private function getWargaBaru(): array
    {
        $this->lastIntent = 'residents_new';
        $this->lastTopic = 'residents_new';
        $this->registerIntentReplay('residents_new', function () {
            return $this->getWargaBaru();
        }, ['intent_label' => $this->intentDescriptions['residents_new']['label_id'] ?? 'residents']);
        $newResidents = User::where('role', 'warga')
            ->where('created_at', '>=', Carbon::now($this->currentTimezone())->subMonths(3))
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get($this->userAddressSelectColumns(['name', 'created_at']));

        $newResidents = $this->applyFactOverridesToResidents($newResidents);

        if ($newResidents->isEmpty()) {
            $this->lastData = [];
            return $this->respondWithTemplate('residents_new', 'slot_filled', 'Belum ada warga baru yang terdaftar dalam 3 bulan terakhir.', [
                'tone' => 'friendly',
                'confidence' => 0.7,
                'followups' => $this->followUpsForIntent('residents_new', [
                    'state' => 'empty',
                ]),
            ]);
        }

        $this->lastData = $newResidents->toArray();

        $items = $newResidents->map(function (User $user) {
            $date = Carbon::parse($user->created_at)->translatedFormat('d M Y');
            $alamat = $this->userAddressValue($user, 'Alamat belum terdata', 'Address missing');
            return chr(7) . " {$user->name} (Daftar: {$date})
 ?? {$alamat}";
        })->join("

");

        $message = "Warga baru (3 bulan terakhir):

{$items}

Selamat datang di RT kita! ??";
        $safeMessage = $this->text(
            'Ada beberapa warga baru. Buka menu **Direktori > Warga Baru** untuk detail lengkapnya ya.',
            'There are some new residents. Open **Directory > New Residents** for the full detail.'
        );

        $numericRules = [
            RuleLibrary::nonNegative('residents_new_count', $newResidents->count()),
        ];

        $dateRules = [];
        $joinedDates = $newResidents
            ->map(fn(User $user) => $this->asCarbon($user->created_at))
            ->filter()
            ->values()
            ->all();

        if (count($joinedDates) >= 2) {
            $dateRules[] = RuleLibrary::dateOrder(
                'residents_new_joined',
                $joinedDates,
                'desc',
                $this->currentTimezone()
            );
        }

        foreach ($joinedDates as $index => $carbon) {
            $dateRules[] = [
                'label' => 'residents_new_joined_' . $index,
                'value' => $carbon,
                'timezone' => $this->currentTimezone(),
            ];
        }

        $clarifications = [
            'numeric_below_min' => [
                'id' => 'Data warga barunya terasa aneh. Mau tentukan rentang tanggal lain?',
                'en' => 'That new resident data feels off. Should I focus on another time window?',
            ],
            'date_order_invalid' => [
                'id' => 'Urutan tanggal daftar kurang pas. Sebut entry mana yang mau dicek?',
                'en' => 'The enrollment dates look off. Which entry should I verify?',
            ],
            'default' => [
                'id' => 'Beri tahu periode atau nama spesifik supaya daftar warga barunya akurat.',
                'en' => 'Share the specific period or names so the list stays accurate.',
            ],
        ];

        $finalMessage = $this->reasoning()->run(function () use ($message, $safeMessage, $numericRules, $dateRules, $clarifications) {
            return ReasoningDraft::make(
                intent: 'residents_new',
                message: $message,
                numerics: $numericRules,
                dates: $dateRules,
                clarifications: $clarifications,
                repairCallback: function () use ($safeMessage, $clarifications) {
                    return ReasoningDraft::make(
                        intent: 'residents_new',
                        message: $safeMessage,
                        numerics: [],
                        dates: [],
                        clarifications: $clarifications
                    );
                }
            );
        }, $this->language);

        $style = [
            'tone' => 'celebrate',
            'confidence' => 0.8,
            'followups' => $this->followUpsForIntent('residents_new', [
                'state' => 'summary',
                'new_resident_count' => $newResidents->count(),
            ]),
        ];

        return $this->respondWithTemplate('residents_new', 'slot_filled', $finalMessage, $style);
    }
    public function supportsStreaming(): bool
    {
        return false;
    }
    public function embed(string $text): ?array
    {
        return null;
    }

    private function handleChitChatKind(string $normalizedMessage, string $rawMessage, ?int $userId): ?array
    {
        $kind = $this->chitChatClassifier->kind($normalizedMessage);

        if ($kind === null) {
            return null;
        }

        if ($kind !== 'ooc') {
            $topicKeywords = [
                'tagihan',
                'iuran',
                'agenda',
                'bayar',
                'pembayaran',
                'riwayat',
                'laporan',
                'rekap',
                'warga',
                'resident',
                'kontak',
                'surat',
                'prosedur',
                'agenda',
                'event',
            ];

            if (Str::length($normalizedMessage) > 64 || $this->contains($normalizedMessage, $topicKeywords)) {
                return null;
            }
        }

        $this->smalltalkKind = $kind;

        return match ($kind) {
            'greeting' => $this->respondGreetingSmallTalk($userId),
            'thanks' => $this->respondThanksSmallTalk(),
            'joke' => $this->respondJokeSmallTalk(),
            'ooc' => $this->respondOocSmallTalk($rawMessage),
            default => null,
        };
    }

    private function respondGreetingSmallTalk(?int $userId): array
    {
        $this->lastIntent = 'smalltalk_greeting';
        $this->lastTopic = 'smalltalk';
        $name = $this->resolveResidentFirstName($userId) ?? $this->text('Warga', 'Neighbor');
        $options = $this->isEnglish()
            ? [
                "Hi {$name}! Hai! Hope you're doing great. Need help with bills, agenda, or anything else?",
                "Hey {$name}, hai! Nice to hear from you! Just say the word if you want me to check something.",
                "All good here, {$name}. Hai! What can I help you sort out today?",
            ]
            : [
                "Hai {$name}! Semoga harimu lancar. Aku siap bantu cek tagihan, agenda, atau info lain.",
                "Hai {$name}, apa kabar? Tinggal bilang kalau ada yang mau dicek, aku bantu urus ya.",
                "Hai {$name}! Aku siap bantu kapan aja. Ada yang mau kamu bahas?",
            ];

        return $this->respond($this->randomChoice($options), [
            'tone' => 'friendly',
            'confidence' => 0.9,
            'followups' => $this->followUpsForIntent('general'),
        ]);
    }

    private function respondThanksSmallTalk(): array
    {
        $this->lastIntent = 'smalltalk_thanks';
        $this->lastTopic = 'smalltalk';
        $options = $this->isEnglish()
            ? [
                'Anytime! If you need to double-check something else, I am right here.',
                'Glad to help. Want me to look into anything else?',
                'You are welcome! Just drop another question whenever you need.',
            ]
            : [
                'Sama-sama! Kalau mau cek hal lain, tinggal sebut aja.',
                'Siap, senang bisa bantu. Ada yang mau dicek lagi?',
                'Sama-sama! Aku standby kalau kamu butuh info lain.',
            ];

        return $this->respond($this->randomChoice($options), [
            'tone' => 'friendly',
            'confidence' => 0.95,
        ]);
    }

    private function respondJokeSmallTalk(): array
    {
        $this->lastIntent = 'smalltalk_joke';
        $this->lastTopic = 'smalltalk';
        $options = $this->isEnglish()
            ? [
                'Haha noted! But let me keep the numbers accurate so the RT stays tidy. Need anything else?',
                'LOL, got it. I will keep the data serious though—RT matters deserve the facts.',
            ]
            : [
                'Haha baiklah! Tapi data RT-nya tetap kudu akurat ya. Ada lagi yang mau dibahas?',
                'Wkwk noted. Biar aku tetap cek datanya serius supaya info RT-nya rapi.',
            ];

        return $this->respond($this->randomChoice($options), [
            'tone' => 'friendly',
            'confidence' => 0.8,
        ]);
    }

    private function respondOocSmallTalk(string $rawMessage): array
    {
        $this->lastIntent = 'smalltalk_ooc';
        $this->lastTopic = 'ooc';

        return $this->respond($this->oocAdvisor->reply($rawMessage), [
            'tone' => 'empathetic',
            'confidence' => 0.55,
            'followups' => [
                $this->text('Perlu aku bantu buat checklist persiapan?', 'Need me to prepare a short checklist?'),
                $this->text('Mau info kontak darurat RT?', 'Want the RT emergency contacts?'),
            ],
        ]);
    }

    private function escalateOffTopic(string $rawMessage, string $reason = 'manual_guard'): void
    {
        $this->lastIntent = 'off_topic';
        $this->lastTopic = 'off_topic';
        $this->lastData = [];

        $this->guardrail('off_topic_redirect', [
            'reason' => $reason,
            'preview' => Str::limit($rawMessage, 80),
        ]);

        throw new OutOfContextException('Off-topic escalation: ' . Str::limit($rawMessage, 160));
    }

    private function proactiveGreeting(?int $userId): array
    {
        $this->lastIntent = 'greeting';
        $this->lastTopic = 'greeting';
        $this->lastData = [];
        $this->registerIntentReplay('greeting', function () use ($userId) {
            return $this->proactiveGreeting($userId);
        }, ['intent_label' => 'Sapaan awal']);

        if (!$userId) {
            return $this->respond($this->text(
                'Hai! Aku siap bantu kapan saja. Ada yang ingin kamu cek soal RT hari ini?',
                'Hai! 👋 How can I help you today?'
            ));
        }

        $urgentBills = Bill::where('user_id', $userId)
            ->where('status', '!=', 'paid')
            ->where('due_date', '<=', Carbon::now($this->currentTimezone())->addDays(3))
            ->count();
        $upcomingEvents = Event::where('is_public', true)
            ->whereBetween('start_at', [Carbon::now($this->currentTimezone()), Carbon::now($this->currentTimezone())->addDays(2)])
            ->count();

        $this->lastData = [
            'urgent_bills' => $urgentBills,
            'upcoming_events' => $upcomingEvents,
        ];

        $greetingId = 'Hai Warga! Semoga harimu lancar. Aku siap bantu cek info RT kapan saja. ';
        $greetingEn = 'Hai! 👋 ';

        if ($urgentBills > 0) {
            $greetingId .= "Ada {$urgentBills} tagihan yang jatuh tempo dalam 3 hari. ";
            $greetingEn .= "There are {$urgentBills} bills due within 3 days. ";
        }

        if ($upcomingEvents > 0) {
            $greetingId .= "Ada {$upcomingEvents} agenda terdekat. ";
            $greetingEn .= "There are {$upcomingEvents} upcoming events. ";
        }

        if ($urgentBills === 0 && $upcomingEvents === 0) {
            $greetingId .= 'Semua lancar! Mau cek tagihan, agenda, atau info lain?';
            $greetingEn .= 'Everything looks clear! Want me to check bills, events, or something else?';
        } else {
            $greetingId .= 'Mau cek detailnya atau bahas topik lain?';
            $greetingEn .= 'Want me to pull up the details or switch topics?';
        }

        if ($this->introduceSelfPreference) {
            $greetingId .= ' Aku Aetheria, siap bantu kapan pun.';
            $greetingEn .= ' I am Aetheria, ready whenever you need me.';
        }

        return $this->respond($this->text($greetingId, $greetingEn));
    }

    private function isSmallTalk(string $normalizedMessage, string $rawMessage): bool
    {
        $normalized = Str::of($normalizedMessage)->lower()->squish()->value();
        $raw = Str::of($rawMessage)->lower()->squish()->value();

        $phrases = [
            'apa kabar',
            'gimana kabar',
            'gimana keadaan',
            'semoga baik',
            'lagi apa',
            'lagi ngapain',
            'apakah kamu baik',
            'apakah dirimu baik',
            'how are you',
            'how are u',
            'how r you',
            'how you doing',
            "what's up",
            'whats up',
            'how is it going',
            'how is everything',
            'are you there',
            'are you around',
        ];

        foreach ($phrases as $phrase) {
            if (Str::contains($raw, $phrase) || Str::contains($normalized, $phrase)) {
                return true;
            }
        }

        return false;
    }

    private function detectNegation(string $message, string $context): bool
    {
        return $this->matches($message, ['^(bukan|salah|tidak|nggak)', '(bukan|salah).*(itu|tersebut)']);
    }

    private function handleNegation(string $message, string $context, ?int $userId): array
    {
        if ($this->contains($message, ['agenda', 'acara'])) {
            if ($this->contains($message, ['besok'])) {
                $this->rememberSlotValue('agenda', 'range', 'tomorrow');
                return $this->respondAgenda($message, $message, $userId, 'tomorrow');
            }
            return $this->respondAgenda($message, $message, $userId, 'week');
        }
        if ($this->contains($message, ['pembayaran', 'bayar'])) {
            return $this->respondPayments($userId, $message, $message);
        }
        if ($this->contains($message, ['tagihan', 'tunggakan'])) {
            return $this->respondBills($userId, $message, $message);
        }
        return $this->respond('Maaf, aku kurang paham koreksinya. Bisa dijelaskan lagi?');
    }

    private function getUrgentBills(?int $userId): array
    {
        $this->lastIntent = 'bills';
        $this->registerIntentReplay('bills', function () use ($userId) {
            return $this->getUrgentBills($userId);
        }, ['intent_label' => $this->intentDescriptions['bills']['label_id'] ?? 'tagihan', 'mode' => 'urgent']);
        if (!$userId) {
            return $this->requireLoginResponse(
                'Untuk cek tagihan urgent, kamu harus login dulu ya! 🔐',
                'To check urgent bills, please log in first. 🔐'
            );
        }
        $bills = Bill::where('user_id', $userId)
            ->where('status', '!=', 'paid')
            ->where('due_date', '<=', Carbon::now($this->currentTimezone())->addDays(7))
            ->orderBy('due_date')
            ->get();
        $bills = $this->applyFactOverridesToBills($bills);
        $this->lastData = $bills->toArray();
        if ($bills->isEmpty()) {
            $this->lastData = [];
            return $this->respond('Tidak ada tagihan urgent dalam 7 hari ke depan. Aman! 🎉');
        }
        $items = $bills->map(function (Bill $bill) {
            $due = $bill->due_date ? Carbon::parse($bill->due_date)->translatedFormat('d M Y') : '-';
            $amount = $this->formatCurrency((int) $bill->amount);
            $daysLeft = $bill->due_date ? Carbon::now($this->currentTimezone())->diffInDays($bill->due_date, false) : 0;
            $urgency = $daysLeft <= 0 ? '🔴 LEWAT' : ($daysLeft <= 3 ? '🟠 URGENT' : '🟡 SEGERA');
            return "• {$urgency} {$bill->title} — {$amount} ({$due})";
        })->join("\n");
        $total = $this->formatCurrency((int) $bills->sum('amount'));
        return $this->respond("Tagihan yang perlu segera diselesaikan:\n\n{$items}\n\nTotal: **{$total}**\n\nPrioritaskan yang paling urgent ya!", [
            'tone' => 'cautious',
            'confidence' => 0.8,
            'followups' => $this->followUpsForIntent('bills'),
        ]);
    }

    private function filterBillsByBudget(string $message, ?int $userId): array
    {
        $this->lastIntent = 'bills';
        $this->lastData = [];
        $this->registerIntentReplay('bills', function () use ($message, $userId) {
            return $this->filterBillsByBudget($message, $userId);
        }, ['intent_label' => $this->intentDescriptions['bills']['label_id'] ?? 'tagihan', 'mode' => 'budget']);
        if (!$userId) {
            return $this->respond('Untuk cek tagihan, kamu harus login dulu ya! 🔐');
        }
        $budget = $this->extractBudgetValue($message);
        if ($budget === 0) {
            return $this->getTagihanInfo($userId);
        }
        $bills = Bill::where('user_id', $userId)
            ->where('status', '!=', 'paid')
            ->where('amount', '<=', $budget)
            ->orderBy('due_date')
            ->get();
        $bills = $this->applyFactOverridesToBills($bills);
        $this->lastData = $bills->toArray();
        if ($bills->isEmpty()) {
            return $this->respond("Tidak ada tagihan yang bisa dibayar dengan budget " . $this->formatCurrency($budget) . ". Cek semua tagihan?");
        }
        $items = $bills->map(function (Bill $bill) {
            $due = $bill->due_date ? Carbon::parse($bill->due_date)->translatedFormat('d M Y') : '-';
            $amount = $this->formatCurrency((int) $bill->amount);
            return "• {$bill->title} — {$amount} ({$due})";
        })->join("\n");
        $total = $this->formatCurrency((int) $bills->sum('amount'));
        return $this->respond("Tagihan yang bisa dibayar dengan budget " . $this->formatCurrency($budget) . ":\n\n{$items}\n\nTotal: **{$total}**");
    }


    private function getImportantInfo(?int $userId): array
    {
        $this->lastIntent = 'summary';
        $this->lastData = [];
        $this->lastTopic = 'summary';
        $this->registerIntentReplay('summary', function () use ($userId) {
            return $this->getImportantInfo($userId);
        }, ['intent_label' => 'Info penting']);

        if (!$userId) {
            return $this->respond('Untuk cek info penting, kamu harus login dulu ya! ??');
        }

        $windowStart = Carbon::now($this->currentTimezone());
        $windowEnd = $windowStart->copy()->addDays(2);

        $urgentBills = Bill::where('user_id', $userId)
            ->where('status', '!=', 'paid')
            ->where('due_date', '<=', $windowStart->copy()->addDays(3))
            ->count();
        $upcomingEvents = Event::where('is_public', true)
            ->whereBetween('start_at', [$windowStart, $windowEnd])
            ->orderBy('start_at')
            ->limit(3)
            ->get();

        $this->lastData = [
            'urgent_bills' => $urgentBills,
            'events' => $upcomingEvents->toArray(),
        ];

        $lines = [
            $this->text('📌 **Info Penting:**', '📌 **Important updates:**'),
            $urgentBills > 0
                ? $this->text(
                    "⚠️ {$urgentBills} tagihan jatuh tempo dalam 3 hari",
                    "⚠️ {$urgentBills} bills are due within 3 days"
                )
                : $this->text('✅ Tidak ada tagihan urgent', '✅ No urgent bills right now'),
        ];

        if ($upcomingEvents->isNotEmpty()) {
            $lines[] = $this->text('📅 Agenda terdekat:', '📅 Upcoming agenda:');
            foreach ($upcomingEvents as $event) {
                $date = $event->start_at
                    ? Carbon::parse($event->start_at)->translatedFormat('d M, H:i')
                    : $this->text('Waktu menyusul', 'Time TBC');
                $lines[] = '• ' . $event->title . ' (' . $date . ')';
            }
        } else {
            $lines[] = $this->text('📅 Tidak ada agenda dalam 2 hari ke depan', '📅 No events in the next 2 days');
        }

        $message = implode("
", $lines);
        $safeMessage = $this->text(
            'Ada kabar terkait tagihan dan agenda. Buka menu Tagihan atau Agenda untuk detail pastinya ya.',
            'There are bill and agenda updates. Please open the Bills or Agenda menu for the exact details.'
        );

        $numericRules = [
            RuleLibrary::nonNegative('summary_urgent_bills', $urgentBills),
            RuleLibrary::nonNegative('summary_event_count', $upcomingEvents->count()),
        ];

        $dateRules = [];
        $eventDates = $upcomingEvents
            ->map(fn(Event $event) => $this->asCarbon($event->start_at))
            ->filter()
            ->values()
            ->all();

        if (count($eventDates) >= 2) {
            $dateRules[] = RuleLibrary::dateOrder(
                'summary_upcoming_events',
                $eventDates,
                'asc',
                $this->currentTimezone()
            );
        }

        foreach ($eventDates as $index => $carbon) {
            $dateRules[] = [
                'label' => 'summary_event_' . $index,
                'value' => $carbon,
                'timezone' => $this->currentTimezone(),
            ];
        }

        $clarifications = [
            'numeric_below_min' => [
                'id' => 'Jumlah tagihan atau agendanya terasa janggal. Detail mana yang mau kamu pastikan?',
                'en' => 'The bill/event counts feel off. Which detail should I double-check?',
            ],
            'date_order_invalid' => [
                'id' => 'Urutan agenda kurang konsisten. Sebut tanggal yang ingin kamu cek?',
                'en' => 'The agenda order looks inconsistent. Which dates should I check?',
            ],
            'negation_conflict' => [
                'id' => 'Tadi kamu menolak ringkasan ini. Mau hentikan atau cek topik lain?',
                'en' => 'You hinted you do not need this summary. Should I stop or switch topics?',
            ],
            'default' => [
                'id' => 'Kasih tahu detail mana yang mau kamu cek supaya ringkasannya akurat ya.',
                'en' => 'Let me know which detail you need so I can keep the summary accurate.',
            ],
        ];

        $negationContext = [
            'label' => 'summary',
            'user_negated' => $this->userRequestsNegation(['info', 'ringkasan', 'summary']),
            'resolved' => false,
        ];

        return $this->respond(
            $this->reasoning()->run(function () use (
                $message,
                $safeMessage,
                $numericRules,
                $dateRules,
                $clarifications,
                $negationContext
            ) {
                return ReasoningDraft::make(
                    intent: 'summary',
                    message: $message,
                    numerics: $numericRules,
                    dates: $dateRules,
                    negation: $negationContext,
                    clarifications: $clarifications,
                    repairCallback: function () use ($safeMessage, $clarifications, $negationContext) {
                        return ReasoningDraft::make(
                            intent: 'summary',
                            message: $safeMessage,
                            numerics: [],
                            dates: [],
                            negation: $negationContext,
                            clarifications: $clarifications
                        );
                    }
                );
            }, $this->language),
            [
                'tone' => 'summary',
                'confidence' => 0.85,
                'followups' => $this->followUpsForIntent('summary'),
            ]
        );
    }
    private function resolveResidentFirstName(?int $userId): ?string
    {
        $user = Auth::user();

        if ((!$user || ($userId !== null && $user->id !== $userId)) && $userId !== null) {
            $user = User::find($userId);
        }

        if (!$user) {
            return null;
        }

        $name = trim((string) $user->name);

        if ($name === '') {
            return null;
        }

        $normalized = Str::of($name)->squish()->value();
        $parts = explode(' ', $normalized);

        return $parts[0] ?? $normalized;
    }

    private function respondWithTemplate(string $intent, string $scenario, string $message, array $style = [], array $tokens = []): array
    {
        $style['template'] = [
            'intent' => $intent,
            'scenario' => $scenario,
            'tokens' => $tokens,
        ];

        if (!isset($style['followups'])) {
            $style['followups'] = $this->followUpsForIntent($intent);
        }

        if (!array_key_exists('ack', $style)) {
            $style['ack'] = $scenario === 'slot_missing' ? 'noted' : 'ready';
        }

        return $this->respond($message, $style);
    }

    private function applyIntentTemplate(string $intent, string $scenario, string $message, array $tokens = []): string
    {
        $template = $this->intentResponseTemplates[$intent][$scenario]
            ?? $this->intentResponseTemplates['default'][$scenario]
            ?? null;

        if ($template === null) {
            return $message;
        }

        $preface = $this->isEnglish()
            ? ($template['en'] ?? $template['id'] ?? '')
            : ($template['id'] ?? $template['en'] ?? '');

        if ($preface === '') {
            return $message;
        }

        foreach ($tokens as $key => $value) {
            $preface = str_replace('{' . $key . '}', (string) $value, $preface);
        }

        $preface = trim($preface);

        if ($preface === '') {
            return $message;
        }

        if ($message === '') {
            return $preface;
        }

        return $preface . "\n\n" . $message;
    }

    private function resetEvaluationEvents(): void
    {
        $this->evaluationEvents = [
            'guardrails' => [],
            'tools' => [],
        ];
        $this->lastResponseContent = '';
        $this->lastResponseConfidence = null;
    }

    private function pushEvaluationEvent(string $type, array $payload): void
    {
        if (!isset($this->evaluationEvents[$type])) {
            $this->evaluationEvents[$type] = [];
        }

        $this->evaluationEvents[$type][] = $payload + ['timestamp' => microtime(true)];
    }

    private function guardrail(string $name, array $context = []): void
    {
        $payload = $context + [
            'intent' => $this->lastIntent,
            'thread_id' => $this->threadId,
            'user_id' => $this->stateUserId,
        ];

        $this->metrics->recordGuardrail($name, $payload);
        $this->pushEvaluationEvent('guardrails', [
            'name' => $name,
            'context' => $payload,
        ]);

        $this->recentGuardrails[] = $name;
        $this->recentGuardrails = array_slice(array_values(array_unique($this->recentGuardrails)), -5);
    }

    private function recordToolCall(string $name, bool $success, array $context = []): void
    {
        $this->pushEvaluationEvent('tools', [
            'name' => $name,
            'success' => $success,
            'context' => $context,
        ]);
    }

    public function evaluationSnapshot(bool $flush = true): array
    {
        $snapshot = [
            'intent' => $this->lastIntent,
            'topic' => $this->lastTopic,
            'slots' => $this->lastIntent !== null ? ($this->slotState[$this->lastIntent] ?? []) : [],
            'all_slots' => $this->slotState,
            'kb_sources' => $this->kbSources,
            'kb_confidence' => $this->lastKnowledgeConfidence,
            'guardrails' => $this->evaluationEvents['guardrails'] ?? [],
            'tool_calls' => $this->evaluationEvents['tools'] ?? [],
            'response' => $this->lastResponseContent,
            'confidence' => $this->lastResponseConfidence,
            'clarification_turns' => $this->clarificationTurns,
            'pending_confirmation' => $this->pendingConfirmation,
            'question' => $this->lastKnowledgeQuestion,
            'data' => $this->lastData,
        ];

        if ($flush) {
            $this->resetEvaluationEvents();
        }

        return $snapshot;
    }

    private function userAddressColumn(): ?string
    {
        if ($this->userAddressColumn !== null) {
            return $this->userAddressColumn !== '' ? $this->userAddressColumn : null;
        }

        $table = (new User())->getTable();

        if (Schema::hasColumn($table, 'alamat')) {
            $this->userAddressColumn = 'alamat';
        } elseif (Schema::hasColumn($table, 'alamat_encrypted')) {
            $this->userAddressColumn = 'alamat_encrypted';
        } else {
            $this->userAddressColumn = '';
        }

        return $this->userAddressColumn !== '' ? $this->userAddressColumn : null;
    }

    private function userAddressSelectColumns(array $columns = []): array
    {
        if (!in_array('id', $columns, true)) {
            $columns[] = 'id';
        }

        $column = $this->userAddressColumn();

        if ($column !== null && !in_array($column, $columns, true)) {
            $columns[] = $column;
        }

        return $columns;
    }

    private function userAddressValue(User $user, ?string $fallbackId = null, ?string $fallbackEn = null): string
    {
        $fallbackId ??= 'Alamat belum tercatat';
        $fallbackEn ??= 'Address not recorded';
        $column = $this->userAddressColumn();
        if ($column === null) {
            return $this->text($fallbackId, $fallbackEn);
        }

        $value = $user->alamat ?? null;

        if ($value === null || $value === '') {
            $value = $user->{$column} ?? null;
        }

        if (($value === null || $value === '') && $column === 'alamat_encrypted' && method_exists($user, 'decryptAttribute')) {
            $value = $user->decryptAttribute('alamat_encrypted');
        }

        $value = is_string($value) ? trim($value) : '';

        return $value !== '' ? $value : $this->text($fallbackId, $fallbackEn);
    }

    private function respond(string $message, array $style = []): array
    {
        $message = trim($message);

        if ($message === '') {
            $this->lastResponseContent = '';
            $this->lastResponseConfidence = null;

            return $this->finalizeResponse(['content' => '']);
        }

        if (($style['tone'] ?? null) === 'cautious') {
            $turns = $this->incrementClarificationTurns();
            if ($turns > 2) {
                $message = $this->text(
                    'Sepertinya aku butuh detail tambahan supaya jawabannya tepat. Boleh jelaskan maksudmu atau kita bahas topik lain dulu?',
                    'I may need more detail to stay accurate. Feel free to clarify or we can switch topics for now.'
                );
                $style['tone'] = 'friendly';
                $style['confidence'] = min($style['confidence'] ?? 0.4, 0.4);
                $style['followups'] = $this->followUpsForIntent('general');
                $this->guardrail('clarification_rate_limit', [
                    'intent' => $this->lastIntent,
                    'turns' => $turns,
                ]);
                $this->resetClarificationTurns();
            }
        } else {
            $this->resetClarificationTurns();
        }

        if (($style['confidence'] ?? 1) < 0.5) {
            $message = $this->appendClarificationQuestion($message, $style['clarification_prompt'] ?? null);

            if (empty($style['followups'] ?? [])) {
                $style['followups'] = $this->followUpsForIntent('general');
            }
        }

        unset($style['clarification_prompt']);
        $message = $this->applyStyleOverrides($message, $style);
        $message = $this->injectFewshotClosing($message);

        $templateMeta = $style['template'] ?? null;
        unset($style['template']);

        if (is_array($templateMeta) && isset($templateMeta['intent'])) {
            $scenario = $templateMeta['scenario'] ?? 'slot_filled';
            $tokens = $templateMeta['tokens'] ?? [];
            $message = $this->applyIntentTemplate($templateMeta['intent'], $scenario, $message, $tokens);
        }

        $message = $this->enforceStyleConsistency($message, $style);
        $shouldRewrite = $style['rewrite_opening'] ?? true;
        unset($style['rewrite_opening']);
        if ($shouldRewrite) {
            $message = $this->responseRewriter->rewrite($message, [
                'language' => $this->language,
                'intent' => $this->lastIntent,
                'variant' => $this->determineVariantKey($style),
                'recent_openers' => $this->recentOpeners,
            ]);
        }

        $style['language'] = $this->language;
        $styled = $this->responseStyle->format($message, $style);
        $finalContent = $styled;

        if ($this->isEnglish()) {
            if (LanguageDetector::detect($styled) === 'en') {
                $finalContent = $styled;
            } else {
                $translated = $this->translator()->translate($styled, 'en', 'id');

                if (is_string($translated) && $translated !== '') {
                    $finalContent = $translated;
                }
            }
        } elseif ($this->isJavanese()) {
            $translated = $this->translator()->translate($styled, 'jv', 'id');

            if (is_string($translated) && $translated !== '') {
                $finalContent = $translated;
            }
        } elseif ($this->isSundanese()) {
            $translated = $this->translator()->translate($styled, 'su', 'id');

            if (is_string($translated) && $translated !== '') {
                $finalContent = $translated;
            }
        }

        $this->metrics->recordResponse([
            'intent' => $this->lastIntent,
            'language' => $this->language,
            'tone' => $style['tone'] ?? null,
            'confidence' => $style['confidence'] ?? null,
            'message' => $finalContent,
            'followups' => $style['followups'] ?? [],
        ]);
        $this->lastResponseContent = $finalContent;
        $this->lastResponseConfidence = isset($style['confidence']) ? (float) $style['confidence'] : null;
        $this->recordOpeningHistory($finalContent);

        $response = $this->finalizeResponse(['content' => $finalContent]);
        $this->recordInteractionSample($finalContent, $style);

        if ($this->lastInteractionSampleId !== null) {
            $meta = $response['meta'] ?? [];
            $meta['interaction_id'] = $this->lastInteractionSampleId;

            if (!isset($meta['intent']) && $this->lastIntent !== null) {
                $meta['intent'] = $this->lastIntent;
            }

            if (!isset($meta['confidence']) && $this->lastResponseConfidence !== null) {
                $meta['confidence'] = $this->lastResponseConfidence;
            }

            $response['meta'] = $meta;
        }

        return $response;
    }

    private function enforceStyleConsistency(string $message, array &$style): string
    {
        $message = $this->squashDuplicateApologies($message);

        $confidence = isset($style['confidence']) ? (float) $style['confidence'] : null;
        $tone = $style['tone'] ?? null;

        if ($tone === 'celebrate' && $confidence !== null && $confidence < 0.65) {
            $style['tone'] = 'friendly';
        }

        if ($tone === 'urgent' && $confidence !== null && $confidence < 0.5) {
            $style['tone'] = 'cautious';
        }

        if ($tone === 'empathetic' && $confidence !== null && $confidence > 0.9) {
            $style['tone'] = 'friendly';
        }

        return trim(preg_replace("/\n{3,}/", "\n\n", $message) ?? $message);
    }

    private function recordInteractionSample(string $responseContent, array $style): void
    {
        try {
            $interactionId = app(InteractionLearner::class)->recordInteraction([
                'user_id' => $this->stateUserId,
                'thread_id' => $this->threadId,
                'message' => $this->currentRawMessage,
                'intent' => $this->lastIntent,
                'response' => $responseContent,
                'confidence' => $style['confidence'] ?? $this->lastResponseConfidence,
                'method' => 'dummy',
            ]);

            if ($interactionId !== null) {
                $this->lastInteractionSampleId = $interactionId;
                $this->persistStateFragment([
                    'metadata' => [
                        'last_interaction_id' => $interactionId,
                    ],
                ]);
            }
        } catch (\Throwable) {
            // swallow
        }
    }

    private function recordInteractionFeedback(bool $wasHelpful, string $reason, ?int $interactionId = null): void
    {
        $targetId = $interactionId ?? $this->lastInteractionSampleId;
        if ($targetId === null) {
            return;
        }

        $cleanReason = Str::of($reason)->squish()->limit(480, '...')->value();

        try {
            app(InteractionLearner::class)->recordFeedback(
                $targetId,
                $wasHelpful,
                $cleanReason === '' ? null : $cleanReason
            );
        } catch (\Throwable) {
            // swallow
        }
    }

    private function applyStyleOverrides(string $message, array &$style): string
    {
        $formality = $this->memoryStyle['formality'] ?? null;

        if ($formality === 'formal') {
            $style['tone'] = $style['tone'] ?? 'informative';
            $style['ack'] = $style['ack'] ?? 'noted';
            $message = $this->stripCasualParticles($message);
            $style['emoji_policy'] = 'none';
            $style['formality'] = 'formal';
        } elseif ($formality === 'santai') {
            $style['tone'] = $style['tone'] ?? 'friendly';
            $style['ack'] = $style['ack'] ?? 'ready';
            $style['emoji_policy'] = $style['emoji_policy'] ?? 'light';
            $style['formality'] = 'santai';
        }

        if (isset($this->memoryStyle['emoji_policy']) && !isset($style['emoji_policy'])) {
            $style['emoji_policy'] = $this->memoryStyle['emoji_policy'];
        }

        if (($style['emoji_policy'] ?? null) === 'none') {
            $message = preg_replace('/[\x{1F300}-\x{1FAFF}]/u', '', $message) ?? $message;
        }

        if (array_key_exists('humor', $this->memoryStyle) && $this->memoryStyle['humor'] === false) {
            $message = preg_replace('/\b(wkwk|haha|lol|hehe)\b/i', '', $message) ?? $message;
        }

        $message = $this->scrubForbiddenPhrases($message);

        return $message;
    }

    private function scrubForbiddenPhrases(string $message): string
    {
        if ($this->forbiddenPhrases === []) {
            return $message;
        }

        $result = $message;

        foreach ($this->forbiddenPhrases as $phrase) {
            if ($phrase === '') {
                continue;
            }

            $pattern = '/' . preg_quote($phrase, '/') . '/i';
            $result = preg_replace($pattern, ' ', $result) ?? $result;
        }

        $result = preg_replace('/ {2,}/', ' ', $result) ?? $result;

        return trim($result);
    }

    private function injectFewshotClosing(string $message): string
    {
        if ($this->fewshotClosing === null || $this->fewshotClosing === '') {
            return $message;
        }

        if (Str::contains(Str::lower($message), Str::lower($this->fewshotClosing))) {
            return $message;
        }

        $separator = Str::endsWith(trim($message), ['.', '!', '?']) ? ' ' : '. ';

        return rtrim($message) . $separator . $this->fewshotClosing;
    }

    private function determineVariantKey(array $style): string
    {
        if ($this->smalltalkKind !== null) {
            return 'smalltalk_' . $this->smalltalkKind;
        }

        if (($style['tone'] ?? null) === 'cautious' || $this->lastIntent === 'correction_feedback') {
            return 'clarification';
        }

        if ($this->lastIntent === 'gratitude_reply') {
            return 'thanks';
        }

        if ($this->lastIntent === 'greeting') {
            return 'greeting';
        }

        return 'general';
    }

    private function recordOpeningHistory(string $message): void
    {
        $opening = $this->extractOpeningSentence($message);

        if ($opening === null) {
            return;
        }

        $normalized = Str::of($opening)->lower()->squish()->value();

        if ($normalized === '') {
            return;
        }

        $this->recentOpeners[] = $normalized;
        $this->recentOpeners = array_values(array_slice($this->recentOpeners, -5));
    }

    private function extractOpeningSentence(string $message): ?string
    {
        $parts = preg_split('/(?<=[.!?])\s+/u', trim($message), 2);

        if (!$parts) {
            return null;
        }

        return trim($parts[0] ?? '');
    }

    private function stripCasualParticles(string $message): string
    {
        $patterns = [
            '/\bya\b/i',
            '/\bdong\b/i',
            '/\bnih\b/i',
            '/\bkok\b/i',
            '/\bgitu\b/i',
        ];

        return preg_replace($patterns, '', $message) ?? $message;
    }

    private function squashDuplicateApologies(string $message): string
    {
        $seen = false;

        $cleaned = preg_replace_callback('/\b(maaf|sorry)\b/i', function ($match) use (&$seen) {
            if ($seen) {
                return '';
            }

            $seen = true;

            return $match[0];
        }, $message);

        if ($cleaned === null) {
            return $message;
        }

        return preg_replace('/ {2,}/', ' ', $cleaned) ?? $cleaned;
    }

    private function appendClarificationQuestion(string $message, ?string $customPrompt = null): string
    {
        $prompt = trim($customPrompt ?? (
            $this->isEnglish()
            ? 'Which detail should I double-check so we can be precise?'
            : 'Detail apa yang perlu aku cek supaya jawabannya pas?'
        ));

        $base = trim($message);

        if ($prompt === '') {
            return $base;
        }

        if (Str::contains($base, $prompt)) {
            return $base;
        }

        if ($base === '') {
            return $prompt;
        }

        return $base . "\n\n" . $prompt;
    }

    private function requiresConfirmationForTool(string $toolName): bool
    {
        return in_array($toolName, $this->riskyToolActions, true);
    }

    private function handlePendingConfirmation(string $normalizedMessage, string $rawMessage, ?int $userId): ?array
    {
        if ($this->pendingConfirmation === null) {
            return null;
        }

        if ($this->isAffirmative($normalizedMessage, $rawMessage)) {
            $pending = $this->pendingConfirmation;
            $this->pendingConfirmation = null;

            return $this->runConfirmedAction($pending, $userId);
        }

        if ($this->isNegative($normalizedMessage, $rawMessage)) {
            $this->pendingConfirmation = null;

            return $this->respond($this->text(
                'Baik, kubatalkan tindakan tersebut.',
                'Alright, I cancelled that action.'
            ), [
                'tone' => 'friendly',
                'confidence' => 0.9,
            ]);
        }

        return null;
    }

    private function runConfirmedAction(array $pending, ?int $userId): array
    {
        if (($pending['type'] ?? '') === 'tool') {
            $toolName = $pending['name'] ?? '';
            $params = $pending['parameters'] ?? [];
            $response = match ($toolName) {
                'export_financial_recap' => $this->getRekapKeuangan(
                    $userId,
                    $params['format'] ?? 'pdf',
                    false,
                    $params['period'] ?? null
                ),
                default => $this->respond($this->text(
                    'Tindakan itu belum bisa dijalankan.',
                    'That action is not available yet.'
                )),
            };

            if (isset($response['content'])) {
                $prefix = $this->text('Siap, kuproses sekarang.', 'Okay, processing it now.');
                $response['content'] = $prefix . "\n\n" . $response['content'];
            }

            return $response;
        }

        return $this->respond($this->text(
            'Konfirmasi diterima, tapi aku belum tahu cara menjalankan aksinya.',
            'Confirmation received, but I do not know how to execute that action yet.'
        ), [
            'tone' => 'cautious',
            'confidence' => 0.4,
        ]);
    }

    private function handleCorrectionMessage(string $rawMessage, string $normalizedMessage, ?int $userId): ?array
    {
        $pair = $this->extractCorrectionPair($rawMessage);

        if ($pair === null) {
            return null;
        }

        $this->recordInteractionFeedback(false, sprintf(
            'direct_correction:%s=>%s | %s',
            Str::limit($pair['wrong'] ?? '', 40),
            Str::limit($pair['right'] ?? '', 40),
            Str::limit($rawMessage, 80)
        ));

        $this->recordCorrection($pair['wrong'], $pair['right']);
        $queueStatus = $this->persistCorrectionEvent($pair, $rawMessage, $userId);
        $this->refreshCurrentMessageContext();

        $constraintNote = null;
        if ($this->intentSupportsRetryConstraints($this->lastIntent)) {
            $constraintNote = $this->applyRetryInclusion($pair['right']);
        }

        $ack = $this->text(
            "Catat ya, {$pair['wrong']} kuganti menjadi {$pair['right']}. Kubetulkan jawabannya.",
            "Noted, I'll treat {$pair['wrong']} as {$pair['right']}. Let me fix the answer."
        );

        if ($constraintNote !== null && $constraintNote['id'] !== '') {
            $ack .= ' ' . $this->text(
                "Fokus ke {$constraintNote['id']} ya.",
                "Focusing on {$constraintNote['en']} next."
            );
        }

        if ($queueStatus !== null) {
            $notice = $this->factQueueNotice($queueStatus);
            if ($notice !== null) {
                $ack .= ' ' . $notice;
            }
        }

        return $this->retryLastIntent($userId, $ack) ?? $this->respond($ack);
    }

    private function handlePendingCorrectionFeedback(string $rawMessage, string $normalizedMessage, ?int $userId): ?array
    {
        if ($this->pendingCorrectionFeedback === null) {
            return null;
        }

        if ($this->matches($normalizedMessage, ['^batal$', '^sudah$', '^sudah benar$', '^gpp$', '^ga jadi$', '^nggak jadi$'])) {
            $this->pendingCorrectionFeedback = null;
            $this->persistStateFragment(['pending_correction' => null]);

            return $this->respond($this->text(
                'Oke, kalau mau koreksi lagi tinggal kabari ya.',
                'Alright, just let me know if you want to adjust anything else.'
            ), [
                'tone' => 'friendly',
                'confidence' => 0.7,
            ]);
        }

        if ($this->shouldReleasePendingCorrection($normalizedMessage, $rawMessage)) {
            $this->pendingCorrectionFeedback = null;
            $this->persistStateFragment(['pending_correction' => null]);

            return null;
        }

        $feedback = Str::of($rawMessage)->trim()->value();

        if ($feedback === '') {
            $reminders = $this->incrementPendingCorrectionReminder();

            if ($reminders >= 2) {
                $this->pendingCorrectionFeedback = null;
                $this->persistStateFragment(['pending_correction' => null]);

                return $this->respond($this->text(
                    'Tidak apa-apa, kita lanjut bahas pertanyaan berikutnya saja ya. Kalau masih mau koreksi, tinggal kabari.',
                    'No worries, we can continue with your next question. Just let me know again if you want to correct something later.'
                ), [
                    'tone' => 'friendly',
                    'confidence' => 0.65,
                ]);
            }

            return $this->respond($this->text(
                'Boleh jelaskan detail yang perlu diperbaiki? Bisa soal fakta, gaya, bahasa, atau langkahnya.',
                'Could you share which part needs fixing - facts, tone/language, or the steps?'
            ), [
                'tone' => 'empathetic',
                'confidence' => 0.4,
            ]);
        }


        $payload = [
            'user_id' => $userId,
            'org_id' => $this->userOrgId,
            'thread_id' => $this->threadId,
            'turn_id' => $this->pendingCorrectionFeedback['turn_id'] ?? (string) Str::uuid(),
            'original_input' => $this->pendingCorrectionFeedback['original_input'] ?? $this->previousUserMessage(),
            'original_answer' => $this->pendingCorrectionFeedback['original_answer'] ?? $this->lastResponseContent,
            'user_feedback_raw' => $feedback,
        ];
        $targetInteractionId = isset($this->pendingCorrectionFeedback['interaction_id'])
            ? (int) $this->pendingCorrectionFeedback['interaction_id']
            : null;

        $eventId = CorrectionIngestor::store($payload);
        $this->lastCorrectionEventId = $eventId;
        $this->pendingCorrectionFeedback = null;
        $this->persistStateFragment(['pending_correction' => null]);
        $this->applyCorrectionMemoryAdjustments($userId);

        if ($targetInteractionId) {
            $this->recordInteractionFeedback(
                false,
                'detailed_correction:' . Str::limit($feedback, 200),
                $targetInteractionId
            );
        }

        $ack = $this->text(
            'Siap, catatannya kusimpan dan langsung kupakai di jawaban selanjutnya.',
            'Got it. I will apply that note right away for the next answers.'
        );

        return $this->retryLastIntent($userId, $ack) ?? $this->respond($ack, [
            'tone' => 'friendly',
            'confidence' => 0.75,
        ]);
    }

    private function incrementPendingCorrectionReminder(): int
    {
        $count = (int) ($this->pendingCorrectionFeedback['reminders'] ?? 0);
        $count++;
        $this->pendingCorrectionFeedback['reminders'] = $count;
        $this->persistStateFragment(['pending_correction' => $this->pendingCorrectionFeedback]);

        return $count;
    }

    private function shouldReleasePendingCorrection(string $normalizedMessage, string $rawMessage): bool
    {
        if ($this->containsCorrectionIntentHints($normalizedMessage)) {
            return false;
        }

        if ($this->matches($normalizedMessage, ['^(lanjut|ganti topik|bahas.*lain|skip).*$'])) {
            return true;
        }

        return $this->looksLikeNewTopicMessage($normalizedMessage, $rawMessage);
    }

    private function containsCorrectionIntentHints(string $normalizedMessage): bool
    {
        $hints = [
            'salah',
            'benar',
            'koreksi',
            'perbaiki',
            'benerin',
            'harus',
            'harusnya',
            'seharusnya',
            'forbidden',
            'jawaban',
            'jawabin',
        ];

        foreach ($hints as $hint) {
            if (Str::contains($normalizedMessage, $hint)) {
                return true;
            }
        }

        return false;
    }

    private function looksLikeNewTopicMessage(string $normalizedMessage, string $rawMessage): bool
    {
        if ($rawMessage !== '' && Str::contains($rawMessage, '?')) {
            return true;
        }

        $questionWords = [
            'apa',
            'berapa',
            'kapan',
            'bagaimana',
            'gimana',
            'siapa',
            'dimana',
            'kenapa',
            'tolong',
            'info',
            'detail',
        ];

        foreach ($questionWords as $word) {
            if (Str::contains($normalizedMessage, $word)) {
                return true;
            }
        }

        return false;
    }

    private function questionMatchTokens(string $text): array
    {
        $normalized = Str::lower(Str::squish($text));

        if ($normalized === '') {
            return [];
        }

        $parts = preg_split('/[^a-z0-9]+/u', $normalized) ?: [];
        $tokens = [];

        foreach ($parts as $part) {
            if ($part === '' || strlen($part) < 3) {
                continue;
            }

            $tokens[] = $part;
        }

        return array_slice(array_values(array_unique($tokens)), 0, 12);
    }

    private function questionSimilarity(string $left, string $right): float
    {
        if ($left === '' || $right === '') {
            return 0.0;
        }

        similar_text($left, $right, $percent);

        return ($percent ?? 0.0) / 100;
    }

    private function isGeneralCorrectionMessage(string $normalizedMessage, string $rawLower): bool
    {
        $phrases = [
            'kamu salah',
            'kamu menjawab salah',
            'jawabanmu salah',
            'jawabannya salah',
            'jawaban salah',
            'jawab salah',
            'itu salah',
            'itu bukan jawab',
            'bukan jawaban',
            'jangan jawab terus',
            'jangan jawab begitu',
            'jangan gitu jawabnya',
            'tolong perbaiki',
            'tolong benerin',
            'perbaiki jawaban',
            'you are wrong',
            'your answer is wrong',
            'that answer is wrong',
        ];

        foreach ($phrases as $phrase) {
            if (Str::contains($normalizedMessage, $phrase) || ($rawLower !== '' && Str::contains($rawLower, $phrase))) {
                return true;
            }
        }

        if ($this->matches($normalizedMessage, ['^salah$', '^salah itu$', '^nggak gitu$', '^tidak begitu$'])) {
            return true;
        }

        return false;
    }

    private function handleGeneralCorrectionMessage(string $normalizedMessage, string $rawMessage, ?int $userId): array
    {
        $this->guardrail('generic_correction_feedback', [
            'intent' => $this->lastIntent,
            'topic' => $this->lastTopic,
        ]);

        $this->recordInteractionFeedback(false, 'general_correction:' . Str::limit($rawMessage, 120));

        $this->resetIntentContext();
        $this->lastIntent = 'correction_feedback';
        $this->lastTopic = 'clarification';
        $this->lastData = [];

        $this->pendingCorrectionFeedback = [
            'turn_id' => (string) Str::uuid(),
            'original_input' => $this->previousUserMessage() ?? $this->currentRawMessage,
            'original_answer' => $this->lastResponseContent,
            'interaction_id' => $this->lastInteractionSampleId,
            'reminders' => 0,
        ];
        $this->conversationState['pending_correction'] = $this->pendingCorrectionFeedback;

        $ack = $this->text(
            'Maaf ya, di bagian mana aku keliru? Koreksinya soal fakta/data, gaya/bahasa, atau langkah yang kuambil?',
            'Sorry about that—could you point out which part went wrong? Is it the facts, the tone/language, or the action steps?'
        );

        $style = [
            'tone' => 'empathetic',
            'confidence' => 0.35,
            'followups' => $this->followUpsForIntent('general'),
        ];

        $replayed = $this->attemptReplayPreviousQuestion();
        if ($replayed !== null) {
            $message = $ack . "\n\n" . ($replayed['message'] ?? '');
            $style = $replayed['style'] ?? [];

            return $this->respond($message, $style);
        }

        return $this->respond($ack, $style);
    }

    private function handleDirectStyleInstruction(string $rawMessage, string $normalizedMessage, ?int $userId): ?array
    {
        if (!$this->isDirectStyleInstruction($rawMessage, $normalizedMessage)) {
            return null;
        }

        $payload = [
            'user_id' => $userId,
            'org_id' => $this->userOrgId,
            'thread_id' => $this->threadId,
            'turn_id' => (string) Str::uuid(),
            'original_input' => $this->previousUserMessage() ?? $this->currentRawMessage,
            'original_answer' => $this->lastResponseContent,
            'user_feedback_raw' => $rawMessage,
            'scope' => $userId ? 'user' : 'thread',
        ];

        try {
            $eventId = CorrectionIngestor::store($payload);
            $this->lastCorrectionEventId = $eventId;
            $this->applyCorrectionMemoryAdjustments($userId);
        } catch (\Throwable $e) {
            report($e);
        }

        $ack = $this->text(
            'Siap, catatan gayanya langsung kupakai di jawaban-jawaban berikutnya.',
            'Noted. I will follow that style from now on.'
        );

        if ($retry = $this->retryLastIntent($userId, $ack)) {
            return $retry;
        }

        return $this->respond($ack, [
            'tone' => 'friendly',
            'confidence' => 0.65,
        ]);
    }

    private function isDirectStyleInstruction(string $rawMessage, string $normalizedMessage): bool
    {
        $candidates = [$rawMessage];

        if ($normalizedMessage !== $rawMessage) {
            $candidates[] = $normalizedMessage;
        }

        $patterns = [
            '/^(?:tolong\s+)?jangan\s+(?:lagi\s+)?(?:pakai|gunakan|ucapkan|sebutkan|pake)\s+(?:kata|bahasa)\b/iu',
            '/stop\s+(?:using|saying)\s+(?:the\s+)?(?:word|phrase)\b/iu',
            '/(?:jawab|balas)\s+(?:pakai|gunakan)\s+(?:bahasa|language)\b/iu',
            '/gunakan\s+bahasa\s+(indonesia|inggris|english|jawa|sunda)/iu',
            '/please\s+(?:answer|reply)\s+in\s+(?:english|indonesian|sundanese|javanese)/iu',
        ];

        foreach ($candidates as $candidate) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $candidate)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function resetIntentContext(): void
    {
        if ($this->lastIntent !== null && isset($this->slotState[$this->lastIntent])) {
            unset($this->slotState[$this->lastIntent]);
        }

        $this->pendingSlot = null;
        $this->pendingConfirmation = null;
        $this->clearIntentReplay();
    }

    /**
     * @param callable():array|null $handler
     */
    private function registerIntentReplay(string $intent, callable $handler, array $meta = []): void
    {
        $this->lastIntentReplayHandler = $handler;
        $this->lastIntentReplayMeta = ['intent' => $intent] + $meta;
    }

    private function clearIntentReplay(): void
    {
        $this->lastIntentReplayHandler = null;
        $this->lastIntentReplayMeta = [];
    }

    private function attemptReplayPreviousQuestion(): ?array
    {
        $previous = $this->previousUserMessage();
        if ($previous === null) {
            return null;
        }

        $normalized = Str::of($previous)->lower()->squish()->value();
        if ($normalized === '') {
            return null;
        }

        if ($this->isIdentityIntroductionQuestion($normalized)) {
            $this->lastIntent = 'identity_introduction';
            $this->lastTopic = 'identity';
            $this->lastData = [];

            return [
                'message' => $this->text(
                    'Namaku Aetheria, asisten virtual RT yang siap bantu cek tagihan, pembayaran, agenda, direktori warga, dan informasi prosedur RT. Mau bahas apa? ??',
                    "I'm Aetheria, the neighborhood assistant, ready to help with bills, payments, events, resident directory, and RT procedures. What would you like to chat about?"
                ),
                'style' => [
                    'tone' => 'friendly',
                    'followups' => $this->followUpsForIntent('general'),
                ],
                'intent' => 'identity_introduction',
            ];
        }

        if ($this->isIdentityScopeQuestion($normalized)) {
            $this->lastIntent = 'identity_scope';
            $this->lastTopic = 'identity';
            $this->lastData = [];

            return [
                'message' => $this->text(
                    'Tugasku bantu warga soal urusan RT: cek tagihan & pembayaran, lihat agenda, cari info warga, dan jelaskan prosedur/FAQ RT. Tinggal sebut aja topik yang kamu butuhkan.',
                    "My role is to help residents with RT matters: checking bills and payments, sharing upcoming events, looking up resident info, and explaining RT SOP/FAQ. Just let me know which topic you need."
                ),
                'style' => [
                    'tone' => 'informative',
                    'followups' => $this->followUpsForIntent('general'),
                ],
                'intent' => 'identity_scope',
            ];
        }

        return null;
    }

    private function previousUserMessage(): ?string
    {
        $count = count($this->messageHistory);

        for ($i = $count - 2; $i >= 0; $i--) {
            $message = $this->messageHistory[$i] ?? null;
            if (!is_array($message)) {
                continue;
            }

            if (($message['role'] ?? '') !== 'user') {
                continue;
            }

            $content = $message['content'] ?? null;
            if (!is_string($content)) {
                continue;
            }

            return $content;
        }

        return null;
    }

    private function handleRetryRequest(string $normalizedMessage, string $rawMessage, ?int $userId): ?array
    {
        if ($this->lastIntent === null) {
            return null;
        }

        if (!$this->isRetryRequest($normalizedMessage)) {
            return null;
        }

        $constraintChanges = $this->captureRetryConstraintsFromMessage($rawMessage);
        $ack = $this->text(
            'Baik, aku ulang dengan detail terbaru.',
            'Sure, retrying with the latest detail.'
        );

        if ($constraintChanges['updated'] ?? false) {
            $idSummary = $constraintChanges['id'] ?? null;
            $enSummary = $constraintChanges['en'] ?? null;
            $ack .= ' ' . $this->text(
                $idSummary ? "Catatan filter: {$idSummary}." : 'Filter diperbarui.',
                $enSummary ? "Filter note: {$enSummary}." : 'Filters updated.'
            );
        }

        return $this->retryLastIntent($userId, $ack);
    }

    private function retryLastIntent(?int $userId, ?string $preface = null): ?array
    {
        if ($this->lastIntentReplayHandler === null) {
            return $preface === null ? null : $this->respond($preface);
        }

        try {
            $response = call_user_func($this->lastIntentReplayHandler);
        } catch (\Throwable $e) {
            report($e);
            $response = null;
        }

        if ($response === null) {
            $intentKey = $this->lastIntentReplayMeta['intent'] ?? ($this->lastIntent ?? 'general');
            $intentLabel = $this->lastIntentReplayMeta['intent_label'] ?? ($this->intentDescriptions[$intentKey]['label_id'] ?? $intentKey);

            $clarification = $this->text(
                "Aku belum bisa mengulang jawaban {$intentLabel}. Boleh jelaskan detail koreksinya supaya tepat?",
                "I could not rerun the {$intentLabel} answer yet. Could you clarify what to adjust so I stay accurate?"
            );

            if ($preface !== null) {
                $clarification = $preface . "\n\n" . $clarification;
            }

            return $this->respond($clarification, [
                'tone' => 'cautious',
                'confidence' => 0.35,
                'followups' => $this->followUpsForIntent($intentKey ?? 'general'),
            ]);
        }

        if ($preface !== null) {
            if (isset($response['content'])) {
                $response['content'] = $preface . "\n\n" . $response['content'];
            } elseif (isset($response['message'])) {
                $response['message'] = $preface . "\n\n" . $response['message'];
            } else {
                $response['content'] = $preface;
            }
        }

        return $response;
    }

    private function extractCorrectionPair(string $message): ?array
    {
        $trimmed = Str::of($message)->squish()->value();

        if ($trimmed === '') {
            return null;
        }

        $negationPattern = '/\b(bukan|bukannya|bkn)\s+(?P<wrong>[\p{L}\d\/&\'\-\s]{2,60})\s*(?:,|\s+)(?:tapi|melainkan)?\s*(?P<right>[\p{L}\d\/&\'\-\s]{2,60})/iu';
        if (preg_match($negationPattern, $message, $matches)) {
            return $this->buildCorrectionPair($matches['wrong'] ?? null, $matches['right'] ?? null);
        }

        $inlineAlias = '/(?P<wrong>[\p{L}\d\/&\'\-\s]{2,60})\s+(?:itu|=|adalah)\s+(?:maksudnya|artinya|aliasnya|alias|sama\s+dengan|=)?\s*(?P<right>[\p{L}\d\/&\'\-\s]{2,60})/iu';
        if (preg_match($inlineAlias, $message, $matches)) {
            // Prioritise exact "X itu Y" sentences to avoid matching long paragraphs.
            if (mb_strlen($trimmed) <= 120 || Str::contains($trimmed, ['maksud', 'alias', 'artinya'])) {
                return $this->buildCorrectionPair($matches['wrong'] ?? null, $matches['right'] ?? null);
            }
        }

        $inlineYangBenar = '/(?P<wrong>[\p{L}\d\/&\'\-\s]{2,60})\s*(?:,|:)?\s*(?:yang\s+benar|yang\s+bener|yg\s+benar|yg\s+bener)\s+(?P<right>[\p{L}\d\/&\'\-\s]{2,60})/iu';
        if (preg_match($inlineYangBenar, $message, $matches)) {
            return $this->buildCorrectionPair($matches['wrong'] ?? null, $matches['right'] ?? null);
        }

        $leadingYangBenar = '/^(?:yang\s+benar|yang\s+bener|yg\s+benar|yg\s+bener)\s+(?P<right>[\p{L}\d\/&\'\-\s]{2,60})/iu';
        if (preg_match($leadingYangBenar, $trimmed, $matches)) {
            return $this->buildCorrectionPair(null, $matches['right'] ?? null, true);
        }

        $inlineHarusnya = '/(?P<wrong>[\p{L}\d\/&\'\-\s]{2,60})\s+(?:harusnya|seharusnya|mestinya|harus|should\s+be)\s+(?P<right>[\p{L}\d\/&\'\-\s.,]{2,60})/iu';
        if (preg_match($inlineHarusnya, $message, $matches)) {
            return $this->buildCorrectionPair($matches['wrong'] ?? null, $matches['right'] ?? null);
        }

        $leadingHarusnya = '/^(?:harusnya|seharusnya|mestinya|should\s+be)\s+(?P<right>[\p{L}\d\/&\'\-\s.,]{2,60})/iu';
        if (preg_match($leadingHarusnya, $trimmed, $matches)) {
            return $this->buildCorrectionPair(null, $matches['right'] ?? null, true);
        }

        return null;
    }

    private function buildCorrectionPair(?string $wrong, ?string $right, bool $allowFallback = false): ?array
    {
        $cleanRight = Str::of($right ?? '')->squish()->value();
        $cleanWrong = Str::of($wrong ?? '')->squish()->value();

        if ($cleanRight === '') {
            return null;
        }

        if ($cleanWrong === '' && $allowFallback) {
            $cleanWrong = Str::of($this->lastCorrectionHint ?? '')->squish()->value();
        }

        if ($cleanWrong === '' || Str::lower($cleanWrong) === Str::lower($cleanRight)) {
            return null;
        }

        return [
            'wrong' => $cleanWrong,
            'right' => $cleanRight,
        ];
    }

    private function recordCorrection(string $wrong, string $right): void
    {
        $key = Str::lower(Str::squish($wrong));
        $alias = Str::squish($wrong);
        $replacement = Str::squish($right);

        if ($key === '' || $replacement === '') {
            return;
        }

        $now = Carbon::now($this->currentTimezone());
        $expiresAt = $now->copy()->addMinutes(self::CORRECTION_TTL_MINUTES)->timestamp;

        $this->corrections[$key] = [
            'alias' => $alias === '' ? $replacement : $alias,
            'wrong' => $key,
            'right' => $replacement,
            'pattern' => '/\b' . preg_quote($key, '/') . '\b/iu',
            'updated_at' => $now->timestamp,
            'expires_at' => $expiresAt,
        ];

        if (count($this->corrections) > 15) {
            $this->corrections = array_slice($this->corrections, -15, null, true);
        }

        $aliasForPromotion = $alias === '' ? $replacement : $alias;
        $this->lexicon->addCorrectionAlias($aliasForPromotion, $replacement, self::CORRECTION_TTL_MINUTES * 60);
        $this->correctionPromoter->record($aliasForPromotion, $replacement);
        $this->lexiconCorrectionsHydrated = true;
        $this->nextCorrectionHint = $replacement;
    }

    /**
     * Persist user supplied correction so it can be reused across sessions/orgs.
     *
     * @param  array{wrong:string,right:string}  $pair
     */
    private function persistCorrectionEvent(array $pair, string $rawMessage, ?int $userId): ?string
    {
        if (!isset($pair['wrong'], $pair['right'])) {
            return null;
        }

        $notice = null;

        try {
            if ($this->threadId === null) {
                $this->threadId = $this->resolveThreadId($this->stateUserId);
            }

            $feedbackPieces = [
                sprintf('"%s" itu "%s"', $pair['wrong'], $pair['right']),
            ];

            $rawSnippet = Str::of($rawMessage ?? '')
                ->squish()
                ->limit(160, '...')
                ->value();

            if ($rawSnippet !== '') {
                $feedbackPieces[] = $rawSnippet;
            }

            $payload = [
                'user_id' => $userId,
                'org_id' => $this->userOrgId,
                'thread_id' => $this->threadId,
                'turn_id' => (string) Str::uuid(),
                'original_input' => $this->previousUserMessage() ?? $this->currentRawMessage,
                'original_answer' => $this->lastResponseContent,
                'user_feedback_raw' => implode(' | ', array_filter($feedbackPieces)),
                'scope' => $this->userOrgId !== null
                    ? 'org'
                    : ($userId !== null ? 'user' : 'global'),
                'correction_type' => 'fakta',
            ];

            $factPatches = $this->buildFactPatches($pair);
            if ($factPatches !== []) {
                $payload['patch_rules']['fact_patch'] = $factPatches;
            }

            if ($synonymPatch = $this->buildSynonymPatch($pair)) {
                $payload['patch_rules']['synonym_add'][] = $synonymPatch;
            }

            $eventId = CorrectionIngestor::store($payload);
            $this->lastCorrectionEventId = $eventId;
            $this->applyCorrectionMemoryAdjustments($userId);

            if ($this->factPatchesIncludeKnowledge($factPatches)) {
                $this->invalidateKnowledgeCache($payload['original_input'] ?? $this->currentRawMessage);
            }

            if ($factPatches !== []) {
                $notice = $this->queueFactCorrections($factPatches, $payload, $eventId);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $notice;
    }

    /**
     * @param  array<int,array<string,mixed>>  $factPatches
     * @param  array<string,mixed>  $payload
     */
    private function queueFactCorrections(array $factPatches, array $payload, int $eventId): ?string
    {
        $status = null;

        foreach ($factPatches as $patch) {
            $result = $this->factCorrectionQueue->enqueue($patch, [
                'assistant_correction_event_id' => $eventId,
                'user_id' => $payload['user_id'] ?? null,
                'org_id' => $payload['org_id'] ?? null,
                'thread_id' => $payload['thread_id'] ?? null,
                'turn_id' => $payload['turn_id'] ?? null,
                'scope' => $payload['scope'] ?? 'user',
                'source_feedback' => $payload['user_feedback_raw'] ?? null,
                'meta' => [
                    'intent' => $this->lastIntent,
                    'original_input' => $payload['original_input'] ?? null,
                    'original_answer' => $payload['original_answer'] ?? null,
                ],
            ]);

            if ($result['status'] === 'queued') {
                $status = 'queued';
            } elseif ($status === null && $result['status'] === 'existing') {
                $status = 'existing';
            }
        }

        return $status;
    }

    private function factQueueNotice(?string $status): ?string
    {
        return match ($status) {
            'existing' => $this->text(
                'Perubahannya masih dalam proses review pengurus, dan akan dipakai begitu selesai.',
                'That change is already in the review queue and will take effect once approved.'
            ),
            'queued' => $this->text(
                'Koreksinya sudah kuantrikan ke pengurus supaya data resminya diperbarui.',
                'I have queued this correction for the admins so the official data can be updated.'
            ),
            default => null,
        };
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function buildFactPatches(array $pair): array
    {
        if (!isset($pair['right'])) {
            return [];
        }

        return match ($this->lastIntent) {
            'bills' => $this->buildBillFactPatches($pair),
            'payments' => $this->buildPaymentFactPatches($pair),
            'finance' => array_merge(
                $this->buildBillFactPatches($pair),
                $this->buildPaymentFactPatches($pair)
            ),
            'agenda' => $this->buildEventFactPatches($pair),
            'residents' => $this->buildResidentFactPatches($pair),
            'residents_new' => $this->buildResidentFactPatches($pair),
            'knowledge_base' => $this->buildKnowledgeFactPatch($pair),
            default => [],
        };
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function buildBillFactPatches(array $pair): array
    {
        $valueSpec = $this->detectFactFieldAndValue($pair['right'] ?? '');
        if ($valueSpec === null) {
            return [];
        }

        $needle = Str::lower(Str::squish($pair['wrong'] ?? ''));
        if ($needle === '') {
            return [];
        }

        return [[
            'entity' => 'bill',
            'field' => $valueSpec['field'],
            'value' => $valueSpec['value'],
            'value_raw' => $pair['right'],
            'intent' => $this->lastIntent,
            'match' => $this->buildFactMatchContext($needle, $pair['wrong'] ?? ''),
        ]];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function buildPaymentFactPatches(array $pair): array
    {
        $valueSpec = $this->detectFactFieldAndValue($pair['right'] ?? '');
        if ($valueSpec === null) {
            return [];
        }

        $needle = Str::lower(Str::squish($pair['wrong'] ?? ''));
        if ($needle === '') {
            return [];
        }

        return [[
            'entity' => 'payment',
            'field' => $valueSpec['field'],
            'value' => $valueSpec['value'],
            'value_raw' => $pair['right'],
            'intent' => $this->lastIntent,
            'match' => $this->buildFactMatchContext($needle, $pair['wrong'] ?? ''),
        ]];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function buildEventFactPatches(array $pair): array
    {
        $valueSpec = $this->detectEventFieldAndValue($pair['right'] ?? '');
        if ($valueSpec === null) {
            return [];
        }

        $needle = Str::lower(Str::squish($pair['wrong'] ?? ''));
        if ($needle === '') {
            $needle = Str::lower(Str::squish($pair['right'] ?? ''));
        }

        if ($needle === '') {
            return [];
        }

        $matchId = $this->matchEntityIdFromContext($needle, ['title', 'location']);

        return [[
            'entity' => 'event',
            'field' => $valueSpec['field'],
            'value' => $valueSpec['value'],
            'value_raw' => $pair['right'],
            'intent' => $this->lastIntent,
            'match' => $this->buildFactMatchContext($needle, $pair['wrong'] ?? ($pair['right'] ?? ''), $matchId),
        ]];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function buildResidentFactPatches(array $pair): array
    {
        $valueSpec = $this->detectResidentFieldAndValue($pair['right'] ?? '');
        if ($valueSpec === null) {
            return [];
        }

        $needle = Str::lower(Str::squish($pair['wrong'] ?? ''));
        if ($needle === '') {
            $needle = Str::lower(Str::squish($pair['right'] ?? ''));
        }

        if ($needle === '') {
            return [];
        }

        $matchId = $this->matchEntityIdFromContext($needle, ['name', 'alamat'], 'id', $this->lastData);

        return [[
            'entity' => 'resident',
            'field' => $valueSpec['field'],
            'value' => $valueSpec['value'],
            'value_raw' => $pair['right'],
            'intent' => $this->lastIntent,
            'match' => $this->buildFactMatchContext($needle, $pair['wrong'] ?? ($pair['right'] ?? ''), $matchId),
        ]];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function buildKnowledgeFactPatch(array $pair): array
    {
        $answer = Str::squish($pair['right'] ?? '');
        if ($answer === '') {
            return [];
        }

        $question = $this->previousUserMessage()
            ?? $this->currentRawMessage
            ?? ($pair['wrong'] ?? '');
        $normalizedQuestion = Str::squish((string) $question);

        return [[
            'entity' => 'knowledge',
            'field' => 'answer',
            'value' => $answer,
            'value_raw' => $pair['right'],
            'intent' => 'knowledge_base',
            'match' => array_filter([
                'question' => $normalizedQuestion,
                'keywords' => $this->extractFactKeywords($pair['wrong'] ?? $question ?? ''),
                'tokens' => $this->questionMatchTokens($normalizedQuestion),
            ]),
        ]];
    }

    private function buildFactMatchContext(string $needle, string $raw, ?int $entityId = null): array
    {
        $keywords = $this->extractFactKeywords($raw);

        $context = [
            'needle' => $needle,
            'keywords' => $keywords,
        ];

        if ($entityId !== null) {
            $context['id'] = $entityId;
        }

        return array_filter($context, fn($value) => $value !== null && $value !== [] && $value !== '');
    }

    private function buildSynonymPatch(array $pair): ?array
    {
        $alias = isset($pair['wrong']) ? Str::squish((string) $pair['wrong']) : null;
        $canonical = isset($pair['right']) ? Str::squish((string) $pair['right']) : null;

        if ($canonical === null || $canonical === '') {
            return null;
        }

        if ($alias === null || $alias === '' || Str::lower($alias) === Str::lower($canonical)) {
            return null;
        }

        return [
            'alias' => $alias,
            'canonical' => $canonical,
            'ttl' => self::CORRECTION_TTL_MINUTES * 60,
        ];
    }

    private function factPatchesIncludeKnowledge(array $patches): bool
    {
        foreach ($patches as $patch) {
            if (($patch['entity'] ?? null) === 'knowledge') {
                return true;
            }
        }

        return false;
    }

    private function invalidateKnowledgeCache(?string $question): void
    {
        if (!is_string($question)) {
            return;
        }

        $variants = array_values(array_unique(array_filter([
            $question,
            Str::lower(Str::squish($question)),
        ], fn($value) => is_string($value) && trim($value) !== '')));

        foreach ($variants as $variant) {
            Cache::forget('dummy_client_rag:' . md5($variant));
        }
    }

    /**
     * @return array{field:string,value:mixed}|null
     */
    private function detectFactFieldAndValue(string $candidate): ?array
    {
        $numeric = $this->extractNumericValue($candidate);
        if ($numeric !== null) {
            return [
                'field' => 'amount',
                'value' => $numeric,
            ];
        }

        $normalized = Str::lower(Str::squish($candidate));
        if ($normalized === '') {
            return null;
        }

        if ($this->stringIndicatesPaid($normalized)) {
            return [
                'field' => 'status',
                'value' => 'paid',
            ];
        }

        if ($this->stringIndicatesUnpaid($normalized)) {
            return [
                'field' => 'status',
                'value' => 'unpaid',
            ];
        }

        return null;
    }

    /**
     * @param array<int|string,mixed>|null $source
     */
    private function matchEntityIdFromContext(string $needle, array $fields, string $idField = 'id', ?array $source = null): ?int
    {
        $source ??= $this->lastData;
        if (!is_iterable($source)) {
            return null;
        }

        $needle = Str::lower(Str::squish($needle));
        if ($needle === '') {
            return null;
        }

        foreach ($source as $entry) {
            if (is_object($entry)) {
                $entry = (array) $entry;
            }

            if (!is_array($entry)) {
                continue;
            }

            $haystack = [];
            foreach ($fields as $field) {
                $haystack[] = Str::lower(Str::squish((string) ($entry[$field] ?? '')));
            }

            $candidate = Str::lower(Str::squish(implode(' ', $haystack)));

            if ($candidate !== '' && Str::contains($candidate, $needle)) {
                $id = $entry[$idField] ?? null;
                if ($id !== null && $id !== '') {
                    return (int) $id;
                }
            }
        }

        return null;
    }

    /**
     * @return array{field:string,value:mixed}|null
     */
    private function detectEventFieldAndValue(string $candidate): ?array
    {
        $normalized = Str::lower(Str::squish($candidate));
        if ($normalized === '') {
            return null;
        }

        if ($this->looksLikeTimeExpression($candidate)) {
            $parsed = $this->parseEventDateTime($candidate);

            return [
                'field' => 'start_at',
                'value' => $parsed?->toDateTimeString() ?? $candidate,
            ];
        }

        if ($this->contains($normalized, ['balai', 'lokasi', 'alamat', 'sekretariat', 'lapangan', 'aula', 'kantor', 'rumah', 'blok', 'gang', 'rt ', 'rw '])) {
            return [
                'field' => 'location',
                'value' => $candidate,
            ];
        }

        return [
            'field' => 'title',
            'value' => $candidate,
        ];
    }

    /**
     * @return array{field:string,value:mixed}|null
     */
    private function detectResidentFieldAndValue(string $candidate): ?array
    {
        $normalized = Str::lower(Str::squish($candidate));
        if ($normalized === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $candidate);
        if ($digits !== '' && strlen($digits) >= 8) {
            return [
                'field' => 'phone',
                'value' => $digits,
            ];
        }

        $statusMap = ['aktif', 'nonaktif', 'sementara'];
        if (in_array($normalized, $statusMap, true)) {
            return [
                'field' => 'status',
                'value' => $normalized,
            ];
        }

        if ($this->contains($normalized, ['alamat', 'jalan', 'jln', 'gang', 'blok', 'rt', 'rw', 'komplek', 'perum'])) {
            return [
                'field' => 'address',
                'value' => $candidate,
            ];
        }

        return [
            'field' => 'name',
            'value' => $candidate,
        ];
    }

    private function extractNumericValue(string $text): ?int
    {
        $normalized = Str::lower($text);
        $multiplier = 1;

        if (str_contains($normalized, 'juta')) {
            $multiplier = 1_000_000;
        } elseif (str_contains($normalized, 'ribu') || str_contains($normalized, 'rb')) {
            $multiplier = 1_000;
        }

        if (!preg_match('/(\d+([.,]\d+)?)/', $normalized, $matches)) {
            return null;
        }

        $number = (float) str_replace(',', '.', $matches[1]);

        return (int) round($number * $multiplier);
    }

    private function extractFactKeywords(string $text): array
    {
        $tokens = preg_split('/[\s,]+/', Str::lower(Str::squish($text))) ?: [];
        $keywords = array_filter($tokens, static fn($token) => strlen($token) >= 4);

        return array_values(array_unique(array_slice($keywords, 0, 5)));
    }

    private function applyFactOverridesToBills(Collection $bills): Collection
    {
        if ($this->memoryFacts === []) {
            return $bills;
        }

        $patches = array_filter($this->memoryFacts, static fn($fact) => ($fact['entity'] ?? null) === 'bill');
        if ($patches === []) {
            return $bills;
        }

        foreach ($bills as $bill) {
            $haystack = Str::lower(trim(
                ($bill->title ?? '') . ' ' .
                    ($bill->type ?? '') . ' ' .
                    ($bill->description ?? '')
            ));

            foreach ($patches as $patch) {
                if (!$this->factMatchesNeedle($haystack, $patch, $bill->id ?? null)) {
                    continue;
                }

                $value = $this->normalizeFactValue($patch['field'] ?? null, $patch);
                if ($value === null) {
                    continue;
                }

                $bill->{$patch['field']} = $value;
            }
        }

        return $bills;
    }

    private function applyFactOverridesToPayments(Collection $payments): Collection
    {
        if ($this->memoryFacts === []) {
            return $payments;
        }

        $patches = array_filter($this->memoryFacts, static fn($fact) => ($fact['entity'] ?? null) === 'payment');
        if ($patches === []) {
            return $payments;
        }

        foreach ($payments as $payment) {
            $haystack = Str::lower(trim(
                ($payment->reference ?? '') . ' ' .
                    ($payment->gateway ?? '') . ' ' .
                    (optional($payment->bill)->title ?? '')
            ));

            foreach ($patches as $patch) {
                if (!$this->factMatchesNeedle($haystack, $patch, $payment->id ?? null)) {
                    continue;
                }

                $value = $this->normalizeFactValue($patch['field'] ?? null, $patch);
                if ($value === null) {
                    continue;
                }

                $payment->{$patch['field']} = $value;
            }
        }

        return $payments;
    }

    private function applyFactOverridesToEvents(Collection $events): Collection
    {
        if ($this->memoryFacts === []) {
            return $events;
        }

        $patches = array_filter($this->memoryFacts, static fn($fact) => ($fact['entity'] ?? null) === 'event');
        if ($patches === []) {
            return $events;
        }

        foreach ($events as $event) {
            $haystack = Str::lower(trim(
                ($event->title ?? '') . ' ' .
                    ($event->description ?? '') . ' ' .
                    ($event->location ?? '')
            ));

            foreach ($patches as $patch) {
                if (!$this->factMatchesNeedle($haystack, $patch, $event->id ?? null)) {
                    continue;
                }

                $value = $this->normalizeEventFactValue($patch['field'] ?? null, $patch);
                if ($value === null) {
                    continue;
                }

                $event->{$patch['field']} = $value;
            }
        }

        return $events;
    }

    private function applyFactOverridesToResidents(Collection $residents): Collection
    {
        if ($this->memoryFacts === []) {
            return $residents;
        }

        $patches = array_filter($this->memoryFacts, static fn($fact) => ($fact['entity'] ?? null) === 'resident');
        if ($patches === []) {
            return $residents;
        }

        $addressColumn = $this->userAddressColumn();

        foreach ($residents as $resident) {
            $addressValue = $resident instanceof User
                ? $this->userAddressValue($resident, '', '')
                : ($addressColumn !== null ? ($resident->{$addressColumn} ?? '') : '');
            $haystack = Str::lower(Str::squish(
                ($resident->name ?? '') . ' ' .
                    ($resident->status ?? '') . ' ' .
                    $addressValue
            ));

            foreach ($patches as $patch) {
                if (!$this->factMatchesNeedle($haystack, $patch, $resident->id ?? null)) {
                    continue;
                }

                $value = $this->normalizeResidentFactValue($patch['field'] ?? null, $patch);
                if ($value === null) {
                    continue;
                }

                if (($patch['field'] ?? '') === 'address') {
                    $resident->alamat = $value;
                    if ($addressColumn !== null) {
                        $resident->{$addressColumn} = $value;
                    }
                    continue;
                }

                $resident->{$patch['field']} = $value;
            }
        }

        return $residents;
    }

    private function factMatchesNeedle(string $haystack, array $patch, ?int $entityId = null): bool
    {
        $match = $patch['match'] ?? [];
        $matchId = isset($match['id']) ? (int) $match['id'] : null;

        if ($matchId !== null && $entityId !== null && $matchId === $entityId) {
            return true;
        }

        $needle = Str::lower((string) ($match['needle'] ?? ''));
        if ($needle !== '' && Str::contains($haystack, $needle)) {
            return true;
        }

        foreach ((array) ($match['keywords'] ?? []) as $keyword) {
            $keyword = Str::lower((string) $keyword);
            if ($keyword !== '' && Str::contains($haystack, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return int|string|null
     */
    private function normalizeFactValue(?string $field, array $patch)
    {
        if ($field === null) {
            return null;
        }

        $value = $patch['value'] ?? $patch['value_raw'] ?? null;
        if ($value === null) {
            return null;
        }

        return match ($field) {
            'amount' => is_numeric($value) ? (int) $value : $this->extractNumericValue((string) $value),
            'status' => $this->stringIndicatesPaid((string) $value)
                ? 'paid'
                : ($this->stringIndicatesUnpaid((string) $value) ? 'unpaid' : $value),
            default => $value,
        };
    }

    /**
     * @return \DateTimeInterface|string|null
     */
    private function normalizeEventFactValue(?string $field, array $patch)
    {
        if ($field === null) {
            return null;
        }

        $value = $patch['value'] ?? $patch['value_raw'] ?? null;
        if ($value === null) {
            return null;
        }

        return match ($field) {
            'start_at' => $this->parseEventDateTime((string) $value) ?? $value,
            'title', 'location' => Str::squish((string) $value),
            default => null,
        };
    }

    private function normalizeResidentFactValue(?string $field, array $patch): ?string
    {
        if ($field === null) {
            return null;
        }

        $value = $patch['value'] ?? $patch['value_raw'] ?? null;
        if ($value === null) {
            return null;
        }

        return match ($field) {
            'phone' => preg_replace('/\D+/', '', (string) $value) ?: null,
            'status' => Str::lower(Str::squish((string) $value)),
            'address', 'name' => Str::squish((string) $value),
            default => null,
        };
    }

    private function stringIndicatesPaid(string $text): bool
    {
        return $this->contains($text, ['lunas', 'sudah bayar', 'paid', 'settled']);
    }

    private function stringIndicatesUnpaid(string $text): bool
    {
        return $this->contains($text, ['belum bayar', 'tunggak', 'outstanding', 'unpaid']);
    }

    private function looksLikeTimeExpression(string $value): bool
    {
        $normalized = Str::lower($value);

        if (preg_match('/\b\d{1,2}[:.]\d{2}\b/', $value)) {
            return true;
        }

        return $this->contains($normalized, ['pukul', 'jam', 'malam', 'pagi', 'siang', 'besok', 'hari ini', 'tanggal', 'tgl', 'wib', 'wit', 'wita']);
    }

    private function parseEventDateTime(string $value): ?Carbon
    {
        try {
            return Carbon::parse($value, $this->currentTimezone());
        } catch (\Throwable) {
            return null;
        }
    }

    private function factOverrideForKnowledgeQuestion(string $question): ?array
    {
        if ($this->memoryFacts === []) {
            return null;
        }

        $questionLower = Str::lower(Str::squish($question));
        $questionTokens = $this->questionMatchTokens($questionLower);

        foreach ($this->memoryFacts as $fact) {
            if (($fact['entity'] ?? null) !== 'knowledge') {
                continue;
            }

            if (!$this->knowledgePatchMatchesQuestion($fact, $questionLower, $questionTokens)) {
                continue;
            }

            $answer = Str::squish((string) ($fact['value'] ?? $fact['value_raw'] ?? ''));
            if ($answer === '') {
                continue;
            }

            return [
                'message' => $answer,
                'answer' => $answer,
                'sources' => $fact['sources'] ?? [],
                'style' => [
                    'tone' => 'informative',
                    'confidence' => (float) ($fact['confidence'] ?? 0.8),
                    'followups' => $this->followUpsForIntent('knowledge_base'),
                    'rewrite_opening' => false,
                ],
            ];
        }

        return null;
    }

    private function knowledgePatchMatchesQuestion(array $patch, string $questionLower, array $questionTokens): bool
    {
        $match = $patch['match'] ?? [];
        $requiredQuestion = Str::lower(Str::squish((string) ($match['question'] ?? '')));

        if ($requiredQuestion !== '') {
            if (!Str::contains($questionLower, $requiredQuestion)) {
                if ($this->questionSimilarity($questionLower, $requiredQuestion) < 0.65) {
                    return false;
                }
            }
        }

        foreach ((array) ($match['keywords'] ?? []) as $keyword) {
            $keyword = Str::lower((string) $keyword);
            if ($keyword !== '' && !Str::contains($questionLower, $keyword)) {
                return false;
            }
        }

        $requiredTokens = array_values(array_filter(array_map(
            fn($token) => is_string($token) ? Str::lower($token) : null,
            (array) ($match['tokens'] ?? [])
        )));

        if ($requiredTokens !== [] && $questionTokens !== []) {
            $overlap = count(array_intersect($requiredTokens, $questionTokens));
            $threshold = min(2, count($requiredTokens));

            if ($overlap < max(1, $threshold)) {
                return false;
            }
        }

        return true;
    }

    private function applyCorrectionsToInput(string $message): string
    {
        if ($message === '') {
            return $message;
        }

        $this->pruneExpiredCorrections();

        if ($this->corrections === []) {
            return $message;
        }

        $processed = $message;

        foreach ($this->corrections as $key => $correction) {
            $pattern = $correction['pattern'] ?? ('/\b' . preg_quote($correction['wrong'] ?? '', '/') . '\b/iu');
            $replacement = $correction['right'] ?? '';

            if ($replacement === '') {
                continue;
            }

            $processed = preg_replace($pattern, $replacement, $processed) ?? $processed;
            $this->corrections[$key]['pattern'] = $pattern;
        }

        return $processed;
    }

    private function refreshCurrentMessageContext(): void
    {
        $this->currentCorrectedMessage = $this->applyCorrectionsToInput($this->currentRawMessage);
        $this->currentNormalizedMessage = $this->normalizeWithSynonyms($this->currentCorrectedMessage);
    }

    private function pruneExpiredCorrections(bool $persist = false): void
    {
        if ($this->corrections === []) {
            return;
        }

        $now = Carbon::now($this->currentTimezone())->timestamp;
        $before = count($this->corrections);

        $this->corrections = array_filter($this->corrections, function (array $entry) use ($now) {
            $expiresAt = isset($entry['expires_at']) ? (int) $entry['expires_at'] : null;

            if ($expiresAt !== null && $expiresAt > 0) {
                return $expiresAt >= $now;
            }

            return true;
        });

        if ($before !== count($this->corrections) && $persist) {
            $this->persistStateFragment(['corrections' => $this->corrections]);
            $this->lexiconCorrectionsHydrated = false;
        }
    }

    private function hydrateLexiconCorrectionsFromState(): void
    {
        if ($this->lexiconCorrectionsHydrated || $this->corrections === []) {
            return;
        }

        $now = Carbon::now($this->currentTimezone())->timestamp;

        foreach ($this->corrections as $entry) {
            $alias = $entry['alias'] ?? $entry['wrong'] ?? null;
            $right = $entry['right'] ?? null;

            if (!is_string($alias) || $alias === '' || !is_string($right) || $right === '') {
                continue;
            }

            $expiresAt = isset($entry['expires_at']) ? (int) $entry['expires_at'] : null;
            $ttl = $expiresAt !== null
                ? max($expiresAt - $now, 60)
                : self::CORRECTION_TTL_MINUTES * 60;

            $this->lexicon->addCorrectionAlias($alias, $right, $ttl);
        }

        $this->lexiconCorrectionsHydrated = true;
    }

    private function guessCorrectionHint(string $rawMessage, string $correctedMessage): ?string
    {
        $candidates = [];
        $rawTrimmed = Str::of($rawMessage)->trim();

        if ($rawTrimmed->contains('"') && preg_match('/"([^"]{2,60})"/u', $rawMessage, $match)) {
            $candidates[] = $match[1];
        }

        if ($rawTrimmed->contains("'") && preg_match("/'([^']{2,60})'/u", $rawMessage, $match)) {
            $candidates[] = $match[1];
        }

        $nameEntities = $this->lexicalContext['entities']['names'] ?? [];
        if (!empty($nameEntities)) {
            $entity = $nameEntities[0];
            $candidates[] = $entity['name'] ?? ($entity['token'] ?? '');
        }

        $oovTokens = $this->lexicalContext['oov'] ?? [];
        if (!empty($oovTokens)) {
            $candidates[] = implode(' ', array_slice($oovTokens, 0, 2));
        }

        $words = preg_split('/\s+/', Str::of($correctedMessage)->squish()->value()) ?: [];
        if (!empty($words)) {
            $candidates[] = implode(' ', array_slice($words, 0, min(3, count($words))));
        }

        foreach ($candidates as $candidate) {
            $value = Str::of($candidate ?? '')->squish()->value();
            if ($value !== '' && mb_strlen($value) >= 2) {
                return $value;
            }
        }

        return $this->lastCorrectionHint;
    }

    private function intentSupportsRetryConstraints(?string $intent): bool
    {
        return in_array($intent, ['bills', 'payments', 'finance'], true);
    }

    private function constraintsForIntent(?string $intent): ?array
    {
        if (!$this->intentSupportsRetryConstraints($intent)) {
            return null;
        }

        $include = $this->retryConstraints['include'] ?? [];
        $exclude = $this->retryConstraints['exclude'] ?? [];

        if ($include === [] && $exclude === []) {
            return null;
        }

        return [
            'include' => $include,
            'exclude' => $exclude,
        ];
    }

    private function captureRetryConstraintsFromMessage(string $message): array
    {
        if (!$this->intentSupportsRetryConstraints($this->lastIntent)) {
            return ['updated' => false, 'id' => null, 'en' => null];
        }

        $partsId = [];
        $partsEn = [];
        $updated = false;

        if ($this->messageRequestsConstraintReset($message)) {
            if (($this->retryConstraints['include'] ?? []) !== [] || ($this->retryConstraints['exclude'] ?? []) !== []) {
                $this->retryConstraints = ['include' => [], 'exclude' => []];
                $partsId[] = 'filter direset';
                $partsEn[] = 'filters reset';
                $updated = true;
            }
        }

        $includes = $this->extractConstraintTokens($message, 'include');
        if ($includes !== []) {
            $this->retryConstraints['include'] = $includes;
            $this->retryConstraints['exclude'] = array_values(array_filter(
                $this->retryConstraints['exclude'] ?? [],
                fn($token) => !in_array($token, $includes, true)
            ));
            $partsId[] = 'fokus ke ' . $this->formatConstraintList($includes, 'id');
            $partsEn[] = 'focus on ' . $this->formatConstraintList($includes, 'en');
            $updated = true;
        }

        $excludes = $this->extractConstraintTokens($message, 'exclude');
        $newExcludes = [];
        foreach ($excludes as $token) {
            if (!in_array($token, $this->retryConstraints['exclude'] ?? [], true)) {
                $this->retryConstraints['exclude'][] = $token;
                $newExcludes[] = $token;
            }
        }

        if ($newExcludes !== []) {
            $partsId[] = 'tanpa ' . $this->formatConstraintList($newExcludes, 'id');
            $partsEn[] = 'without ' . $this->formatConstraintList($newExcludes, 'en');
            $updated = true;
        }

        if (!$updated) {
            return ['updated' => false, 'id' => null, 'en' => null];
        }

        return [
            'updated' => true,
            'id' => implode(', ', array_filter($partsId)),
            'en' => implode(', ', array_filter($partsEn)),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function extractConstraintTokens(string $message, string $mode): array
    {
        $patterns = $mode === 'include'
            ? [
                '/\b(fokus(?:nya)?|khusus|hanya|cuma|cuman|utamakan|prioritaskan)\s+(?P<term>[\p{L}\d\/&\'\-\s]{2,40})/iu',
                '/\b(?P<term>[\p{L}\d\/&\'\-\s]{2,40})\s+(?:saja|aja|doang)\b/iu',
            ]
            : [
                '/\b(jangan|jgn|tidak\s+usah|ga\s+usah|gak\s+usah)\s+(?:hitung|ikut(?:(?:kan)|in)?|masuk(?:(?:kan)|in)?|tampilkan|sebut(?:in)?|sertakan|pakai)\s+(?P<term>[\p{L}\d\/&\'\-\s]{2,40})/iu',
                '/\b(tanpa|exclude|abaikan|skip|hindari|hapus)\s+(?P<term>[\p{L}\d\/&\'\-\s]{2,40})/iu',
            ];

        $tokens = [];

        foreach ($patterns as $pattern) {
            if (!preg_match_all($pattern, $message, $matches, PREG_SET_ORDER)) {
                continue;
            }

            foreach ($matches as $match) {
                $candidate = $this->sanitizeConstraintCategory($match['term'] ?? '');
                if ($candidate === null || !$this->constraintTokenMatchesContext($candidate)) {
                    continue;
                }

                if (!in_array($candidate, $tokens, true)) {
                    $tokens[] = $candidate;
                }
            }
        }

        return $tokens;
    }

    private function constraintTokenMatchesContext(string $token): bool
    {
        $plain = Str::lower(Str::replace('_', ' ', $token));
        $normalized = Str::lower($this->currentNormalizedMessage ?? '');

        if ($normalized !== '' && (Str::contains($normalized, $plain) || Str::contains($normalized, $token))) {
            return true;
        }

        $tokens = $this->lexicalContext['tokens'] ?? [];

        return in_array($token, $tokens, true) || in_array($plain, $tokens, true);
    }

    private function messageRequestsConstraintReset(string $message): bool
    {
        return (bool) preg_match('/\b(reset(?:\s+filter)?|balik(?:in)?\s*(?:awal|semula|default)|normal\s+saja|semua\s+saja)\b/iu', $message);
    }

    private function formatConstraintList(array $tokens, string $lang = 'id'): string
    {
        if ($tokens === []) {
            return '';
        }

        $labels = array_map(
            fn($token) => $this->presentConstraintToken($token, $lang),
            $tokens
        );

        if (count($labels) === 1) {
            return $labels[0];
        }

        $last = array_pop($labels);
        $glue = $lang === 'en' ? ' and ' : ' dan ';

        return implode(', ', $labels) . $glue . $last;
    }

    private function presentConstraintToken(string $token, string $lang = 'id'): string
    {
        $label = Str::replace('_', ' ', $token);

        if ($lang === 'en') {
            return $label;
        }

        return $label;
    }

    private function describeConstraintSet(array $constraints, string $lang = 'id'): ?string
    {
        $parts = [];

        if (!empty($constraints['include'])) {
            $parts[] = ($lang === 'en' ? 'focus on ' : 'fokus ke ') . $this->formatConstraintList($constraints['include'], $lang);
        }

        if (!empty($constraints['exclude'])) {
            $parts[] = ($lang === 'en' ? 'without ' : 'tanpa ') . $this->formatConstraintList($constraints['exclude'], $lang);
        }

        return $parts === [] ? null : implode(', ', $parts);
    }

    /**
     * @param Collection<int, mixed> $items
     * @return array{0:Collection<int, mixed>,1:?string}
     */
    private function filterCollectionByConstraints(Collection $items, ?array $constraints, callable $valueResolver): array
    {
        if ($constraints === null || (($constraints['include'] ?? []) === [] && ($constraints['exclude'] ?? []) === [])) {
            return [$items, null];
        }

        $filtered = $items;
        $originalCount = $items->count();

        if (!empty($constraints['include'])) {
            $filtered = $filtered->filter(function ($item) use ($constraints, $valueResolver) {
                $haystack = Str::lower((string) ($valueResolver($item) ?? ''));

                foreach ($constraints['include'] as $token) {
                    $needle = Str::lower(Str::replace('_', ' ', $token));
                    if ($needle !== '' && Str::contains($haystack, $needle)) {
                        return true;
                    }
                }

                return false;
            });
        }

        if (!empty($constraints['exclude'])) {
            $filtered = $filtered->reject(function ($item) use ($constraints, $valueResolver) {
                $haystack = Str::lower((string) ($valueResolver($item) ?? ''));

                foreach ($constraints['exclude'] as $token) {
                    $needle = Str::lower(Str::replace('_', ' ', $token));
                    if ($needle !== '' && Str::contains($haystack, $needle)) {
                        return true;
                    }
                }

                return false;
            });
        }

        if ($filtered->isEmpty() && $originalCount > 0) {
            $idDesc = $this->describeConstraintSet($constraints, 'id');
            $enDesc = $this->describeConstraintSet($constraints, 'en');
            $message = $this->text(
                $idDesc
                    ? "Tidak ada data yang cocok setelah filter {$idDesc}. Kasih detail lain ya?"
                    : 'Tidak ada data yang cocok setelah filter khusus. Bisa kasih detail lain?',
                $enDesc
                    ? "No data matched after applying {$enDesc}. Want to adjust the filter?"
                    : 'No data matched after applying the filter. Feel free to adjust it.'
            );

            return [collect(), $message];
        }

        return [$filtered->values(), null];
    }

    private function applyRetryInclusion(string $term): ?array
    {
        if (!$this->intentSupportsRetryConstraints($this->lastIntent)) {
            return null;
        }

        $token = $this->sanitizeConstraintCategory($term);

        if ($token === null) {
            return null;
        }

        $this->retryConstraints['include'] = [$token];
        $this->retryConstraints['exclude'] = array_values(array_filter(
            $this->retryConstraints['exclude'] ?? [],
            fn($entry) => $entry !== $token
        ));

        return [
            'id' => $this->formatConstraintList([$token], 'id'),
            'en' => $this->formatConstraintList([$token], 'en'),
        ];
    }

    private function normalizePeriodInput(string|array|null $period): ?array
    {
        if (is_array($period) && isset($period['start'], $period['end'])) {
            return $period;
        }

        if (is_string($period)) {
            $key = Str::lower(Str::squish($period));
            $now = Carbon::now($this->currentTimezone());

            return match ($key) {
                'current', 'current_month', 'bulan_ini', 'this_month' => $this->temporal()->monthRange($now),
                'previous', 'previous_month', 'bulan_lalu', 'last_month' => $this->temporal()->monthRange($now->copy()->subMonth()),
                'next', 'next_month', 'bulan_depan' => $this->temporal()->monthRange($now->copy()->addMonth()),
                default => null,
            };
        }

        return null;
    }

    private function incrementClarificationTurns(): int
    {
        $this->clarificationTurns++;

        if ($this->lastIntent !== null) {
            $this->clarificationHistory[$this->lastIntent] = ($this->clarificationHistory[$this->lastIntent] ?? 0) + 1;
        }

        return $this->clarificationTurns;
    }

    private function resetClarificationTurns(): void
    {
        $this->clarificationTurns = 0;
        if ($this->lastIntent !== null) {
            $this->clarificationHistory[$this->lastIntent] = 0;
        }
    }

    private function followUpsForIntent(string $intent, array $context = []): array
    {
        $context['language'] = $this->language;

        return $this->suggestedFollowUps->forIntent($intent, $context);
    }

    public function getLastIntent(): ?string
    {
        return $this->lastIntent;
    }

    public function getLastCorrectionEventId(): ?int
    {
        return $this->lastCorrectionEventId;
    }

    public function getSmalltalkKind(): ?string
    {
        return $this->smalltalkKind;
    }

    private function handleClassifierFallback(string $normalizedMessage, string $rawMessage, ?int $userId): ?array
    {
        $classification = $this->classifier->classify($this->currentCorrectedMessage, $this->lexicalContext);
        if (!$classification) {
            return null;
        }

        if (($classification['score'] ?? 0) < 0.55) {
            return null;
        }

        if ($this->shouldForceOffTopic($normalizedMessage, $rawMessage)) {
            $this->escalateOffTopic($rawMessage, 'classifier_guard');
        }

        $this->guardrail('classifier_fallback', [
            'intent' => $classification['intent'],
            'score' => $classification['score'],
        ]);

        return $this->completeClassifierIntent($classification, $normalizedMessage, $rawMessage, $userId);
    }

    private function completeClassifierIntent(array $classification, string $normalizedMessage, string $rawMessage, ?int $userId): ?array
    {
        $intent = $classification['intent'] ?? null;
        if ($intent === null) {
            return null;
        }

        foreach (($classification['slots'] ?? []) as $slot => $value) {
            if ($value !== null) {
                $this->rememberSlotValue($intent, $slot, $value);
            }
        }

        return match ($intent) {
            'bills' => $this->respondBills($userId, $normalizedMessage, $rawMessage),
            'payments' => $this->respondPayments($userId, $normalizedMessage, $rawMessage),
            'agenda' => $this->respondAgenda($normalizedMessage, $rawMessage, $userId, $this->getSlotValue('agenda', 'range') ?? 'week'),
            'finance' => $this->respondFinance($userId, $normalizedMessage, $rawMessage),
            'residents' => $this->respondResidentsIntent($userId, $normalizedMessage, $rawMessage),
            'residents_new' => $this->getWargaBaru(),
            'knowledge_base' => $this->respond($this->text(
                'Aku bantu cek panduan terkait ya. Bisa sebutkan prosedur atau dokumen apa yang kamu maksud?',
                'Let me check the relevant guideline. Could you mention the exact procedure or document you need?'
            ), [
                'tone' => 'cautious',
                'confidence' => 0.5,
                'followups' => $this->followUpsForIntent('knowledge_base'),
            ]),
            default => null,
        };
    }

    private function isRetryRequest(string $normalizedMessage): bool
    {
        $normalized = Str::of($normalizedMessage)->lower()->squish()->value();

        if ($normalized === '' || Str::length($normalized) > 48) {
            return false;
        }

        $keywords = [
            'ulang',
            'retry',
            'redo',
            'perbaiki',
            'benerin',
            'coba lagi',
            'ulangin',
            'ulang dong',
            'itu salah',
            'salah itu',
            'kamu salah',
            'jawaban salah',
            'kamu menjawab salah',
            'jawabannya salah',
            'salah jawab',
            'kamu jawab salah',
        ];

        foreach ($keywords as $keyword) {
            if (Str::contains($normalized, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function isAffirmative(string $normalizedMessage, string $rawMessage): bool
    {
        $candidates = [$normalizedMessage, Str::of($rawMessage)->lower()->squish()->value()];

        foreach ($candidates as $candidate) {
            if ($candidate === '') {
                continue;
            }

            if (preg_match('/^(ya|iya|y|yes|lanjut|boleh|silakan|ok|oke|jalan|gas)/i', $candidate)) {
                return true;
            }
        }

        return false;
    }

    private function isNegative(string $normalizedMessage, string $rawMessage): bool
    {
        $candidates = [$normalizedMessage, Str::of($rawMessage)->lower()->squish()->value()];

        foreach ($candidates as $candidate) {
            if ($candidate === '') {
                continue;
            }

            if (preg_match('/^(tidak|ga|gak|nggak|no|cancel|batal|jangan)/i', $candidate)) {
                return true;
            }
        }

        return false;
    }

    private function reasoning(): ReasoningEngine
    {
        if ($this->reasoning === null) {
            $engine = new ReasoningEngine($this->currentTimezone());
            $engine->setViolationLogger(function (string $intent, array $violations): void {
                $this->metrics->recordReasoningViolation($intent, $violations, [
                    'thread_id' => $this->threadId,
                    'user_id' => $this->stateUserId,
                ]);
            });
            $this->reasoning = $engine;
        }

        return $this->reasoning;
    }

    private function detectInputNegation(string $message): bool
    {
        $normalized = Str::of($message)->lower()->squish()->value();

        if ($normalized === '') {
            return false;
        }

        foreach ($this->negationWords as $word) {
            $needle = Str::of($word)->lower()->squish()->value();

            if ($needle === '') {
                continue;
            }

            $pattern = '/(?<!\p{L})' . str_replace('\ ', '\s+', preg_quote($needle, '/')) . '(?!\p{L})/u';

            if (preg_match($pattern, $normalized)) {
                return true;
            }
        }

        return false;
    }

    private function userRequestsNegation(array $keywords = []): bool
    {
        if (!$this->inputHasNegation) {
            return false;
        }

        if ($keywords === []) {
            return true;
        }

        return $this->contains((string) $this->currentNormalizedMessage, $keywords);
    }

    private function asCarbon(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value->copy();
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_string($value) && $value !== '') {
            try {
                return Carbon::parse($value, $this->currentTimezone());
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    private function currentTimezone(): string
    {
        return $this->timezonePreference ?? config('app.timezone', 'UTC');
    }

    private function persistStateFragment(array $fragment): void
    {
        if ($this->threadId === null) {
            $this->threadId = $this->resolveThreadId($this->stateUserId);
        }

        $this->conversationState = $this->stateRepository->merge(
            $this->stateUserId,
            $this->threadId,
            $fragment
        );
    }

    private function deriveFewshotHints(): void
    {
        $this->fewshotClosing = null;

        if ($this->memoryFewshot === []) {
            return;
        }

        foreach ($this->memoryFewshot as $example) {
            if (!is_array($example)) {
                continue;
            }

            $text = $example['preferred_response'] ?? $example['response'] ?? null;
            if (!is_string($text)) {
                continue;
            }

            $normalized = Str::of($text)->squish()->value();
            if ($normalized === '') {
                continue;
            }

            if ($this->fewshotClosing === null) {
                $sentences = preg_split('/(?<=[.!?])\s+/u', $normalized) ?: [];
                $candidate = trim((string) end($sentences));
                if ($candidate !== '') {
                    $this->fewshotClosing = $candidate;
                }
            }

            if (preg_match('/[\x{1F300}-\x{1FAFF}]/u', $normalized)) {
                $this->memoryStyle['emoji_policy'] ??= 'light';
            }
        }
    }

    private function rememberKnowledgeContext(
        array $sources,
        string $answer,
        string $question,
        ?float $confidence = null
    ): void {
        $this->kbSources = array_slice($sources, 0, self::MAX_KB_SOURCES);
        $this->lastKnowledgeAnswer = $answer;
        $this->lastKnowledgeQuestion = $question;
        $this->lastKnowledgeConfidence = $confidence;

        $this->persistStateFragment([
            'kb_sources' => $this->kbSources,
            'metadata' => [
                'last_kb_answer' => $this->lastKnowledgeAnswer,
                'last_kb_question' => $this->lastKnowledgeQuestion,
                'last_kb_confidence' => $this->lastKnowledgeConfidence,
            ],
        ]);
    }

    private function normalizeRetryConstraints(mixed $value): array
    {
        $defaults = ['include' => [], 'exclude' => []];

        if (!is_array($value)) {
            return $defaults;
        }

        $include = [];
        foreach ((array) ($value['include'] ?? []) as $entry) {
            $category = $this->sanitizeConstraintCategory($entry);
            if ($category !== null && !in_array($category, $include, true)) {
                $include[] = $category;
            }
        }

        $exclude = [];
        foreach ((array) ($value['exclude'] ?? []) as $entry) {
            $category = $this->sanitizeConstraintCategory($entry);
            if ($category !== null && !in_array($category, $exclude, true)) {
                $exclude[] = $category;
            }
        }

        return [
            'include' => $include,
            'exclude' => $exclude,
        ];
    }

    private function sanitizeConstraintCategory(mixed $value): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        $normalized = Str::lower(Str::squish($value));
        if ($normalized === '') {
            return null;
        }

        if (isset($this->topicKeywords[$normalized])) {
            return $normalized;
        }

        foreach ($this->topicKeywords as $topic => $keywords) {
            foreach ($keywords as $keyword) {
                if (Str::lower($keyword) === $normalized) {
                    return $topic;
                }
            }
        }

        return Str::of($normalized)->replace(' ', '_')->value();
    }

    private function translator(): AutoTranslator
    {
        if ($this->translator === null) {
            $this->translator = app(AutoTranslator::class);
        }

        return $this->translator;
    }

    private function temporal(): TemporalInterpreter
    {
        $timezone = $this->currentTimezone();

        if ($this->temporal === null || $this->temporalTimezone !== $timezone) {
            $this->temporal = new TemporalInterpreter($timezone);
            $this->temporalTimezone = $timezone;
        }

        return $this->temporal;
    }

    private function isEnglish(): bool
    {
        return $this->language === 'en';
    }

    private function isJavanese(): bool
    {
        return $this->language === 'jv';
    }

    private function isSundanese(): bool
    {
        return $this->language === 'su';
    }

    private function text(string $idText, string $enText, array $replacements = []): string
    {
        $template = $this->isEnglish() ? $enText : $idText;

        foreach ($replacements as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }

        return $template;
    }

    private function buildSuggestions(array $excludeIntents = [], int $limit = 3, ?string $hint = null): string
    {
        $orderedIntents = array_keys($this->intentDescriptions);
        $availableIntents = [];

        foreach ($orderedIntents as $intent) {
            if (in_array($intent, $excludeIntents, true)) {
                continue;
            }

            $availableIntents[] = $intent;
        }

        if (empty($availableIntents)) {
            return '';
        }

        $normalizedHint = $hint !== null ? Str::lower($hint) : null;
        $scores = [];

        foreach ($availableIntents as $intent) {
            $scores[$intent] = 0;

            if ($normalizedHint !== null && isset($this->suggestionMatrix[$intent])) {
                foreach ($this->suggestionMatrix[$intent] as $keyword) {
                    $keyword = Str::lower($keyword);
                    if ($keyword === '') {
                        continue;
                    }

                    if (Str::contains($normalizedHint, $keyword)) {
                        $scores[$intent]++;
                    }
                }
            }
        }

        usort($availableIntents, function (string $a, string $b) use ($scores, $orderedIntents) {
            $scoreComparison = ($scores[$b] ?? 0) <=> ($scores[$a] ?? 0);
            if ($scoreComparison !== 0) {
                return $scoreComparison;
            }

            $aIndex = array_search($a, $orderedIntents, true);
            $bIndex = array_search($b, $orderedIntents, true);
            $aIndex = $aIndex === false ? PHP_INT_MAX : $aIndex;
            $bIndex = $bIndex === false ? PHP_INT_MAX : $bIndex;

            return $aIndex <=> $bIndex;
        });

        $suggestions = array_slice($availableIntents, 0, $limit);
        $lines = [];

        foreach ($suggestions as $intent) {
            $meta = $this->intentDescriptions[$intent] ?? null;
            if ($meta === null) {
                continue;
            }

            $label = $this->isEnglish() ? ($meta['label_en'] ?? '') : ($meta['label_id'] ?? '');
            $sample = $this->isEnglish() ? ($meta['sample_en'] ?? '') : ($meta['sample_id'] ?? '');

            if ($label === '') {
                continue;
            }

            $lines[] = '- ' . $label . ($sample !== '' ? ' - ' . $sample : '');
        }

        if (empty($lines)) {
            return '';
        }

        $intro = $this->isEnglish()
            ? 'You can also ask me about:'
            : 'Kamu juga bisa tanya hal-hal berikut:';

        return $intro . "\n" . implode("\n", $lines);
    }

    private function isLetterInquiry(string $normalizedMessage, string $rawLower): bool
    {
        $keywords = [
            'surat',
            'pengantar',
            'domisili',
            'keterangan',
            'skck',
            'pindah',
            'rt/rw',
            'dokumen rt',
        ];

        $haystack = $normalizedMessage . ' ' . $rawLower;

        foreach ($keywords as $keyword) {
            if ($keyword !== '' && Str::contains($haystack, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function respondLetterGuidance(): array
    {
        $this->lastIntent = 'knowledge_base';
        $this->lastTopic = 'administration';
        $this->registerIntentReplay('knowledge_base', function () {
            return $this->respondLetterGuidance();
        }, ['intent_label' => 'Panduan surat']);

        $message = $this->text(
            'Untuk urus surat RT (domisili/pengantar): siapkan KTP & KK, isi formulir di sekretariat RT/RW, lalu jelaskan kebutuhan suratnya. Prosesnya biasanya selesai dalam 1-2 hari kerja dan gratis.',
            'To process RT letters (domicile/reference): prepare your ID card and family card, fill the form at the RT office, and explain the purpose. It usually finishes within 1-2 working days and is free.'
        );

        return $this->respond($message, [
            'tone' => 'informative',
            'confidence' => 0.6,
            'followups' => $this->followUpsForIntent('knowledge_base'),
        ]);
    }
}
