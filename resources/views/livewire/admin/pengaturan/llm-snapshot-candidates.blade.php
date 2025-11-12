<div class="space-y-6 font-['Inter'] text-slate-800 dark:text-slate-100" data-settings-page data-admin-stack>
    <x-admin.settings-nav current="llm-candidates" />

    <section class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-2">
                <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-300">Kurasi Snapshot LLM</p>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">Pilih jawaban LLM yang layak jadi memori permanen</h1>
                <p class="text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                    Panel ini menampilkan fallback LLM yang dinilai membantu oleh warga atau evaluator. Promosikan ke fact patch, masukkan ke knowledge base,
                    atau tahan untuk review tambahan agar DummyClient belajar lebih aman.
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.settings.dummy-monitor') }}"
                   class="inline-flex items-center gap-2 rounded-2xl border border-slate-200/70 bg-white/80 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-indigo-300 hover:text-indigo-600 dark:border-slate-700/70 dark:bg-slate-900/60 dark:text-slate-200">
                    Monitor DummyClient
                </a>
                <a href="{{ route('admin.settings.analytics') }}"
                   class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-indigo-500 to-purple-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-500/40 transition hover:shadow-purple-500/40">
                    Lihat Analitik
                </a>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="group rounded-2xl border border-slate-100 bg-white/80 p-4 shadow-sm transition dark:border-slate-800 dark:bg-slate-900/60">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Pending</p>
                <p class="text-3xl font-semibold text-slate-900 dark:text-white">{{ number_format($stats['pending']) }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Snapshot menunggu kurasi.</p>
            </article>
            <article class="group rounded-2xl border border-emerald-100 bg-emerald-50/70 p-4 shadow-sm transition dark:border-emerald-600/30 dark:bg-emerald-900/40">
                <p class="text-sm font-medium text-emerald-700 dark:text-emerald-200">Siap auto-promote</p>
                <p class="text-3xl font-semibold text-emerald-700 dark:text-emerald-100">{{ number_format($stats['auto_ready']) }}</p>
                <p class="text-xs text-emerald-700/80 dark:text-emerald-200/80">Telah mendapat ≥2 feedback positif atau PASS.</p>
            </article>
            <article class="group rounded-2xl border border-amber-100 bg-amber-50/70 p-4 shadow-sm transition dark:border-amber-500/30 dark:bg-amber-900/40">
                <p class="text-sm font-medium text-amber-700 dark:text-amber-200">Need review</p>
                <p class="text-3xl font-semibold text-amber-700 dark:text-amber-100">{{ number_format($stats['needs_review']) }}</p>
                <p class="text-xs text-amber-700/80 dark:text-amber-200/80">Butuh kurasi manual atau data tambahan.</p>
            </article>
            <article class="group rounded-2xl border border-sky-100 bg-sky-50/70 p-4 shadow-sm transition dark:border-sky-600/30 dark:bg-sky-900/40">
                <p class="text-sm font-medium text-sky-700 dark:text-sky-200">Sudah dipromosikan</p>
                <p class="text-3xl font-semibold text-sky-700 dark:text-sky-200">{{ number_format($stats['promoted']) }}</p>
                <p class="text-xs text-sky-700/80 dark:text-sky-200/80">Masuk ke fact patch atau KB.</p>
            </article>
        </div>

        <div class="mt-8 flex flex-col gap-4 rounded-2xl border border-slate-200/60 bg-white/80 p-4 shadow-inner dark:border-slate-800/70 dark:bg-slate-900/60">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-1 flex-col gap-2 md:flex-row">
                    <label class="flex flex-1 items-center gap-2 rounded-2xl border border-slate-200/60 bg-white px-3 py-2 text-sm text-slate-600 shadow-sm focus-within:border-indigo-400 dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-300">
                        <span class="text-xs uppercase tracking-wide text-slate-400 dark:text-slate-500">Cari</span>
                        <input type="search" wire:model.debounce.400ms="search" placeholder="intent, konten, pertanyaan..." class="flex-1 border-none bg-transparent text-sm text-slate-700 placeholder:text-slate-400 focus:ring-0 dark:text-slate-200" />
                    </label>
                    <label class="flex items-center gap-2 rounded-2xl border border-slate-200/60 bg-white px-3 py-2 text-sm text-slate-600 shadow-sm dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-300 md:w-60">
                        <span class="text-xs uppercase tracking-wide text-slate-400 dark:text-slate-500">Status</span>
                        <select wire:model="status" class="flex-1 border-none bg-transparent text-sm text-slate-700 focus:ring-0 dark:text-slate-200">
                            <option value="pending">Pending</option>
                            <option value="needs-review">Need Review</option>
                            <option value="promoted">Promoted</option>
                            <option value="failed">Failed</option>
                        </select>
                    </label>
                </div>
                <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 dark:text-slate-300">
                    <input type="checkbox" wire:model="onlyAutoReady" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                    Hanya tampilkan yang siap auto-promote
                </label>
            </div>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card>
        @if ($snapshots->isEmpty())
            <div class="flex flex-col items-center gap-3 py-16 text-center text-slate-500 dark:text-slate-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.75 9 21l3-1.5 3 1.5-.813-5.25M7.5 8.25l-2.25.75L3 12l2.25.75 1.5 2.25 1.5-2.25L10.5 12l-2.25-.75L7.5 8.25Zm9-6 1.5 2.25L21 5.25l-2.25.75-1.5 2.25-1.5-2.25-2.25-.75 2.25-.75L16.5 2.25Z" />
                </svg>
                <p class="text-lg font-semibold text-slate-700 dark:text-slate-200">Tidak ada kandidat</p>
                <p class="text-sm max-w-lg">
                    Semua snapshot yang siap autopromote sudah diproses. Arahkan warga untuk memberi feedback positif agar muncul kandidat baru.
                </p>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($snapshots as $snapshot)
                    @php
                        $interaction = $snapshot->interaction;
                        $lastReview = optional($snapshot->reviews->first());
                        $meta = $snapshot->metadata ?? [];
                        $evaluationLabels = collect(data_get($meta, 'evaluation_labels', []))
                            ->map(fn ($label) => strtoupper((string) $label))
                            ->values();
                        $hasPassLabel = $evaluationLabels->contains('PASS');
                        $regressionPassed = (bool) data_get($meta, 'evaluation_passed', false);
                        $autoReasons = [];
                        if ($snapshot->positive_feedback_count >= 2) {
                            $autoReasons[] = '≥2 feedback positif';
                        }
                        if ($hasPassLabel) {
                            $autoReasons[] = 'Label PASS';
                        }
                        if ($regressionPassed) {
                            $autoReasons[] = 'Regression PASS';
                        }
                    @endphp
                    <article class="rounded-2xl border border-slate-200/70 bg-white/90 p-4 shadow-sm transition hover:border-indigo-200 dark:border-slate-800/70 dark:bg-slate-900/60">
                        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                            <div class="space-y-2">
                                <div class="flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">
                                    <span>ID #{{ $snapshot->id }}</span>
                                    <span>Intent: {{ $snapshot->intent ?? 'unknown' }}</span>
                                    <span>Provider: {{ $snapshot->provider ?? '-' }}</span>
                                    <span>Status: {{ $snapshot->promotion_status }}</span>
                                    @if ($snapshot->auto_promote_ready)
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-200">Auto-ready</span>
                                    @endif
                                    @if ($snapshot->needs_review)
                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-amber-700 dark:bg-amber-500/20 dark:text-amber-200">Need Review</span>
                                    @endif
                                </div>
                                <p class="text-sm font-semibold text-slate-600 dark:text-slate-300">Pertanyaan: {{ $interaction?->query ?? 'Tidak tersedia' }}</p>
                                <div class="rounded-xl border border-slate-200/60 bg-white/80 p-3 text-sm text-slate-600 shadow-inner dark:border-slate-800/60 dark:bg-slate-900/60 dark:text-slate-200">
                                    <p class="font-semibold text-slate-700 dark:text-slate-100">Jawaban LLM</p>
                                    <p class="mt-1 whitespace-pre-line text-sm leading-relaxed">{{ Str::limit($snapshot->content, 650) }}</p>
                                </div>
                                <div class="flex flex-wrap gap-3 text-xs text-slate-500 dark:text-slate-400">
                                    <span>Feedback +: {{ $snapshot->positive_feedback_count }}</span>
                                    <span>Feedback -: {{ $snapshot->negative_feedback_count }}</span>
                                    <span>Last feedback: {{ optional($snapshot->last_feedback_at)->diffForHumans() ?? '—' }}</span>
                                    <span>Terakhir review: {{ optional($lastReview?->created_at)->diffForHumans() ?? 'Belum' }}</span>
                                </div>

                                <div class="rounded-2xl border border-slate-200/60 bg-white/70 p-3 text-xs text-slate-600 dark:border-slate-700/60 dark:bg-slate-900/50 dark:text-slate-300">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Kriteria Auto-Promote</p>
                                    <div class="mt-2 grid gap-2 md:grid-cols-3">
                                        <div class="rounded-xl border border-slate-100/70 bg-white/70 p-2 text-center shadow-sm dark:border-slate-800/70 dark:bg-slate-900/50">
                                            <p class="text-[10px] uppercase tracking-wide text-slate-400">Feedback Positif</p>
                                            <p class="text-lg font-semibold text-slate-800 dark:text-white">{{ $snapshot->positive_feedback_count }}/2</p>
                                        </div>
                                        <div class="rounded-xl border border-slate-100/70 bg-white/70 p-2 text-center shadow-sm dark:border-slate-800/70 dark:bg-slate-900/50">
                                            <p class="text-[10px] uppercase tracking-wide text-slate-400">Label PASS</p>
                                            <p class="text-base font-semibold {{ $hasPassLabel ? 'text-emerald-600 dark:text-emerald-300' : 'text-slate-500 dark:text-slate-400' }}">
                                                {{ $hasPassLabel ? 'Ya' : 'Belum' }}
                                            </p>
                                        </div>
                                        <div class="rounded-xl border border-slate-100/70 bg-white/70 p-2 text-center shadow-sm dark:border-slate-800/70 dark:bg-slate-900/50">
                                            <p class="text-[10px] uppercase tracking-wide text-slate-400">Regression</p>
                                            <p class="text-base font-semibold {{ $regressionPassed ? 'text-emerald-600 dark:text-emerald-300' : 'text-slate-500 dark:text-slate-400' }}">
                                                {{ $regressionPassed ? 'PASS' : 'Menunggu' }}
                                            </p>
                                        </div>
                                    </div>
                                    <p class="mt-2 text-[11px] text-slate-500 dark:text-slate-400">
                                        Alasan auto-ready: {{ $autoReasons === [] ? 'Belum memenuhi syarat otomasi.' : implode(', ', $autoReasons) }}
                                    </p>
                                </div>

                                @if ($snapshot->reviews->isNotEmpty())
                                    <div class="rounded-2xl border border-slate-200/60 bg-slate-50/70 p-3 text-xs text-slate-600 shadow-inner dark:border-slate-800/70 dark:bg-slate-900/40 dark:text-slate-300">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Audit Trail</p>
                                        <ul class="mt-2 space-y-2">
                                            @foreach ($snapshot->reviews as $review)
                                                <li class="flex flex-col gap-0.5">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="rounded-full bg-slate-200/80 px-2 py-0.5 text-[11px] uppercase tracking-wide text-slate-600 dark:bg-slate-800/60 dark:text-slate-200">
                                                            {{ $review->action }}
                                                        </span>
                                                        <span class="text-[11px] text-slate-400 dark:text-slate-500">{{ optional($review->created_at)->diffForHumans() ?? '—' }}</span>
                                                    </div>
                                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                                        {{ $review->notes ?? 'Tidak ada catatan.' }}
                                                    </p>
                                                    <p class="text-[10px] text-slate-400 dark:text-slate-500">
                                                        Oleh: {{ $review->user?->name ?? 'System' }} (ID: {{ $review->user_id ?? '—' }})
                                                    </p>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                            <div class="flex flex-col gap-2 text-sm font-medium">
                                <button type="button" wire:click="promoteSnapshot({{ $snapshot->id }}, 'fact')" class="inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-emerald-700 transition hover:border-emerald-400 hover:bg-emerald-100 dark:border-emerald-600/40 dark:bg-emerald-500/10 dark:text-emerald-200">
                                    Promote Fact Patch
                                </button>
                                <button type="button" wire:click="promoteSnapshot({{ $snapshot->id }}, 'kb')" class="inline-flex items-center justify-center gap-2 rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-sky-700 transition hover:border-sky-400 hover:bg-sky-100 dark:border-sky-600/40 dark:bg-sky-500/10 dark:text-sky-200">
                                    Promote KB Article
                                </button>
                                <button type="button" wire:click="markEvaluationPass({{ $snapshot->id }})" class="inline-flex items-center justify-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2 text-indigo-700 transition hover:border-indigo-400 hover:bg-indigo-100 dark:border-indigo-600/40 dark:bg-indigo-500/10 dark:text-indigo-200">
                                    Tandai PASS
                                </button>
                                <button type="button" wire:click="toggleAutoReady({{ $snapshot->id }})" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-slate-600 transition hover:border-slate-400 hover:bg-slate-100 dark:border-slate-700/60 dark:bg-slate-800/60 dark:text-slate-200">
                                    {{ $snapshot->auto_promote_ready ? 'Matikan auto-ready' : 'Paksa auto-ready' }}
                                </button>
                                <div class="flex gap-2">
                                    <button type="button" wire:click="markNeedsReview({{ $snapshot->id }})" class="flex-1 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-amber-700 transition hover:border-amber-400 hover:bg-amber-100 dark:border-amber-600/40 dark:bg-amber-500/10 dark:text-amber-200">
                                        Butuh Review
                                    </button>
                                    <button type="button" wire:click="dismissSnapshot({{ $snapshot->id }})" class="flex-1 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-rose-700 transition hover:border-rose-400 hover:bg-rose-100 dark:border-rose-600/40 dark:bg-rose-500/10 dark:text-rose-200">
                                        Tolak
                                    </button>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div>
                {{ $snapshots->links() }}
            </div>
        @endif
    </section>
</div>
