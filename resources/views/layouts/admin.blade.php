@php
    $nav = [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'home'],
        ['label' => 'Data Warga', 'route' => 'admin.warga.index', 'icon' => 'users'],
        ['label' => 'Tagihan', 'route' => 'admin.tagihan.index', 'icon' => 'document'],
        ['label' => 'Pembayaran', 'route' => 'admin.pembayaran.index', 'icon' => 'wallet'],
        ['label' => 'Kelola Kas & Pengeluaran', 'route' => 'admin.kas.index', 'icon' => 'cashflow'],
        ['label' => 'Reminder Automatis', 'route' => 'admin.reminder.automation', 'icon' => 'bell'],
        ['label' => 'Laporan', 'route' => 'admin.laporan.index', 'icon' => 'chart'],
        ['label' => 'Agenda', 'route' => 'admin.agenda.index', 'icon' => 'calendar'],
        ['label' => 'Pengaturan', 'route' => 'admin.settings.general', 'icon' => 'settings'],
    ];

    // Inline SVG icons keyed by nav identifier for reuse in the sidebar.
    $navIcons = [
        'home' => <<<'SVG'
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true" class="h-4 w-4" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9.75 12 3l9 6.75M4.5 10.5V21h5.25v-5.25h4.5V21H19.5V10.5" />
            </svg>
        SVG,
        'users' => <<<'SVG'
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true" class="h-4 w-4" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 7.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm-9 11.25V18A4.5 4.5 0 0 1 12 13.5h0A4.5 4.5 0 0 1 16.5 18v.75M7.5 14.25a3 3 0 1 1-3-3m2.25 10.5v-.75a3 3 0 0 0-3-3H3" />
            </svg>
        SVG,
        'document' => <<<'SVG'
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true" class="h-4 w-4" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 13.5h6m-6 3h3.75M7.5 3.75A2.25 2.25 0 0 1 9.75 1.5h4.5A2.25 2.25 0 0 1 16.5 3.75v16.5l-4.5-2.25L7.5 20.25z" />
            </svg>
        SVG,
        'wallet' => <<<'SVG'
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true" class="h-4 w-4" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 7.5v-3A1.5 1.5 0 0 1 6 3h12a1.5 1.5 0 0 1 1.5 1.5v3M3 9.75A1.75 1.75 0 0 1 4.75 8h14.5A1.75 1.75 0 0 1 21 9.75v7.5A1.75 1.75 0 0 1 19.25 19H4.75A1.75 1.75 0 0 1 3 17.25z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 12.75h.008v.009h-.008z" />
            </svg>
        SVG,
        'cashflow' => <<<'SVG'
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true" class="h-4 w-4" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 6.75V4.5a1.5 1.5 0 0 1 1.5-1.5h3M6 1.5 9 4.5 6 7.5m13.5 9.75v2.25a1.5 1.5 0 0 1-1.5 1.5h-3m3 3-3-3 3-3M3 12h10.5m0 0 3-3m-3 3 3 3" />
            </svg>
        SVG,
        'bell' => <<<'SVG'
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true" class="h-4 w-4" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 18.75a1.5 1.5 0 0 1-3 0M3.75 18.75h16.5M5.25 18.75v-6a6.75 6.75 0 0 1 13.5 0v6" />
            </svg>
        SVG,
        'chart' => <<<'SVG'
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true" class="h-4 w-4" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7.5 14.25l3-3 3 3 4.5-4.5" />
            </svg>
        SVG,
        'calendar' => <<<'SVG'
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true" class="h-4 w-4" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v3m10.5-3v3M4.5 8.25h15m-13.5 3h3m3 0h3m-9 3h3m3 0h3m-9 3h3" />
                <rect width="15" height="15" x="4.5" y="5.25" rx="2" />
            </svg>
        SVG,
        'settings' => <<<'SVG'
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true" class="h-4 w-4" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.75a1.5 1.5 0 0 1 2.812 0l.219.657a1.5 1.5 0 0 0 1.425 1.039h.69a1.5 1.5 0 0 1 1.184 2.444l-.495.495a1.5 1.5 0 0 0 0 2.122l.495.495a1.5 1.5 0 0 1-1.184 2.444h-.69a1.5 1.5 0 0 0-1.425 1.039l-.219.657a1.5 1.5 0 0 1-2.812 0l-.219-.657a1.5 1.5 0 0 0-1.425-1.039h-.69a1.5 1.5 0 0 1-1.184-2.444l.495-.495a1.5 1.5 0 0 0 0-2.122l-.495-.495a1.5 1.5 0 0 1 1.184-2.444h.69a1.5 1.5 0 0 0 1.425-1.039z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" />
            </svg>
        SVG,
        'logout' => <<<'SVG'
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" aria-hidden="true" class="h-4 w-4" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6.75V5.25A2.25 2.25 0 0 0 13.5 3h-6A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25v-1.5M18 8.25 21.75 12 18 15.75M9.75 12h12" />
            </svg>
        SVG,
    ];

    $siteName = 'RT Smart';
    $logoInitials = 'SR';
    $logoUrl = null;

    if (\Illuminate\Support\Facades\Schema::hasTable('site_settings')) {
        $siteSettings = \App\Models\SiteSetting::keyValue()->toArray();
        $siteName = $siteSettings['site_name'] ?? $siteName;
        $logoInitials = $siteSettings['logo_initials'] ?? $logoInitials;
        $logoPath = $siteSettings['logo_path'] ?? null;

        if (! empty($logoPath)) {
            $logoUrl = \App\Support\StorageUrl::forPublicDisk($logoPath);
        }
    }

    $activeNav = collect($nav)->first(function (array $item) {
        $routeName = $item['route'];
        $nestedPattern = str_replace('.index', '', $routeName) . '.*';

        return request()->routeIs($routeName) || request()->routeIs($nestedPattern);
    });

    $pageTitle = $title ?? data_get($activeNav, 'label') ?? 'Panel Admin';
@endphp
<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50 transition-colors duration-300 dark:bg-black" data-app="admin">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }} - SIRW</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }

        .secret-toggle {
            display: inline-grid;
            place-items: center;
            border-radius: 9999px;
            width: 34px;
            height: 34px;
            color: #0369a1;
            background-color: rgba(255, 255, 255, 0.92);
            box-shadow: 0 10px 25px rgba(2, 132, 199, 0.12);
            transition: transform 0.2s ease, background-color 0.2s ease, color 0.2s ease;
        }

        .secret-toggle:hover {
            transform: translateY(-1px);
            background-color: rgba(2, 132, 199, 0.14);
            color: #0f172a;
        }

        .secret-toggle--inline {
            background-color: transparent;
            box-shadow: none;
        }

        .secret-toggle--inline:hover {
            background-color: rgba(2, 132, 199, 0.1);
        }

        .secret-toggle--surface {
            background-color: rgba(2, 132, 199, 0.08);
            box-shadow: none;
        }

        .secret-toggle--surface:hover {
            background-color: rgba(2, 132, 199, 0.16);
        }

        .secret-toggle svg {
            grid-area: 1 / 1;
            width: 18px;
            height: 18px;
            opacity: 0;
            transform: scale(0.6);
            transition: opacity 0.2s ease, transform 0.2s ease;
        }

        .secret-toggle[data-secret-visible="true"] [data-secret-icon="open"] {
            opacity: 1;
            transform: scale(1);
        }

        .secret-toggle[data-secret-visible="true"] [data-secret-icon="closed"] {
            opacity: 0;
            transform: scale(0.4);
        }

        .secret-toggle[data-secret-visible="false"] [data-secret-icon="open"] {
            opacity: 0;
            transform: scale(0.4);
        }

        .secret-toggle[data-secret-visible="false"] [data-secret-icon="closed"] {
            opacity: 1;
            transform: scale(1);
        }
    </style>
    @php
        $cspNonceValue = $cspNonce ?? (app()->bound('cspNonce') ? app('cspNonce') : null);
    @endphp
    @include('layouts.partials.theme-initializer')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/darkmode.css') }}">
@livewireStyles
</head>
<body class="h-full bg-transparent text-slate-900 antialiased transition-colors duration-300 dark:bg-black dark:text-slate-100">
    <div class="min-h-screen w-full" data-admin-root>
        <div class="relative flex min-h-screen" data-admin-shell>
            <aside
                id="adminSidebar"
                data-sidebar
                data-sidebar-collapse-class="-translate-x-full lg:-translate-x-full"
                data-sidebar-expand-class="translate-x-0 lg:translate-x-0"
                class="fixed inset-y-0 left-0 z-40 flex w-64 shrink-0 flex-col -translate-x-full border-r border-slate-200/70 bg-white/95 p-6 shadow-xl shadow-slate-900/5 transition-transform duration-300 dark:border-slate-800/70 dark:bg-slate-900/95 lg:relative lg:z-auto lg:translate-x-0"
                data-admin-sidebar
            >
                <div class="flex items-center gap-3">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="Logo" class="h-10 w-10 rounded-full object-cover shadow-lg shadow-sky-200/50 ring-2 ring-white dark:ring-slate-800">
                    @else
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-sky-500 to-emerald-500 text-sm font-semibold text-white shadow-lg shadow-sky-200/50">{{ $logoInitials }}</div>
                    @endif
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-600 dark:text-slate-400">Admin</p>
                        <p class="text-lg font-bold text-slate-900 dark:text-slate-100">{{ $siteName }}</p>
                    </div>
                </div>
                <nav class="mt-10 space-y-1" data-admin-nav>
                    @foreach ($nav as $item)
                        @php
                            $isActive = request()->routeIs($item['route']) || request()->routeIs(str_replace('.index', '', $item['route']).'.*');
                            $iconSvg = $navIcons[$item['icon']] ?? '';
                        @endphp
                        <a
                            href="{{ route($item['route']) }}"
                            class="flex items-center gap-3 rounded-lg border border-transparent px-4 py-2 text-sm font-medium transition-all duration-300 hover:bg-sky-50 hover:text-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-200 dark:hover:bg-slate-800/70 dark:hover:text-white {{ $isActive ? 'bg-sky-100 text-sky-700 hover:bg-sky-100 border-sky-200 dark:border-sky-400/70 dark:bg-slate-900/60 dark:text-white dark:hover:text-white dark:shadow-[0_0_22px_rgba(56,189,248,0.55)] dark:ring-1 dark:ring-sky-400/60 dark:ring-offset-1 dark:ring-offset-slate-950/80' : 'text-slate-700 dark:text-slate-300' }}"
                        >
                            <span class="flex h-8 w-8 items-center justify-center rounded-full border transition-colors duration-300 {{ $isActive ? 'border-sky-300/60 bg-sky-50 text-sky-600 dark:border-sky-400/60 dark:bg-sky-500/20 dark:text-white dark:shadow-[0_0_14px_rgba(56,189,248,0.45)]' : 'border-slate-200 bg-white text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400' }}">
                                {!! $iconSvg !!}
                            </span>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </nav>
                <hr class="my-6 border-slate-200 dark:border-slate-800">
                <button
                    type="button"
                    class="group flex w-full items-center gap-3 rounded-lg px-4 py-2 text-left text-sm font-semibold text-slate-600 transition-all duration-300 hover:bg-sky-50 hover:text-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-200 dark:text-slate-300 dark:hover:bg-slate-800/70 dark:hover:text-white dark:hover:ring-1 dark:hover:ring-sky-400/70 dark:hover:ring-offset-1 dark:hover:ring-offset-slate-950"
                    data-control-center-toggle
                    data-control-center-target="adminControlCenter"
                    aria-haspopup="true"
                    aria-expanded="false"
                    aria-controls="adminControlCenter"
                >
                    <span class="flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition-all duration-300 group-hover:border-sky-300 group-hover:bg-sky-50 group-hover:text-sky-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:group-hover:border-sky-400/70 dark:group-hover:bg-sky-500/10 dark:group-hover:text-white">
                        {!! $navIcons['settings'] !!}
                    </span>
                    <span class="transition-colors duration-300 group-hover:text-sky-700 dark:group-hover:text-white">Pusat Kontrol</span>
                </button>
                <div class="mt-12 rounded-2xl bg-slate-900 p-4 text-white shadow-lg shadow-slate-900/40 dark:bg-slate-800">
                    <p class="text-sm font-semibold">Reminder Otomatis</p>
                    <p class="mt-2 text-xs text-slate-200 dark:text-slate-300">Pastikan email warga tervalidasi untuk mengirim pengingat tagihan & kegiatan tepat waktu.</p>
                </div>
            </aside>
            <div class="fixed inset-0 z-30 bg-slate-900/40 opacity-0 pointer-events-none transition-opacity duration-300 lg:hidden" data-sidebar-overlay="adminSidebar"></div>
            <div
                class="flex min-h-screen flex-1 flex-col transition-[padding] duration-300 lg:pl-0 xl:pl-0"
                data-sidebar-content="adminSidebar"
                data-sidebar-open-class="lg:pl-0 xl:pl-0"
                data-sidebar-closed-class="lg:pl-0 xl:pl-0"
                data-admin-content-shell
            >
                <header class="sticky top-0 z-20 border-b border-slate-200/70 bg-white/90 backdrop-blur transition-colors duration-300 dark:border-slate-800/70 dark:bg-slate-900/80" data-admin-header>
                    <div class="mx-auto flex w-full max-w-6xl flex-col gap-4 px-4 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:gap-6 lg:px-6 xl:px-8" data-admin-header-inner>
                        <div class="flex items-start gap-3 lg:items-center">
                            <button
                                type="button"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm transition-colors duration-300 hover:border-[#0284C7] hover:text-[#0284C7] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0284C7] focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-300 dark:hover:border-sky-500 dark:hover:text-sky-300 dark:focus-visible:ring-sky-400 dark:focus-visible:ring-offset-slate-900 lg:hidden"
                                data-sidebar-toggle
                                data-sidebar-target="adminSidebar"
                                aria-controls="adminSidebar"
                                aria-expanded="false"
                            >
                                <span class="sr-only">Buka tutup navigasi</span>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>
                            <div>
                                <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                    <span
                                        data-admin-clock
                                        data-clock-locale="id-ID"
                                    >
                                        {{ __('Memuat waktu perangkat...') }}
                                    </span>
                                </p>
                                @php($resolvedTitleClass = $titleClass ?? 'text-slate-900 dark:text-slate-100')
                                <h1 class="text-xl font-semibold {{ $resolvedTitleClass }}">{{ $pageTitle }}</h1>
                            </div>
                        </div>
                        <div class="flex flex-col items-stretch gap-2 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
                            <div class="order-2 grid w-full gap-2 sm:order-1 sm:w-auto sm:grid-cols-2">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white/85 px-3 py-1.5 text-sm font-medium text-slate-600 shadow-sm transition duration-300 hover:border-[#0284C7] hover:text-[#0284C7] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0284C7] focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-slate-700/70 dark:bg-slate-800/70 dark:text-slate-300 dark:hover:border-sky-500 dark:hover:text-sky-300 dark:focus-visible:ring-sky-400 dark:focus-visible:ring-offset-slate-900"
                                    data-command-open
                                >
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 19.5-4.35-4.35m0 0a6 6 0 1 0-8.49-8.49 6 6 0 0 0 8.49 8.49Z" />
                                    </svg>
                                    <span class="hidden sm:inline">Cari / Perintah</span>
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-500 dark:bg-slate-700/70 dark:text-slate-400">Ctrl + K</span>
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white/85 px-3 py-1.5 text-sm font-semibold text-slate-600 shadow-sm transition duration-300 hover:border-sky-300 hover:text-sky-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-300 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-slate-700/70 dark:bg-slate-800/70 dark:text-slate-200 dark:hover:border-sky-500 dark:hover:text-sky-300 dark:focus-visible:ring-sky-400 dark:focus-visible:ring-offset-slate-900"
                                    data-copilot-open
                                >
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8.25c0-1.012 0-1.518.094-1.986a4 4 0 0 1 3.17-3.17C6.732 3 7.238 3 8.25 3h7.5c1.012 0 1.518 0 1.986.094a4 4 0 0 1 3.17 3.17c.094.468.094.974.094 1.986v7.5c0 1.012 0 1.518-.094 1.986a4 4 0 0 1-3.17 3.17c-.468.094-.974.094-1.986.094h-7.5c-1.012 0-1.518 0-1.986-.094a4 4 0 0 1-3.17-3.17C3 17.268 3 16.762 3 15.75Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9a3.75 3.75 0 0 1 3.75-3.75m0 0A3.75 3.75 0 0 1 15.75 9m-3.75-3.75V9m0 9.75a3.75 3.75 0 0 1-3.75-3.75m3.75 3.75a3.75 3.75 0 0 0 3.75-3.75m-3.75 3.75V15" />
                                    </svg>
                                    <span class="hidden sm:inline">Buka CoPilot</span>
                                </button>
                            </div>
                            <div class="order-1 flex w-full items-center justify-end gap-3 sm:order-2 sm:w-auto">
                                <div class="flex shrink-0 items-center gap-2 rounded-full border border-slate-200 bg-white/85 px-3 py-1.5 shadow-sm transition duration-300 dark:border-slate-700/70 dark:bg-slate-800/70">
                                    <div class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white shadow-inner shadow-slate-200/50 dark:border-slate-700 dark:bg-slate-900">
                                        @if (auth()->user()?->profile_photo_url)
                                            <img src="{{ auth()->user()->profile_photo_url }}" alt="avatar" class="h-full w-full rounded-full object-cover">
                                        @else
                                            <div class="flex h-full w-full items-center justify-center text-[10px] font-semibold text-sky-600 dark:text-sky-300">
                                                {{ Str::of(auth()->user()?->name)->trim()->explode(' ')->map(fn ($part) => Str::substr($part, 0, 1))->take(2)->implode('') }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="hidden text-[11px] leading-tight text-slate-600 dark:text-slate-300 sm:block">
                                        <p class="font-semibold">{{ auth()->user()?->name }}</p>
                                        <p class="text-slate-400 dark:text-slate-500">Administrator</p>
                                    </div>
                                </div>
                                <div class="relative" data-control-center-root>
                                    <button
                                        type="button"
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm transition-colors duration-300 hover:border-[#0284C7] hover:text-[#0284C7] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0284C7] focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-300 dark:hover:border-sky-500 dark:hover:text-sky-300 dark:focus-visible:ring-sky-400 dark:focus-visible:ring-offset-slate-900"
                                        data-control-center-toggle
                                        aria-haspopup="true"
                                        aria-expanded="false"
                                        aria-controls="adminControlCenter"
                                    >
                                        <span class="sr-only">Buka pusat kontrol</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 0 1 .75-.75h7.5a.75.75 0 0 1 0 1.5h-7.5A.75.75 0 0 1 12 6.75Zm0 5.25c0-.414.336-.75.75-.75h7.5a.75.75 0 0 1 0 1.5h-7.5a.75.75 0 0 1-.75-.75Zm0 6c0-.414.336-.75.75-.75h7.5a.75.75 0 0 1 0 1.5h-7.5a.75.75 0 0 1-.75-.75ZM3.75 7.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm0 6a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm0 6a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
                                        </svg>
                                    </button>
                                    <div
                                        id="adminControlCenter"
                                        class="pointer-events-none absolute right-0 top-12 z-40 w-72 origin-top-right scale-95 transform rounded-2xl border border-slate-200/70 bg-white/95 p-4 opacity-0 shadow-2xl shadow-slate-900/5 ring-1 ring-slate-900/5 transition duration-200 dark:border-slate-700/70 dark:bg-slate-900/90 dark:ring-0"
                                        data-control-center-panel
                                        data-open="false"
                                        role="dialog"
                                        aria-modal="true"
                                        aria-label="Pusat kontrol administrator"
                                    >
                                        <div class="flex items-start gap-3">
                                            <div class="relative flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white shadow-inner shadow-slate-200/50 dark:border-slate-700 dark:bg-slate-800">
                                                @if (auth()->user()?->profile_photo_url)
                                                    <img src="{{ auth()->user()->profile_photo_url }}" alt="avatar" class="h-full w-full rounded-full object-cover">
                                                @else
                                                    <div class="flex h-full w-full items-center justify-center text-xs font-semibold text-sky-600 dark:text-sky-300">
                                                        {{ Str::of(auth()->user()?->name)->trim()->explode(' ')->map(fn ($part) => Str::substr($part, 0, 1))->take(2)->implode('') }}
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ auth()->user()?->name }}</p>
                                                <p class="text-xs text-slate-500 dark:text-slate-400">Administrator</p>
                                            </div>
                                            <button
                                                type="button"
                                                class="ml-auto inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:text-slate-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-300 dark:text-slate-500 dark:hover:text-slate-300 dark:focus-visible:ring-slate-600"
                                                data-control-center-close
                                                aria-label="Tutup pusat kontrol"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75 17.25 17.25m0-10.5-10.5 10.5" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="mt-4 space-y-4">
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">Preferensi Tampilan</p>
                                                <div class="mt-2 inline-flex w-full items-center justify-between rounded-full border border-slate-200 bg-slate-100/80 p-1 shadow-inner shadow-slate-200/40 dark:border-slate-700 dark:bg-slate-900/80">
                                                    <button type="button" data-theme-mode="light" class="theme-toggle__button flex-1 rounded-full px-3 py-1 text-xs font-medium" aria-pressed="false">Terang</button>
                                                    <button type="button" data-theme-mode="dark" class="theme-toggle__button flex-1 rounded-full px-3 py-1 text-xs font-medium" aria-pressed="false">Gelap</button>
                                                    <button type="button" data-theme-mode="auto" class="theme-toggle__button flex-1 rounded-full px-3 py-1 text-xs font-medium" aria-pressed="false">Otomatis</button>
                                                </div>
                                            </div>
                                            <div class="space-y-2 rounded-2xl border border-slate-200/80 bg-white/70 p-3 dark:border-slate-700/70 dark:bg-slate-900/60">
                                                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Animasi Antarmuka</p>
                                                <button
                                                    type="button"
                                                    class="inline-flex w-full items-center justify-between rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 transition hover:border-sky-200 hover:text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-sky-500/60"
                                                    data-calm-toggle
                                                    aria-pressed="false"
                                                >
                                                    <span data-calm-state>Mode Tenang nonaktif</span>
                                                    <span class="flex h-2.5 w-6 items-center rounded-full bg-slate-200 px-0.5 transition dark:bg-slate-700">
                                                        <span class="h-2 w-2 rounded-full bg-slate-500 transition-all duration-300 ease-out dark:bg-slate-400" data-calm-indicator></span>
                                                    </span>
                                                </button>
                                                <p class="text-[11px] text-slate-400 dark:text-slate-500">Pusat kontrol menghormati preferensi <code>prefers-reduced-motion</code>.</p>
                                            </div>
                                            <div class="space-y-2 border-t border-slate-200 pt-3 dark:border-slate-700/60">
                                                <a href="{{ route('admin.profile.view') }}" class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 transition hover:border-sky-200 hover:text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-sky-500/60">
                                                    Kelola Profil
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5h11.25M8.25 9H19.5M8.25 13.5H19.5M4.5 4.5h.008v.008H4.5V4.5Zm0 4.5h.008v.008H4.5V9Zm0 4.5h.008v.008H4.5V13.5Zm0 4.5h.008v.008H4.5V18Z" />
                                                    </svg>
                                                </a>
                                                <form method="POST" action="{{ route('logout') }}">
                                                    @csrf
                                                    <button type="submit" class="flex w-full items-center justify-between rounded-xl border border-rose-200/60 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-600 transition hover:border-rose-300 hover:bg-rose-100 dark:border-rose-500/40 dark:bg-rose-500/10 dark:text-rose-200 dark:hover:border-rose-400 dark:hover:bg-rose-500/20">
                                                        Keluar dengan aman
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l3-3m0 0 3 3m-3-3v12" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>
                <main class="flex-1" data-admin-main>
                    <div class="mx-auto w-full max-w-6xl px-4 py-6 sm:px-6 lg:px-6 xl:px-8" data-admin-container>
                    @if (session('status'))
                        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-200">
                            {{ session('status') }}
                        </div>
                    @endif
                    {{ $slot }}
                    </div>
                </main>
                <div
                    class="fixed inset-0 z-[70] pointer-events-none opacity-0 transition duration-200"
                    data-command-root
                    data-command-open="false"
                    aria-hidden="true"
                    style="display: none;"
                >
                    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm opacity-0 transition-opacity duration-200 dark:bg-slate-950/60" data-command-backdrop></div>
                    <div class="relative mx-auto mt-24 w-full max-w-3xl scale-95 transform rounded-3xl border border-slate-200/70 bg-white/95 p-6 opacity-0 shadow-2xl shadow-slate-900/10 ring-1 ring-slate-900/10 transition duration-200 dark:border-slate-700/70 dark:bg-slate-900/95 dark:shadow-slate-900/40 dark:ring-0" data-command-panel role="dialog" aria-modal="true" aria-label="Command bar administrator">
                        <form action="{{ route('admin.search') }}" method="GET" data-command-form class="relative flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-inner shadow-slate-200/40 dark:border-slate-700 dark:bg-slate-900">
                            <svg class="h-5 w-5 text-slate-400 dark:text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 19.5-4.35-4.35m0 0a6 6 0 1 0-8.49-8.49 6 6 0 0 0 8.49 8.49Z" />
                            </svg>
                            <input
                                data-command-input
                                name="q"
                                type="search"
                                autocomplete="off"
                                placeholder="Tanya apa saja: “cetak laporan kas bulan ini” atau “buka data warga”"
                                class="h-9 w-full bg-transparent text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none dark:text-slate-100 dark:placeholder:text-slate-500"
                            >
                            <span class="hidden items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-[11px] font-medium text-slate-500 dark:bg-slate-800/80 dark:text-slate-400 md:flex">
                                <kbd class="rounded bg-white px-1 py-0.5 shadow dark:bg-slate-900">Enter</kbd>
                                Jalankan
                            </span>
                        </form>
                        <div class="mt-4 max-h-72 overflow-y-auto" data-command-content>
                            <div class="space-y-4" data-command-groups>
                                <div class="space-y-2" data-command-group>
                                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Navigasi</p>
                                    <ul class="space-y-1" role="listbox">
                                        <li
                                            role="option"
                                            tabindex="-1"
                                            class="command-item"
                                            data-command-item
                                            data-command-keywords="dashboard ringkasan aktivitas overview beranda"
                                            data-command-url="{{ route('admin.dashboard') }}"
                                        >
                                            <button type="button" class="flex w-full items-center justify-between rounded-xl border border-transparent px-4 py-3 text-left text-sm font-medium text-slate-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-slate-800 dark:text-slate-200 dark:hover:border-sky-500/40 dark:hover:bg-slate-800/60 dark:hover:text-white">
                                                <span>
                                                    <span class="block text-sm font-semibold">Buka Dashboard</span>
                                                    <span class="block text-xs text-slate-400 dark:text-slate-500">Lihat ringkasan aktivitas dan statistik realtime.</span>
                                                </span>
                                                <span class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-300 dark:text-slate-500">Go</span>
                                            </button>
                                        </li>
                                        <li role="option" tabindex="-1" class="command-item" data-command-item data-command-type="action" data-command-action="open-copilot" data-command-keywords="copilot insight rekomendasi ai asisten">
                                            <button type="button" class="flex w-full items-center justify-between rounded-xl border border-transparent px-4 py-3 text-left text-sm font-medium text-slate-600 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-slate-800 dark:text-slate-200 dark:hover:border-emerald-500/40 dark:hover:bg-slate-800/60 dark:hover:text-white">
                                                <span>
                                                    <span class="block text-sm font-semibold">Buka CoPilot</span>
                                                    <span class="block text-xs text-slate-400 dark:text-slate-500">Tampilkan insight prioritas dan rekomendasi otomatis.</span>
                                                </span>
                                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-[0.2em] text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-200">C</span>
                                            </button>
                                        </li>
                                        <li role="option" tabindex="-1" class="command-item" data-command-item data-command-keywords="warga resident data penduduk profil" data-command-url="{{ route('admin.warga.index') }}">
                                            <button type="button" class="flex w-full items-center justify-between rounded-xl border border-transparent px-4 py-3 text-left text-sm font-medium text-slate-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-slate-800 dark:text-slate-200 dark:hover:border-sky-500/40 dark:hover:bg-slate-800/60 dark:hover:text-white">
                                                <span>
                                                    <span class="block text-sm font-semibold">Kelola Data Warga</span>
                                                    <span class="block text-xs text-slate-400 dark:text-slate-500">Cari keluarga, ubah status, atau unduh data penduduk.</span>
                                                </span>
                                                <span class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-300 dark:text-slate-500">Go</span>
                                            </button>
                                        </li>
                                        <li role="option" tabindex="-1" class="command-item" data-command-item data-command-keywords="tagihan iuran rekening billing" data-command-url="{{ route('admin.tagihan.index') }}">
                                            <button type="button" class="flex w-full items-center justify-between rounded-xl border border-transparent px-4 py-3 text-left text-sm font-medium text-slate-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-slate-800 dark:text-slate-200 dark:hover:border-sky-500/40 dark:hover:bg-slate-800/60 dark:hover:text-white">
                                                <span>
                                                    <span class="block text-sm font-semibold">Lihat Daftar Tagihan</span>
                                                    <span class="block text-xs text-slate-400 dark:text-slate-500">Verifikasi tagihan aktif, status pembayaran, dan jatuh tempo.</span>
                                                </span>
                                                <span class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-300 dark:text-slate-500">Go</span>
                                            </button>
                                        </li>
                                        <li role="option" tabindex="-1" class="command-item" data-command-item data-command-keywords="pembayaran kas transaksi verifikasi" data-command-url="{{ route('admin.pembayaran.index') }}">
                                            <button type="button" class="flex w-full items-center justify-between rounded-xl border border-transparent px-4 py-3 text-left text-sm font-medium text-slate-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-slate-800 dark:text-slate-200 dark:hover:border-sky-500/40 dark:hover:bg-slate-800/60 dark:hover:text-white">
                                                <span>
                                                    <span class="block text-sm font-semibold">Pantau Pembayaran</span>
                                                    <span class="block text-xs text-slate-400 dark:text-slate-500">Verifikasi bukti bayar atau tindak lanjuti keterlambatan.</span>
                                                </span>
                                                <span class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-300 dark:text-slate-500">Go</span>
                                            </button>
                                        </li>
                                        <li role="option" tabindex="-1" class="command-item" data-command-item data-command-keywords="kas arus uang pemasukan pengeluaran" data-command-url="{{ route('admin.kas.index') }}">
                                            <button type="button" class="flex w-full items-center justify-between rounded-xl border border-transparent px-4 py-3 text-left text-sm font-medium text-slate-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-slate-800 dark:text-slate-200 dark:hover:border-sky-500/40 dark:hover:bg-slate-800/60 dark:hover:text-white">
                                                <span>
                                                    <span class="block text-sm font-semibold">Kelola Kas & Pengeluaran</span>
                                                    <span class="block text-xs text-slate-400 dark:text-slate-500">Analisis arus kas terbaru dan detail transaksi manual.</span>
                                                </span>
                                                <span class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-300 dark:text-slate-500">Go</span>
                                            </button>
                                        </li>
                                        <li role="option" tabindex="-1" class="command-item" data-command-item data-command-keywords="reminder otomatis pengingat automation notifikasi" data-command-url="{{ route('admin.reminder.automation') }}">
                                            <button type="button" class="flex w-full items-center justify-between rounded-xl border border-transparent px-4 py-3 text-left text-sm font-medium text-slate-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-slate-800 dark:text-slate-200 dark:hover:border-sky-500/40 dark:hover:bg-slate-800/60 dark:hover:text-white">
                                                <span>
                                                    <span class="block text-sm font-semibold">Atur Reminder Otomatis</span>
                                                    <span class="block text-xs text-slate-400 dark:text-slate-500">Pantau jadwal pengingat dan personalisasi pesan warga.</span>
                                                </span>
                                                <span class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-300 dark:text-slate-500">Go</span>
                                            </button>
                                        </li>
                                        <li role="option" tabindex="-1" class="command-item" data-command-item data-command-keywords="agenda kegiatan rapat event" data-command-url="{{ route('admin.agenda.index') }}">
                                            <button type="button" class="flex w-full items-center justify-between rounded-xl border border-transparent px-4 py-3 text-left text-sm font-medium text-slate-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-slate-800 dark:text-slate-200 dark:hover:border-sky-500/40 dark:hover:bg-slate-800/60 dark:hover:text-white">
                                                <span>
                                                    <span class="block text-sm font-semibold">Kelola Agenda</span>
                                                    <span class="block text-xs text-slate-400 dark:text-slate-500">Lihat jadwal kegiatan dan status publikasi kepada warga.</span>
                                                </span>
                                                <span class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-300 dark:text-slate-500">Go</span>
                                            </button>
                                        </li>
                                        <li role="option" tabindex="-1" class="command-item" data-command-item data-command-keywords="laporan analytics insight pdf" data-command-url="{{ route('admin.laporan.index') }}">
                                            <button type="button" class="flex w-full items-center justify-between rounded-xl border border-transparent px-4 py-3 text-left text-sm font-medium text-slate-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-slate-800 dark:text-slate-200 dark:hover:border-sky-500/40 dark:hover:bg-slate-800/60 dark:hover:text-white">
                                                <span>
                                                    <span class="block text-sm font-semibold">Buka Laporan</span>
                                                    <span class="block text-xs text-slate-400 dark:text-slate-500">Susun laporan keuangan dan aktivitas warga siap dibagikan.</span>
                                                </span>
                                                <span class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-300 dark:text-slate-500">Go</span>
                                            </button>
                                        </li>
                                        <li role="option" tabindex="-1" class="command-item" data-command-item data-command-keywords="pengaturan konfigurasi general settings preferensi" data-command-url="{{ route('admin.settings.general') }}">
                                            <button type="button" class="flex w-full items-center justify-between rounded-xl border border-transparent px-4 py-3 text-left text-sm font-medium text-slate-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-slate-800 dark:text-slate-200 dark:hover:border-sky-500/40 dark:hover:bg-slate-800/60 dark:hover:text-white">
                                                <span>
                                                    <span class="block text-sm font-semibold">Pengaturan Umum</span>
                                                    <span class="block text-xs text-slate-400 dark:text-slate-500">Kelola identitas portal, branding, dan metadata komunitas.</span>
                                                </span>
                                                <span class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-300 dark:text-slate-500">Go</span>
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                                <div class="space-y-2" data-command-group>
                                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Tindakan Cepat</p>
                                    <ul class="space-y-1" role="listbox">
                                        <li role="option" tabindex="-1" class="command-item" data-command-item data-command-type="action" data-command-keywords="tema light dark auto mode" data-command-action="cycle-theme">
                                            <button type="button" class="flex w-full items-center justify-between rounded-xl border border-transparent px-4 py-3 text-left text-sm font-medium text-slate-600 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-slate-800 dark:text-slate-200 dark:hover:border-emerald-500/40 dark:hover:bg-slate-800/60 dark:hover:text-white">
                                                <span>
                                                    <span class="block text-sm font-semibold">Ganti mode tampilan</span>
                                                    <span class="block text-xs text-slate-400 dark:text-slate-500">Siklus cepat antara terang, gelap, dan otomatis.</span>
                                                </span>
                                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-[0.2em] text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-200">Shift + T</span>
                                            </button>
                                        </li>
                                        <li role="option" tabindex="-1" class="command-item" data-command-item data-command-type="action" data-command-keywords="tenang animasi calm motion prefers reduce" data-command-action="toggle-calm">
                                            <button type="button" class="flex w-full items-center justify-between rounded-xl border border-transparent px-4 py-3 text-left text-sm font-medium text-slate-600 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-slate-800 dark:text-slate-200 dark:hover:border-emerald-500/40 dark:hover:bg-slate-800/60 dark:hover:text-white">
                                                <span>
                                                    <span class="block text-sm font-semibold">Toggle Mode Tenang</span>
                                                    <span class="block text-xs text-slate-400 dark:text-slate-500">Perhalus animasi sesuai preferensi aksesibilitas.</span>
                                                </span>
                                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-[0.2em] text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-200">Shift + M</span>
                                            </button>
                                        </li>
                                        <li role="option" tabindex="-1" class="command-item" data-command-item data-command-type="action" data-command-keywords="profil akun identitas" data-command-action="open-profile" data-command-url="{{ route('admin.profile.view') }}">
                                            <button type="button" class="flex w-full items-center justify-between rounded-xl border border-transparent px-4 py-3 text-left text-sm font-medium text-slate-600 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-slate-800 dark:text-slate-200 dark:hover:border-emerald-500/40 dark:hover:bg-slate-800/60 dark:hover:text-white">
                                                <span>
                                                    <span class="block text-sm font-semibold">Buka Pengaturan Profil</span>
                                                    <span class="block text-xs text-slate-400 dark:text-slate-500">Edit data akun, foto, dan preferensi keamanan.</span>
                                                </span>
                                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-[0.2em] text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-200">P</span>
                                            </button>
                                        </li>
                                        <li role="option" tabindex="-1" class="command-item" data-command-item data-command-type="action" data-command-action="focus-control-center" data-command-keywords="pusat kontrol tema motion logout" data-command-target="adminControlCenter">
                                            <button type="button" class="flex w-full items-center justify-between rounded-xl border border-transparent px-4 py-3 text-left text-sm font-medium text-slate-600 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-slate-800 dark:text-slate-200 dark:hover:border-emerald-500/40 dark:hover:bg-slate-800/60 dark:hover:text-white">
                                                <span>
                                                    <span class="block text-sm font-semibold">Buka Pusat Kontrol</span>
                                                    <span class="block text-xs text-slate-400 dark:text-slate-500">Kelola tema, mode tenang, dan keluar dengan aman.</span>
                                                </span>
                                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-[0.2em] text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-200">C</span>
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="hidden rounded-2xl border border-slate-200/70 bg-slate-50/70 px-4 py-6 text-center text-sm text-slate-500 dark:border-slate-700/70 dark:bg-slate-900/70 dark:text-slate-400" data-command-empty>
                                Tidak ada hasil cepat. Tekan <strong class="font-semibold text-slate-600 dark:text-slate-200">Enter</strong> untuk mencari melalui portal.
                            </div>
                        </div>
                        <div class="mt-4 flex items-center justify-between text-[11px] font-medium uppercase tracking-[0.3em] text-slate-300 dark:text-slate-500">
                            <span>Esc untuk keluar</span>
                            <span>Livewire aware</span>
                        </div>
                    </div>
                </div>
                @livewire('admin.copilot.panel')
            </div>
        </div>
    </div>
    @livewireScripts
    @stack('scripts')
    <script @if($cspNonceValue) nonce="{{ $cspNonceValue }}" @endif>
        (() => {
            const toggleSecretInput = (button) => {
                const targetId = button.getAttribute('data-secret-toggle');
                if (!targetId) {
                    return;
                }
                const input = document.getElementById(targetId);
                if (!input) {
                    return;
                }
                const currentlyVisible = input.type !== 'password';
                const nextVisible = !currentlyVisible;

                input.setAttribute('type', nextVisible ? 'text' : 'password');
                button.setAttribute('data-secret-visible', nextVisible ? 'true' : 'false');
                button.setAttribute('aria-pressed', nextVisible ? 'true' : 'false');
                button.setAttribute('aria-label', nextVisible ? 'Sembunyikan nilai rahasia' : 'Tampilkan nilai rahasia');

            };

            document.addEventListener('click', (event) => {
                const trigger = event.target.closest('[data-secret-toggle]');
                if (!trigger) {
                    return;
                }
                event.preventDefault();
                toggleSecretInput(trigger);
            });
        })();
    </script>
</body>
</html>
