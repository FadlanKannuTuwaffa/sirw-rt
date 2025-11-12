@php
    $now = now();
    $statCards = [
        [
            'key' => 'all',
            'label' => 'Total agenda',
            'description' => 'Semua agenda yang tercatat dalam sistem.',
            'accent' => 'sky',
            'icon' => '<svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 3v2m8-2v2m-9 4h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/></svg>',
        ],
        [
            'key' => 'today',
            'label' => 'Agenda hari ini',
            'description' => 'Agenda yang berlangsung pada ' . $now->translatedFormat('l') . '.',
            'accent' => 'emerald',
            'icon' => '<svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m15 13-3 3m0 0-3-3m3 3V8m7 4a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>',
        ],
        [
            'key' => 'upcoming',
            'label' => 'Agenda mendatang',
            'description' => 'Jadwal yang sudah disiapkan untuk beberapa waktu ke depan.',
            'accent' => 'purple',
            'icon' => '<svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6h4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>',
        ],
        [
            'key' => 'ongoing',
            'label' => 'Sedang berlangsung',
            'description' => 'Agenda yang aktif sekarang.',
            'accent' => 'amber',
            'icon' => '<svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m4.5 12.75 6 6 9-13.5"/></svg>',
        ],
        [
            'key' => 'completed',
            'label' => 'Agenda selesai',
            'description' => 'Kegiatan yang berhasil dilaksanakan.',
            'accent' => 'emerald',
            'icon' => '<svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m4.5 12.75 6 6 9-13.5"/></svg>',
        ],
        [
            'key' => 'cancelled',
            'label' => 'Agenda dibatalkan',
            'description' => 'Daftar agenda yang batal diselenggarakan.',
            'accent' => 'rose',
            'icon' => '<svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>',
        ],
        [
            'key' => 'past',
            'label' => 'Agenda lewat',
            'description' => 'Agenda terjadwal yang sudah melewati waktunya.',
            'accent' => 'slate',
            'icon' => '<svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>',
        ],
    ];
@endphp

<div class="space-y-8 font-['Inter'] text-slate-800 dark:text-slate-100" data-admin-stack>
    <section class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition-colors duration-200 dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
            @foreach ($statCards as $card)
                <button type="button"
                        wire:click="$set('status', '{{ $card['key'] }}')"
                        @class([
                            'space-y-3 rounded-3xl border p-5 text-left transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-slate-900',
                            'border-sky-400 ring-2 ring-sky-400 ring-offset-2 dark:border-sky-500' => $status === $card['key'],
                            'border-slate-200/80 hover:border-sky-200 dark:border-slate-700 dark:hover:border-sky-500/40' => $status !== $card['key'],
                        ]) data-metric-card data-accent="{{ $card['accent'] }}">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $card['label'] }}</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format($stats[$card['key']] ?? 0) }}</p>
                        </div>
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200/60 bg-white/70 text-slate-500 shadow-sm dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-200">
                            {!! $card['icon'] !!}
                        </span>
                    </div>
                    <p class="text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ $card['description'] }}</p>
                </button>
            @endforeach
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200/70 bg-white/90 p-6 shadow-sm transition-colors duration-200 hover:border-sky-200 dark:border-slate-700/80 dark:bg-slate-900/70" data-motion-card>
        <header class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-sky-600 dark:text-sky-300">Daftar Agenda</h2>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    Pantau, kelola, dan tindak lanjuti seluruh agenda warga dari satu tempat.
                </p>
            </div>
            <div class="flex w-full flex-col gap-3 lg:w-auto">
                <div class="flex flex-wrap gap-2">
                    @foreach ($statusOptions as $value => $label)
                        <button type="button"
                                wire:click="$set('status', '{{ $value }}')"
                                @class([
                                    'inline-flex items-center justify-center rounded-full border px-4 py-1.5 text-xs font-semibold transition-colors duration-200',
                                    'border-sky-500 bg-sky-600 text-white shadow-sm dark:border-sky-400 dark:bg-sky-500/90' => $status === $value,
                                    'border-slate-200/70 bg-white/80 text-slate-500 hover:border-sky-300 hover:text-sky-600 dark:border-slate-700 dark:bg-slate-800/80 dark:text-slate-300 dark:hover:border-sky-500/40' => $status !== $value,
                                ])>
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-end">
                    <div class="relative flex-1">
                        <input wire:model.debounce.500ms="search"
                               type="search"
                               placeholder="Cari judul, lokasi, atau deskripsi agenda"
                               class="w-full rounded-2xl border border-slate-200/80 bg-white/80 py-3 pl-11 pr-4 text-sm text-slate-600 shadow-inner shadow-sky-100/40 transition-all duration-200 focus:border-sky-400 focus:ring-2 focus:ring-sky-200 focus:ring-offset-1 focus:ring-offset-white dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-200 dark:placeholder:text-slate-500 dark:focus:border-sky-500 dark:focus:ring-sky-500/40 dark:focus:ring-offset-slate-900">
                        <svg class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-sky-500 dark:text-sky-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 3.478 9.772l3.875 3.875a.75.75 0 1 0 1.06-1.06l-3.875-3.875A5.5 5.5 0 0 0 9 3.5Zm-4 5.5a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="flex flex-1 flex-wrap gap-3 sm:flex-nowrap lg:flex-none">
                        <label class="flex w-full flex-col text-xs font-semibold text-slate-500 dark:text-slate-400 sm:w-auto">
                            <span class="mb-1">Mulai</span>
                            <input wire:model="from"
                                   type="date"
                                   class="w-full rounded-2xl border border-slate-200/80 bg-white/80 py-3 px-4 text-sm text-slate-600 shadow-inner shadow-sky-100/40 transition-all duration-200 focus:border-sky-400 focus:ring-2 focus:ring-sky-200 focus:ring-offset-1 focus:ring-offset-white dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-200 dark:focus:border-sky-500 dark:focus:ring-sky-500/40 dark:focus:ring-offset-slate-900">
                        </label>
                        <label class="flex w-full flex-col text-xs font-semibold text-slate-500 dark:text-slate-400 sm:w-auto">
                            <span class="mb-1">Sampai</span>
                            <input wire:model="to"
                                   type="date"
                                   class="w-full rounded-2xl border border-slate-200/80 bg-white/80 py-3 px-4 text-sm text-slate-600 shadow-inner shadow-sky-100/40 transition-all duration-200 focus:border-sky-400 focus:ring-2 focus:ring-sky-200 focus:ring-offset-1 focus:ring-offset-white dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-200 dark:focus:border-sky-500 dark:focus:ring-sky-500/40 dark:focus:ring-offset-slate-900">
                        </label>
                        <button type="button"
                                wire:click="resetFilters"
                                class="inline-flex h-[52px] items-center justify-center rounded-2xl border border-slate-200/80 bg-white/80 px-4 text-xs font-semibold text-slate-500 transition-all duration-200 hover:border-rose-300 hover:bg-rose-50 hover:text-rose-600 dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-300 dark:hover:border-rose-400 dark:hover:bg-rose-500/20 dark:hover:text-rose-200">
                            Reset
                        </button>
                        <a wire:navigate href="{{ route('admin.agenda.create') }}"
                           class="btn-soft-emerald h-[52px] px-6 text-sm">
                            + Agenda Baru
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <div class="mt-8 space-y-4">
            <div wire:loading.flex class="rounded-2xl border border-dashed border-sky-200/60 bg-white/60 p-6 text-xs font-semibold uppercase tracking-[0.3em] text-sky-500 backdrop-blur-sm dark:border-slate-700/60 dark:bg-slate-900/40 dark:text-sky-300">
                Memuat agenda...
            </div>

            @php
                $now = now();
            @endphp

            @forelse ($events as $event)
                @php
                    $isOngoing = $event->status === 'scheduled'
                        && $event->start_at?->lte($now)
                        && (
                            ($event->end_at && $event->end_at->gte($now))
                            || (!$event->end_at && $event->start_at?->isSameDay($now))
                        );
                    $isPast = $event->status === 'scheduled' && $event->start_at?->lt($now) && ! $isOngoing;

                    $badge = match (true) {
                        $event->status === 'cancelled' => ['label' => 'Dibatalkan', 'classes' => 'bg-rose-50 text-rose-600 border border-rose-100 dark:bg-rose-500/10 dark:text-rose-200 dark:border-rose-400/30'],
                        $event->status === 'completed' => ['label' => 'Selesai', 'classes' => 'bg-emerald-50 text-emerald-600 border border-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-200 dark:border-emerald-400/30'],
                        $isOngoing => ['label' => 'Sedang Berlangsung', 'classes' => 'bg-amber-50 text-amber-600 border border-amber-100 dark:bg-amber-500/10 dark:text-amber-200 dark:border-amber-400/30'],
                        $isPast => ['label' => 'Sudah Lewat', 'classes' => 'bg-slate-100 text-slate-600 border border-slate-200 dark:bg-slate-700/50 dark:text-slate-200 dark:border-slate-600/60'],
                        default => ['label' => 'Mendatang', 'classes' => 'bg-sky-50 text-sky-600 border border-sky-100 dark:bg-sky-500/10 dark:text-sky-200 dark:border-sky-400/30'],
                    };
                @endphp

                <article class="relative overflow-hidden rounded-3xl border border-slate-200/70 bg-white/80 p-6 shadow-sm transition-colors duration-200 hover:border-sky-200 dark:border-slate-700 dark:bg-slate-900/60 dark:hover:border-sky-500/40">
                    <span class="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full bg-sky-100/40 blur-3xl dark:bg-sky-500/20"></span>
                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div class="flex flex-col gap-3 md:max-w-2xl">
                            <div class="flex items-start gap-3">
                                <span class="mt-1 inline-flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-sky-500/15 text-sky-600 dark:bg-sky-500/20 dark:text-sky-200">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2v-9a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2z"/>
                                    </svg>
                                </span>
                                <div class="space-y-2">
                                    <h3 class="text-lg font-semibold text-slate-800 dark:text-white">{{ $event->title }}</h3>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">
                                        {{ Str::limit($event->description, 180) ?: 'Belum ada deskripsi agenda.' }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
                                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200/70 bg-white/60 px-3 py-1 dark:border-slate-700 dark:bg-slate-800/70">
                                    <svg class="h-4 w-4 text-sky-500 dark:text-sky-300" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                                    </svg>
                                    {{ $event->start_at?->translatedFormat('l, d F Y • H:i') ?? 'Jadwal belum ditentukan' }}
                                    @if ($event->end_at)
                                        <span class="text-[10px] uppercase tracking-[0.3em] text-slate-300 dark:text-slate-500">sampai</span>
                                        {{ $event->end_at?->translatedFormat('H:i') }}
                                    @endif
                                </span>
                                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200/70 bg-white/60 px-3 py-1 dark:border-slate-700 dark:bg-slate-800/70">
                                    <svg class="h-4 w-4 text-sky-500 dark:text-sky-300" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657 13.414 12.414a4 4 0 1 0-1.414 1.414l4.243 4.243a1 1 0 0 0 1.414-1.414z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 6a4 4 0 1 1 0 8 4 4 0 0 1 0-8z"/>
                                    </svg>
                                    {{ $event->location ?: 'Belum ditentukan' }}
                                </span>
                                @if ($event->creator?->name)
                                    <span class="inline-flex items-center gap-2 rounded-full border border-slate-200/70 bg-white/60 px-3 py-1 dark:border-slate-700 dark:bg-slate-800/70">
                                        <svg class="h-4 w-4 text-sky-500 dark:text-sky-300" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0zM12 14a7 7 0 0 0-7 7h14a7 7 0 0 0-7-7z"/>
                                        </svg>
                                        Penanggung jawab: {{ $event->creator->name }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-3">
                            <span class="inline-flex items-center gap-2 rounded-full px-4 py-1 text-xs font-semibold {{ $badge['classes'] }}">
                                {{ $badge['label'] }}
                            </span>
                            <div class="flex flex-wrap justify-end gap-2 text-xs font-semibold">
                                @php
                                    $isCompleted = $event->status === 'completed';
                                @endphp

                                @unless($isCompleted)
                                    <a wire:navigate
                                       href="{{ route('admin.agenda.edit', $event) }}"
                                       class="inline-flex items-center gap-2 rounded-full border border-sky-200 px-4 py-2 text-sky-600 transition-colors duration-200 hover:border-sky-400 hover:bg-sky-50 dark:border-slate-700 dark:text-sky-200 dark:hover:border-sky-400 dark:hover:bg-slate-800">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m16.862 3.487 2.651 2.651a1.875 1.875 0 0 1 0 2.652l-8.955 8.955a4.5 4.5 0 0 1-1.897 1.128l-2.977.849a.75.75 0 0 1-.926-.927l.849-2.977a4.5 4.5 0 0 1 1.128-1.897l8.955-8.955a1.875 1.875 0 0 1 2.652 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.488 7.012 16.5 4.012"/>
                                        </svg>
                                        Edit
                                    </a>
                                    @if($event->is_all_day)
                                        <button wire:click="markCompleted({{ $event->id }})"
                                                type="button"
                                                class="inline-flex items-center gap-2 rounded-full border border-emerald-200 px-4 py-2 text-emerald-600 transition-colors duration-200 hover:border-emerald-400 hover:bg-emerald-50 dark:border-emerald-500/40 dark:text-emerald-200 dark:hover:bg-emerald-500/20">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.5 12.75 9 17.25 19.5 6.75"/>
                                            </svg>
                                            Tandai Selesai
                                        </button>
                                    @endif
                                    <button wire:click="cancelEvent({{ $event->id }})"
                                            type="button"
                                            class="inline-flex items-center gap-2 rounded-full border border-amber-200 px-4 py-2 text-amber-600 transition-colors duration-200 hover:border-amber-400 hover:bg-amber-50 dark:border-amber-500/40 dark:text-amber-200 dark:hover:bg-amber-500/20">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m15 9-6 6m0-6 6 6"/>
                                        </svg>
                                        Batalkan
                                    </button>
                                @endunless

                                <button wire:click="deleteEvent({{ $event->id }})"
                                        type="button"
                                        class="inline-flex items-center gap-2 rounded-full border border-rose-200 px-4 py-2 text-rose-600 transition-colors duration-200 hover:border-rose-400 hover:bg-rose-50 dark:border-rose-500/40 dark:text-rose-200 dark:hover:bg-rose-500/20">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m9 10 6 6m0-6-6 6"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 12a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0z"/>
                                    </svg>
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-200/70 bg-white/70 p-10 text-center text-sm text-slate-400 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-500">
                    Tidak ada agenda sesuai filter. Coba atur ulang pencarian atau rentang tanggal.
                </div>
            @endforelse
        </div>

        <div class="mt-8 flex items-center justify-between text-xs text-slate-400 dark:text-slate-500">
            <p>Menampilkan {{ $events->count() }} dari {{ $events->total() }} agenda</p>
            {{ $events->onEachSide(1)->links() }}
        </div>
    </section>
</div>
