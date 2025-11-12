@extends('layouts.landing_min')

@section('content')
@php
    $hasSlides = isset($slides) && is_object($slides) && method_exists($slides, 'isNotEmpty')
        ? $slides->isNotEmpty()
        : !empty($slides);
@endphp

{{-- HERO --}}
<section id="hero" data-hero data-motion-hero class="relative overflow-hidden border-b border-slate-200/70 bg-white/95 pt-4 pb-12 transition-colors duration-300 dark:border-slate-800/60 dark:bg-slate-900/90 md:pt-6 md:pb-16">
    <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-32 bg-gradient-to-b from-sky-50/50 to-transparent dark:from-slate-800/40"></div>
    <div class="container-app grid items-start gap-10 md:gap-14 lg:grid-cols-2">
        <div class="space-y-6 self-start">
            <span class="inline-flex items-center gap-2 rounded-full border border-slate-200/80 bg-white px-3 py-1 text-xs font-medium text-slate-600 shadow-sm dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-300" data-motion-pill>
                <span class="inline-flex h-2 w-2 rounded-full bg-emerald-400" aria-hidden="true"></span>
                <span data-i18n="hero.badge">Portal Warga Digital</span>
            </span>
            <h1 class="text-4xl font-semibold tracking-tight text-slate-900 dark:text-slate-100 sm:text-5xl lg:text-6xl">
                <span data-i18n="site.brand_title">{{ $site['name'] ?? 'Sistem Informasi RT' }}</span>
            </h1>
            <p class="max-w-xl text-base leading-relaxed text-slate-600 dark:text-slate-400">
                <span data-i18n="hero.description">Kelola iuran, agenda, dan informasi lingkungan secara real time. Akses warga dan pengurus berada di satu platform yang aman serta mudah digunakan.</span>
            </p>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('register') }}"
                   class="btn-base btn-full bg-sky-600 text-white shadow-sm shadow-sky-200/60 transition-all duration-200 hover:bg-sky-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:bg-sky-500 dark:hover:bg-sky-400 dark:focus-visible:ring-sky-400 dark:focus-visible:ring-offset-slate-900 sm:w-auto md:text-base"
                   id="cta-register" data-i18n="hero.cta_register">Daftar</a>
                <a href="{{ route('login') }}"
                   class="btn-base btn-full border border-slate-200 bg-white text-slate-900 transition-all duration-200 hover:border-sky-200 hover:text-sky-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:border-sky-500 dark:hover:text-sky-300 dark:focus-visible:ring-sky-400 dark:focus-visible:ring-offset-slate-900 sm:w-auto md:text-base"
                   id="cta-login" data-i18n="hero.cta_login">Masuk sebagai Warga</a>
            </div>
            <ul class="mt-6 space-y-4 text-sm text-slate-600 dark:text-slate-300" data-hero-benefits>
                <li class="flex items-start gap-3 rounded-2xl border border-slate-200/80 bg-white/90 p-4 shadow-sm transition-colors duration-200 hover:border-sky-200 dark:border-slate-700/70 dark:bg-slate-900/70 dark:hover:border-sky-500/40">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-sky-100 text-sky-600 dark:bg-sky-500/10 dark:text-sky-300" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-6.25 6.3a1 1 0 0 1-1.417.006L3.29 7.559a1 1 0 1 1 1.42-1.406l4.012 4.052 5.543-5.59a1 1 0 0 1 1.44-.325z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    <div>
                        <p class="font-semibold text-slate-900 dark:text-slate-100" data-i18n="hero.benefits.fast.title">Konfirmasi cepat</p>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400" data-i18n="hero.benefits.fast.desc">Pembayaran diverifikasi otomatis oleh pengurus dengan notifikasi instan.</p>
                    </div>
                </li>
                <li class="flex items-start gap-3 rounded-2xl border border-slate-200/80 bg-white/90 p-4 shadow-sm transition-colors duration-200 hover:border-sky-200 dark:border-slate-700/70 dark:bg-slate-900/70 dark:hover:border-sky-500/40">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10.75 3a.75.75 0 0 0-1.5 0v6.095l-3.485 4.18a.75.75 0 1 0 1.17.93l3.065-3.675 3.065 3.675a.75.75 0 0 0 1.17-.93L10.75 9.096z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    <div>
                        <p class="font-semibold text-slate-900 dark:text-slate-100" data-i18n="hero.benefits.reminder.title">Pengumuman real time</p>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400" data-i18n="hero.benefits.reminder.desc">Agenda dan berita terbaru tampil langsung tanpa menunggu pengurus menyebar manual.</p>
                    </div>
                </li>
                <li class="flex items-start gap-3 rounded-2xl border border-slate-200/80 bg-white/90 p-4 shadow-sm transition-colors duration-200 hover:border-sky-200 dark:border-slate-700/70 dark:bg-slate-900/70 dark:hover:border-sky-500/40">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-violet-100 text-violet-600 dark:bg-violet-500/10 dark:text-violet-300" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 2a6 6 0 0 0-6 6v1.586L2.293 12.3A1 1 0 0 0 3 14h14a1 1 0 0 0 .707-1.707L16 9.586V8a6 6 0 0 0-6-6z" />
                            <path d="M7 16a3 3 0 0 0 6 0H7z" />
                        </svg>
                    </span>
                    <div>
                        <p class="font-semibold text-slate-900 dark:text-slate-100" data-i18n="hero.benefits.device.title">Nyaman di semua perangkat</p>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400" data-i18n="hero.benefits.device.desc">Tampilan responsif yang ringan untuk ponsel, tablet, hingga desktop RT.</p>
                    </div>
                </li>
            </ul>
            <div class="flex items-center gap-2 pt-2 text-xs font-medium text-slate-500 dark:text-slate-400" data-hero-scroll>
                <span aria-hidden="true" class="hidden h-px w-6 rounded bg-slate-300 dark:bg-slate-700 sm:block"></span>
                <a href="#features" class="inline-flex items-center gap-2 rounded-full border border-slate-200/80 bg-transparent px-3 py-1.5 text-slate-600 transition-colors duration-200 hover:border-sky-300 hover:text-sky-600 dark:border-slate-700 dark:text-slate-300 dark:hover:border-sky-500 dark:hover:text-sky-300" data-i18n="hero.scroll_for_more" data-i18n-attr="aria-label" aria-label="Lihat fitur lainnya">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 0 1 1.06 0L10 10.94l3.72-3.72a.75.75 0 0 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.28a.75.75 0 0 1 0-1.06z" clip-rule="evenodd" />
                    </svg>
                    <span data-i18n="hero.scroll_for_more.text">Scroll untuk lihat fitur</span>
                </a>
            </div>
        </div>
        <div class="min-h-safe md:min-h-[520px] self-start slider-wrapper">
            @includeIf('landing.partials.illustration', ['slides' => $slides, 'hasSlides' => $hasSlides])
        </div>
    </div>
</section>

@includeIf('landing.partials.demo')

<livewire:landing.realtime-stats :initial="$stats" />

@includeIf('landing.partials.features')
@includeIf('landing.partials.experience')
@includeIf('landing.partials.agenda')
@includeIf('landing.partials.contact')
@includeIf('landing.partials.cta')
@endsection
