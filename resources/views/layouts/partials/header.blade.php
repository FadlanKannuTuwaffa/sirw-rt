<div class="mx-auto flex w-full max-w-7xl items-center justify-between gap-4 px-4 py-3 text-sm font-semibold sm:px-6 lg:px-8 md:py-5">
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
    <nav class="hidden items-center gap-6 md:flex" data-desktop-nav>
        @foreach ($navigationLinks as $link)
            <a href="{{ route($link['route']) }}" class="relative inline-flex items-center gap-1 transition-all duration-300 hover:-translate-y-0.5 hover:text-sky-600 dark:hover:text-sky-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-sky-400 dark:focus-visible:ring-offset-slate-900 {{ request()->routeIs($link['route']) ? 'text-sky-600 dark:text-sky-400 after:absolute after:-bottom-1 after:left-0 after:h-0.5 after:w-full after:rounded-full after:bg-sky-500 dark:after:bg-sky-400' : 'text-slate-600 dark:text-slate-300' }}" data-i18n="{{ $link['label'] }}">{{ $link['text'] }}</a>
        @endforeach
    </nav>
    <div class="hidden md:flex items-center gap-4">
        @includeWhen(View::exists('layouts.partials.tools_cluster'), 'layouts.partials.tools_cluster')
        <div class="flex items-center gap-2">
            <a href="{{ route('login') }}" class="btn-pill btn-pill--ghost flex-shrink-0" data-i18n="nav.login">Masuk</a>
            <a href="{{ route('register') }}" class="btn-pill btn-pill--solid flex-shrink-0" data-i18n="nav.register">Daftar</a>
        </div>
    </div>
</div>
