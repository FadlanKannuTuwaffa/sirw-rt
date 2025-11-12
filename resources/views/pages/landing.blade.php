@extends('layouts.public', [
    'title' => $title ?? null,
    'site' => $site,
    'dynamicTranslations' => $dynamicTranslations ?? [],
])

@section('content')
@php $hasSlides = $slides->isNotEmpty(); @endphp
<section data-hero class="relative overflow-hidden bg-gradient-to-b from-slate-50/80 via-white to-white pt-8 pb-12 transition-colors duration-500 dark:from-slate-900/80 dark:via-slate-950 dark:to-slate-950 sm:pt-10 sm:pb-14 md:pt-12 md:pb-16">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-12 px-4 sm:px-6 md:flex-row md:items-start md:justify-between md:gap-16 lg:px-8">
        <div class="flex-1 space-y-6">
            <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-4 py-1.5 text-xs font-semibold text-slate-700 shadow-sm shadow-sky-100/60 ring-1 ring-slate-200 transition-colors duration-300 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700" data-i18n="hero.badge">Portal Warga Digital</span>
            <h1 class="text-3xl font-bold leading-tight text-slate-900 transition-colors duration-300 sm:text-5xl md:text-6xl dark:text-slate-100">
                <span data-i18n="site.brand_title">{{ $site['name'] ?? 'Sistem Informasi RT' }}</span>
                <span class="mt-2 block bg-gradient-to-r from-sky-500 via-sky-400 to-emerald-500 bg-clip-text text-transparent" data-i18n="hero.tagline">Kolaboratif dan Transparan</span>
            </h1>
            <p class="max-w-xl text-base text-slate-600 transition-colors duration-300 md:text-lg dark:text-slate-400" data-i18n="hero.description">Kelola iuran, agenda, dan informasi lingkungan secara real time. Akses warga dan pengurus berada di satu platform yang aman serta mudah digunakan.</p>
            <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:gap-4 md:gap-4">
                <a href="{{ route('login') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-medium text-white shadow-lg shadow-sky-200 transition-transform duration-300 hover:-translate-y-0.5 hover:bg-sky-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:bg-sky-500 dark:hover:bg-sky-600 dark:focus-visible:ring-sky-400 dark:focus-visible:ring-offset-slate-900 sm:w-auto md:text-base" data-i18n="hero.cta_login">Masuk sebagai Warga</a>
                <a href="{{ route('register') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-900 shadow-sm transition-transform duration-300 hover:-translate-y-0.5 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700 dark:focus-visible:ring-sky-400 dark:focus-visible:ring-offset-slate-900 sm:w-auto md:text-base" data-i18n="hero.cta_register">Daftar</a>
            </div>
        </div>
        <div class="flex-1 md:self-start md:-mt-2">
            <div class="relative overflow-hidden rounded-3xl border border-slate-200/60 bg-slate-900 text-white shadow-2xl shadow-sky-200/40 transition-transform duration-500 hover:-translate-y-2 dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-900/40" data-slider-root>
                @if ($hasSlides)
                    <div class="relative h-64 sm:h-72 md:h-80 lg:h-96 w-full overflow-hidden rounded-3xl" data-slider-track data-slider-interval="6500">
                        @foreach ($slides as $slide)
                            <article
                                data-slider-slide
                                data-index="{{ $loop->index }}"
                                data-active="{{ $loop->first ? 'true' : 'false' }}"
                                aria-hidden="{{ $loop->first ? 'false' : 'true' }}"
                                @class([
                                    "hero-slide group pointer-events-none absolute inset-0 flex h-full w-full flex-col justify-end gap-3 overflow-hidden rounded-3xl p-8 transition-all duration-[900ms] ease-[cubic-bezier(0.16,1,0.3,1)]",
                                    "opacity-100 scale-100 pointer-events-auto z-20" => $loop->first,
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
                        @if ($slides->count() > 1)
                            <div class="pointer-events-none absolute inset-x-0 bottom-4 flex items-center justify-between px-6 text-white/80 sm:px-8">
                                <button type="button" data-slider-prev class="pointer-events-auto inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/15 text-white shadow-sm shadow-slate-900/30 transition-all duration-300 hover:bg-white/25 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/70 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900">
                                    <span class="sr-only" data-i18n="hero.prev">Slide sebelumnya</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m15 19-7-7 7-7"/>
                                    </svg>
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
                    @if ($slides->count() > 1)
                        <div class="border-t border-white/15 bg-slate-900/60 px-5 py-4 text-xs text-slate-200/90 sm:px-6">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <p class="font-semibold uppercase tracking-[0.45em] text-slate-300/90" data-i18n="hero.more_info">Informasi lainnya</p>
                                <span class="hidden text-[0.65rem] uppercase tracking-[0.35em] text-white/60 sm:inline" data-i18n="hero.indicator_hint">Geser atau klik untuk melihat</span>
                            </div>
                            <ul class="mt-3 flex flex-wrap gap-2.5" data-slider-indicators>
                                @foreach ($slides as $indicator)
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
                                        <span class="text-[0.7rem] font-medium tracking-wide" data-i18n="landing.slides.{{ $indicator->id }}.indicator">{{ Str::limit($indicator->title, 34) }}</span>
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
        </div>
    </div>
</section>

<livewire:landing.realtime-stats :initial="$stats" />

<section class="bg-gradient-to-b from-white via-white to-slate-50 pt-8 pb-12 md:pt-12 md:pb-16 transition-colors duration-300 dark:from-slate-950 dark:via-slate-950 dark:to-slate-900">
    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-slate-900 dark:text-slate-100" data-i18n="agenda.section_title">Agenda Terdekat</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400" data-i18n="agenda.section_desc">Tetap terhubung dengan kegiatan warga dan rapat lingkungan.</p>
            </div>
            <a href="{{ route('landing.agenda') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-sky-200 bg-white px-5 py-2.5 text-sm font-medium text-sky-600 shadow-sm transition-transform duration-300 hover:-translate-y-0.5 hover:bg-sky-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-slate-700 dark:bg-slate-900 dark:text-sky-300 dark:hover:bg-slate-800 dark:focus-visible:ring-sky-400 dark:focus-visible:ring-offset-slate-900 sm:w-auto md:text-base" data-i18n="agenda.view_all">Lihat semua agenda</a>
        </div>
        <div class="mt-10 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($upcomingEvents as $event)
                <article class="rounded-2xl border border-transparent bg-white/90 p-6 shadow-lg shadow-slate-200/60 transition-all duration-300 hover:-translate-y-1 hover:border-sky-200/60 hover:shadow-xl dark:border-slate-800/70 dark:bg-slate-900/80 dark:shadow-slate-900/40">
                    <p class="text-xs uppercase tracking-[0.3em] text-sky-500 dark:text-sky-300">{{ $event->start_at?->locale(app()->getLocale())->translatedFormat('l, d M Y H:i') }}</p>
                    <h3 class="mt-3 text-lg font-semibold text-slate-900 dark:text-slate-100" data-i18n="landing.events.{{ $event->id }}.title">{{ $event->title }}</h3>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400" data-i18n="landing.events.{{ $event->id }}.description">{{ Str::limit($event->description, 120) }}</p>
                    <p class="mt-4 text-xs font-medium text-slate-400 dark:text-slate-500">
                        <span class="mr-1 font-semibold" data-i18n="agenda.location_label">Lokasi:</span>
                        @if ($event->location)
                            <span data-i18n="landing.events.{{ $event->id }}.location">{{ $event->location }}</span>
                        @else
                            <span data-i18n="agenda.location_tbd">Akan diinformasikan</span>
                        @endif
                    </p>
                </article>
            @empty
                <p class="rounded-2xl border border-dashed border-sky-200/70 bg-white/80 p-6 text-center text-sm text-slate-400 md:col-span-2 lg:col-span-3 dark:border-slate-800/60 dark:bg-slate-900/70 dark:text-slate-500" data-i18n="agenda.empty">Belum ada agenda terjadwal.</p>
            @endforelse
        </div>
    </div>
</section>

<section class="bg-gradient-to-br from-slate-900 via-slate-900 to-slate-800 pt-8 pb-12 text-white md:pt-12 md:pb-16">
    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid gap-10 lg:grid-cols-2 lg:items-center">
            <div class="space-y-4">
                <h2 class="text-3xl font-semibold text-white" data-i18n="value.section_title">Kenapa memilih platform ini?</h2>
                <p class="text-base text-slate-200 md:text-lg" data-i18n="value.section_desc"> memudahkan kolaborasi antara pengurus dan warga, menghadirkan transparansi keuangan, serta mengirim reminder otomatis agar kegiatan berjalan tertib.</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-2xl bg-white/10 p-5 shadow-lg shadow-slate-900/30 transition-all duration-300 hover:-translate-y-1 hover:bg-white/15">
                    <h3 class="text-lg font-semibold" data-i18n="value.card_transparent_title">Transparan</h3>
                    <p class="mt-2 text-sm text-slate-200" data-i18n="value.card_transparent_desc">Rekap kas dan arus kas dapat dipantau warga secara realtime.</p>
                </div>
                <div class="rounded-2xl bg-white/10 p-5 shadow-lg shadow-slate-900/30 transition-all duration-300 hover:-translate-y-1 hover:bg-white/15">
                    <h3 class="text-lg font-semibold" data-i18n="value.card_responsive_title">Responsif</h3>
                    <p class="mt-2 text-sm text-slate-200" data-i18n="value.card_responsive_desc">Tampilan nyaman diakses dari ponsel maupun desktop.</p>
                </div>
                <div class="rounded-2xl bg-white/10 p-5 shadow-lg shadow-slate-900/30 transition-all duration-300 hover:-translate-y-1 hover:bg-white/15">
                    <h3 class="text-lg font-semibold" data-i18n="value.card_integrated_title">Terintegrasi</h3>
                    <p class="mt-2 text-sm text-slate-200" data-i18n="value.card_integrated_desc">Pengingat email dan pencatatan keuangan menyatu tanpa perlu aplikasi lain.</p>
                </div>
                <div class="rounded-2xl bg-white/10 p-5 shadow-lg shadow-slate-900/30 transition-all duration-300 hover:-translate-y-1 hover:bg-white/15">
                    <h3 class="text-lg font-semibold" data-i18n="value.card_secure_title">Aman</h3>
                    <p class="mt-2 text-sm text-slate-200" data-i18n="value.card_secure_desc">Hanya warga terdaftar yang bisa memiliki akun dan mengakses data sensitif.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="bg-gradient-to-b from-white via-white to-slate-50 pt-8 pb-12 md:pt-12 md:pb-16 transition-colors duration-300 dark:from-slate-950 dark:via-slate-950 dark:to-slate-900">
    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-4">
                <h2 class="text-2xl font-semibold text-slate-900 dark:text-slate-100" data-i18n="news.section_title">Kabar Warga</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400" data-i18n="news.section_desc">Update kegiatan terakhir dari pengurus.</p>
                <div class="mt-6 space-y-4">
                    @forelse ($news as $item)
                        <div class="rounded-2xl border border-transparent bg-white/95 p-6 shadow-lg shadow-slate-200/70 transition-all duration-300 hover:-translate-y-1 hover:border-sky-200/70 hover:shadow-xl dark:border-slate-800/70 dark:bg-slate-900/80 dark:shadow-slate-900/40">
                            <p class="text-xs uppercase tracking-[0.3em] text-sky-500 dark:text-sky-300">{{ $item->start_at?->translatedFormat('d M Y') ?? $item->created_at->translatedFormat('d M Y') }}</p>
                            <h3 class="mt-3 text-lg font-semibold text-slate-900 dark:text-slate-100" data-i18n="landing.news.{{ $item->id }}.title">{{ $item->title }}</h3>
                            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400" data-i18n="landing.news.{{ $item->id }}.description">{{ Str::limit($item->description, 200) }}</p>
                        </div>
                    @empty
                        <p class="rounded-2xl border border-dashed border-sky-200/70 bg-white/80 p-6 text-center text-sm text-slate-400 dark:border-slate-800/60 dark:bg-slate-900/70 dark:text-slate-500" data-i18n="news.empty">Belum ada berita terbaru.</p>
                    @endforelse
                </div>
            </div>
            <div class="space-y-6 rounded-2xl border border-sky-100 bg-gradient-to-br from-sky-50 via-white to-emerald-50 p-6 shadow-lg shadow-sky-100 transition-all duration-300 hover:-translate-y-1 dark:border-slate-800/60 dark:from-slate-900 dark:via-slate-900/80 dark:to-slate-900">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100" data-i18n="contact.card_title">Tanya Pengurus</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400" data-i18n="contact.card_desc">Hubungi pengurus untuk aktivasi akun atau informasi lain.</p>
                <ul class="space-y-3 text-sm text-slate-600 dark:text-slate-300">
                    <li>
                        <strong class="font-semibold" data-i18n="contact.email_label">Email:</strong>
                        @if (!empty($site['contact_email']))
                            {{ $site['contact_email'] }}
                        @else
                            <span data-i18n="contact.unavailable">Belum tersedia</span>
                        @endif
                    </li>
                    <li>
                        <strong class="font-semibold" data-i18n="contact.phone_label">Telepon/WA:</strong>
                        @if (!empty($site['contact_phone']))
                            {{ $site['contact_phone'] }}
                        @else
                            <span data-i18n="contact.unavailable">Belum tersedia</span>
                        @endif
                    </li>
                    <li>
                        <strong class="font-semibold" data-i18n="contact.address_label">Alamat:</strong>
                        @if (!empty($site['address']))
                            {{ $site['address'] }}
                        @else
                            <span data-i18n="contact.unavailable">Belum tersedia</span>
                        @endif
                    </li>
                </ul>
                <a href="{{ route('landing.contact') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-medium text-white shadow-lg shadow-sky-200 transition-transform duration-300 hover:-translate-y-0.5 hover:bg-sky-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:bg-sky-500 dark:hover:bg-sky-600 dark:focus-visible:ring-sky-400 dark:focus-visible:ring-offset-slate-900 sm:w-auto md:text-base dark:shadow-slate-900/40" data-i18n="contact.btn">Hubungi Pengurus</a>
            </div>
        </div>
    </div>
</section>
@endsection


