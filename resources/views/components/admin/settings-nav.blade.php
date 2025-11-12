@props(['current' => null])

@php
    $items = [
        [
            'key' => 'general',
            'label' => 'Pengaturan Umum',
            'description' => 'Identitas & kontak utama',
            'route' => 'admin.settings.general',
            'icon' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94a1.5 1.5 0 0 1 2.812 0l.379 1.108a1.5 1.5 0 0 0 1.383 1.03l1.169.034a1.5 1.5 0 0 1 .882 2.676l-.92.743a1.5 1.5 0 0 0-.5 1.59l.347 1.133a1.5 1.5 0 0 1-1.427 1.954l-1.166-.067a1.5 1.5 0 0 0-1.281.673l-.63.95a1.5 1.5 0 0 1-2.524 0l-.63-.95a1.5 1.5 0 0 0-1.28-.673l-1.167.067a1.5 1.5 0 0 1-1.427-1.954l.347-1.133a1.5 1.5 0 0 0-.5-1.59l-.92-.743a1.5 1.5 0 0 1 .882-2.676l1.17-.034a1.5 1.5 0 0 0 1.382-1.03L9.594 3.94Z" />
    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
</svg>
SVG,
        ],
        [
            'key' => 'assistant-analytics',
            'label' => 'Analytics Asisten',
            'description' => 'Pemakaian & performa AI',
            'route' => 'admin.settings.analytics',
            'icon' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7.5 15l3-3 2.25 2.25L18 9" />
</svg>
SVG,
        ],
        [
            'key' => 'llm-candidates',
            'label' => 'LLM Candidates',
            'description' => 'Kurasi snapshot fallback',
            'route' => 'admin.settings.llm-candidates',
            'icon' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3v1.5m4.5-1.5v1.5M4.5 7.5h15m-13.5 0V19.5A1.5 1.5 0 0 0 7.5 21h9a1.5 1.5 0 0 0 1.5-1.5V7.5m-10.5 6h6m-6 3h3" />
</svg>
SVG,
        ],
        [
            'key' => 'slider',
            'label' => 'Slider Landing',
            'description' => 'Konten hero & urutan slide',
            'route' => 'admin.settings.slider',
            'icon' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.5h16.5M3.75 9.75h16.5M3.75 15h16.5M3.75 5.625A1.875 1.875 0 0 1 5.625 3.75h12.75a1.875 1.875 0 0 1 1.875 1.875v12.75a1.875 1.875 0 0 1-1.875 1.875H5.625A1.875 1.875 0 0 1 3.75 18.375V5.625Z" />
</svg>
SVG,
        ],
        [
            'key' => 'smtp',
            'label' => 'SMTP & Email',
            'description' => 'Kredensial & identitas pengirim',
            'route' => 'admin.settings.smtp',
            'icon' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25h16.5A1.5 1.5 0 0 1 21.75 6.75v10.5a1.5 1.5 0 0 1-1.5 1.5H3.75a1.5 1.5 0 0 1-1.5-1.5V6.75a1.5 1.5 0 0 1 1.5-1.5Z" />
    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 7.5 12 12.75l8.25-5.25" />
</svg>
SVG,
        ],
        [
            'key' => 'payment',
            'label' => 'Payment Gateway',
            'description' => 'Tripay & transfer manual',
            'route' => 'admin.settings.payment',
            'icon' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5.25h18M3 9.75h18M4.5 5.25h15A1.5 1.5 0 0 1 21 6.75v10.5a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 17.25V6.75a1.5 1.5 0 0 1 1.5-1.5Z" />
    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 15.75h3" />
</svg>
SVG,
        ],
        [
            'key' => 'reminder',
            'label' => 'Template Reminder',
            'description' => 'Email pengingat dinamis',
            'route' => 'admin.settings.reminder',
            'icon' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6 6 0 1 0-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 1 1-6 0" />
</svg>
SVG,
        ],
        [
            'key' => 'telegram',
            'label' => 'Bot Telegram',
            'description' => 'Webhook & otomatisasi chat',
            'route' => 'admin.settings.telegram',
            'icon' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12 20.25 3.75 16.5 20.25 11.621 13.866 3.75 12Z" />
    <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 3.75-8.629 7.201" />
</svg>
SVG,
        ],
        [
            'key' => 'tool-blueprints',
            'label' => 'Tool Blueprint',
            'description' => 'Monitoring intent yang gagal',
            'route' => 'admin.settings.tool-blueprints',
            'icon' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2" />
    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75 12 3l8.25 3.75v8.5L12 21l-8.25-3.75v-10.5Z" />
</svg>
SVG,
        ],
        [
            'key' => 'reasoning-lessons',
            'label' => 'Reasoning Lessons',
            'description' => 'Langkah bernalar per intent',
            'route' => 'admin.settings.reasoning-lessons',
            'icon' => <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l3 3" />
    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5h7.5a2.25 2.25 0 0 1 2.25 2.25v10.5A2.25 2.25 0 0 1 15.75 19.5h-7.5A2.25 2.25 0 0 1 6 17.25V6.75A2.25 2.25 0 0 1 8.25 4.5Z" />
</svg>
SVG,
        ],
    ];
@endphp

<nav data-settings-nav class="settings-surface w-full overflow-hidden border border-slate-200/70 bg-white/90 p-4 shadow-xl shadow-slate-200/50 transition-all duration-300 dark:border-slate-800/70 dark:bg-slate-900/70 dark:shadow-slate-900/50">
    <div class="flex snap-x gap-3 overflow-x-auto pb-1 pl-1 pr-4 sm:pb-2 sm:pl-0 sm:pr-2 md:flex-wrap md:justify-center md:overflow-visible md:px-0 md:snap-none" role="tablist" aria-label="Navigasi Pengaturan">
        @foreach ($items as $item)
            @php
                $isActive = $current === $item['key'] || request()->routeIs($item['route']);
            @endphp
            <a
                href="{{ route($item['route']) }}"
                role="tab"
                aria-selected="{{ $isActive ? 'true' : 'false' }}"
                @class([
                    'settings-chip relative snap-start',
                    'is-active' => $isActive,
                ])
            >
                <span @class([
                    'settings-chip__icon',
                    'bg-white/20 text-white dark:bg-white/10 dark:text-white' => $isActive,
                ])>
                    {!! $item['icon'] !!}
                </span>
                <span class="settings-chip__meta">
                    <span class="settings-chip__label">{{ $item['label'] }}</span>
                    <span class="settings-chip__caption">{{ $item['description'] }}</span>
                </span>
                <span class="settings-chip__badge" aria-hidden="true">Go</span>
            </a>
        @endforeach
    </div>
</nav>
