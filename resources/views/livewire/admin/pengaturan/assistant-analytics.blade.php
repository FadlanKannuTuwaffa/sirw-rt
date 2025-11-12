@php
    use Illuminate\Support\Str;

    $title = 'Pengaturan';
    $titleClass = 'text-white';
    $maxTrend = max(1, collect($trendSeries)->max(fn ($item) => $item['total']));
@endphp

<div class="space-y-6 font-['Inter'] text-slate-800 dark:text-slate-100" data-settings-page data-admin-stack wire:poll.keep-alive.20s>
    <x-admin.settings-nav current="assistant-analytics" />

    <section id="recent-interactions" class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition-colors duration-300 dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card>
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="space-y-3">
                <p class="text-sm font-medium text-sky-600 dark:text-sky-300">Kinerja Asisten Warga</p>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Pantau kualitas jawaban & pemakaian tool</h1>
                <p class="max-w-2xl text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                    Insight berikut merangkum interaksi terbaru, efektivitas intent handler, serta performa pemanggilan tool. Gunakan data ini untuk mengoptimalkan konten RAG dan alur percakapan.
                </p>
            </div>
        <div class="flex flex-wrap gap-3 text-xs text-slate-500 dark:text-slate-400">
            <span class="inline-flex items-center gap-2 rounded-full border border-slate-200/80 px-3 py-1 dark:border-slate-700">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                {{ $summary['today'] }} interaksi hari ini
            </span>
            <span class="inline-flex items-center gap-2 rounded-full border border-slate-200/80 px-3 py-1 dark:border-slate-700">
                <span class="h-2 w-2 rounded-full bg-sky-500"></span>
                {{ $summary['last_hour'] }} interaksi 60 menit terakhir
            </span>
            <a href="{{ route('admin.settings.dummy-monitor') }}"
               class="inline-flex items-center gap-2 rounded-full border border-sky-200/80 bg-white px-4 py-1 text-xs font-semibold text-sky-600 shadow-sm transition hover:bg-sky-50 dark:border-slate-700 dark:bg-slate-900/70 dark:text-sky-300">
                Monitor DummyClient →
            </a>
        </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <article class="group space-y-2 rounded-2xl border border-slate-100 bg-white/70 p-4 shadow-sm transition dark:border-slate-800 dark:bg-slate-900/60">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total interaksi</p>
                <p class="text-3xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format($summary['total']) }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Log tersimpan sejak implementasi analytics.</p>
            </article>
            <article class="group space-y-2 rounded-2xl border border-emerald-100 bg-emerald-50/70 p-4 shadow-sm transition dark:border-emerald-600/30 dark:bg-emerald-900/40">
                <p class="text-sm font-medium text-emerald-600 dark:text-emerald-300">Tingkat keberhasilan</p>
                <p class="text-3xl font-semibold text-emerald-700 dark:text-emerald-200">{{ number_format($summary['success_rate'], 1) }}%</p>
                <p class="text-xs text-emerald-700/80 dark:text-emerald-200/80">{{ number_format($summary['success_count']) }} percakapan selesai dengan respons memenuhi.</p>
            </article>
            <article class="group space-y-2 rounded-2xl border border-sky-100 bg-sky-50/70 p-4 shadow-sm transition dark:border-sky-600/30 dark:bg-sky-900/40">
                <p class="text-sm font-medium text-sky-600 dark:text-sky-300">Pemanggilan tool</p>
                <p class="text-3xl font-semibold text-sky-700 dark:text-sky-200">{{ number_format($summary['tool_usage_rate'], 1) }}%</p>
                <p class="text-xs text-sky-700/80 dark:text-sky-200/80">{{ number_format($summary['tool_usage_count']) }} interaksi memanfaatkan data real-time.</p>
            </article>
            <article class="group space-y-2 rounded-2xl border border-violet-100 bg-violet-50/70 p-4 shadow-sm transition dark:border-violet-600/30 dark:bg-violet-900/40">
                <p class="text-sm font-medium text-violet-600 dark:text-violet-300">Jawaban lewat LLM</p>
                <p class="text-3xl font-semibold text-violet-700 dark:text-violet-200">{{ number_format($summary['llm_usage_rate'], 1) }}%</p>
                <p class="text-xs text-violet-700/80 dark:text-violet-200/80">{{ number_format($summary['llm_usage_count']) }} percakapan ditangani model LLM eksternal.</p>
            </article>
            <article class="group space-y-2 rounded-2xl border border-amber-100 bg-amber-50/70 p-4 shadow-sm transition dark:border-amber-500/30 dark:bg-amber-900/40">
                <p class="text-sm font-medium text-amber-600 dark:text-amber-300">Rata-rata waktu respons</p>
                <p class="text-3xl font-semibold text-amber-700 dark:text-amber-200">{{ $summary['average_duration_human'] }}</p>
                <p class="text-xs text-amber-700/80 dark:text-amber-200/80">Durasi dihitung dari permintaan diterima sampai balasan final.</p>
            </article>
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition-colors duration-300 dark:border-slate-800/60 dark:bg-slate-900/70 lg:col-span-2" data-motion-card>
            <header class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Tren 14 hari</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Bandingkan total permintaan dan tingkat keberhasilan harian.</p>
                </div>
            </header>
            @php
                $trendCollection = collect($trendSeries ?? []);
                if ($trendCollection->isEmpty()) {
                    $trendCollection = collect([
                        ['date' => '--', 'total' => 0, 'success' => 0],
                    ]);
                }

                $trendLabels = $trendCollection
                    ->pluck('date')
                    ->map(fn ($date) => $date ?? '--')
                    ->toArray();

                $trendTotalValues = $trendCollection
                    ->pluck('total')
                    ->map(fn ($value) => (int) max(0, $value ?? 0))
                    ->toArray();

                $trendSuccessValues = $trendCollection
                    ->pluck('success')
                    ->map(fn ($value) => (int) max(0, $value ?? 0))
                    ->toArray();

                $trendSummaries = $trendCollection->map(function ($row) {
                    $total = (int) max(0, $row['total'] ?? 0);
                    $success = (int) max(0, $row['success'] ?? 0);

                    return [
                        'date' => $row['date'] ?? '--',
                        'total' => $total,
                        'success' => $success,
                        'rate' => $total > 0 ? round(($success / max($total, 1)) * 100) : 0,
                    ];
                });

                $trendChartPayload = [
                    'labels' => $trendLabels,
                    'data' => [
                        'total' => $trendTotalValues,
                        'success' => $trendSuccessValues,
                    ],
                ];
            @endphp

            <div class="mt-6 space-y-6">
                <div class="relative overflow-hidden rounded-2xl border border-slate-100/80 bg-white/80 p-4 dark:border-slate-800/60 dark:bg-slate-900/60" wire:ignore>
                    <div class="absolute inset-0 z-10 hidden items-center justify-center bg-white/80 backdrop-blur-sm dark:bg-slate-900/80" data-chart-loader="trend">
                        <div class="flex flex-col items-center gap-2">
                            <div class="h-8 w-8 animate-spin rounded-full border-4 border-slate-200 border-t-sky-500 dark:border-slate-700 dark:border-t-sky-400"></div>
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Memperbarui data...</p>
                        </div>
                    </div>
                    <canvas id="assistant-trend-chart" class="h-48 w-full md:h-56"></canvas>
                </div>

                <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500 dark:text-slate-400">
                    <span class="inline-flex items-center gap-2 rounded-full border border-slate-200/70 px-3 py-1 dark:border-slate-700/80">
                        <span class="h-2 w-2 rounded-full bg-sky-400"></span>
                        Total permintaan
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full border border-slate-200/70 px-3 py-1 dark:border-slate-700/80">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        Respons sukses
                    </span>
                </div>

                <div class="grid gap-3 text-xs text-slate-500 dark:text-slate-400 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($trendSummaries as $entry)
                        <article class="flex items-center justify-between rounded-xl border border-slate-100/70 bg-white/80 px-3 py-3 shadow-sm transition hover:border-sky-200 dark:border-slate-800/60 dark:bg-slate-900/60">
                            <div>
                                <p class="font-semibold text-slate-700 dark:text-slate-200">{{ $entry['date'] }}</p>
                                <p class="mt-1 text-[11px] font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">Sukses {{ $entry['rate'] }}%</p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-slate-700 dark:text-slate-100">{{ number_format($entry['total']) }} total</p>
                                <p class="text-emerald-600 dark:text-emerald-300">{{ number_format($entry['success']) }} sukses</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition-colors duration-300 dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card>
            <header class="mb-6">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Distribusi channel</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Sumber jawaban yang dipakai asisten.</p>
            </header>
            <div class="space-y-3">
                @forelse ($channelStats as $channel)
                    <article class="rounded-xl border border-slate-100/70 bg-white/70 p-4 shadow-sm transition hover:shadow-md dark:border-slate-800/60 dark:bg-slate-900/60">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $channel['label'] }}</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ number_format($channel['count']) }} percakapan</p>
                            </div>
                            <span class="text-lg font-bold text-slate-700 dark:text-slate-200">{{ number_format($channel['share'], 1) }}%</span>
                        </div>
                    </article>
                @empty
                    <p class="text-sm text-slate-500 dark:text-slate-400">Belum ada data interaksi.</p>
                @endforelse
            </div>
        </section>
    </div>

    <section id="llm-providers" class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition-colors duration-300 dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card>
            <header class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Penyedia LLM</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Pantau distribusi jawaban antar model dan lihat siapa yang paling sering menangani percakapan.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2 text-sm sm:justify-end">
                    <a href="{{ route('admin.settings.general') }}#assistant-llm" class="inline-flex items-center gap-2 rounded-full border border-slate-200/60 px-3 py-1.5 text-slate-600 transition hover:border-sky-400 hover:text-sky-600 dark:border-slate-700 dark:text-slate-300 dark:hover:border-sky-500 dark:hover:text-sky-300">
                        <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-sky-500/15 text-xs text-sky-600 dark:bg-sky-500/25 dark:text-sky-300">⚙</span>
                        Kelola prioritas provider
                    </a>
                    <a href="#recent-interactions" class="inline-flex items-center gap-2 rounded-full border border-slate-200/60 px-3 py-1.5 text-slate-600 transition hover:border-emerald-400 hover:text-emerald-600 dark:border-slate-700 dark:text-slate-300 dark:hover:border-emerald-500 dark:hover:text-emerald-300">
                        <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-emerald-500/15 text-xs text-emerald-600 dark:bg-emerald-500/25 dark:text-emerald-300">⏱</span>
                        Lihat log interaksi
                    </a>
                </div>
            </header>

            @php
                $topProvider = $llmHighlights['top_provider'] ?? null;
                $topProviderName = $topProvider['provider'] ?? null;
                $topProviderEnabled = $topProvider['enabled'] ?? true;
                $activeProviders = $llmHighlights['active_count'] ?? 0;
                $totalUsage = $llmHighlights['total_usage'] ?? 0;
                $latestSnapshot = $llmHighlights['last_snapshot'] ?? null;
                $delta = $llmHighlights['delta'] ?? null;
            @endphp

            <div class="space-y-6">
                <article class="rounded-2xl border border-violet-100 bg-violet-50/70 p-5 shadow-sm dark:border-violet-600/30 dark:bg-violet-900/40">
                    <p class="text-xs font-semibold uppercase tracking-wide text-violet-600 dark:text-violet-300">Highlight</p>
                    <div class="mt-3 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                                {{ $topProviderName ? "Performa terbaik: {$topProviderName}" : 'Belum ada provider aktif' }}
                            </h3>
                            <p class="mt-1 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                                @if ($topProviderName)
                                    @if (! $topProviderEnabled)
                                        Provider ini memimpin historis, namun saat ini dinonaktifkan. Aktifkan kembali jika kuota sudah stabil.
                                    @else
                                        Provider ini memimpin jumlah percakapan selama periode 14 hari terakhir.
                                    @endif
                                @else
                                    Begitu percakapan pertama tercatat, highlight provider akan tampil otomatis.
                                @endif
                            </p>
                            <p class="mt-3 text-xs text-slate-500 dark:text-slate-300">
                                Urutan fallback saat ini:
                                <span class="font-medium text-violet-700 dark:text-violet-200">{{ implode(' → ', $llmHighlights['provider_order']) }}</span>
                            </p>
                        </div>
                        <div class="grid min-w-[220px] gap-3 sm:grid-cols-3 lg:grid-cols-1">
                            <div class="rounded-xl border border-white/70 bg-white/80 px-4 py-3 text-left shadow-sm dark:border-violet-700/50 dark:bg-violet-950/40">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Provider aktif</p>
                                <p class="mt-1 text-xl font-semibold text-slate-900 dark:text-slate-100">{{ $activeProviders }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">dari {{ count($llmHighlights['provider_order']) }} provider tersedia</p>
                            </div>
                            <div class="rounded-xl border border-white/70 bg-white/80 px-4 py-3 text-left shadow-sm dark:border-violet-700/50 dark:bg-violet-950/40">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Volume 14 hari</p>
                                <p class="mt-1 text-xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format($totalUsage) }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">percakapan dialihkan ke LLM</p>
                            </div>
                            @if ($latestSnapshot)
                                <div class="rounded-xl border border-white/70 bg-white/80 px-4 py-3 text-left shadow-sm dark:border-violet-700/50 dark:bg-violet-950/40">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Snapshot terakhir</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $latestSnapshot['date'] }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ number_format($latestSnapshot['total']) }} percakapan</p>
                                    @if ($delta)
                                        <p class="mt-2 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide {{ $delta['direction'] === 'up' ? 'text-emerald-600 dark:text-emerald-300' : 'text-rose-500 dark:text-rose-300' }}">
                                            {{ $delta['direction'] === 'up' ? '▲ +'.number_format($delta['value']) : '▼ '.number_format($delta['value']) }} dibanding hari sebelumnya
                                        </p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </article>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @forelse ($llmStats as $stat)
                        @php
                            $share = number_format($stat['share'], 1);
                            $barWidth = max(0, min(100, $stat['share']));
                            $isTop = $topProvider && $topProvider['provider'] === $stat['provider'] && ($stat['count'] ?? 0) > 0;
                            $isEnabled = $stat['enabled'] ?? true;
                        @endphp
                        <article class="group relative min-w-0 rounded-xl border border-slate-100/70 bg-white/80 p-4 shadow-sm transition hover:-translate-y-1 hover:border-sky-300 hover:shadow-lg dark:border-slate-800/60 dark:bg-slate-900/60 dark:hover:border-sky-500">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 space-y-1">
                                    <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $stat['provider'] }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ number_format($stat['count']) }} percakapan</p>
                                </div>
                                <div class="flex flex-col items-end gap-2">
                                    <span class="shrink-0 text-sm font-medium text-slate-700 dark:text-slate-200">{{ $share }}%</span>
                                    <button
                                        type="button"
                                        wire:click="toggleProvider('{{ $stat['provider'] }}')"
                                        class="inline-flex items-center gap-1 rounded-full border border-slate-200/70 px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-slate-600 transition hover:border-sky-400 hover:text-sky-600 dark:border-slate-700 dark:text-slate-300 dark:hover:border-sky-500 dark:hover:text-sky-300"
                                    >
                                        {{ $isEnabled ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </div>
                            </div>
                            <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-slate-200/60 dark:bg-slate-700/60">
                                <div class="{{ $isTop ? 'bg-violet-500 dark:bg-violet-400' : 'bg-sky-500/80 dark:bg-sky-400/70' }} h-full transition-all duration-500" style="width: {{ $barWidth }}%; opacity: {{ $isEnabled ? '1' : '0.35' }};"></div>
                            </div>
                            @if ($isTop)
                                <p class="mt-2 inline-flex items-center gap-1 rounded-full bg-violet-50/80 px-2 py-1 text-[11px] font-semibold uppercase tracking-wide text-violet-600 dark:bg-violet-900/50 dark:text-violet-200">
                                    ✦ Terpopuler
                                </p>
                            @elseif (($stat['count'] ?? 0) === 0 && $isEnabled)
                                <p class="mt-2 inline-flex items-center gap-1 rounded-full bg-slate-100/70 px-2 py-1 text-[11px] font-medium uppercase tracking-wide text-slate-500 dark:bg-slate-800/50 dark:text-slate-400">
                                    Menunggu data
                                </p>
                            @endif
                            <p class="mt-2 inline-flex items-center gap-1 rounded-full px-2 py-1 text-[11px] font-semibold uppercase tracking-wide {{ $isEnabled ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-slate-100 text-slate-500 dark:bg-slate-800/60 dark:text-slate-400' }}">
                                {{ $isEnabled ? 'Aktif' : 'Dinonaktifkan' }}
                            </p>
                            @unless($isEnabled)
                                <div class="pointer-events-none absolute inset-0 rounded-xl bg-white/70 backdrop-blur-[1px] dark:bg-slate-900/70"></div>
                            @endunless
                        </article>
                    @empty
                        <p class="text-sm text-slate-500 dark:text-slate-400">Belum ada interaksi yang menggunakan LLM.</p>
                    @endforelse
                </div>
            </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition-colors duration-300 dark:border-slate-800/60 dark:bg-slate-900/70 assistant-analytics-intents" data-motion-card>
            <header class="mb-6">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Intent teratas</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Topik yang paling sering ditanyakan warga.</p>
            </header>
            <div class="space-y-3 assistant-analytics-intents__list">
                @forelse ($topIntents as $intent)
                    <article class="flex items-center justify-between rounded-xl border border-slate-100/70 bg-white/70 px-4 py-3 shadow-sm transition hover:shadow-md dark:border-slate-800/60 dark:bg-slate-900/60 assistant-analytics-intents__item">
                        <div>
                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $intent['intent'] }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ number_format($intent['count']) }} permintaan</p>
                        </div>
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ number_format($intent['share'], 1) }}%</span>
                    </article>
                @empty
                    <p class="text-sm text-slate-500 dark:text-slate-400">Belum ada data intent.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition-colors duration-300 dark:border-slate-800/60 dark:bg-slate-900/70 lg:col-span-2 assistant-analytics-tools" data-motion-card>
            <header class="mb-6">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Performa tool</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Tool yang paling sering dipanggil beserta tingkat keberhasilannya.</p>
            </header>
            <div class="overflow-x-auto rounded-xl border border-slate-100 dark:border-slate-800 assistant-analytics-tools__table-wrapper">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700 assistant-analytics-tools__table">
                    <thead class="bg-slate-50 dark:bg-slate-900/60">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Tool</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Jumlah</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">Keberhasilan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($toolStats as $tool)
                            <tr class="bg-white transition hover:bg-slate-50 dark:bg-slate-900/40 dark:hover:bg-slate-900/60">
                                <td class="px-4 py-3 font-medium text-slate-800 dark:text-slate-200 assistant-analytics-tools__cell" data-label="Tool">
                                    <span class="assistant-analytics-tools__value assistant-analytics-tools__value--name">{{ $tool['name'] }}</span>
                                </td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300 assistant-analytics-tools__cell" data-label="Jumlah">
                                    <span class="assistant-analytics-tools__value">{{ number_format($tool['count']) }}</span>
                                </td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300 assistant-analytics-tools__cell" data-label="Keberhasilan">
                                    <span class="assistant-analytics-tools__value">{{ number_format($tool['success_rate'], 1) }}%</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">Belum ada data pemanggilan tool.</td>
            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    @if (!empty($llmTrend['series']))
        @php
            $llmTrendProviders = collect($llmTrend['providers'] ?? [])->values();
            $llmTrendSeries = collect($llmTrend['series'] ?? [])->values();
            $latestSnapshot = $llmHighlights['last_snapshot'] ?? null;
            $delta = $llmHighlights['delta'] ?? null;

            $llmChartLabels = $llmTrendSeries
                ->pluck('date')
                ->map(fn ($date) => $date ?? '--')
                ->toArray();

            $llmTotalValues = $llmTrendSeries
                ->pluck('total')
                ->map(fn ($value) => (int) max(0, $value ?? 0))
                ->toArray();

            if (empty($llmChartLabels)) {
                $llmChartLabels = ['--'];
            }

            if (empty($llmTotalValues)) {
                $llmTotalValues = array_fill(0, count($llmChartLabels), 0);
            }

            $llmPalette = [
                '#38bdf8',
                '#a855f7',
                '#22c55e',
                '#f97316',
                '#6366f1',
                '#ec4899',
                '#14b8a6',
                '#f87171',
                '#facc15',
                '#0ea5e9',
            ];

            $llmProviderDatasets = $llmTrendProviders
                ->map(function ($provider, $index) use ($llmTrendSeries, $llmPalette) {
                    $dataCollection = $llmTrendSeries
                        ->map(fn ($row) => (int) max(0, $row['data'][$provider] ?? 0));
                    $color = $llmPalette[$index % count($llmPalette)];

                    return [
                        'key' => $provider,
                        'label' => Str::headline(str_replace('_', ' ', $provider)),
                        'color' => $color,
                        'data' => $dataCollection->toArray(),
                        'total' => $dataCollection->sum(),
                        'max' => $dataCollection->max() ?? 0,
                    ];
                })
                ->sortByDesc('total')
                ->values();

            $llmProviderColorMap = $llmProviderDatasets
                ->mapWithKeys(fn ($dataset) => [$dataset['key'] => $dataset['color']])
                ->all();

            $llmDailyHighlights = $llmTrendSeries
                ->map(function ($row) use ($llmProviderColorMap) {
                    $total = (int) max(0, $row['total'] ?? 0);
                    $data = collect($row['data'] ?? []);
                    $nonZero = $data->filter(fn ($value) => $value > 0)->sortDesc();
                    $topKey = $nonZero->keys()->first();

                    return [
                        'date' => $row['date'] ?? '--',
                        'total' => $total,
                        'top_provider' => $topKey,
                        'top_value' => $topKey ? (int) max(0, $nonZero[$topKey] ?? 0) : 0,
                        'providers' => $nonZero
                            ->map(function ($value, $key) use ($llmProviderColorMap) {
                                return [
                                    'label' => Str::headline(str_replace('_', ' ', $key)),
                                    'value' => (int) max(0, $value),
                                    'color' => $llmProviderColorMap[$key] ?? '#94a3b8',
                                ];
                            })
                            ->values(),
                    ];
                })
                ->values();

            $llmTotalAggregate = collect($llmTotalValues)->sum();

            $llmChartPayload = [
                'labels' => $llmChartLabels,
                'total' => $llmTotalValues,
                'providers' => $llmProviderDatasets
                    ->map(function ($dataset) {
                        return [
                            'key' => $dataset['key'],
                            'label' => $dataset['label'],
                            'color' => $dataset['color'],
                            'data' => $dataset['data'],
                        ];
                    })
                    ->values(),
            ];
        @endphp
        <section id="llm-trend" class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition-colors duration-300 dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card>
            <header class="mb-4 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Trend penggunaan LLM (14 hari)</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Pantau dinamika volume percakapan per provider untuk mendeteksi lonjakan permintaan atau penurunan performa.
                        @if ($latestSnapshot)
                            Snapshot terakhir: <span class="font-medium text-slate-700 dark:text-slate-200">{{ $latestSnapshot['date'] }}</span>
                            ({{ number_format($latestSnapshot['total']) }} percakapan).
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap gap-2 text-sm">
                    <a href="#llm-providers" class="inline-flex items-center gap-2 rounded-full border border-slate-200/60 px-3 py-1.5 text-slate-600 transition hover:border-sky-400 hover:text-sky-600 dark:border-slate-700 dark:text-slate-300 dark:hover:border-sky-500 dark:hover:text-sky-300">
                        ← Ringkasan provider
                    </a>
                </div>
            </header>
            @if ($delta)
                <div class="mb-4 inline-flex items-center gap-2 rounded-full bg-white/70 px-3 py-1.5 text-xs font-medium uppercase tracking-wide {{ $delta['direction'] === 'up' ? 'text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-200' : 'text-rose-500 dark:bg-rose-900/40 dark:text-rose-200' }}">
                    {{ $delta['direction'] === 'up' ? '▲ Kenaikan ' . number_format($delta['value']) : '▼ Penurunan ' . number_format(abs($delta['value'])) }} percakapan dibanding hari sebelumnya
                </div>
            @endif
            <div class="space-y-6">
                <div class="relative overflow-hidden rounded-2xl border border-slate-100/80 bg-white/80 p-4 dark:border-slate-800/60 dark:bg-slate-900/60" wire:ignore>
                    <div class="absolute inset-0 z-10 hidden items-center justify-center bg-white/80 backdrop-blur-sm dark:bg-slate-900/80" data-chart-loader="llm">
                        <div class="flex flex-col items-center gap-2">
                            <div class="h-8 w-8 animate-spin rounded-full border-4 border-slate-200 border-t-violet-500 dark:border-slate-700 dark:border-t-violet-400"></div>
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Memperbarui data...</p>
                        </div>
                    </div>
                    <canvas id="assistant-llm-chart" class="h-56 w-full md:h-64"></canvas>
                </div>

                <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
                    <span class="inline-flex items-center gap-2 rounded-full border border-slate-200/70 px-3 py-1 dark:border-slate-700/70">
                        <span class="h-2 w-2 rounded-full bg-slate-700 dark:bg-slate-200"></span>
                        Total percakapan
                        <span class="font-semibold text-slate-600 dark:text-slate-200">{{ number_format($llmTotalAggregate) }}</span>
                    </span>
                    @foreach ($llmProviderDatasets as $dataset)
                        @php
                            $usageClass = $dataset['total'] > 0 ? 'font-semibold text-slate-600 dark:text-slate-200' : 'text-slate-400 dark:text-slate-500';
                        @endphp
                        <span class="inline-flex items-center gap-2 rounded-full border border-slate-200/70 px-3 py-1 dark:border-slate-700/70">
                            <span class="h-2 w-2 rounded-full" style="background-color: {{ $dataset['color'] }};"></span>
                            {{ $dataset['label'] }}
                            <span class="{{ $usageClass }}">{{ number_format($dataset['total']) }}</span>
                        </span>
                    @endforeach
                </div>

                @if ($llmDailyHighlights->isNotEmpty())
                    <div class="grid gap-3 text-xs text-slate-500 dark:text-slate-400 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($llmDailyHighlights as $day)
                            <article class="space-y-3 rounded-xl border border-slate-100/70 bg-white/80 p-4 shadow-sm transition hover:border-violet-200 dark:border-slate-800/60 dark:bg-slate-900/60">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $day['date'] }}</p>
                                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-100">{{ number_format($day['total']) }} total</span>
                                </div>
                                @if ($day['top_provider'])
                                    <p class="text-[11px] uppercase tracking-wide text-slate-400 dark:text-slate-500">
                                        Terbanyak:
                                        <span class="font-semibold text-slate-600 dark:text-slate-200">
                                            {{ Str::headline(str_replace('_', ' ', $day['top_provider'])) }}
                                        </span>
                                        <span class="text-slate-500 dark:text-slate-400">({{ number_format($day['top_value']) }})</span>
                                    </p>
                                @else
                                    <p class="text-[11px] uppercase tracking-wide text-slate-400 dark:text-slate-500">Belum ada penggunaan LLM.</p>
                                @endif

                                @if ($day['providers']->isNotEmpty())
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($day['providers'] as $provider)
                                            <span class="inline-flex items-center gap-1 rounded-full border px-2 py-1 text-[11px]" style="border-color: {{ $provider['color'] }}; color: {{ $provider['color'] }};">
                                                <span class="h-1.5 w-1.5 rounded-full" style="background-color: {{ $provider['color'] }};"></span>
                                                {{ $provider['label'] }}
                                                <span class="font-semibold">{{ number_format($provider['value']) }}</span>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endif

    <section class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition-colors duration-300 dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card>
        <header class="mb-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Log interaksi terbaru</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Cuplikan 12 percakapan terakhir dari warga.</p>
        </header>
        <div class="space-y-3">
            @forelse ($recentInteractions as $interaction)
                <article class="rounded-xl border border-slate-100/70 bg-white/70 p-4 shadow-sm transition hover:border-sky-300 hover:shadow-md dark:border-slate-800/60 dark:bg-slate-900/60">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div class="space-y-2">
                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $interaction->query }}</p>
                            <div class="flex flex-wrap gap-2 text-xs">
                                @foreach ((array) $interaction->intents as $intent)
                                    <span class="inline-flex items-center rounded-full bg-sky-100 px-3 py-1 font-medium text-sky-700 dark:bg-sky-900/60 dark:text-sky-200">
                                        {{ Str::headline(str_replace('_', ' ', $intent)) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        <div class="text-right text-xs text-slate-500 dark:text-slate-400">
                            <p>{{ $interaction->created_at->diffForHumans() }}</p>
                            <p>{{ $interaction->duration_ms ? number_format($interaction->duration_ms, 0) . ' ms' : '—' }}</p>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500 dark:text-slate-400">
                        <span>
                            Channel: <span class="font-medium text-slate-700 dark:text-slate-300">{{ Str::headline(str_replace('_', ' ', $interaction->responded_via)) }}</span>
                            @if ($interaction->success)
                                <span class="ml-2 inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 font-medium text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300">Sukses</span>
                            @else
                                <span class="ml-2 inline-flex items-center gap-1 rounded-full bg-rose-100 px-2 py-0.5 font-medium text-rose-700 dark:bg-rose-900/60 dark:text-rose-300">Butuh tindak lanjut</span>
                            @endif
                            @if (!empty($interaction->llm_provider))
                                <span class="ml-2 inline-flex items-center gap-1 rounded-full bg-violet-100 px-2 py-0.5 font-medium text-violet-700 dark:bg-violet-900/60 dark:text-violet-200">
                                    LLM: {{ $interaction->llm_provider }}
                                </span>
                            @endif
                        </span>
                        <span class="flex flex-wrap items-center gap-2">
                            @php
                                $tools = collect((array) $interaction->tool_calls)->map(fn ($call) => $call['name'] ?? 'unknown');
                            @endphp
                            @if ($tools->isNotEmpty())
                                <span>Tools: {{ $tools->implode(', ') }}</span>
                            @else
                                <span>Tanpa tool</span>
                            @endif
                        </span>
                    </div>
                </article>
            @empty
                <p class="text-sm text-slate-500 dark:text-slate-400">Belum ada interaksi yang terekam.</p>
            @endforelse
        </div>
    </section>

    @livewire('admin.pengaturan.assistant-corrections')

    <script type="application/json" id="assistant-analytics-config">
        {!! json_encode([
            'trend' => $trendChartPayload,
            'llm' => $llmChartPayload ?? null,
        ]) !!}
    </script>
    
</div>

@push('scripts')
    @once
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    @endonce
    <script>
        (function () {
            window.SIRW = window.SIRW || {};
            const chartStore = (window.SIRW.assistantAnalyticsCharts = window.SIRW.assistantAnalyticsCharts || {});
            chartStore.livewireHookRegistered = chartStore.livewireHookRegistered || false;
            chartStore.renderTimeout = null;
            chartStore.isRendering = false;

            function hexToRgba(hex, alpha) {
                const raw = hex.replace('#', '');
                const bigint = parseInt(raw.length === 3 ? raw.split('').map((c) => c + c).join('') : raw, 16);
                const r = (bigint >> 16) & 255;
                const g = (bigint >> 8) & 255;
                const b = bigint & 255;
                return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + alpha + ')';
            }

            function themePalette() {
                const isDark = document.documentElement.classList.contains('dark');
                return {
                    grid: isDark ? 'rgba(148, 163, 184, 0.18)' : 'rgba(148, 163, 184, 0.12)',
                    ticks: isDark ? '#cbd5f5' : '#64748b',
                    tooltipBg: isDark ? 'rgba(15, 23, 42, 0.92)' : 'rgba(255, 255, 255, 0.95)',
                    tooltipText: isDark ? '#f8fafc' : '#0f172a',
                    tooltipBorder: isDark ? 'rgba(148, 163, 184, 0.25)' : 'rgba(148, 163, 184, 0.25)',
                };
            }

            function destroyChart(key) {
                if (chartStore[key]) {
                    chartStore[key].destroy();
                    chartStore[key] = null;
                }
            }

            function readChartConfig() {
                const node = document.getElementById('assistant-analytics-config');
                if (!node) {
                    return { trend: null, llm: null };
                }

                try {
                    const payload = JSON.parse(node.textContent || '{}');
                    return {
                        trend: payload.trend ?? null,
                        llm: payload.llm ?? null,
                    };
                } catch (error) {
                    console.warn('[AssistantAnalytics] Gagal membaca konfigurasi chart', error);
                    return { trend: null, llm: null };
                }
            }

            function showLoader(type) {
                const loader = document.querySelector(`[data-chart-loader="${type}"]`);
                if (loader) {
                    loader.classList.remove('hidden');
                    loader.classList.add('flex');
                }
            }

            function hideLoader(type) {
                const loader = document.querySelector(`[data-chart-loader="${type}"]`);
                if (loader) {
                    loader.classList.add('hidden');
                    loader.classList.remove('flex');
                }
            }

            function renderTrendChart(cfg) {
                const canvas = document.getElementById('assistant-trend-chart');
                destroyChart('trend');

                if (!canvas || !cfg || !Array.isArray(cfg.labels)) {
                    hideLoader('trend');
                    return;
                }

                const palette = themePalette();
                const ctx = canvas.getContext('2d');

                chartStore.trend = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: cfg.labels,
                        datasets: [
                            {
                                label: 'Total permintaan',
                                data: cfg.data?.total ?? [],
                                borderColor: '#38bdf8',
                                backgroundColor: hexToRgba('#38bdf8', 0.18),
                                pointBackgroundColor: '#38bdf8',
                                pointBorderColor: '#ffffff',
                                borderWidth: 2,
                                tension: 0.35,
                                pointRadius: 3,
                                pointHoverRadius: 5,
                                fill: true,
                            },
                            {
                                label: 'Respons sukses',
                                data: cfg.data?.success ?? [],
                                borderColor: '#22c55e',
                                backgroundColor: hexToRgba('#22c55e', 0.22),
                                pointBackgroundColor: '#22c55e',
                                pointBorderColor: '#ffffff',
                                borderWidth: 2,
                                tension: 0.35,
                                pointRadius: 3,
                                pointHoverRadius: 5,
                                fill: true,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false,
                            mode: 'nearest',
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: palette.tooltipBg,
                                borderColor: palette.tooltipBorder,
                                borderWidth: 1,
                                titleColor: palette.tooltipText,
                                bodyColor: palette.tooltipText,
                                padding: 12,
                                callbacks: {
                                    label(context) {
                                        const value = context.parsed.y ?? 0;
                                        return context.dataset.label + ': ' + value.toLocaleString('id-ID');
                                    },
                                },
                            },
                        },
                        scales: {
                            x: {
                                grid: { color: palette.grid },
                                ticks: { color: palette.ticks, maxRotation: 0, autoSkipPadding: 10 },
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: palette.grid },
                                ticks: { color: palette.ticks, precision: 0 },
                            },
                        },
                        animation: {
                            duration: 750,
                            easing: 'easeInOutQuart',
                        },
                    },
                });

                hideLoader('trend');
            }

            function renderLlmChart(cfg) {
                const canvas = document.getElementById('assistant-llm-chart');
                destroyChart('llm');

                if (!canvas || !cfg || !Array.isArray(cfg.labels)) {
                    hideLoader('llm');
                    return;
                }

                const palette = themePalette();
                const ctx = canvas.getContext('2d');

                const providerDatasets = (cfg.providers ?? []).map((provider) => ({
                    label: provider.label,
                    data: provider.data ?? [],
                    borderColor: provider.color,
                    backgroundColor: hexToRgba(provider.color, 0.16),
                    borderWidth: 1.8,
                    tension: 0.35,
                    pointRadius: 2.4,
                    pointHoverRadius: 4,
                    fill: false,
                }));

                const datasets = [
                    {
                        label: 'Total',
                        data: cfg.total ?? [],
                        borderColor: '#1e293b',
                        backgroundColor: hexToRgba('#1e293b', 0.08),
                        borderWidth: 2.2,
                        tension: 0.3,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        fill: false,
                    },
                    ...providerDatasets,
                ];

                chartStore.llm = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: cfg.labels,
                        datasets,
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false,
                            mode: 'nearest',
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: palette.tooltipBg,
                                borderColor: palette.tooltipBorder,
                                borderWidth: 1,
                                titleColor: palette.tooltipText,
                                bodyColor: palette.tooltipText,
                                padding: 12,
                                callbacks: {
                                    label(context) {
                                        const value = context.parsed.y ?? 0;
                                        return context.dataset.label + ': ' + value.toLocaleString('id-ID');
                                    },
                                },
                            },
                        },
                        scales: {
                            x: {
                                grid: { color: palette.grid },
                                ticks: { color: palette.ticks, maxRotation: 0, autoSkipPadding: 12 },
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: palette.grid },
                                ticks: { color: palette.ticks, precision: 0 },
                            },
                        },
                        animation: {
                            duration: 750,
                            easing: 'easeInOutQuart',
                        },
                    },
                });

                hideLoader('llm');
            }

            function renderAll() {
                if (typeof Chart === 'undefined' || chartStore.isRendering) {
                    return;
                }

                chartStore.isRendering = true;
                showLoader('trend');
                showLoader('llm');

                const config = readChartConfig();
                chartStore.config = config;

                requestAnimationFrame(() => {
                    renderTrendChart(config.trend);
                    renderLlmChart(config.llm);
                    chartStore.isRendering = false;
                });
            }

            function scheduleRender() {
                if (typeof Chart === 'undefined') {
                    return;
                }

                if (chartStore.renderTimeout) {
                    clearTimeout(chartStore.renderTimeout);
                }

                chartStore.renderTimeout = setTimeout(() => {
                    renderAll();
                    chartStore.renderTimeout = null;
                }, 150);
            }

            function resolveComponentElement(arg1, arg2) {
                if (arg2 && arg2.el instanceof HTMLElement) {
                    return arg2.el;
                }
                if (arg1 && arg1.el instanceof HTMLElement) {
                    return arg1.el;
                }
                if (arg1 && arg1.component && arg1.component.el instanceof HTMLElement) {
                    return arg1.component.el;
                }
                if (arg2 && arg2.component && arg2.component.el instanceof HTMLElement) {
                    return arg2.component.el;
                }

                return null;
            }

            document.addEventListener('analytics-data-updated', scheduleRender);
            document.addEventListener('sirw:theme-changed', scheduleRender);

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', renderAll, { once: true });
            } else {
                renderAll();
            }

            document.addEventListener('livewire:navigated', scheduleRender);
        })();
    </script>
@endpush

