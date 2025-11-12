<div class="font-['Inter'] text-slate-800 space-y-8" data-admin-stack>
    @php
        $scheduledBills = $billStats['scheduled'] ?? 0;
        $todayBills = $billStats['today'] ?? 0;
        $scheduledEvents = $eventStats['scheduled'] ?? 0;
        $sentTodayBills = $billStats['sent_today'] ?? 0;
        $sentTodayEvents = $eventStats['sent_today'] ?? 0;
        $sentTodayTotal = $sentTodayBills + $sentTodayEvents;

        $statCards = [
            [
                'label' => 'Reminder Tagihan',
                'value' => number_format($scheduledBills),
                'description' => 'Terjadwal dan siap terkirim ke warga.',
                'accent' => 'sky',
            ],
            [
                'label' => 'Reminder Tagihan Hari Ini',
                'value' => number_format($todayBills),
                'description' => 'Periksa jika butuh penyesuaian jadwal.',
                'accent' => 'emerald',
            ],
            [
                'label' => 'Reminder Agenda',
                'value' => number_format($scheduledEvents),
                'description' => 'Agenda komunitas aktif dan siap diingatkan.',
                'accent' => 'purple',
            ],
            [
                'label' => 'Reminder Terkirim Hari Ini',
                'value' => number_format($sentTodayTotal),
                'description' => 'Gabungan pengingat tagihan & agenda.',
                'accent' => 'amber',
            ],
        ];

        $insights = [];

        if ($todayBills > 0) {
            $insights[] = "{$todayBills} pengingat tagihan jatuh tempo hari ini. Pastikan data pembayaran terbaru sebelum sistem mengirim notifikasi.";
        }

        if ($scheduledEvents > 0) {
            $insights[] = "Ada {$scheduledEvents} agenda aktif. Pertimbangkan mengirim teaser visual agar partisipasi warga meningkat.";
        }

        if ($scheduledBills === 0 && $scheduledEvents === 0) {
            $insights[] = 'Tidak ada pengingat baru. Jadwalkan pengingat rutin agar warga tetap terinformasi.';
        }

        if ($sentTodayTotal > $todayBills) {
            $insights[] = 'Pengiriman hari ini berjalan lancar. Pantau feedback warga melalui kanal favorit (WA/Telegram).';
        }

        if (empty($insights)) {
            $insights[] = 'Automasi stabil. Coba aktifkan personalisasi pesan agar pengingat terasa lebih relevan.';
        }
    @endphp

    <header class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between" data-motion-animated>
        <div>
            <h1 class="text-2xl font-semibold text-slate-900 transition-colors dark:text-slate-100">Automasi Reminder</h1>
            <p class="mt-1 text-sm text-slate-500 transition-colors dark:text-slate-400">
                Monitor status pengingat, optimalkan interaksi warga, dan aktifkan ritme pengiriman yang seimbang.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-full border border-slate-200/70 bg-white/80 px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:border-sky-200 hover:text-slate-700 dark:border-slate-800/70 dark:bg-slate-900/70 dark:text-slate-300 dark:hover:border-sky-500/60"
                data-calm-toggle
                aria-pressed="false"
            >
                <span class="flex h-2.5 w-2.5 rounded-full bg-slate-300 transition-colors dark:bg-slate-600" data-calm-indicator></span>
                <span data-calm-state>Mode Tenang nonaktif</span>
            </button>
            <small class="text-xs text-slate-400 transition-colors dark:text-slate-600">
                Menghormati preferensi <code>prefers-reduced-motion</code>.
            </small>
        </div>
    </header>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4" data-aos="fade-up" data-aos-delay="40" data-motion-animated>
        @foreach ($statCards as $card)
            <article
                class="group relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white/95 p-6 text-slate-700 shadow-lg shadow-slate-900/5 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:border-slate-300 dark:border-slate-800/65 dark:bg-slate-900/75 dark:text-slate-200 dark:shadow-slate-900/40"
                data-metric-card
                data-accent="{{ $card['accent'] }}"
                data-motion-card
            >
                <p class="text-[13px] font-semibold uppercase tracking-[0.22em] text-slate-400 transition-colors dark:text-slate-500" data-metric-label>
                    {{ $card['label'] }}
                </p>
                <p class="mt-4 text-3xl font-semibold text-slate-900 transition-colors dark:text-slate-100" data-metric-value>
                    {{ $card['value'] }}
                </p>
                <p class="mt-3 text-sm leading-relaxed text-slate-500 transition-colors dark:text-slate-400">
                    {{ $card['description'] }}
                </p>
            </article>
        @endforeach
    </section>

    <section class="rounded-3xl border border-slate-200/80 bg-white/90 p-6 shadow-lg shadow-slate-900/5 transition-all duration-300 hover:border-slate-300 dark:border-slate-800/65 dark:bg-slate-900/70 dark:text-slate-200 dark:shadow-slate-900/40" data-motion-card data-motion-animated>
        <h2 class="text-base font-semibold text-slate-700 transition-colors dark:text-slate-200">Sorotan pintar</h2>
        <p class="mt-1 text-xs uppercase tracking-[0.18em] text-slate-400 transition-colors dark:text-slate-500">Rekomendasi otomatis</p>
        <ul class="mt-4 space-y-3 text-sm text-slate-600 transition-colors dark:text-slate-300">
            @foreach ($insights as $insight)
                <li class="flex gap-3">
                    <span class="mt-1 inline-flex h-2.5 w-2.5 flex-none rounded-full bg-slate-300 transition-colors dark:bg-slate-600"></span>
                    <span>{{ $insight }}</span>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="grid gap-4 xl:grid-cols-2" data-aos="fade-up" data-aos-delay="80" data-motion-animated>
        <div class="rounded-3xl border border-slate-200/80 bg-white/95 p-6 shadow-lg shadow-slate-900/5 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:border-sky-200 dark:border-slate-800/60 dark:bg-slate-900/80 dark:shadow-slate-900/40" data-motion-card>
            <h2 class="text-lg font-semibold text-sky-600 dark:text-sky-300">Reminder Tagihan</h2>
            <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">Kirim pengingat otomatis ke warga agar pembayaran selalu tepat waktu.</p>

            <form wire:submit.prevent="scheduleBillReminders" class="mt-6 space-y-5">
                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-600 dark:text-sky-300">Pemetaan Reminder</label>
                    <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center">
                        <div class="relative flex-1">
                            <input wire:model.debounce.400ms="bill_search" type="search" placeholder="Nama warga / invoice / judul"
                                   class="w-full rounded-xl border border-sky-200/60 bg-white/95 py-2.5 pl-10 pr-3 text-sm text-slate-600 shadow-inner shadow-sky-100 transition-all duration-300 focus:border-sky-400 focus:ring-2 focus:ring-sky-200 focus:ring-offset-1 focus:ring-offset-white dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-200">
                            <svg class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-sky-400 dark:text-sky-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m21 21-4.35-4.35m0 0a7.5 7.5 0 1 0-10.607 0 7.5 7.5 0 0 0 10.607 0Z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <label class="inline-flex items-center gap-2 rounded-full border border-sky-200/70 bg-white/95 py-2 px-3 text-xs font-semibold text-sky-600 shadow-sm shadow-sky-100 transition-all duration-300 hover:border-sky-300 dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-sky-300">
                            <input type="radio" value="single" wire:model.live="bill_scope" class="h-4 w-4 text-sky-500 focus:ring-sky-400">
                            Satu Tagihan
                        </label>
                        <label class="inline-flex items-center gap-2 rounded-full border border-sky-200/70 bg-white/95 py-2 px-3 text-xs font-semibold text-sky-600 shadow-sm shadow-sky-100 transition-all duration-300 hover:border-sky-300 dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-sky-300">
                            <input type="radio" value="all" wire:model.live="bill_scope" class="h-4 w-4 text-sky-500 focus:ring-sky-400">
                            Semua Tagihan Belum Lunas ({{ number_format($outstandingSummary['count'] ?? 0) }})
                        </label>
                    </div>

                    @if ($bill_scope === 'single')
                        <select wire:model="bill_id" class="mt-3 w-full rounded-xl border border-sky-200/60 bg-white/95 py-2.5 px-4 text-sm text-slate-600 shadow-inner shadow-sky-100 transition-all duration-300 focus:border-sky-400 focus:ring-2 focus:ring-sky-200 focus:ring-offset-1 focus:ring-offset-white dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-200">
                            <option value="">Pilih tagihan...</option>
                            @foreach ($billCandidates as $candidate)
                                <option value="{{ $candidate->id }}">
                                {{ $candidate->user?->name ?? 'Tanpa warga' }} - {{ $candidate->title }} - {{ $candidate->invoice_number }}
                                </option>
                            @endforeach
                        </select>
                        @error('bill_id') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                    @else
                        <div class="mt-3 rounded-2xl border border-sky-200/60 bg-sky-50/80 p-4 text-xs text-slate-500 shadow-inner shadow-sky-100 dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-300">
                            <p class="font-semibold text-slate-600 dark:text-slate-200">Mode massal aktif.</p>
                            <p class="mt-1">Reminder akan dikirim ke {{ number_format($outstandingSummary['count'] ?? 0) }} tagihan yang belum lunas.</p>
                            <p class="mt-1">Nilai tertunggak: <span class="font-semibold text-sky-600 dark:text-sky-300">Rp {{ number_format($outstandingSummary['total'] ?? 0) }}</span></p>
                            <p class="mt-1 text-[11px]">Jatuh tempo terdekat: {{ $outstandingSummary['next_due'] ?? '-' }}.</p>
                        </div>
                    @endif
                    <p class="mt-3 text-[11px] text-slate-400 dark:text-slate-500">Integrasi email & Telegram mengikuti pengaturan pada menu Pengaturan &gt; SMTP dan Pengaturan &gt; Bot Telegram.</p>
                    @error('bill_scope') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-600 dark:text-sky-300">Preset Pengingat</label>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        @foreach ($billPresetOptions as $key => $label)
                            <label class="flex items-start gap-3 rounded-2xl border border-sky-200/40 bg-white/95 p-4 text-xs text-slate-500 shadow-sm shadow-sky-100/40 transition-all duration-300 hover:-translate-y-1 hover:border-sky-300 dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-300">
                                <input wire:model="bill_presets" type="checkbox" value="{{ $key }}" class="mt-1 h-4 w-4 rounded border-sky-300 text-sky-500 focus:ring-sky-400">
                                <span>
                                    <span class="block text-sm font-semibold text-slate-700 dark:text-slate-100">{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $key)) }}</span>
                                    {{ $label }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('bill_presets') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-600 dark:text-sky-300">Waktu Custom</label>
                        <input wire:model.defer="bill_manual_at" type="datetime-local" class="mt-2 w-full rounded-xl border border-sky-200/60 bg-white/95 py-2.5 px-4 text-sm text-slate-600 shadow-inner shadow-sky-100 transition-all duration-300 focus:border-sky-400 focus:ring-2 focus:ring-sky-200 focus:ring-offset-1 focus:ring-offset-white dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-200">
                        @error('bill_manual_at') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                    <div class="rounded-2xl border border-sky-200/40 bg-sky-50/80 p-4 text-xs text-slate-500 shadow-inner shadow-sky-100 dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-400">
                        <p>Email & Telegram akan dikirim lewat integrasi multi-channel. Sistem otomatis menyesuaikan bahasa, template, dan memeriksa warga yang sudah menautkan akun Telegram.</p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-sky-500 to-emerald-500 px-5 py-2 text-[0.875rem] font-semibold leading-5 text-white shadow-lg shadow-sky-200/60 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-sky-300 focus:ring-offset-1 focus:ring-offset-white dark:shadow-sky-900/40 dark:focus:ring-sky-500/60">
                        Jadwalkan Reminder
                    </button>
                </div>
            </form>
        </div>

        <div class="rounded-3xl border border-indigo-200/70 bg-white/95 p-6 shadow-lg shadow-indigo-200/40 transition-all duration-300 hover:-translate-y-1 hover:border-indigo-300 hover:shadow-xl dark:border-slate-800/60 dark:bg-slate-900/80 dark:shadow-slate-900/40" data-motion-card>
            <h2 class="text-lg font-semibold text-indigo-600 dark:text-indigo-300">Reminder Agenda</h2>
            <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">Informasikan agenda komunitas tepat sebelum acara dimulai.</p>

            <form wire:submit.prevent="scheduleEventReminders" class="mt-6 space-y-5">
                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-300">Cari Agenda</label>
                    <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center">
                        <div class="relative flex-1">
                            <input wire:model.debounce.400ms="event_search" type="search" placeholder="Judul / lokasi / deskripsi"
                                   class="w-full rounded-xl border border-indigo-200/60 bg-white/95 py-2.5 pl-10 pr-3 text-sm text-slate-600 shadow-inner shadow-indigo-100 transition-all duration-300 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 focus:ring-offset-1 focus:ring-offset-white dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-200">
                            <svg class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-indigo-400 dark:text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m21 21-4.35-4.35m0 0a7.5 7.5 0 1 0-10.607 0 7.5 7.5 0 0 0 10.607 0Z" />
                            </svg>
                        </div>
                    </div>
                    <select wire:model="event_id" class="mt-3 w-full rounded-xl border border-indigo-200/60 bg-white/95 py-2.5 px-4 text-sm text-slate-600 shadow-inner shadow-indigo-100 transition-all duration-300 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 focus:ring-offset-1 focus:ring-offset-white dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-200">
                        <option value="">Pilih agenda...</option>
                        @foreach ($eventCandidates as $candidate)
                            <option value="{{ $candidate->id }}">
                                {{ $candidate->title }} - {{ optional($candidate->start_at)->translatedFormat('d M Y H:i') ?? '-' }}
                            </option>
                        @endforeach
                    </select>
                    @error('event_id') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-300">Preset Pengingat</label>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        @foreach ($eventPresetOptions as $key => $label)
                            <label class="flex items-start gap-3 rounded-2xl border border-indigo-200/40 bg-white/95 p-4 text-xs text-slate-500 shadow-sm shadow-indigo-100/40 transition-all duration-300 hover:-translate-y-1 hover:border-indigo-300 dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-300">
                                <input wire:model="event_presets" type="checkbox" value="{{ $key }}" class="mt-1 h-4 w-4 rounded border-indigo-300 text-indigo-500 focus:ring-indigo-400">
                                <span>
                                    <span class="block text-sm font-semibold text-slate-700 dark:text-slate-100">{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $key)) }}</span>
                                    {{ $label }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('event_presets') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-300">Waktu Custom</label>
                        <input wire:model.defer="event_manual_at" type="datetime-local" class="mt-2 w-full rounded-xl border border-indigo-200/60 bg-white/95 py-2.5 px-4 text-sm text-slate-600 shadow-inner shadow-indigo-100 transition-all duration-300 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 focus:ring-offset-1 focus:ring-offset-white dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-200">
                        @error('event_manual_at') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                    <div class="rounded-2xl border border-indigo-200/40 bg-indigo-50/80 p-4 text-xs text-slate-500 shadow-inner shadow-indigo-100 dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-400">
                        <p>Pengingat akan menampilkan detail lokasi dan lampiran agenda yang relevan. Ideal untuk rapat, kerja bakti, dan kegiatan komunitas.</p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 px-5 py-2 text-[0.875rem] font-semibold leading-5 text-white shadow-lg shadow-indigo-200/60 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:ring-offset-1 focus:ring-offset-white dark:shadow-indigo-900/40 dark:focus:ring-indigo-500/60">
                        Jadwalkan Reminder
                    </button>
                </div>
            </form>
        </div>
    </section>

    <section class="grid gap-4 lg:grid-cols-2" data-aos="fade-up" data-aos-delay="110">
        <div class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-lg shadow-slate-200/40 transition-all duration-300 hover:-translate-y-1 hover:border-slate-300 hover:shadow-xl dark:border-slate-800/60 dark:bg-slate-900/80 dark:shadow-slate-900/40" data-motion-card>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-50">Jadwal Reminder Tagihan</h2>
                    <p class="text-xs text-slate-400 dark:text-slate-500">Pantau kiriman otomatis untuk setiap tagihan.</p>
                </div>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-900/90 text-[11px] uppercase tracking-wide text-white/90 dark:bg-slate-800/80">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">Tagihan</th>
                            <th class="px-4 py-3 text-left font-semibold">Kirim Pada</th>
                            <th class="px-4 py-3 text-left font-semibold">Preset</th>
                            <th class="px-4 py-3 text-left font-semibold">Status</th>
                            <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100/80 bg-white/95 dark:divide-slate-800/60 dark:bg-slate-900/70">
                        @forelse ($billReminders as $reminder)
                            @php
                                $bill = $reminder->model;
                                $isSent = filled($reminder->sent_at);
                                $preset = $reminder->payload['preset'] ?? null;
                            @endphp
                            <tr class="transition-all duration-300 hover:bg-slate-50/80 dark:hover:bg-slate-800/60">
                                <td class="px-4 py-4">
                                    <p class="font-semibold text-slate-800 dark:text-slate-100">{{ $bill?->title ?? 'Tagihan tidak tersedia' }}</p>
                                    <p class="text-xs text-slate-400 dark:text-slate-500">{{ $bill?->user?->name ?? '—' }} • {{ $bill?->invoice_number ?? '—' }}</p>
                                </td>
                                <td class="px-4 py-4 text-xs text-slate-500 dark:text-slate-400">
                                    <p>{{ optional($reminder->send_at)->translatedFormat('d M Y H:i') ?? '-' }}</p>
                                </td>
                                <td class="px-4 py-4 text-xs text-slate-500 dark:text-slate-400">
                                    {{ $preset ? \Illuminate\Support\Str::headline(str_replace('_', ' ', $preset)) : 'Custom' }}
                                </td>
                                <td class="px-4 py-4 text-xs">
                                    @if ($isSent)
                                        <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-4 py-1 text-[11px] font-semibold uppercase tracking-wide text-emerald-600 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">Terkirim</span>
                                    @else
                                        <span class="inline-flex rounded-full border border-sky-200 bg-sky-50 px-4 py-1 text-[11px] font-semibold uppercase tracking-wide text-sky-600 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-200">Terjadwal</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-right">
                                    @if (! $isSent)
                                        <button type="button" wire:click="cancelReminder({{ $reminder->id }})" class="rounded-full border border-rose-200 px-4 py-1 text-xs font-semibold text-rose-500 transition-all duration-300 hover:-translate-y-0.5 hover:bg-rose-50 dark:border-rose-500/30 dark:text-rose-200 dark:hover:bg-rose-500/10">
                                            Batalkan
                                        </button>
                                    @else
                                        <span class="text-[11px] text-slate-400 dark:text-slate-500">Selesai</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-400 dark:text-slate-500">Belum ada reminder yang dijadwalkan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex items-center justify-between text-xs text-slate-400 dark:text-slate-500">
                <span>Menampilkan {{ $billReminders->firstItem() ?? 0 }}-{{ $billReminders->lastItem() ?? 0 }} dari {{ $billReminders->total() }} reminder</span>
                <span class="text-sm">{{ $billReminders->onEachSide(1)->links() }}</span>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-lg shadow-slate-200/40 transition-all duration-300 hover:-translate-y-1 hover:border-slate-300 hover:shadow-xl dark:border-slate-800/60 dark:bg-slate-900/80 dark:shadow-slate-900/40" data-motion-card>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-50">Jadwal Reminder Agenda</h2>
                    <p class="text-xs text-slate-400 dark:text-slate-500">Lihat pengingat kegiatan yang akan dikirim.</p>
                </div>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-900/90 text-[11px] uppercase tracking-wide text-white/90 dark:bg-slate-800/80">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">Agenda</th>
                            <th class="px-4 py-3 text-left font-semibold">Kirim Pada</th>
                            <th class="px-4 py-3 text-left font-semibold">Preset</th>
                            <th class="px-4 py-3 text-left font-semibold">Status</th>
                            <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100/80 bg-white/95 dark:divide-slate-800/60 dark:bg-slate-900/70">
                        @forelse ($eventReminders as $reminder)
                            @php
                                $event = $reminder->model;
                                $isSent = filled($reminder->sent_at);
                                $preset = $reminder->payload['preset'] ?? null;
                            @endphp
                            <tr class="transition-all duration-300 hover:bg-slate-50/80 dark:hover:bg-slate-800/60">
                                <td class="px-4 py-4">
                                    <p class="font-semibold text-slate-800 dark:text-slate-100">{{ $event?->title ?? 'Agenda tidak tersedia' }}</p>
                                    <p class="text-xs text-slate-400 dark:text-slate-500">{{ optional($event?->start_at)->translatedFormat('d M Y H:i') ?? '-' }}</p>
                                </td>
                                <td class="px-4 py-4 text-xs text-slate-500 dark:text-slate-400">
                                    <p>{{ optional($reminder->send_at)->translatedFormat('d M Y H:i') ?? '-' }}</p>
                                </td>
                                <td class="px-4 py-4 text-xs text-slate-500 dark:text-slate-400">
                                    {{ $preset ? \Illuminate\Support\Str::headline(str_replace('_', ' ', $preset)) : 'Custom' }}
                                </td>
                                <td class="px-4 py-4 text-xs">
                                    @if ($isSent)
                                        <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-4 py-1 text-[11px] font-semibold uppercase tracking-wide text-emerald-600 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">Terkirim</span>
                                    @else
                                        <span class="inline-flex rounded-full border border-indigo-200 bg-indigo-50 px-4 py-1 text-[11px] font-semibold uppercase tracking-wide text-indigo-600 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-200">Terjadwal</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-right">
                                    @if (! $isSent)
                                        <button type="button" wire:click="cancelReminder({{ $reminder->id }})" class="rounded-full border border-rose-200 px-4 py-1 text-xs font-semibold text-rose-500 transition-all duration-300 hover:-translate-y-0.5 hover:bg-rose-50 dark:border-rose-500/30 dark:text-rose-200 dark:hover:bg-rose-500/10">
                                            Batalkan
                                        </button>
                                    @else
                                        <span class="text-[11px] text-slate-400 dark:text-slate-500">Selesai</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-400 dark:text-slate-500">Belum ada reminder agenda yang dijadwalkan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex items-center justify-between text-xs text-slate-400 dark:text-slate-500">
                <span>Menampilkan {{ $eventReminders->firstItem() ?? 0 }}-{{ $eventReminders->lastItem() ?? 0 }} dari {{ $eventReminders->total() }} reminder</span>
                <span class="text-sm">{{ $eventReminders->onEachSide(1)->links() }}</span>
            </div>
        </div>
    </section>
</div>
