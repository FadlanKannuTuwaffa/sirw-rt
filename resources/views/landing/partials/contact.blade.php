<section class="border-t border-slate-200/70 bg-white py-12 transition-colors duration-300 dark:border-slate-800/60 dark:bg-slate-900 md:py-16">
    <div class="container-app grid gap-10 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-5">
            <div>
                <h2 class="text-2xl font-semibold text-slate-900 dark:text-slate-100" data-i18n="news.section_title">Kabar Warga</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400" data-i18n="news.section_desc">Update kegiatan terakhir dari pengurus.</p>
            </div>
            <div class="space-y-3">
                @forelse ($news as $item)
                    @php
                        $displayDate = $item->start_at?->copy() ?? $item->created_at?->copy();
                        if ($displayDate) {
                            $displayDate->locale(app()->getLocale());
                        }
                    @endphp
                    <article id="news-{{ $item->id }}" class="flex flex-col gap-2 rounded-2xl border border-slate-200/70 bg-white/95 p-5 shadow-sm transition-colors duration-200 hover:border-sky-200 dark:border-slate-800/60 dark:bg-slate-900/70">
                        <div class="flex items-center gap-3 text-sm font-medium text-slate-500 dark:text-slate-400">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-sky-100 text-sky-600 dark:bg-sky-500/10 dark:text-sky-300">
                                {{ $displayDate?->translatedFormat('d') }}
                            </span>
                            <span>{{ $displayDate?->translatedFormat('M Y') }}</span>
                        </div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100" data-i18n="landing.news.{{ $item->id }}.title">{{ $item->title }}</h3>
                        <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-400" data-i18n="landing.news.{{ $item->id }}.description">{{ \Illuminate\Support\Str::limit($item->description, 200) }}</p>
                        <a href="{{ route('landing.agenda', ['event' => $item->id]) }}#agenda-{{ $item->id }}" class="mt-1 inline-flex items-center gap-2 text-sm font-semibold text-sky-600 transition-colors duration-200 hover:text-sky-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:text-sky-300 dark:hover:text-sky-200 dark:focus-visible:ring-sky-400 dark:focus-visible:ring-offset-slate-900" aria-label="Baca selengkapnya berita {{ $item->title }}">
                            <span>Baca selengkapnya</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 0 1 1.06 0L10 10.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.28a.75.75 0 0 1 0-1.06z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    </article>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-300 bg-white/90 p-6 text-center text-sm text-slate-400 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-500" data-i18n="news.empty">Belum ada berita terbaru.</p>
                @endforelse
            </div>
        </div>
        <aside class="space-y-5 rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm dark:border-slate-800/60 dark:bg-slate-900/70">
            <div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100" data-i18n="contact.card_title">Tanya Pengurus</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400" data-i18n="contact.card_desc">Hubungi pengurus untuk aktivasi akun atau informasi lain.</p>
            </div>
            <dl class="space-y-3 text-sm text-slate-600 dark:text-slate-300">
                <div class="space-y-1">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500" data-i18n="contact.email_label">Email</dt>
                    <dd>
                        @if (!empty($site['contact_email']))
                            {{ $site['contact_email'] }}
                        @else
                            <span data-i18n="contact.unavailable">Belum tersedia</span>
                        @endif
                    </dd>
                </div>
                <div class="space-y-1">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500" data-i18n="contact.phone_label">Telepon/WA</dt>
                    <dd>
                        @if (!empty($site['contact_phone']))
                            {{ $site['contact_phone'] }}
                        @else
                            <span data-i18n="contact.unavailable">Belum tersedia</span>
                        @endif
                    </dd>
                </div>
                <div class="space-y-1">
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500" data-i18n="contact.address_label">Alamat</dt>
                    <dd>
                        @if (!empty($site['address']))
                            {{ $site['address'] }}
                        @else
                            <span data-i18n="contact.unavailable">Belum tersedia</span>
                        @endif
                    </dd>
                </div>
            </dl>
            <a href="{{ route('landing.contact') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-full border border-sky-200 bg-sky-600 px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-sky-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-sky-500/40 dark:bg-sky-500 dark:hover:bg-sky-400 dark:focus-visible:ring-sky-400 dark:focus-visible:ring-offset-slate-900" data-i18n="contact.btn">Hubungi Pengurus</a>
        </aside>
    </div>
</section>
