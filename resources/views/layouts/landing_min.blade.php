<!DOCTYPE html>
@php
    $currentLocale = app()->getLocale();
    $currentCode = strtoupper(substr(str_replace('_', '-', $currentLocale), 0, 2));
@endphp
<html lang="{{ str_replace('_', '-', $currentLocale) }}" data-locale="{{ $currentLocale }}" class="h-full antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ ($title ?? config('app.name', 'SIRW')) }}</title>
    @include('layouts.partials.theme-initializer')
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
    @stack('head')
</head>
<body class="min-h-full bg-white text-slate-900 antialiased transition-colors duration-300 dark:bg-slate-950 dark:text-slate-100">
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

    {{-- ===== HEADER ===== --}}
    <header id="site-header" data-sticky-header class="sticky top-0 z-50 bg-white/80 dark:bg-slate-900/80 border-b border-slate-200 dark:border-slate-800 backdrop-blur h-auto">
        <div class="hidden md:block">
            @includeWhen(View::exists('layouts.partials.header'), 'layouts.partials.header')
        </div>
        <div class="md:hidden">
            @includeWhen(View::exists('layouts.partials.header_mobile'), 'layouts.partials.header_mobile')
        </div>
    </header>

    {{-- ===== LANDING CONTENT (isolated) ===== --}}
    <div id="landing-root">
        <main id="landing-main" class="pt-safe overflow-x-hidden">
            @yield('content')
        </main>
    </div>

    @livewireScripts
    <script @if($cspNonceValue) nonce="{{ $cspNonceValue }}" @endif>
        document.addEventListener('DOMContentLoaded', () => {
            const toggle = document.querySelector('[data-mobile-nav-toggle]');
            const menu = document.querySelector('[data-mobile-nav-menu]');
            if (!toggle || !menu) {
                return;
            }
            const VISIBLE_CLASS = 'mobile-menu--visible';
            let hideTimer = null;

            const clearHideTimer = () => {
                if (hideTimer !== null) {
                    window.clearTimeout(hideTimer);
                    hideTimer = null;
                }
            };
            const finalizeHide = () => {
                menu.classList.add('hidden');
                menu.dataset.state = 'closed';
            };
            const showMenu = () => {
                clearHideTimer();
                if (menu.classList.contains(VISIBLE_CLASS)) {
                    toggle.setAttribute('aria-expanded', 'true');
                    return;
                }
                menu.dataset.state = 'opening';
                menu.classList.remove('hidden');
                void menu.offsetHeight;
                menu.classList.add(VISIBLE_CLASS);
                toggle.setAttribute('aria-expanded', 'true');
                const handleEnd = (event) => {
                    if (event.target !== menu || event.propertyName !== 'opacity') return;
                    menu.removeEventListener('transitionend', handleEnd);
                    if (menu.classList.contains(VISIBLE_CLASS)) {
                        menu.dataset.state = 'open';
                    }
                };
                menu.addEventListener('transitionend', handleEnd);
            };
            const hideMenu = () => {
                clearHideTimer();
                if (!menu.classList.contains(VISIBLE_CLASS)) {
                    finalizeHide();
                    toggle.setAttribute('aria-expanded', 'false');
                    return;
                }
                menu.dataset.state = 'closing';
                menu.classList.remove(VISIBLE_CLASS);
                const handleEnd = (event) => {
                    if (event.target !== menu || event.propertyName !== 'opacity') return;
                    menu.removeEventListener('transitionend', handleEnd);
                    finalizeHide();
                };
                menu.addEventListener('transitionend', handleEnd);
                hideTimer = window.setTimeout(() => {
                    menu.removeEventListener('transitionend', handleEnd);
                    finalizeHide();
                }, 260);
                toggle.setAttribute('aria-expanded', 'false');
            };

            toggle.addEventListener('click', (event) => {
                event.preventDefault();
                const expanded = toggle.getAttribute('aria-expanded') === 'true';
                if (expanded) {
                    hideMenu();
                } else {
                    showMenu();
                }
            });
            toggle.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    const expanded = toggle.getAttribute('aria-expanded') === 'true';
                    if (expanded) {
                        hideMenu();
                    } else {
                        showMenu();
                    }
                }
            });
            menu.addEventListener('click', (event) => event.stopPropagation());
            document.addEventListener('click', (event) => {
                if (!menu.contains(event.target) && !toggle.contains(event.target)) {
                    hideMenu();
                }
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    hideMenu();
                }
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
