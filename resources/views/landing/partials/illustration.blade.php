@php
    $slidesCollection = $slides ?? collect();
    if (is_array($slidesCollection)) {
        $slidesCollection = collect($slidesCollection);
    }
    $hasSlides = isset($hasSlides) ? (bool) $hasSlides : ($slidesCollection instanceof \Illuminate\Support\Collection ? $slidesCollection->isNotEmpty() : !empty($slidesCollection));
@endphp

<div class="relative overflow-hidden rounded-3xl border border-slate-200/60 bg-slate-900 text-white shadow-2xl shadow-sky-200/40 transition-transform duration-500 hover:-translate-y-2 dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-900/40" data-slider-root>
    @if ($hasSlides)
        <div class="relative h-72 w-full overflow-hidden rounded-3xl" data-slider-track data-slider-interval="6500">
            @foreach ($slidesCollection as $slide)
                <article
                    data-slider-slide
                    data-index="{{ $loop->index }}"
                    data-active="{{ $loop->first ? 'true' : 'false' }}"
                    aria-hidden="{{ $loop->first ? 'false' : 'true' }}"
                    @class([
                        "hero-slide group pointer-events-none absolute inset-0 flex h-full w-full flex-col justify-end gap-3 overflow-hidden rounded-3xl p-8 transition-all duration-[900ms] ease-[cubic-bezier(0.16,1,0.3,1)]",
                        "opacity-100 scale-100 translate-x-0 pointer-events-auto z-20" => $loop->first,
                        "opacity-0 scale-[0.98] translate-x-6 z-10" => ! $loop->first,
                    ])
                >
                    @if ($slide->image_path)
                        <img src="{{ asset('storage/'.$slide->image_path) }}" @if ($slide->title) data-i18n="landing.slides.{{ $slide->id }}.title" data-i18n-attr="alt" @endif alt="{{ $slide->title }}" class="absolute inset-0 h-full w-full object-cover opacity-85 transition-transform duration-[1200ms] ease-[cubic-bezier(0.16,1,0.3,1)] group-hover:scale-[1.05]">
                    @else
                        <div class="absolute inset-0 bg-gradient-to-br from-sky-500/70 via-emerald-500/70 to-slate-900/60"></div>
                    @endif
                    <div class="absolute inset-0 bg-gradient-to-br from-slate-900/80 via-slate-900/45 to-slate-900/25 backdrop-blur-[2px]"></div>
                    <div class="relative z-10 flex flex-col gap-3">
                        @if ($slide->subtitle)
                            <p class="text-[0.65rem] uppercase tracking-[0.35em] text-slate-200/80" data-i18n="landing.slides.{{ $slide->id }}.subtitle">{{ $slide->subtitle }}</p>
                        @endif
                        <h2 class="text-3xl font-semibold tracking-tight" data-i18n="landing.slides.{{ $slide->id }}.title">{{ $slide->title }}</h2>
                        @if ($slide->description)
                            <p class="text-sm text-slate-200/90" data-i18n="landing.slides.{{ $slide->id }}.description">{{ $slide->description }}</p>
                        @endif
                        @if ($slide->button_label && $slide->button_url)
                            <a href="{{ $slide->button_url }}" class="inline-flex items-center gap-2 text-sm font-semibold text-sky-200 transition-colors duration-300 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-200 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900">
                                <span data-i18n="landing.slides.{{ $slide->id }}.button">{{ $slide->button_label }}</span>
                                <span aria-hidden="true">&rarr;</span>
                            </a>
                        @endif
                    </div>
                </article>
            @endforeach
            @if ($slidesCollection->count() > 1)
                <div class="pointer-events-none absolute inset-x-0 bottom-4 flex items-center justify-between gap-3 px-6 text-white/80 sm:px-8">
                    <button type="button" data-slider-prev class="pointer-events-auto inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/15 text-white shadow-sm shadow-slate-900/30 transition-all duration-300 hover:bg-white/25 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/70 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900">
                        <span class="sr-only" data-i18n="hero.prev">Slide sebelumnya</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m15 19-7-7 7-7"/>
                        </svg>
                    </button>
                    <button type="button"
                            data-slider-toggle
                            data-slider-play-label="Putar slider"
                            data-slider-pause-label="Jeda slider"
                            data-slider-play-text="Putar"
                            data-slider-pause-text="Jeda"
                            class="pointer-events-auto inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.35em] text-white/80 backdrop-blur transition-all duration-300 hover:bg-white/20 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900"
                            aria-pressed="false"
                            aria-label="Jeda slider">
                        <svg xmlns="http://www.w3.org/2000/svg" data-slider-icon="pause" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M7 4.75A.75.75 0 0 1 7.75 4h1.5a.75.75 0 0 1 .75.75v10.5a.75.75 0 0 1-.75.75h-1.5A.75.75 0 0 1 7 15.25zM11 4.75A.75.75 0 0 1 11.75 4h1.5a.75.75 0 0 1 .75.75v10.5a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1-.75-.75z" />
                        </svg>
                        <svg xmlns="http://www.w3.org/2000/svg" data-slider-icon="play" class="hidden h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M6.3 4.88A1 1 0 0 1 7.85 4.1l7.5 5.4a1 1 0 0 1 0 1.64l-7.5 5.4A1 1 0 0 1 6 15.8V5.2a1 1 0 0 1 .3-.32z" />
                        </svg>
                        <span data-slider-toggle-text>Jeda</span>
                    </button>
                    <button type="button" data-slider-next class="pointer-events-auto inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/15 text-white shadow-sm shadow-slate-900/30 transition-all duration-300 hover:bg-white/25 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/70 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900">
                        <span class="sr-only" data-i18n="hero.next">Slide selanjutnya</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m9 5 7 7-7 7"/>
                        </svg>
                    </button>
                </div>
            @endif
        </div>
        @if ($slidesCollection->count() > 1)
            <div class="border-t border-white/15 bg-slate-900/60 px-5 py-4 text-xs text-slate-200/90 sm:px-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="font-semibold uppercase tracking-[0.45em] text-slate-300/90" data-i18n="hero.more_info">Informasi lainnya</p>
                    <span class="hidden text-[0.65rem] uppercase tracking-[0.35em] text-white/60 sm:inline" data-i18n="hero.indicator_hint">Geser atau klik untuk melihat</span>
                </div>
                <ul class="mt-3 flex flex-wrap gap-2.5" data-slider-indicators>
                    @foreach ($slidesCollection as $indicator)
                        <li
                            class="slider-indicator"
                            data-slider-indicator
                            data-index="{{ $loop->index }}"
                            data-active="{{ $loop->first ? 'true' : 'false' }}"
                            role="button"
                            tabindex="0"
                            aria-label="Tampilkan {{ $indicator->title }}"
                            data-i18n="landing.slides.{{ $indicator->id }}.aria_label"
                            data-i18n-attr="aria-label"
                        >
                            <span class="slider-indicator__track">
                                <span data-slider-progress class="slider-indicator__progress"></span>
                            </span>
                            <span class="text-[0.7rem] font-medium tracking-wide" data-i18n="landing.slides.{{ $indicator->id }}.indicator">{{ \Illuminate\Support\Str::limit($indicator->title, 34) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @else
        <div class="flex h-72 w-full flex-col items-center justify-center gap-3 rounded-3xl bg-slate-900/80 p-6 text-center">
            <p class="text-sm font-semibold text-white/70" data-i18n="hero.no_slides_title">Belum ada slider aktif.</p>
            <p class="text-xs text-white/50" data-i18n="hero.no_slides_desc">Tambahkan slider dari panel admin untuk menampilkan informasi terbaru.</p>
        </div>
    @endif
</div>
