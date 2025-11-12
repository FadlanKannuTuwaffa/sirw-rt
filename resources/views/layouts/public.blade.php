<!DOCTYPE html>
@php
    $currentLocale = app()->getLocale();
    $currentCode = strtoupper(substr(str_replace('_', '-', $currentLocale), 0, 2));
@endphp
<html lang="{{ str_replace('_', '-', $currentLocale) }}" data-locale="{{ $currentLocale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ ($title ?? 'Sistem Informasi RT') . ' - SIRW' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
    @include('layouts.partials.theme-initializer')
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
    @php
        $cspNonceValue = $cspNonce ?? (app()->bound('cspNonce') ? app('cspNonce') : null);
        $landingTranslations = $dynamicTranslations ?? [];
    @endphp
    @if (! empty($landingTranslations))
        <script @if($cspNonceValue) nonce="{{ $cspNonceValue }}" @endif>
            window.SIRW = window.SIRW || {};
            window.SIRW.dynamicTranslations = window.SIRW.dynamicTranslations || {};
            const landingDynamicTranslations = @json($landingTranslations, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            Object.keys(landingDynamicTranslations).forEach(function (locale) {
                const entries = landingDynamicTranslations[locale] || {};
                window.SIRW.dynamicTranslations[locale] = Object.assign(
                    {},
                    window.SIRW.dynamicTranslations[locale] || {},
                    entries
                );
            });
        </script>
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="landing min-h-full overflow-x-hidden bg-white text-slate-900 antialiased transition-colors duration-300 dark:bg-slate-950 dark:text-slate-100">
    @php
        $logoUrl = ! empty($site['logo_path'] ?? null) ? \App\Support\StorageUrl::forPublicDisk($site['logo_path']) : null;
        $logoInitials = $site['logo_initials'] ?? 'SR';
    @endphp
    @php
        $navigationLinks = [
            ['route' => 'landing', 'label' => 'nav.home', 'text' => 'Beranda'],
            ['route' => 'about', 'label' => 'nav.about', 'text' => 'Tentang'],
            ['route' => 'landing.agenda', 'label' => 'nav.agenda', 'text' => 'Agenda'],
            ['route' => 'landing.contact', 'label' => 'nav.contact', 'text' => 'Kontak'],
        ];
    @endphp
    <div class="flex min-h-screen flex-col">
        <header id="site-header" data-sticky-header class="sticky top-0 z-50 bg-white/80 dark:bg-slate-900/80 border-b border-slate-200 dark:border-slate-800 backdrop-blur h-14 sm:h-16 md:h-20 lg:h-20">
            <div class="mx-auto flex h-full w-full max-w-7xl flex-wrap items-center justify-between gap-4 px-4 text-sm font-semibold sm:px-6 lg:px-8">
                <a href="{{ route('landing') }}" class="flex items-center gap-3 transition-transform duration-300 hover:scale-[1.01]">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="Logo" class="h-11 w-11 rounded-full object-cover shadow-lg shadow-sky-200/70 ring-2 ring-white dark:ring-slate-800">
                    @else
                        <div class="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-sky-500 to-emerald-500 text-lg font-semibold text-white shadow-lg shadow-sky-300/50 ring-2 ring-white dark:ring-slate-800">{{ $logoInitials }}</div>
                    @endif
                    <div class="space-y-0.5">
                        <p class="text-sm uppercase tracking-[0.5em] text-slate-600 dark:text-slate-300" data-i18n="site.brand_title">{{ $site['name'] ?? 'Sistem Informasi RT' }}</p>
                        @if (! empty($site['tagline']))
                            <p class="text-xs text-slate-400 dark:text-slate-500" data-i18n="site.brand_tagline">{{ $site['tagline'] }}</p>
                        @endif
                    </div>
                </a>
                <nav class="hidden items-center gap-6 text-sm font-semibold md:flex" data-desktop-nav>
                    @foreach ($navigationLinks as $link)
                        <a href="{{ route($link['route']) }}" class="relative inline-flex items-center gap-1 transition-all duration-300 hover:-translate-y-0.5 hover:text-sky-600 dark:hover:text-sky-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-sky-400 dark:focus-visible:ring-offset-slate-900 {{ request()->routeIs($link['route']) ? 'text-sky-600 dark:text-sky-400 after:absolute after:-bottom-1 after:left-0 after:h-0.5 after:w-full after:rounded-full after:bg-sky-500 dark:after:bg-sky-400' : 'text-slate-600 dark:text-slate-300' }}" data-i18n="{{ $link['label'] }}">{{ $link['text'] }}</a>
                    @endforeach
                </nav>
                <button type="button" class="inline-flex items-center justify-center rounded-full border border-slate-200 px-3 py-2 text-slate-600 shadow-sm transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-slate-700 dark:text-slate-300 dark:focus-visible:ring-sky-400 dark:focus-visible:ring-offset-slate-900 md:hidden" data-mobile-nav-toggle aria-expanded="false" aria-label="Buka navigasi" data-i18n="nav.toggle_open" data-i18n-attr="aria-label">
                    <span class="sr-only" data-nav-toggle-label data-i18n="nav.toggle_open">Buka navigasi</span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5m-16.5 5.25h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
                <div class="flex w-full flex-wrap items-center justify-end gap-3 gap-y-2 sm:w-auto sm:flex-nowrap sm:gap-3 md:flex-1 md:gap-y-0 lg:flex-nowrap lg:gap-y-0 min-w-0">
                    <div class="flex w-full flex-wrap items-center justify-end gap-2 sm:w-auto sm:flex-nowrap sm:gap-3 md:justify-end lg:w-auto lg:flex-nowrap min-w-0">
                        <div class="theme-toggle order-2 sm:order-1" role="group" aria-label="Pengaturan tema">
                            <button type="button" data-theme-mode="light" class="theme-toggle__button focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-sky-400 dark:focus-visible:ring-offset-slate-900" aria-pressed="false">
                                <span class="sr-only">Mode terang</span>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.75v-1.5m0 19.5v-1.5m8.25-8.25h1.5m-19.5 0h1.5m13.364-6.114.53-.531m-13.728 13.728.53-.53m12.668 0 .531.53M4.828 5.032l.53.53M17.25 12a5.25 5.25 0 1 1-10.5 0 5.25 5.25 0 0 1 10.5 0Z" />
                            </svg>
                            </button>
                            <button type="button" data-theme-mode="dark" class="theme-toggle__button focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-sky-400 dark:focus-visible:ring-offset-slate-900" aria-pressed="false">
                                <span class="sr-only">Mode gelap</span>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1 1 11.21 3 7.5 7.5 0 0 0 21 12.79Z" />
                                </svg>
                            </button>
                            <button type="button" data-theme-mode="auto" class="theme-toggle__button hidden sm:inline-flex focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-sky-400 dark:focus-visible:ring-offset-slate-900" aria-pressed="false">
                                <span class="sr-only">Ikuti sistem</span>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1.5m0 15V21M6.364 6.364l1.06 1.06m9.152 9.152 1.06 1.06M3 12h1.5m15 0H21M6.364 17.636l1.06-1.06m9.152-9.152 1.06-1.06M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                            </button>
                        </div>
                        @livewire('landing.language-switcher')
                    </div>
                    <div class="flex w-full flex-wrap items-center justify-end gap-2 sm:w-auto sm:flex-nowrap sm:gap-3 md:w-auto md:flex-nowrap md:gap-3">
                        <a href="{{ route('login') }}" class="btn-pill btn-pill--ghost" data-i18n="nav.login">Masuk</a>
                        <a href="{{ route('register') }}" class="btn-pill btn-pill--solid" data-i18n="nav.register">Daftar</a>
                    </div>
                </div>
                </div>
            </div>
        </header>
        <div class="hidden w-full border-b border-slate-200 bg-white/90 px-4 py-4 shadow-sm transition-colors duration-300 dark:border-slate-800 dark:bg-slate-900/90 md:hidden" data-mobile-nav-menu>
            <div class="flex flex-col gap-3">
                <nav class="flex flex-col gap-3 text-sm font-semibold text-slate-600 dark:text-slate-300">
                @foreach ($navigationLinks as $link)
                    <a href="{{ route($link['route']) }}" class="rounded-lg px-3 py-2 text-slate-600 transition-colors duration-200 hover:bg-sky-50 hover:text-sky-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-sky-300 dark:focus-visible:ring-sky-400 dark:focus-visible:ring-offset-slate-900" data-i18n="{{ $link['label'] }}">{{ $link['text'] }}</a>
                @endforeach
            </nav>
            </div>
        </div>
        <main id="content" class="relative flex-1 overflow-x-hidden bg-white transition-colors duration-300 dark:bg-slate-950">
            <div class="landing-flow space-y-10 md:space-y-16
                        [&>section>div]:mx-auto [&>section>div]:max-w-7xl
                        [&>section>div]:px-4 [&>section>div]:sm:px-6 [&>section>div]:lg:px-8">
                @hasSection('content')
                    @yield('content')
                @else
                    {{ $slot ?? '' }}
                @endif
            </div>
        </main>
        <footer class="border-t border-slate-200 bg-white/80 backdrop-blur-md shadow-inner shadow-sky-100/30 transition-colors duration-300 dark:border-slate-800 dark:bg-slate-900/80 dark:shadow-slate-900/30">
            <div class="mx-auto flex w-full max-w-7xl flex-col gap-3 px-4 py-6 text-sm text-slate-600 transition-colors duration-300 sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8 dark:text-slate-300">
                <p>(c) {{ now()->year }} {{ $site['name'] ?? 'Sistem Informasi RT' }}. <span data-i18n="footer.all_rights">All rights reserved.</span></p>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs">
                    <span class="text-slate-500 dark:text-slate-400">
                    @if (!empty($site['address']))
                        {{ $site['address'] }}
                    @else
                        <span data-i18n="footer.address_unset">Alamat belum diatur</span>
                    @endif
                </span>
                    @if (!empty($site['contact_email']))
                        <a href="mailto:{{ $site['contact_email'] }}" class="transition-colors duration-300 hover:text-sky-600 dark:hover:text-sky-400">{{ $site['contact_email'] }}</a>
                    @endif
                    @if (!empty($site['contact_phone']))
                        <a href="https://wa.me/{{ preg_replace('/\\D+/', '', $site['contact_phone']) }}" target="_blank" class="transition-colors duration-300 hover:text-sky-600 dark:hover:text-sky-400">{{ $site['contact_phone'] }}</a>
                    @endif
                </div>
            </div>
        </footer>
    </div>
    @livewireScripts
    <script @if($cspNonceValue) nonce="{{ $cspNonceValue }}" @endif>
        document.addEventListener('DOMContentLoaded', () => {
            const toggle = document.querySelector('[data-mobile-nav-toggle]');
            const menu = document.querySelector('[data-mobile-nav-menu]');
            if (!toggle || !menu) {
                return;
            }
            const hideMenu = () => {
                menu.classList.add('hidden');
                toggle.setAttribute('aria-expanded', 'false');
            };
            const showMenu = () => {
                menu.classList.remove('hidden');
                toggle.setAttribute('aria-expanded', 'true');
            };
            toggle.addEventListener('click', () => {
                const expanded = toggle.getAttribute('aria-expanded') === 'true';
                if (expanded) {
                    hideMenu();
                } else {
                    showMenu();
                }
            });
            document.addEventListener('click', (event) => {
                if (!menu.contains(event.target) && !toggle.contains(event.target)) {
                    hideMenu();
                }
            });
        });
    </script>
    @stack('scripts')
</body>
</html>




