<div class="tools-cluster flex min-w-0 flex-nowrap items-center gap-2 sm:gap-3">
    <div class="theme-toggle flex items-center gap-2 rounded-full border border-slate-200 bg-white/70 px-1.5 py-1 text-xs font-medium text-slate-600 shadow-sm shadow-sky-100/50 transition-all duration-300 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-300">
        <button type="button" data-theme-mode="light" class="theme-toggle__button focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-sky-400 dark:focus-visible:ring-offset-slate-900" aria-pressed="false">
            <span class="sr-only">Mode terang</span>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1.5m0 15V21M6.364 6.364l1.06 1.06m9.152 9.152 1.06 1.06M3 12h1.5m15 0H21M6.364 17.636l1.06-1.06m9.152-9.152 1.06-1.06M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            </svg>
        </button>
        <button type="button" data-theme-mode="dark" class="theme-toggle__button focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-sky-400 dark:focus-visible:ring-offset-slate-900" aria-pressed="false">
            <span class="sr-only">Mode gelap</span>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1 1 11.21 3 7.5 7.5 0 0 0 21 12.79Z" />
            </svg>
        </button>
        <button type="button" data-theme-mode="auto" class="theme-toggle__button focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-sky-400 dark:focus-visible:ring-offset-slate-900" aria-pressed="false">
            <span class="sr-only">Ikuti sistem</span>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1.5m0 15V21M6.364 6.364l1.06 1.06m9.152 9.152 1.06 1.06M3 12h1.5m15 0H21M6.364 17.636l1.06-1.06m9.152-9.152 1.06-1.06M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            </svg>
        </button>
    </div>
    <div class="relative" data-language-switcher>
        <button type="button" data-language-toggle aria-expanded="false" class="language-button focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-sky-400 dark:focus-visible:ring-offset-slate-900" aria-label="Pilih bahasa">
            <x-flag :locale="$currentCode" data-language-flag />
            <span class="language-code" data-language-label>{{ $currentCode }}</span>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
            </svg>
        </button>
        <div class="language-menu hidden space-y-1" data-language-menu>
            <button type="button" class="language-option {{ $currentCode === 'ID' ? 'language-option--active' : '' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-sky-400 dark:focus-visible:ring-offset-slate-900" data-language-option="id" aria-pressed="{{ $currentCode === 'ID' ? 'true' : 'false' }}">
                <x-flag locale="ID" class="shrink-0" data-language-flag />
                <div class="language-option__meta flex-1">
                    <span class="language-option__name" data-language-name>Bahasa Indonesia</span>
                    <span class="language-option__code" data-language-code>ID</span>
                </div>
            </button>
            <button type="button" class="language-option {{ $currentCode === 'EN' ? 'language-option--active' : '' }} focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-sky-400 dark:focus-visible:ring-offset-slate-900" data-language-option="en" aria-pressed="{{ $currentCode === 'EN' ? 'true' : 'false' }}">
                <x-flag locale="EN" class="shrink-0" data-language-flag />
                <div class="language-option__meta flex-1">
                    <span class="language-option__name" data-language-name>English</span>
                    <span class="language-option__code" data-language-code>EN</span>
                </div>
            </button>
        </div>
    </div>
</div>
