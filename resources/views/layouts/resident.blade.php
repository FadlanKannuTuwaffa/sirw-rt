<!DOCTYPE html>
@php use App\Support\RT; @endphp
@php
    $experiencePreferences = auth()->user()?->experience_preferences ?? [];
    $residentLanguage = $experiencePreferences['language'] ?? 'id';
    $textSizePreference = $experiencePreferences['text_size'] ?? 'normal';
    $contrastPreference = $experiencePreferences['contrast'] ?? 'normal';

@endphp
<html lang="{{ $residentLanguage }}" data-locale="{{ $residentLanguage }}" class="h-full bg-gradient-to-br from-sky-50 via-white to-emerald-50 transition-colors duration-500 ease-out dark:bg-[#0B1012] dark:from-[#0B1012] dark:via-[#10171A] dark:to-[#12181B]{{ $textSizePreference === 'large' ? ' resident-text-large' : '' }}{{ $contrastPreference === 'high' ? ' resident-contrast-high' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ($title ?? RT::text('Tagihan & Pembayaran', 'Bills & Payments')) . ' - SIRW' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @include('layouts.partials.theme-initializer')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/darkmode.css') }}">
    @livewireStyles
    @include('components.dashboard-enhancements')
</head>
<body
    class="h-full transition-colors duration-300"
    data-resident-root
    data-profile-url="{{ route('resident.profile') }}"
    data-language="{{ $residentLanguage }}"
    data-text-size="{{ $textSizePreference }}"
    data-contrast="{{ $contrastPreference }}"
>
    @php
        $logoInitials = $site['logo_initials'] ?? 'RW';
        $logoUrl = null;

        if (! empty($site['logo_path'] ?? null)) {
            $logoUrl = \App\Support\StorageUrl::forPublicDisk($site['logo_path']);
        } elseif (\Illuminate\Support\Facades\Schema::hasTable('site_settings')) {
            $siteSettings = \App\Models\SiteSetting::keyValue()->toArray();
            $logoInitials = $siteSettings['logo_initials'] ?? $logoInitials;

            if (! empty($siteSettings['logo_path'] ?? null)) {
                $logoUrl = \App\Support\StorageUrl::forPublicDisk($siteSettings['logo_path']);
            }
        }
    @endphp
    <div class="min-h-screen w-full" data-resident-shell>
        <div class="relative flex min-h-screen" data-resident-layout>
            <aside
                id="residentSidebar"
                data-sidebar
                data-sidebar-collapse-class="-translate-x-full lg:-translate-x-full"
                data-sidebar-expand-class="translate-x-0 lg:translate-x-0"
                class="fixed inset-y-0 left-0 z-40 flex w-64 shrink-0 flex-col -translate-x-full p-6 transition-transform duration-500 ease-out lg:relative lg:z-auto lg:translate-x-0"
                data-resident-sidebar
            >
                @php
                    $residentNav = [
                        [
                            'label' => __('resident.layout.dashboard'),
                            'route' => 'resident.dashboard',
                            'active' => 'resident.dashboard',
                            'icon' => 'grid',
                            'caption' => __('resident.layout.neighbourhood_summary'),
                        ],
                        [
                            'label' => __('resident.layout.bills_payments'),
                            'route' => 'resident.bills',
                            'active' => 'resident.bills*',
                            'icon' => 'wallet',
                            'caption' => __('resident.layout.manage_household_obligations'),
                        ],
                        [
                            'label' => __('resident.layout.financial_recap'),
                            'route' => 'resident.reports',
                            'active' => 'resident.reports',
                            'icon' => 'report',
                            'caption' => __('resident.layout.community_finance_transparency'),
                        ],
                        [
                            'label' => __('resident.layout.resident_directory'),
                            'route' => 'resident.directory',
                            'active' => 'resident.directory',
                            'icon' => 'users',
                            'caption' => __('resident.layout.find_your_neighbours'),
                        ],
                        [
                            'label' => RT::text('Pencarian', 'Search'),
                            'route' => 'resident.search',
                            'active' => 'resident.search',
                            'icon' => 'search',
                            'caption' => RT::text('Temukan info cepat', 'Find info quickly'),
                        ],
                        [
                            'label' => RT::text('Asisten Warga', 'Resident Assistant'),
                            'route' => 'resident.assistant',
                            'active' => 'resident.assistant',
                            'icon' => 'assistant',
                            'caption' => RT::text('Panduan cepat & ringkasan', 'Guides & summaries'),
                        ],
                        [
                            'label' => RT::text('Profil Saya', 'My Profile'),
                            'route' => 'resident.profile',
                            'active' => 'resident.profile*',
                            'icon' => 'profile',
                            'caption' => RT::text('Kelola data pribadi', 'Manage personal details'),
                        ],
                    ];

                    $residentIcon = static function (string $type): string {
                        return match ($type) {
                            'grid' => <<<'SVG'
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="icon h-5 w-5">
                                    <rect x="3" y="3" width="8" height="8" rx="2" />
                                    <rect x="13" y="3" width="8" height="8" rx="2" />
                                    <rect x="3" y="13" width="8" height="8" rx="2" />
                                    <rect x="13" y="13" width="8" height="8" rx="2" />
                                </svg>
                            SVG,
                            'wallet' => <<<'SVG'
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="icon h-5 w-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7a3 3 0 0 1 3-3h12a1 1 0 0 1 0 2H7a1 1 0 0 0-1 1v1h13a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H5a3 3 0 0 1-3-3z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 13h2" />
                                </svg>
                            SVG,
                            'report' => <<<'SVG'
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="icon h-5 w-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 19V9a2 2 0 0 1 2-2h2m4-4 6 6m-6-6v4a2 2 0 0 0 2 2h4" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 17v2m4-6v6m4-10v10" />
                                </svg>
                            SVG,
                            'users' => <<<'SVG'
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="icon h-5 w-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 21a4.5 4.5 0 0 0-15 0" />
                                </svg>
                            SVG,
                            'search' => <<<'SVG'
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="icon h-5 w-5">
                                    <circle cx="11" cy="11" r="5" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.5 16.5 3 3" />
                                </svg>
                            SVG,
                            'assistant' => <<<'SVG'
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="icon h-5 w-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 8.5a5 5 0 1 1 10 0v1.25a5 5 0 0 1-5 5h-1l-2.5 3.5" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 15.5v3a2 2 0 0 0 2 2h8.5" />
                                </svg>
                            SVG,
                            'profile' => <<<'SVG'
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="icon h-5 w-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 20a6 6 0 0 1 12 0" />
                                </svg>
                            SVG,
                            default => '',
                        };
                    };
                @endphp
                <div class="flex items-center gap-3 rounded-2xl px-4 py-3 transition-[background-color,border-color,box-shadow,color] duration-300" data-resident-brand data-resident-fade>
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ RT::text('Logo', 'Logo') }}" class="h-10 w-10 rounded-full object-cover shadow-lg shadow-emerald-300/50 ring-2 ring-white/80 dark:ring-slate-900/70">
                    @else
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-sky-500 text-sm font-semibold text-white shadow-lg shadow-emerald-300/50">{{ $logoInitials }}</div>
                    @endif
                    <div>
                        <p class="text-[0.7rem] font-semibold uppercase tracking-[0.35em] text-[color:var(--text-2)] transition-colors duration-300">{{ $site['name'] ?? RT::text('Sistem Informasi RT', 'Neighbourhood Information System') }}</p>
                        <p class="text-xs text-[color:var(--text-3)] transition-colors duration-300">{{ RT::text('Halaman Portal Warga Terintegrasi', 'Integrated Resident Portal') }}</p>
                    </div>
                </div>
                <nav class="mt-8 space-y-1 text-sm font-semibold" data-resident-fade="delayed">
                    @foreach ($residentNav as $item)
                        @php
                            $isActive = request()->routeIs($item['active']);
                        @endphp
                        <a
                            href="{{ route($item['route']) }}"
                            class="item group flex w-full items-start gap-3 px-3 py-2 min-h-[var(--ctrl-h)] transition-[background-color,border-color,box-shadow,color] duration-300 will-change-[background-color,box-shadow,color] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--accent)] focus-visible:ring-offset-2 focus-visible:ring-offset-[color:var(--bg)]"
                            aria-current="{{ $isActive ? 'page' : 'false' }}"
                        >
                            {!! $residentIcon($item['icon']) !!}
                            <span class="copy flex min-w-0 flex-col gap-0.5 leading-snug">
                                <span class="label">{{ $item['label'] }}</span>
                                <span class="caption">{{ $item['caption'] }}</span>
                            </span>
                        </a>
                    @endforeach
                </nav>
                <form method="POST" action="{{ route('logout') }}" class="mt-10" data-resident-fade="later">
                    @csrf
                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-full border border-transparent bg-gradient-to-r from-[#0284C7] via-[#0EA5E9] to-[#22D3EE] px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-sky-200/40 transition-[transform,box-shadow] duration-300 hover:-translate-y-0.5 hover:shadow-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--accent)] focus-visible:ring-offset-2 focus-visible:ring-offset-[color:var(--bg)]">
                        {{ RT::text('Keluar', 'Sign out') }}
                    </button>
                </form>
                <div class="mt-10 rounded-2xl p-4 text-xs shadow-lg transition-[background-color,border-color,box-shadow,color] duration-300" data-resident-tip>
                    <p class="font-semibold uppercase tracking-wide">{{ RT::text('Tips keamanan', 'Security tips') }}</p>
                    <p class="mt-2 leading-relaxed">{{ RT::text('Segarkan sandi akun secara berkala dan aktifkan notifikasi email agar tidak tertinggal informasi penting.', 'Refresh your account password regularly and enable email notifications to stay updated.') }}</p>
                </div>
            </aside>
            <div class="fixed inset-0 z-30 bg-slate-900/40 opacity-0 pointer-events-none transition-opacity duration-300 lg:hidden" data-sidebar-overlay="residentSidebar" data-resident-overlay></div>
            <div
                class="flex min-h-screen flex-1 flex-col transition-[padding] duration-300 lg:pl-0 xl:pl-0"
                data-sidebar-content="residentSidebar"
                data-sidebar-open-class="lg:pl-0 xl:pl-0"
                data-sidebar-closed-class="lg:pl-0 xl:pl-0"
                data-resident-content
            >
                <header class="relative" data-resident-header data-resident-fade>
                    <div class="mx-auto flex w-full max-w-6xl flex-col gap-5 px-4 py-5 sm:px-6 lg:px-6 xl:px-8" data-resident-container>
                        <div class="flex flex-col gap-4 lg:grid lg:grid-cols-[minmax(0,1fr)_minmax(20rem,26rem)] lg:items-start">
                            <div class="flex w-full items-start gap-3 px-4 py-4" data-resident-card data-variant="accent" data-resident-greeting>
                                <button
                                    type="button"
                                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-transparent bg-white/80 text-slate-600 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:text-[#0284C7] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0284C7]/40 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:bg-slate-900/60 dark:text-slate-300 dark:hover:text-sky-300 dark:focus-visible:ring-sky-400 dark:focus-visible:ring-offset-slate-900 lg:hidden"
                                    data-sidebar-toggle
                                    data-sidebar-target="residentSidebar"
                                    aria-controls="residentSidebar"
                                    aria-expanded="false"
                                >
                                    <span class="sr-only">{{ RT::text('Buka tutup navigasi', 'Toggle navigation') }}</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                                    </svg>
                                </button>
                                <div class="flex flex-col gap-1">
                                    <span data-resident-chip data-tone="neutral">{{ RT::text('Halo', 'Hello') }}, {{ auth()->user()?->name }}</span>
                                    <h1 class="text-xl font-semibold text-slate-900 dark:text-slate-100">{{ $title ?? RT::text('Tagihan & Pembayaran', 'Bills & Payments') }}</h1>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('resident.layout.greeting_note') }}</p>
                                </div>
                            </div>
                            <div class="flex w-full max-w-xl flex-col gap-4 px-4 py-5 lg:w-full" data-resident-card data-variant="muted" data-resident-actions>
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex items-center gap-2 text-[0.68rem] font-semibold uppercase tracking-[0.28em] text-slate-400 dark:text-slate-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-sky-500 dark:text-sky-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1.5m0 15V21M6.364 6.364l1.06 1.06m9.152 9.152 1.06 1.06M3 12h1.5m15 0H21M6.364 17.636l1.06-1.06m9.152-9.152 1.06-1.06M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg>
                                        <span>{{ __('resident.layout.theme_label') }}</span>
                                    </div>
                                    <div class="theme-toggle theme-toggle--resident" role="group" aria-label="{{ __('resident.layout.theme_label') }}">
                                        <button type="button" data-theme-mode="light" class="theme-toggle__button" aria-pressed="false">
                                            <span class="sr-only">{{ __('resident.layout.theme_light') }}</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.75v-1.5m0 19.5v-1.5m8.25-8.25h1.5m-19.5 0h1.5m13.364-6.114.53-.531m-13.728 13.728.53-.53m12.668 0 .531.53M4.828 5.032l.53.53M17.25 12a5.25 5.25 0 1 1-10.5 0 5.25 5.25 0 0 1 10.5 0Z" />
                                            </svg>
                                        </button>
                                        <button type="button" data-theme-mode="dark" class="theme-toggle__button" aria-pressed="false">
                                            <span class="sr-only">{{ __('resident.layout.theme_dark') }}</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1 1 11.21 3 7.5 7.5 0 0 0 21 12.79Z" />
                                            </svg>
                                        </button>
                                        <button type="button" data-theme-mode="auto" class="theme-toggle__button" aria-pressed="false">
                                            <span class="sr-only">{{ __('resident.layout.theme_auto') }}</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1.5m0 15V21M6.364 6.364l1.06 1.06m9.152 9.152 1.06 1.06M3 12h1.5m15 0H21M6.364 17.636l1.06-1.06m9.152-9.152 1.06-1.06M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 rounded-2xl bg-white/90 px-3 py-3 shadow-inner shadow-white/45 ring-1 ring-white/70 transition-colors duration-300 dark:bg-slate-900/75 dark:shadow-slate-900/45 dark:ring-slate-700/65" data-resident-profile>
                                    <div class="h-11 w-11 overflow-hidden rounded-full bg-gradient-to-br from-emerald-400/25 via-emerald-300/15 to-sky-300/20 ring-2 ring-emerald-200/70 backdrop-blur-sm dark:from-emerald-500/25 dark:via-emerald-500/15 dark:to-sky-400/20 dark:ring-slate-700">
                                        @if (auth()->user()?->profile_photo_url)
                                            <img src="{{ auth()->user()->profile_photo_url }}" class="h-full w-full object-cover" alt="{{ RT::text('Avatar', 'Avatar') }}">
                                        @else
                                            <div class="flex h-full w-full items-center justify-center text-xs font-semibold text-emerald-600 dark:text-emerald-300">
                                                {{ Str::of(auth()->user()?->name)->trim()->explode(' ')->map(fn ($part) => Str::substr($part, 0, 1))->take(2)->implode('') }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="leading-relaxed text-xs text-slate-600 dark:text-slate-300">
                                        <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ auth()->user()?->name }}</p>
                                        <p class="text-[0.7rem] uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">{{ RT::text('Warga', 'Resident') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>
                <main class="mx-auto w-full max-w-6xl px-4 py-6 sm:px-6 lg:px-6 xl:px-8" data-resident-main data-resident-container>
                    @if (session('status'))
                        @php
                            $statusType = session('status_type', 'success');
                            $statusTone = [
                                'success' => ['tone' => 'success', 'text' => 'text-emerald-700 dark:text-emerald-200'],
                                'error' => ['tone' => 'danger', 'text' => 'text-rose-600 dark:text-rose-200'],
                            ][$statusType] ?? ['tone' => 'info', 'text' => 'text-slate-600 dark:text-slate-200'];
                        @endphp
                        <div class="mb-6" data-resident-card data-variant="muted">
                            <div class="flex items-start gap-3 p-4 text-sm {{ $statusTone['text'] }}">
                                <span data-resident-chip data-tone="{{ $statusTone['tone'] }}">{{ RT::text('Status', 'Status') }}</span>
                                <p class="leading-relaxed">{{ session('status') }}</p>
                            </div>
                        </div>
                    @endif
                    @hasSection('content')
                        @yield('content')
                    @else
                        {{ $slot ?? '' }}
                    @endif
                </main>
            </div>
        </div>
    </div>
    <livewire:resident.telegram-reminder />
    <livewire:resident.presence-probe />
    @livewireScripts
    @stack('scripts')
</body>
</html>
