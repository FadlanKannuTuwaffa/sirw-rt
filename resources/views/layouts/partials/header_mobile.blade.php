<div class="container-app flex items-center justify-between gap-3 py-3 text-sm font-semibold">
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
    <button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200/70 bg-white/80 text-slate-600 shadow-sm shadow-sky-100 transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-slate-700/70 dark:bg-slate-900/80 dark:text-slate-300 dark:shadow-slate-950/40 dark:focus-visible:ring-sky-400 dark:focus-visible:ring-offset-slate-900 md:hidden" data-mobile-nav-toggle aria-expanded="false" aria-label="Buka navigasi" data-i18n="nav.toggle_open" data-i18n-attr="aria-label">
        <span class="sr-only" data-nav-toggle-label data-i18n="nav.toggle_open">Buka navigasi</span>
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5m-16.5 5.25h16.5m-16.5 5.25h16.5" />
        </svg>
    </button>
</div>

<div class="mobile-menu hidden w-full border-t border-slate-200 bg-white/90 px-4 py-4 shadow-lg shadow-slate-200/60 backdrop-blur transition-all duration-300 dark:border-slate-800 dark:bg-slate-900/90 dark:shadow-slate-950/50 md:hidden" data-mobile-nav-menu>
    <div class="mobile-menu__inner flex flex-col gap-6">
        <div class="mobile-menu__section mobile-menu__section--surface" data-mobile-tools-stack>
            <span class="mobile-menu__section-label">Pengaturan Tampilan</span>
            <div class="mobile-menu__utilities w-full">
                @includeWhen(View::exists('layouts.partials.tools_cluster'), 'layouts.partials.tools_cluster')
            </div>
        </div>
        <div class="mobile-menu__section mobile-menu__section--nav">
            <nav class="mobile-menu__nav flex flex-col gap-1 text-slate-600 dark:text-slate-300">
                @foreach ($navigationLinks as $link)
                    <a href="{{ route($link['route']) }}" class="mobile-menu__link {{ request()->routeIs($link['route']) ? 'mobile-menu__link--active' : '' }}" data-i18n="{{ $link['label'] }}">{{ $link['text'] }}</a>
                @endforeach
            </nav>
        </div>
        <div class="mobile-menu__section mobile-menu__section--surface">
            <span class="mobile-menu__section-label">Akses Akun</span>
            <div class="flex flex-col gap-2">
                <a href="{{ route('login') }}" class="btn-mobile-menu btn-mobile-menu--primary" data-i18n="nav.login">Masuk</a>
                <a href="{{ route('register') }}" class="btn-mobile-menu btn-mobile-menu--ghost" data-i18n="nav.register">Daftar</a>
            </div>
        </div>
    </div>
</div>
