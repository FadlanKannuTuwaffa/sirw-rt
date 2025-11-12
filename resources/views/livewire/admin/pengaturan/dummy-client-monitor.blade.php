@php
    $trendPayload = [
        'labels' => $trend['labels'] ?? [],
        'success' => $trend['success_rate'] ?? [],
        'correction' => $trend['correction_rate'] ?? [],
        'fallback' => $trend['fallback_rate'] ?? [],
        'knowledge' => $trend['knowledge_rate'] ?? [],
    ];
@endphp

<div class="space-y-6 font-['Inter'] text-slate-800 dark:text-slate-100" data-settings-page data-admin-stack>
    <x-admin.settings-nav current="assistant-analytics" />

    <section class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="space-y-2">
                <p class="text-sm font-semibold uppercase tracking-wide text-sky-600 dark:text-sky-300">Monitor DummyClient</p>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">Pantau peningkatan kecerdasan & adaptasi realtime</h1>
                <p class="text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                    Halaman ini menampilkan reuse rate koreksi, fallback LLM, hingga repetisi jawaban. Gunakan insight ini untuk mengetahui seberapa cepat DummyClient belajar dari interaksi warga.
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.settings.analytics') }}"
                   class="inline-flex items-center gap-2 rounded-2xl border border-slate-200/70 bg-white/80 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-sky-300 hover:text-sky-600 dark:border-slate-700/70 dark:bg-slate-900/60 dark:text-slate-200">
                    ← Kembali ke Analitik
                </a>
                <a href="{{ route('admin.settings.analytics') }}#recent-interactions"
                   class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-sky-500 to-indigo-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-sky-500/40 transition hover:shadow-indigo-500/40">
                    ⚙️ Pengaturan Lain
                </a>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <article class="group space-y-2 rounded-2xl border border-slate-100 bg-white/80 p-4 shadow-sm transition dark:border-slate-800 dark:bg-slate-900/60">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total interaksi</p>
                <p class="text-3xl font-semibold text-slate-900 dark:text-white">{{ number_format($summary['total_interactions']) }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Sejak pelacakan adaptasi diaktifkan.</p>
            </article>
            <article class="group space-y-2 rounded-2xl border border-emerald-100 bg-emerald-50/70 p-4 shadow-sm transition dark:border-emerald-600/30 dark:bg-emerald-900/40">
                <p class="text-sm font-medium text-emerald-600 dark:text-emerald-300">Reuse koreksi</p>
                <p class="text-3xl font-semibold text-emerald-700 dark:text-emerald-200">{{ number_format($summary['correction_reuse_rate'], 1) }}%</p>
                <p class="text-xs text-emerald-700/80 dark:text-emerald-200/80">Semakin tinggi semakin cepat bot belajar dari koreksi.</p>
            </article>
            <article class="group space-y-2 rounded-2xl border border-sky-100 bg-sky-50/70 p-4 shadow-sm transition dark:border-sky-600/30 dark:bg-sky-900/40">
                <p class="text-sm font-medium text-sky-600 dark:text-sky-300">Fallback LLM</p>
                <p class="text-3xl font-semibold text-sky-700 dark:text-sky-200">{{ number_format($summary['provider_fallback_rate'], 1) }}%</p>
                <p class="text-xs text-sky-700/80 dark:text-sky-200/80">Semakin rendah semakin stabil DummyClient.</p>
            </article>
            <article class="group space-y-2 rounded-2xl border border-amber-100 bg-amber-50/70 p-4 shadow-sm transition dark:border-amber-500/30 dark:bg-amber-900/40">
                <p class="text-sm font-medium text-amber-600 dark:text-amber-300">Median fallback latency</p>
                <p class="text-3xl font-semibold text-amber-700 dark:text-amber-200">{{ number_format($summary['median_fallback_latency_ms']) }} ms</p>
                <p class="text-xs text-amber-700/80 dark:text-amber-200/80">Durasi ketika terpaksa ganti provider.</p>
            </article>
            <article class="group space-y-2 rounded-2xl border border-rose-100 bg-rose-50/70 p-4 shadow-sm transition dark:border-rose-600/30 dark:bg-rose-900/40">
                <p class="text-sm font-medium text-rose-600 dark:text-rose-300">Tool 4xx rate</p>
                <p class="text-3xl font-semibold text-rose-700 dark:text-rose-200">{{ number_format($summary['tool_4xx_rate'], 1) }}%</p>
                <p class="text-xs text-rose-700/80 dark:text-rose-200/80">Jaga agar tetap rendah dengan perbaikan schema.</p>
            </article>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <article class="space-y-2 rounded-2xl border border-indigo-100 bg-indigo-50/70 p-4 shadow-sm transition dark:border-indigo-600/30 dark:bg-indigo-900/40">
                <p class="text-sm font-medium text-indigo-700 dark:text-indigo-200">LLM dipromosikan</p>
                <p class="text-3xl font-semibold text-indigo-800 dark:text-indigo-100">{{ number_format($summary['llm_snapshots_promoted']) }}</p>
                <p class="text-xs text-indigo-700/80 dark:text-indigo-200/70">
                    Dari total {{ number_format($summary['llm_snapshots_total']) }} snapshot helpful ({{ number_format($summary['llm_promoted_ratio'], 1) }}%).
                </p>
            </article>
            <article class="space-y-2 rounded-2xl border border-emerald-100 bg-emerald-50/70 p-4 shadow-sm transition dark:border-emerald-600/30 dark:bg-emerald-900/40">
                <p class="text-sm font-medium text-emerald-700 dark:text-emerald-200">Rasio pertanyaan dipelajari</p>
                <p class="text-3xl font-semibold text-emerald-800 dark:text-emerald-100">{{ number_format($summary['llm_learned_ratio'], 1) }}%</p>
                <p class="text-xs text-emerald-700/80 dark:text-emerald-200/70">
                    Persentase fallback LLM yang sudah diserap ke memori DummyClient.
                </p>
            </article>
            <article class="space-y-2 rounded-2xl border border-slate-200 bg-white/70 p-4 shadow-sm transition dark:border-slate-700/50 dark:bg-slate-900/50">
                <p class="text-sm font-medium text-slate-600 dark:text-slate-200">Rata-rata waktu promosi</p>
                <p class="text-3xl font-semibold text-slate-900 dark:text-white">{{ number_format($summary['llm_avg_time_to_promotion_hours'], 1) }} jam</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Durasi sejak snapshot tercatat hingga masuk fact patch / KB.</p>
            </article>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-3">
            <article class="space-y-2 rounded-2xl border border-violet-100 bg-violet-50/70 p-4 shadow-sm transition dark:border-violet-600/30 dark:bg-violet-900/40">
                <p class="text-sm font-medium text-violet-700 dark:text-violet-200">Query knowledge base</p>
                <p class="text-3xl font-semibold text-violet-800 dark:text-violet-100">
                    {{ number_format($summary['knowledge_queries']) }}
                </p>
                <p class="text-xs text-violet-700/80 dark:text-violet-200/70">
                    Success rate {{ number_format($summary['knowledge_success_rate'], 1) }}%.
                </p>
                <p class="text-xs text-violet-700/70 dark:text-violet-200/60">
                    Feedback: {{ number_format($summary['kb_feedback_helpful']) }} helpful /
                    {{ number_format($summary['kb_feedback_unhelpful']) }} butuh perbaikan.
                </p>
            </article>
            <article class="space-y-2 rounded-2xl border border-indigo-100 bg-indigo-50/70 p-4 shadow-sm transition dark:border-indigo-600/30 dark:bg-indigo-900/40">
                <p class="text-sm font-medium text-indigo-700 dark:text-indigo-200">Low confidence KB</p>
                <p class="text-3xl font-semibold text-indigo-800 dark:text-indigo-100">{{ number_format($summary['knowledge_low_confidence']) }}</p>
                <p class="text-xs text-indigo-700/80 dark:text-indigo-200/70">
                    Tindak lanjuti dokumen yang sering gagal dijawab.
                </p>
            </article>
            <article class="space-y-2 rounded-2xl border border-fuchsia-100 bg-fuchsia-50/70 p-4 shadow-sm transition dark:border-fuchsia-600/30 dark:bg-fuchsia-900/40">
                <p class="text-sm font-medium text-fuchsia-700 dark:text-fuchsia-200">Auto-promoted corrections</p>
                <p class="text-3xl font-semibold text-fuchsia-800 dark:text-fuchsia-100">{{ number_format($summary['autopromoted_corrections']) }}</p>
                <p class="text-xs text-fuchsia-700/80 dark:text-fuchsia-200/70">
                    Dari total {{ number_format($summary['active_corrections']) }} koreksi aktif.
                </p>
            </article>
        </div>
    </section>

    @if (!empty($evaluationSummary))
        <section class="rounded-3xl border border-emerald-100 bg-emerald-50/80 p-6 shadow-sm transition dark:border-emerald-500/30 dark:bg-emerald-900/30" data-motion-card>
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-300">Milestone Evaluation</p>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Offline regression score</h2>
                    <p class="text-sm text-emerald-900/70 dark:text-emerald-100/80">
                        Dataset terbaru menghasilkan {{ number_format($evaluationSummary['total_cases'] ?? 0) }} skenario. Detail lengkap tersedia pada laporan JSON/HTML di storage
                        <code class="rounded bg-black/10 px-1">assistant_eval/latest.*</code>.
                    </p>
                </div>
                <div class="text-right text-sm text-emerald-800 dark:text-emerald-200">
                    <p>CSAT simulasi</p>
                    <p class="text-3xl font-semibold">{{ number_format($evaluationSummary['csat'] ?? 0, 2) }}</p>
                </div>
            </div>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <article class="rounded-2xl border border-white/80 bg-white/80 p-4 shadow-sm dark:border-emerald-700/40 dark:bg-emerald-900/50">
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-200">Intent accuracy</p>
                    <p class="text-2xl font-semibold text-slate-900 dark:text-white">{{ number_format($evaluationSummary['intent_accuracy'] ?? 0, 2) }}%</p>
                    <p class="text-[11px] uppercase tracking-wide text-slate-400 dark:text-slate-500">Target ≥ 90%</p>
                </article>
                <article class="rounded-2xl border border-white/80 bg-white/80 p-4 shadow-sm dark:border-emerald-700/40 dark:bg-emerald-900/50">
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-200">Slot accuracy</p>
                    <p class="text-2xl font-semibold text-slate-900 dark:text-white">{{ number_format($evaluationSummary['slot_accuracy'] ?? 0, 2) }}%</p>
                    <p class="text-[11px] uppercase tracking-wide text-slate-400 dark:text-slate-500">Target ≥ 85%</p>
                </article>
                <article class="rounded-2xl border border-white/80 bg-white/80 p-4 shadow-sm dark:border-emerald-700/40 dark:bg-emerald-900/50">
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-200">Turn success rate</p>
                    <p class="text-2xl font-semibold text-slate-900 dark:text-white">{{ number_format($evaluationSummary['turn_success_rate'] ?? 0, 2) }}%</p>
                    <p class="text-[11px] uppercase tracking-wide text-slate-400 dark:text-slate-500">Mencakup intent, slot, tool, dan KB</p>
                </article>
                <article class="rounded-2xl border border-white/80 bg-white/80 p-4 shadow-sm dark:border-emerald-700/40 dark:bg-emerald-900/50">
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-200">Tool success</p>
                    <p class="text-xl font-semibold text-slate-900 dark:text-white">{{ number_format($evaluationSummary['tool_success_rate'] ?? 0, 2) }}%</p>
                </article>
                <article class="rounded-2xl border border-white/80 bg-white/80 p-4 shadow-sm dark:border-emerald-700/40 dark:bg-emerald-900/50">
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-200">RAG faithfulness</p>
                    <p class="text-xl font-semibold text-slate-900 dark:text-white">{{ number_format($evaluationSummary['rag_faithfulness'] ?? 0, 2) }}%</p>
                </article>
                <article class="rounded-2xl border border-white/80 bg-white/80 p-4 shadow-sm dark:border-emerald-700/40 dark:bg-emerald-900/50">
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-200">Self-repair success</p>
                    <p class="text-xl font-semibold text-slate-900 dark:text-white">{{ number_format($evaluationSummary['self_repair_rate'] ?? 0, 2) }}%</p>
                </article>
                <article class="rounded-2xl border border-white/80 bg-white/80 p-4 shadow-sm dark:border-emerald-700/40 dark:bg-emerald-900/50">
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-200">Guardrail hits</p>
                    <p class="text-xl font-semibold text-slate-900 dark:text-white">{{ number_format(array_sum($evaluationSummary['guardrails'] ?? [])) }}</p>
                </article>
            </div>
        </section>
    @endif

    <livewire:admin.pengaturan.assistant-fact-corrections />

    <section class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card>
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-semibold text-sky-600 dark:text-sky-300">Tren kecerdasan</p>
                <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Perkembangan 2 minggu terakhir</h2>
            </div>
            <span class="inline-flex items-center gap-2 rounded-full border border-slate-200/80 px-3 py-1 text-xs text-slate-500 dark:border-slate-700 dark:text-slate-400">
                <span class="h-2 w-2 rounded-full bg-green-400"></span> Pemutakhiran realtime
            </span>
        </div>
        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-100/80 bg-white/80 p-4 dark:border-slate-800/70 dark:bg-slate-900/60" wire:ignore>
            <canvas id="dummy-client-trend-chart" class="h-56 w-full md:h-64"></canvas>
        </div>
        <div class="mt-4 flex flex-wrap gap-4 text-xs text-slate-500 dark:text-slate-400">
            <span class="inline-flex items-center gap-2 rounded-full border border-slate-200/70 px-3 py-1 dark:border-slate-700/80">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Reuse koreksi
            </span>
            <span class="inline-flex items-center gap-2 rounded-full border border-slate-200/70 px-3 py-1 dark:border-slate-700/80">
                <span class="h-2 w-2 rounded-full bg-sky-500"></span> Tingkat sukses
            </span>
            <span class="inline-flex items-center gap-2 rounded-full border border-slate-200/70 px-3 py-1 dark:border-slate-700/80">
                <span class="h-2 w-2 rounded-full bg-rose-400"></span> Fallback LLM
            </span>
            <span class="inline-flex items-center gap-2 rounded-full border border-slate-200/70 px-3 py-1 dark:border-slate-700/80">
                <span class="h-2 w-2 rounded-full bg-violet-500"></span> KB success
            </span>
        </div>
    </section>

    <section class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-sky-600 dark:text-sky-300">Koreksi terbaru</p>
                    <h3 class="text-xl font-semibold text-slate-900 dark:text-white">Bagaimana user mengajari DummyClient</h3>
                </div>
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ $recentCorrections->count() }} catatan</span>
            </div>
            <div class="mt-4 space-y-4">
                @forelse ($recentCorrections as $event)
                    <article class="rounded-2xl border border-slate-100 bg-white/80 p-4 shadow-sm dark:border-slate-800/60 dark:bg-slate-900/60">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ \Illuminate\Support\Str::upper($event->correction_type ?? 'lainnya') }}</span>
                            <span class="text-xs text-slate-400 dark:text-slate-500">{{ \Illuminate\Support\Carbon::parse($event->created_at)->diffForHumans() }}</span>
                        </div>
                        <p class="mt-2 text-sm text-slate-700 dark:text-slate-200 break-words">{{ $event->user_feedback_raw ?? '-' }}</p>
                        @if ($event->patch_rules)
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                Patch rules:
                                <span class="font-mono text-[11px] break-all whitespace-pre-wrap">{{ $event->patch_rules }}</span>
                            </p>
                        @endif
                    </article>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-200 p-4 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">Belum ada koreksi baru.</p>
                @endforelse
            </div>
        </div>
        <div class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-sky-600 dark:text-sky-300">Fallback provider</p>
                    <h3 class="text-xl font-semibold text-slate-900 dark:text-white">Monitoring kesehatan pipeline LLM</h3>
                </div>
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ $recentFallbacks->count() }} catatan</span>
            </div>
            <div class="mt-4 space-y-4">
                @forelse ($recentFallbacks as $log)
                    <article class="rounded-2xl border border-slate-100 bg-white/80 p-4 shadow-sm dark:border-slate-800/60 dark:bg-slate-900/60">
                        <div class="flex items-center justify-between text-xs text-slate-400 dark:text-slate-500">
                            <span>{{ \Illuminate\Support\Carbon::parse($log->created_at)->diffForHumans() }}</span>
                            <span>{{ number_format($log->duration_ms) }} ms</span>
                        </div>
                        <p class="mt-2 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $log->provider_primary }} → {{ $log->provider_final ?? 'n/a' }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Fallback dari {{ $log->provider_fallback_from ?? 'unknown' }}</p>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ $log->query }}</p>
                    </article>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-200 p-4 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">Tidak ada fallback baru.</p>
                @endforelse
            </div>
        </div>
        <div class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-fuchsia-600 dark:text-fuchsia-300">Auto-promoted alias</p>
                    <h3 class="text-xl font-semibold text-slate-900 dark:text-white">Alias yang kini permanen</h3>
                </div>
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ $autoPromoted->count() }} entri</span>
            </div>
            <div class="mt-4 space-y-3">
                @forelse ($autoPromoted as $correction)
                    <div class="rounded-2xl border border-slate-100 bg-white/80 px-4 py-3 text-sm dark:border-slate-800/60 dark:bg-slate-900/60">
                        <div class="flex items-center justify-between text-xs uppercase tracking-wide text-slate-400 dark:text-slate-500">
                            <span>Alias</span>
                            <span>{{ \Illuminate\Support\Carbon::parse($correction->updated_at)->diffForHumans() }}</span>
                        </div>
                        <p class="mt-1 font-semibold text-slate-900 dark:text-white">{{ $correction->alias }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">=&gt; {{ $correction->canonical }}</p>
                    </div>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-200 p-4 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">Belum ada auto-promoted correction.</p>
                @endforelse
            </div>
            <div class="mt-6 border-t border-dashed border-slate-200/70 pt-4 dark:border-slate-700/70">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-600 dark:text-slate-300">Feedback knowledge base</p>
                    <span class="text-xs text-slate-400 dark:text-slate-500">{{ $kbFeedbackSamples->count() }} catatan</span>
                </div>
                <div class="mt-3 space-y-3">
                    @forelse ($kbFeedbackSamples as $feedback)
                        <div class="rounded-2xl border border-slate-100 bg-white/80 px-4 py-3 text-xs text-slate-600 shadow-sm dark:border-slate-800/60 dark:bg-slate-900/60 dark:text-slate-300">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold {{ $feedback->helpful ? 'text-emerald-600 dark:text-emerald-300' : 'text-rose-600 dark:text-rose-300' }}">
                                    {{ $feedback->helpful ? 'Membantu' : 'Butuh revisi' }}
                                </span>
                                <span class="text-[11px] text-slate-400 dark:text-slate-500">{{ \Illuminate\Support\Carbon::parse($feedback->responded_at)->diffForHumans() }}</span>
                            </div>
                            <p class="mt-1 font-semibold text-slate-800 dark:text-slate-100">{{ \Illuminate\Support\Str::limit($feedback->question, 90) }}</p>
                            @if ($feedback->note)
                                <p class="mt-1 italic text-slate-500 dark:text-slate-400">“{{ \Illuminate\Support\Str::limit($feedback->note, 140) }}”</p>
                            @endif
                        </div>
                    @empty
                        <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-3 text-center text-xs text-slate-400 dark:border-slate-700 dark:text-slate-500">Belum ada feedback warga.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <div id="dummy-client-monitor-config" class="hidden">
        {!! json_encode($trendPayload, JSON_UNESCAPED_UNICODE) !!}
    </div>
    <section class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card>
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-semibold text-sky-600 dark:text-sky-300">Knowledge Base</p>
                <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Kelola konten SOP & FAQ</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">Unggah dokumen baru, sinkronkan ulang, dan lihat riwayat potongan yang dipakai RAG.</p>
            </div>
        </div>
        <div class="mt-6">
            <livewire:admin.pengaturan.knowledge-base-manager />
        </div>
    </section>
    @if (!empty($llmDistribution))
        <section class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card>
            <div class="flex flex-col gap-2">
                <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-300">Feedback Snapshot LLM</p>
            <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Distribusi helpful vs unhelpful per intent</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Bagian ini membantu melihat intent mana yang paling sering dinilai membantu/tidak membantu setelah fallback LLM.
            </p>
        </div>

        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200/80 bg-white/80 dark:border-slate-800/80 dark:bg-slate-900/60">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50/80 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-900/70 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Intent</th>
                        <th class="px-4 py-3">Helpful</th>
                        <th class="px-4 py-3">Unhelpful</th>
                        <th class="px-4 py-3">Pending</th>
                        <th class="px-4 py-3">Auto-ready</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100/70 dark:divide-slate-800/70">
                    @foreach ($llmDistribution as $row)
                        @php
                            $total = max(1, $row['total']);
                            $helpfulRate = round(($row['helpful'] / $total) * 100);
                            $unhelpfulRate = round(($row['unhelpful'] / $total) * 100);
                            $pendingRate = round(($row['pending'] / $total) * 100);
                        @endphp
                        <tr class="text-slate-700 dark:text-slate-100">
                            <td class="px-4 py-3 font-semibold capitalize">{{ $row['intent'] }}</td>
                            <td class="px-4 py-3">
                                <span class="font-medium text-emerald-600 dark:text-emerald-300">{{ $row['helpful'] }}</span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">({{ $helpfulRate }}%)</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-medium text-rose-600 dark:text-rose-300">{{ $row['unhelpful'] }}</span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">({{ $unhelpfulRate }}%)</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-medium text-amber-600 dark:text-amber-300">{{ $row['pending'] }}</span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">({{ $pendingRate }}%)</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-medium text-indigo-600 dark:text-indigo-300">{{ $row['auto_ready'] }}</span>
                                <span class="text-xs text-slate-500 dark:text-slate-400"> dari {{ $row['total'] }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        </section>
    @endif

    @if (!empty($autoLearnTimeline['labels']))
        <section class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card>
            <div class="flex flex-col gap-2">
                <p class="text-sm font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-300">Auto-Learned Answers</p>
                <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Intent yang otomatis dipromosikan</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Jumlah koreksi baru hasil analisis interaksi sukses (tanpa feedback manual) dalam {{ count($autoLearnTimeline['labels'] ?? []) }} hari terakhir.
                </p>
            </div>
            <div class="mt-6 grid gap-3 md:grid-cols-7">
                @foreach ($autoLearnTimeline['labels'] as $index => $label)
                    <article class="rounded-2xl border border-slate-100 bg-white/80 p-3 text-center shadow-sm transition dark:border-slate-800 dark:bg-slate-900/60">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ $label }}</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ $autoLearnTimeline['counts'][$index] ?? 0 }}</p>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">intent</p>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if (!empty($intentSkillScores))
        <section class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card>
            <div class="flex flex-col gap-2">
                <p class="text-sm font-semibold uppercase tracking-wide text-amber-600 dark:text-amber-300">Intent Skill Score</p>
                <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Intent dengan risiko kesalahan tertinggi</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Urutan berdasarkan keberhasilan percakapan {{ count($intentSkillScores) }} intent terakhir. Gunakan insight ini untuk menentukan prioritas training.
                </p>
            </div>
            <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200/80 bg-white/80 dark:border-slate-800/80 dark:bg-slate-900/60">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50/80 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-900/70 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Intent</th>
                            <th class="px-4 py-3">Success Rate</th>
                            <th class="px-4 py-3">Interaksi</th>
                            <th class="px-4 py-3">Contoh kegagalan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100/70 dark:divide-slate-800/70">
                        @foreach ($intentSkillScores as $row)
                            <tr class="text-slate-700 dark:text-slate-100">
                                <td class="px-4 py-3 font-semibold capitalize">{{ $row['intent'] }}</td>
                                <td class="px-4 py-3">
                                    <span class="font-semibold text-{{ $row['success_rate'] >= 70 ? 'emerald' : ($row['success_rate'] >= 50 ? 'amber' : 'rose') }}-600 dark:text-{{ $row['success_rate'] >= 70 ? 'emerald' : ($row['success_rate'] >= 50 ? 'amber' : 'rose') }}-300">
                                        {{ $row['success_rate'] }}%
                                    </span>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">(fail {{ $row['failure_rate'] }}%)</span>
                                </td>
                                <td class="px-4 py-3">{{ $row['total'] }}</td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-300">
                                    {{ $row['sample_failure'] ?? 'Belum ada sampel.' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($regressionReviews->isNotEmpty())
        <section class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card>
            <div class="flex flex-col gap-2">
                <p class="text-sm font-semibold uppercase tracking-wide text-rose-600 dark:text-rose-300">Regresi Otomatis</p>
                <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Riwayat PASS/WARN setelah promosi</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Gunakan daftar ini untuk melihat snapshot mana yang lolos regresi otomatis dan mana yang perlu perhatian ekstra.
                </p>
            </div>

            <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200/80 bg-white/80 dark:border-slate-800/80 dark:bg-slate-900/60">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50/80 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-900/70 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Snapshot</th>
                            <th class="px-4 py-3">Intent</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Catatan</th>
                            <th class="px-4 py-3">Waktu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100/70 dark:divide-slate-800/70">
                        @foreach ($regressionReviews as $review)
                            <tr class="text-slate-700 dark:text-slate-100">
                                <td class="px-4 py-3 font-semibold">#{{ $review->assistant_llm_snapshot_id }}</td>
                                <td class="px-4 py-3">{{ $review->snapshot?->intent ?? 'unknown' }}</td>
                                <td class="px-4 py-3">
                                    @if ($review->action === 'regression_pass')
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200">PASS</span>
                                    @else
                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700 dark:bg-amber-500/20 dark:text-amber-200">WARN</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-300">{{ $review->notes ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ optional($review->created_at)->diffForHumans() ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($toolBlueprints->isNotEmpty())
        <section class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card>
            <div class="flex flex-col gap-2">
                <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-300">Tool Blueprint</p>
                <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Intent prioritas untuk dibuatkan tool</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Data diambil dari rekomendasi otomatis `assistant:recommend-tool-blueprints`. Klik tombol Tool Blueprint pada menu Pengaturan untuk mengubah status detailnya.
                </p>
            </div>
            <div class="mt-6 grid gap-4 md:grid-cols-3">
                @foreach ($toolBlueprints as $item)
                    <article class="rounded-2xl border border-slate-200/70 bg-white/80 p-4 shadow-sm transition dark:border-slate-800/70 dark:bg-slate-900/60">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">Intent</p>
                        <h3 class="text-xl font-semibold text-slate-900 dark:text-white capitalize">{{ $item->intent }}</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Status: <span class="font-semibold">{{ \Illuminate\Support\Str::headline($item->status) }}</span>
                            · Failure {{ number_format($item->failure_rate, 1) }}% · Tool usage {{ number_format($item->tool_usage_rate, 1) }}%
                        </p>
                        <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">
                            Contoh pertanyaan gagal:
                            <span class="font-medium">{{ $item->sample_failure ?? 'Tidak ada contoh' }}</span>
                        </p>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</div>

@once
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
            (function () {
                if (window.__dummyMonitorRegistered) {
                    return;
                }

                window.__dummyMonitorRegistered = true;
                window.__dummyMonitorChart = null;

                document.addEventListener('livewire:navigated', initDummyMonitorChart);
                document.addEventListener('DOMContentLoaded', initDummyMonitorChart, { once: true });

                function initDummyMonitorChart() {
                    const node = document.getElementById('dummy-client-monitor-config');
                    const canvas = document.getElementById('dummy-client-trend-chart');
                    if (!node || !canvas || typeof Chart === 'undefined') {
                        return;
                    }

                    const config = JSON.parse(node.innerText || '{}');

                    if (window.__dummyMonitorChart instanceof Chart) {
                        window.__dummyMonitorChart.destroy();
                        window.__dummyMonitorChart = null;
                    }

                    const data = {
                        labels: config.labels || [],
                        datasets: [
                            {
                                label: 'Success rate',
                                data: config.success || [],
                                borderColor: 'rgb(56, 189, 248)',
                                backgroundColor: 'rgba(56, 189, 248, 0.15)',
                                tension: 0.35,
                            },
                            {
                                label: 'Reuse koreksi',
                                data: config.correction || [],
                                borderColor: 'rgb(16, 185, 129)',
                                backgroundColor: 'rgba(16, 185, 129, 0.15)',
                                tension: 0.35,
                            },
                            {
                                label: 'Fallback LLM',
                                data: config.fallback || [],
                                borderColor: 'rgb(248, 113, 113)',
                                backgroundColor: 'rgba(248, 113, 113, 0.1)',
                                borderDash: [6, 4],
                                tension: 0.35,
                            },
                            {
                                label: 'KB success',
                                data: config.knowledge || [],
                                borderColor: 'rgb(168, 85, 247)',
                                backgroundColor: 'rgba(168, 85, 247, 0.15)',
                                tension: 0.35,
                            },
                        ],
                    };

                    window.__dummyMonitorChart = new Chart(canvas.getContext('2d'), {
                        type: 'line',
                        data,
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                intersect: false,
                                mode: 'index',
                            },
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                },
                            },
                            scales: {
                                y: {
                                    suggestedMin: 0,
                                    suggestedMax: 100,
                                    ticks: {
                                        callback: (value) => value + '%',
                                    },
                                },
                            },
                        },
                    });
                }
            })();
        </script>
    @endpush
@endonce
