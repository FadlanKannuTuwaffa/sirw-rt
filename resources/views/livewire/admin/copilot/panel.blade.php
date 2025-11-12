@php
    use Carbon\CarbonImmutable;

    $lastUpdatedLabel = $generatedAt
        ? CarbonImmutable::parse($generatedAt)->locale('id')->diffForHumans(null, true) . ' lalu'
        : 'Baru saja';

    $severityStyles = [
        'high' => 'border-rose-200/70 bg-rose-50/70 text-rose-700 dark:border-rose-500/40 dark:bg-rose-500/10 dark:text-rose-200',
        'medium' => 'border-amber-200/70 bg-amber-50/70 text-amber-700 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200',
        'info' => 'border-slate-200/70 bg-white/80 text-slate-600 dark:border-slate-700/50 dark:bg-slate-900/60 dark:text-slate-300',
    ];

@endphp

<div
    wire:ignore.self
    x-data="{}"
    data-copilot-root
    data-open="false"
    class="fixed inset-0 z-[80] flex items-center justify-center p-4 pointer-events-none opacity-0 transition-all duration-500 ease-out"
    aria-hidden="true"
>
    <div class="absolute inset-0 bg-gradient-to-br from-slate-900/60 via-slate-900/50 to-slate-800/60 opacity-0 transition-opacity duration-500 backdrop-blur-sm dark:from-slate-950/80 dark:via-slate-950/70 dark:to-slate-900/80" data-copilot-backdrop></div>
    <div class="relative h-full max-h-[90vh] w-full max-w-5xl scale-95 transform opacity-0 transition-all duration-500 ease-out will-change-transform" data-copilot-panel role="dialog" aria-modal="true" aria-label="Sesi CoPilot">
        <div class="flex h-full flex-col overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-2xl shadow-slate-900/20 ring-1 ring-slate-900/5 backdrop-blur-xl dark:border-slate-700/80 dark:bg-slate-900/95 dark:shadow-slate-900/60 dark:ring-slate-700/50 animate-in fade-in slide-in-from-bottom-4">
            <header class="flex items-start justify-between gap-4 border-b border-slate-200/70 bg-gradient-to-r from-sky-50/50 to-transparent px-6 py-5 dark:border-slate-700/70 dark:from-sky-950/20">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-sky-500 dark:text-sky-300">SIRW CoPilot</p>
                    <h2 class="mt-1 text-2xl font-semibold text-slate-900 dark:text-slate-100">Rekomendasi prioritas hari ini</h2>
                    <p class="text-xs text-slate-400 dark:text-slate-500">Pembaruan {{ $lastUpdatedLabel }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="group inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white/80 px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm transition-all duration-200 hover:scale-105 hover:border-sky-300 hover:bg-sky-50 hover:text-sky-600 hover:shadow-md active:scale-95 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-300 dark:hover:border-sky-500/40 dark:hover:bg-sky-950/30 dark:hover:text-sky-300"
                        wire:click="refreshData"
                    >
                        <svg class="h-4 w-4 transition-transform duration-300 group-hover:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001m-18 5.003a9 9 0 0 0 15.364 6.36L21 21m-9-18a9 9 0 0 0-9 9m9-9c2.282 0 4.352.856 5.916 2.258L21 3" />
                        </svg>
                        Segarkan
                    </button>
                    <button
                        type="button"
                        class="group inline-flex h-9 w-9 items-center justify-center rounded-full bg-slate-100/50 text-slate-400 transition-all duration-200 hover:scale-110 hover:bg-rose-100 hover:text-rose-600 active:scale-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-300 dark:bg-slate-800/50 dark:text-slate-500 dark:hover:bg-rose-950/30 dark:hover:text-rose-400 dark:focus-visible:ring-sky-600"
                        data-copilot-close
                        aria-label="Tutup CoPilot"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition-transform duration-200 group-hover:rotate-90" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </header>
            <div class="flex-1 overflow-y-auto scrollbar-thin scrollbar-thumb-slate-300 scrollbar-track-transparent hover:scrollbar-thumb-slate-400 dark:scrollbar-thumb-slate-700 dark:hover:scrollbar-thumb-slate-600">
                <div wire:loading.flex wire:target="refreshData" class="flex h-full flex-col gap-4 p-6">
                    <div class="h-12 animate-pulse rounded-2xl bg-gradient-to-r from-slate-100 via-slate-50 to-slate-100 dark:from-slate-800 dark:via-slate-850 dark:to-slate-800"></div>
                    <div class="grid flex-1 gap-4 md:grid-cols-2">
                        <div class="animate-pulse rounded-2xl bg-gradient-to-br from-slate-100 to-slate-50 dark:from-slate-800 dark:to-slate-850"></div>
                        <div class="hidden animate-pulse rounded-2xl bg-gradient-to-br from-slate-100 to-slate-50 dark:from-slate-800 dark:to-slate-850 md:block"></div>
                    </div>
                    <div class="h-40 animate-pulse rounded-3xl bg-gradient-to-r from-slate-100 via-slate-50 to-slate-100 dark:from-slate-800 dark:via-slate-850 dark:to-slate-800"></div>
                </div>
                <div wire:loading.remove wire:target="refreshData" class="flex h-full flex-col gap-6 p-6">
                    @if (count($alerts) > 0)
                        <section aria-label="Prioritas">
                            <header class="flex items-center justify-between">
                                <h3 class="text-sm font-semibold uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">Prioritas</h3>
                                <span class="text-xs text-slate-400 dark:text-slate-500">{{ count($alerts) }} insight genting</span>
                            </header>
                            <div class="mt-3 space-y-3">
                                @foreach ($alerts as $alert)
                                    @php
                                        $style = $severityStyles[$alert['severity']] ?? $severityStyles['info'];
                                    @endphp
                                    <article
                                        wire:key="copilot-alert-{{ $alert['id'] }}"
                                        class="group rounded-2xl border px-4 py-3 transition-all duration-300 hover:-translate-y-1 hover:scale-[1.02] hover:shadow-xl hover:shadow-slate-900/10 {{ $style }}"
                                    >
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <h4 class="text-sm font-semibold">{{ $alert['title'] }}</h4>
                                                <p class="mt-1 text-sm leading-relaxed">{{ $alert['description'] }}</p>
                                                @if (!empty($alert['tags']))
                                                    <div class="mt-3 flex flex-wrap gap-2 text-[11px] uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">
                                                        @foreach ($alert['tags'] as $tag)
                                                            <span class="rounded-full border border-current/20 px-2 py-0.5">{{ $tag }}</span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                            @if (!empty($alert['action']['label'] ?? null))
                                                <button
                                                    type="button"
                                                    class="inline-flex shrink-0 items-center gap-1 rounded-full bg-white/70 px-3 py-1 text-xs font-semibold text-slate-600 shadow-sm transition-all duration-200 hover:scale-105 hover:bg-white hover:shadow-md active:scale-95 dark:bg-slate-900/70 dark:text-slate-200 dark:hover:bg-slate-800"
                                                data-copilot-action="{{ $alert['action']['type'] ?? 'route' }}"
                                                data-copilot-action-payload="{{ $alert['action']['payload'] ?? '' }}"
                                                data-copilot-action-id="{{ $alert['id'] }}"
                                                @if (!empty($alert['action']['meta']['route'] ?? null)) data-copilot-meta-route="{{ $alert['action']['meta']['route'] }}" @endif
                                            >
                                                {{ $alert['action']['label'] }}
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                                </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    <section aria-label="Insight">
                        <header class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">Insight</h3>
                            <span class="text-xs text-slate-400 dark:text-slate-500">{{ count($insights) }} temuan</span>
                        </header>
                        <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($insights as $insight)
                                @php
                                    $style = $severityStyles[$insight['severity']] ?? $severityStyles['info'];
                                @endphp
                                <article
                                    wire:key="copilot-insight-{{ $insight['id'] }}"
                                    class="group flex h-full flex-col justify-between rounded-2xl border px-4 py-4 transition-all duration-300 hover:-translate-y-1 hover:scale-[1.02] hover:shadow-xl hover:shadow-slate-900/10 {{ $style }}"
                                >
                                    <div>
                                        <div class="flex items-start justify-between gap-3">
                                            <h4 class="text-base font-semibold">{{ $insight['title'] }}</h4>
                                            @if (!empty($insight['severity']))
                                                <span class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">{{ $insight['severity'] }}</span>
                                            @endif
                                        </div>
                                        <p class="mt-2 text-sm leading-relaxed">{{ $insight['description'] }}</p>
                                    </div>
                                    <div class="mt-4 flex items-center justify-between gap-3">
                                        @if (!empty($insight['tags']))
                                            <div class="flex flex-wrap gap-2 text-[11px] uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">
                                                @foreach ($insight['tags'] as $tag)
                                                    <span class="rounded-full border border-current/20 px-2 py-0.5">{{ $tag }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                        @if (!empty($insight['action']['label'] ?? null))
                                            <button
                                                type="button"
                                                class="inline-flex shrink-0 items-center gap-1 rounded-full bg-white/70 px-3 py-1 text-xs font-semibold text-slate-600 shadow-sm transition-all duration-200 hover:scale-105 hover:bg-white hover:shadow-md active:scale-95 dark:bg-slate-900/70 dark:text-slate-200 dark:hover:bg-slate-800"
                                                data-copilot-action="{{ $insight['action']['type'] ?? 'route' }}"
                                                data-copilot-action-payload="{{ $insight['action']['payload'] ?? '' }}"
                                                data-copilot-action-id="{{ $insight['id'] }}"
                                                @if (!empty($insight['action']['meta']['route'] ?? null)) data-copilot-meta-route="{{ $insight['action']['meta']['route'] }}" @endif
                                            >
                                                {{ $insight['action']['label'] }}
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>

                    <section aria-label="Tindakan cepat">
                        <header class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">Tindakan</h3>
                            <span class="text-xs text-slate-400 dark:text-slate-500">{{ count($actions) }} opsi</span>
                        </header>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($actions as $action)
                                <button
                                    wire:key="copilot-action-{{ $action['id'] }}"
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white/80 px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm transition-all duration-200 hover:scale-105 hover:border-sky-300 hover:bg-sky-50 hover:text-sky-600 hover:shadow-md active:scale-95 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300 dark:hover:border-sky-500/40 dark:hover:bg-sky-950/30 dark:hover:text-sky-300"
                                    data-copilot-action="{{ $action['type'] ?? 'route' }}"
                                    data-copilot-action-payload="{{ $action['payload'] ?? '' }}"
                                    data-copilot-action-id="{{ $action['id'] }}"
                                    @if (!empty($action['meta']['route'] ?? null)) data-copilot-meta-route="{{ $action['meta']['route'] }}" @endif
                                >
                                    {{ $action['label'] }}
                                </button>
                            @endforeach
                        </div>
                    </section>

                    <section aria-label="Linimasa">
                        <header class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">Linimasa</h3>
                            <span class="text-xs text-slate-400 dark:text-slate-500">Aktivitas terbaru warga & kas</span>
                        </header>
                        <ol class="mt-3 space-y-3">
                            @forelse ($timeline as $entry)
                                <li
                                    wire:key="copilot-timeline-{{ $entry['id'] }}"
                                    class="group flex items-start gap-3 rounded-2xl border border-slate-200/70 bg-white/70 px-4 py-3 text-sm text-slate-600 shadow-sm transition-all duration-300 hover:-translate-x-1 hover:border-slate-300 hover:bg-white hover:shadow-md dark:border-slate-700/60 dark:bg-slate-900/60 dark:text-slate-300 dark:hover:border-slate-600 dark:hover:bg-slate-900"
                                >
                                    <span class="mt-1 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-slate-100 text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                    </span>
                                    <div class="flex-1">
                                        <p class="font-semibold text-slate-700 dark:text-slate-200">{{ $entry['title'] }}</p>
                                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ $entry['description'] }}</p>
                                        <div class="mt-2 flex flex-wrap items-center gap-3 text-[11px] uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">
                                            <span>{{ $entry['time'] }}</span>
                                            @if (!empty($entry['tags']))
                                                @foreach ($entry['tags'] as $tag)
                                                    <span class="rounded-full border border-current/20 px-2 py-0.5">{{ $tag }}</span>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                </li>
                            @empty
                                <li class="rounded-2xl border border-slate-200/70 bg-white/80 px-4 py-5 text-sm text-slate-500 shadow-sm dark:border-slate-700/60 dark:bg-slate-900/60 dark:text-slate-400">
                                    Aktivitas terbaru akan muncul di sini setelah ada transaksi atau agenda baru.
                                </li>
                            @endforelse
                        </ol>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>
