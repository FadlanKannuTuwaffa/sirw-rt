@extends('layouts.landing_min', [
    'title' => $title ?? null,
    'site' => $site,
    'dynamicTranslations' => $dynamicTranslations ?? [],
])

@section('content')
<section class="pt-8 pb-12 sm:pt-10 sm:pb-14 md:pt-12 md:pb-16 bg-white transition-colors duration-300 dark:bg-slate-950">
    <div class="container-app max-w-5xl">
        <h1 class="text-3xl font-semibold text-slate-900 transition-colors duration-300 dark:text-slate-100" data-i18n="agenda.page_title">Agenda Warga</h1>
        <p class="mt-4 text-sm text-slate-600 transition-colors duration-300 dark:text-slate-300" data-i18n="agenda.page_description">Dapatkan informasi kegiatan lingkungan terkini. Login untuk menambahkan pengingat pribadi.</p>

        <div class="mt-10 grid gap-4 md:grid-cols-2">
            @forelse ($events as $event)
                @php
                    $isFocused = isset($focusEventId) && (int) $focusEventId === $event->id;
                @endphp
                <article id="agenda-{{ $event->id }}" @class([
                    'rounded-2xl border border-slate-200 bg-slate-50 p-5 shadow-sm transition-colors duration-300 dark:border-slate-800/70 dark:bg-slate-900/75 dark:shadow-slate-900/30',
                    'ring-2 ring-sky-400 ring-offset-2 ring-offset-white dark:ring-sky-500 dark:ring-offset-slate-950' => $isFocused,
                ]) data-focus-target="{{ $isFocused ? 'true' : 'false' }}">
                    <p class="text-xs uppercase tracking-wide text-slate-500 transition-colors duration-300 dark:text-slate-400">{{ $event->start_at?->translatedFormat('l, d M Y H:i') }}</p>
                    <h2 class="mt-2 text-xl font-semibold text-slate-900 transition-colors duration-300 dark:text-slate-100" data-i18n="agenda.events.{{ $event->id }}.title">{{ $event->title }}</h2>
                    <p class="mt-2 text-sm text-slate-600 transition-colors duration-300 dark:text-slate-300" data-i18n="agenda.events.{{ $event->id }}.description">{{ Str::limit($event->description, 180) }}</p>
                    <div class="mt-3 text-xs text-slate-500 transition-colors duration-300 dark:text-slate-400">
                        <p>
                            <span data-i18n="agenda.location_label">Lokasi:</span>
                            @if ($event->location)
                                <span data-i18n="agenda.events.{{ $event->id }}.location">{{ $event->location }}</span>
                            @else
                                <span data-i18n="agenda.location_tbd">Akan diinformasikan</span>
                            @endif
                        </p>
                        <p><span data-i18n="agenda.status_label">Status:</span> <span data-i18n="agenda.events.{{ $event->id }}.status">{{ $event->status }}</span></p>
                    </div>
                </article>
            @empty
                <p class="rounded-2xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-400 transition-colors duration-300 dark:border-slate-700 dark:text-slate-500 md:col-span-2" data-i18n="agenda.page_empty">Belum ada agenda terjadwal.</p>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $events->appends(request()->except(['page', 'event']))->links() }}
        </div>
    </div>
</section>
@endsection
