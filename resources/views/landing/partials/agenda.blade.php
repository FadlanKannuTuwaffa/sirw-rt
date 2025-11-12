<section class="pt-8 pb-12 md:pt-12 md:pb-16 bg-gradient-to-b from-white via-white to-slate-50 transition-colors duration-300 dark:from-slate-950 dark:via-slate-950 dark:to-slate-900">
    <div class="container-app">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-slate-900 dark:text-slate-100" data-i18n="agenda.section_title">Agenda Terdekat</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400" data-i18n="agenda.section_desc">Tetap terhubung dengan kegiatan warga dan rapat lingkungan.</p>
            </div>
            <a href="{{ route('landing.agenda') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-sky-200 bg-white px-5 py-2.5 text-sm font-medium text-sky-600 shadow-sm transition-transform duration-300 hover:-translate-y-0.5 hover:bg-sky-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-slate-700 dark:bg-slate-900 dark:text-sky-300 dark:hover:bg-slate-800 dark:focus-visible:ring-sky-400 dark:focus-visible:ring-offset-slate-900 sm:w-auto md:text-base" data-i18n="agenda.view_all">Lihat semua agenda</a>
        </div>
        <div class="mt-10 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($upcomingEvents as $event)
                <article id="agenda-{{ $event->id }}" class="rounded-2xl border border-transparent bg-white/90 p-6 shadow-lg shadow-slate-200/60 transition-all duration-300 hover:-translate-y-1 hover:border-sky-200/60 hover:shadow-xl dark:border-slate-800/70 dark:bg-slate-900/80 dark:shadow-slate-900/40">
                    <p class="text-sm uppercase tracking-[0.3em] text-sky-500 dark:text-sky-300">{{ $event->start_at?->locale(app()->getLocale())->translatedFormat('l, d M Y H:i') }}</p>
                    <h3 class="mt-3 text-lg font-semibold text-slate-900 dark:text-slate-100" data-i18n="landing.events.{{ $event->id }}.title">{{ $event->title }}</h3>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400" data-i18n="landing.events.{{ $event->id }}.description">{{ \Illuminate\Support\Str::limit($event->description, 120) }}</p>
                    <p class="mt-4 text-sm font-medium text-slate-400 dark:text-slate-500">
                        <span class="mr-1 font-semibold" data-i18n="agenda.location_label">Lokasi:</span>
                        @if ($event->location)
                            <span data-i18n="landing.events.{{ $event->id }}.location">{{ $event->location }}</span>
                        @else
                            <span data-i18n="agenda.location_tbd">Akan diinformasikan</span>
                        @endif
                    </p>
                    <a href="{{ route('landing.agenda', ['event' => $event->id]) }}#agenda-{{ $event->id }}" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-sky-600 transition-colors duration-200 hover:text-sky-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:text-sky-300 dark:hover:text-sky-200 dark:focus-visible:ring-sky-400 dark:focus-visible:ring-offset-slate-900" aria-label="Baca selengkapnya agenda {{ $event->title }}">
                        <span>Baca selengkapnya</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 0 1 1.06 0L10 10.94l3.72-3.72a.75.75 0 0 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.28a.75.75 0 0 1 0-1.06z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </article>
            @empty
                <p class="rounded-2xl border border-dashed border-sky-200/70 bg-white/80 p-6 text-center text-sm text-slate-400 md:col-span-2 lg:col-span-3 dark:border-slate-800/60 dark:bg-slate-900/70 dark:text-slate-500" data-i18n="agenda.empty">Belum ada agenda terjadwal.</p>
            @endforelse
        </div>
    </div>
</section>
