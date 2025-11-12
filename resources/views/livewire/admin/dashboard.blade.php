@php
    use Illuminate\Support\Str;

    $maxBar = max(
        $cashFlow->pluck('income')->max() ?? 1,
        $cashFlow->pluck('expense')->max() ?? 1,
        abs($cashFlow->pluck('net')->min() ?? 0),
        abs($cashFlow->pluck('net')->max() ?? 0),
        1
    );
@endphp

<div class="space-y-10 font-['Inter'] text-slate-800 dark:text-slate-100" data-admin-stack wire:poll.20s>
    <section class="rounded-3xl border border-slate-200/70 bg-white/95 p-8 shadow-sm transition-colors duration-300 dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card>
        <div class="flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">
            <div class="space-y-4">
                <p class="text-sm font-medium text-sky-600 dark:text-sky-300">Dashboard Admin</p>
                <h1 class="text-3xl font-semibold text-slate-900 dark:text-slate-100">Monitor aktivitas RT dalam sekejap</h1>
                <p class="max-w-2xl text-sm leading-relaxed text-slate-500 dark:text-slate-400">Angka-angka terpadu dari buku kas, sistem pembayaran, dan agenda. Gunakan insights berikut untuk mengambil langkah cepat dan menjaga pengalaman warga tetap prima.</p>
            </div>
            <dl class="flex items-center gap-3 rounded-full border border-slate-200/80 bg-white px-5 py-3 text-sm font-medium text-sky-600 shadow-inner shadow-slate-200/50 dark:border-sky-500/40 dark:bg-slate-900/70 dark:text-sky-300">
                <dt class="sr-only">Warga online</dt>
                <dd>{{ number_format($stats['online_residents']) }} warga aktif 3 menit terakhir</dd>
            </dl>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="space-y-3 p-5" data-metric-card data-accent="sky">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total warga</p>
                <p class="text-3xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format($stats['residents']) }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ number_format($stats['active_residents']) }} aktif · {{ number_format($stats['online_residents']) }} online</p>
            </article>
            <article class="space-y-3 p-5" data-metric-card data-accent="amber">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Tagihan belum lunas</p>
                <p class="text-3xl font-semibold text-slate-900 dark:text-slate-100">Rp {{ number_format($stats['outstanding_bills']) }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Sisihkan waktu untuk tindak lanjut otomatis.</p>
            </article>
            <article class="space-y-3 p-5" data-metric-card data-accent="emerald">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Pembayaran bulan ini</p>
                <p class="text-3xl font-semibold text-slate-900 dark:text-slate-100">Rp {{ number_format($stats['paid_this_month']) }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ number_format($stats['bills_generated']) }} tagihan diterbitkan</p>
            </article>
            <article class="space-y-3 p-5" data-metric-card data-accent="purple">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Reminder aktif</p>
                <p class="text-3xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format($stats['reminders']) }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Otomasi pengingat pembayaran & agenda.</p>
            </article>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-3" data-motion-animated>
        <div class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition-colors duration-200 hover:border-emerald-200 dark:border-slate-700 dark:bg-slate-900/80 xl:col-span-2" data-motion-card>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Arus Kas 6 Bulan Terakhir</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Grafik pemasukan vs pengeluaran bulanan.</p>
                </div>
                <div class="rounded-full border border-slate-200/70 bg-white/80 px-3 py-1 text-[11px] text-slate-500 shadow-inner shadow-slate-100 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300">Net tertinggi: Rp {{ number_format($cashFlow->pluck('net')->max() ?? 0) }}</div>
            </div>

            <div class="mt-6 space-y-4">
                @foreach ($cashFlow as $row)
                    @php
                        $incomeWidth = max(round(($row['income'] / $maxBar) * 100), 6);
                        $expenseWidth = max(round(($row['expense'] / $maxBar) * 100), 6);
                        $net = $row['net'];
                    @endphp
                    <div class="rounded-2xl border border-slate-200/70 bg-white/90 p-4 shadow-inner shadow-slate-100 dark:border-slate-700 dark:bg-slate-900/70 dark:shadow-slate-900/40">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $row['label'] }}</p>
                            <span class="text-xs font-semibold {{ $net >= 0 ? 'text-emerald-500' : 'text-rose-500' }}">Net: Rp {{ number_format($net) }}</span>
                        </div>
                        <div class="mt-3 space-y-2 text-xs font-semibold text-slate-600 dark:text-slate-300">
                            <div>
                                <div class="flex items-center justify-between">
                                    <span>Pemasukan</span>
                                    <span>Rp {{ number_format($row['income']) }}</span>
                                </div>
                                <div class="mt-1 h-2 rounded-full bg-emerald-100/60 dark:bg-emerald-900/40">
                                    <div class="h-full rounded-full bg-emerald-400/90 transition-all duration-500" style="width: {{ $incomeWidth }}%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex items-center justify-between">
                                    <span>Pengeluaran</span>
                                    <span>Rp {{ number_format($row['expense']) }}</span>
                                </div>
                                <div class="mt-1 h-2 rounded-full bg-rose-100/60 dark:bg-rose-900/40">
                                    <div class="h-full rounded-full bg-rose-400/90 transition-all duration-500" style="width: {{ $expenseWidth }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition-colors duration-200 hover:border-sky-200 dark:border-slate-700 dark:bg-slate-900/80" data-motion-card>
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Agenda Terdekat</h2>
                    <span class="text-xs text-slate-400 dark:text-slate-500">{{ $upcomingEvents->count() }} kegiatan</span>
                </div>
                <ul class="mt-5 space-y-4 text-sm">
                    @forelse ($upcomingEvents as $event)
                        <li class="rounded-2xl border border-slate-200/70 bg-white/90 p-4 shadow-inner shadow-slate-100 transition-colors duration-200 hover:border-sky-200 dark:border-slate-700 dark:bg-slate-900/70 dark:shadow-slate-900/40 dark:hover:border-sky-500/40">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-slate-800 dark:text-slate-100">{{ $event->title }}</p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $event->scheduled_for?->translatedFormat('d M Y H:i') ?? '-' }}</p>
                                </div>
                                <span class="rounded-full border border-sky-200/70 bg-sky-50 px-3 py-1 text-[11px] font-semibold text-sky-600 dark:border-sky-500/40 dark:bg-sky-500/10 dark:text-sky-200">Reminder aktif</span>
                            </div>
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ Str::limit($event->description, 120) }}</p>
                            @if ($event->location)
                                <p class="mt-2 text-[11px] text-slate-400 dark:text-slate-500">Lokasi: {{ $event->location }}</p>
                            @endif
                        </li>
                    @empty
                        <li class="rounded-2xl border border-dashed border-slate-200 p-6 text-center text-xs text-slate-400 dark:border-slate-700/70 dark:text-slate-500">Tidak ada agenda dalam 7 hari ke depan.</li>
                    @endforelse
                </ul>
            </div>

            <div class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition-colors duration-200 hover:border-sky-200 dark:border-slate-700 dark:bg-slate-900/80" data-motion-card>
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Aktivitas Terbaru</h2>
                    <span class="text-xs text-slate-400 dark:text-slate-500">{{ count($latestActivities) }} log</span>
                </div>
                <ul class="mt-5 space-y-3 text-xs text-slate-500 dark:text-slate-400">
                    @forelse ($latestActivities as $activity)
                        <li class="rounded-2xl border border-slate-200/70 bg-white/90 p-4 shadow-inner shadow-slate-100 transition-colors duration-200 hover:border-sky-200 dark:border-slate-700 dark:bg-slate-900/70 dark:shadow-slate-900/40 dark:hover:border-sky-500/40">
                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $activity['title'] }}</p>
                            <p class="mt-1">{{ $activity['description'] }}</p>
                            <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">{{ $activity['time'] }}</p>
                        </li>
                    @empty
                        <li class="rounded-2xl border border-dashed border-slate-200 p-6 text-center text-xs text-slate-400 dark:border-slate-700/70 dark:text-slate-500">Belum ada aktivitas tercatat.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </section>

    <section class="grid gap-6 lg:grid-cols-2" data-motion-animated>
        <div class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition-colors duration-200 hover:border-rose-200 dark:border-slate-700 dark:bg-slate-900/80" data-motion-card>
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Tagihan Jatuh Tempo</h2>
                <span class="text-xs text-slate-400 dark:text-slate-500">{{ $overdueBills->count() }} tagihan</span>
            </div>
            <div class="mt-4 space-y-4 text-sm">
                @forelse ($overdueBills as $bill)
                    <div class="rounded-2xl border border-rose-100/70 bg-gradient-to-br from-white via-rose-50 to-amber-50/70 p-4 shadow-sm shadow-rose-100/70 transition-colors duration-200 hover:border-rose-200 dark:border-slate-800/70 dark:bg-slate-900/70 dark:shadow-slate-900/40">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <p class="font-semibold text-rose-600 dark:text-rose-300">{{ $bill->user?->name }}</p>
                            <span class="inline-flex items-center justify-center rounded-full bg-white/80 px-3 py-1 text-[11px] font-semibold text-rose-500 shadow-inner shadow-rose-100 dark:bg-slate-900/70 dark:text-rose-200">Rp {{ number_format($bill->amount) }}</span>
                        </div>
                        <p class="mt-1 text-xs text-rose-500 dark:text-rose-300/90">Jatuh tempo {{ $bill->due_date?->translatedFormat('d M Y') }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ Str::limit($bill->title, 80) }}</p>
                        <a href="{{ route('admin.tagihan.index', ['bill_id' => $bill->id]) }}" class="mt-2 inline-flex items-center text-xs font-semibold text-sky-600 transition-colors duration-300 hover:text-sky-800 focus:outline-none focus:underline dark:text-sky-300 dark:hover:text-sky-200">Lihat detail</a>
                    </div>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-200 p-6 text-center text-xs text-slate-400 dark:border-slate-700/70 dark:text-slate-500">Semua tagihan dalam kondisi aman.</p>
                @endforelse
            </div>
        </div>
        <div class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition-colors duration-200 hover:border-sky-200 dark:border-slate-700 dark:bg-slate-900/80" data-motion-card>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Ringkasan Singkat</h2>
            <div class="mt-4 grid gap-3 text-xs text-slate-500 dark:text-slate-400">
                <div class="flex items-center justify-between rounded-2xl border border-slate-200/70 bg-white/80 px-4 py-3 dark:border-slate-700 dark:bg-slate-900/70">
                    <span>Pemasukan hari ini</span>
                    <span class="font-semibold text-emerald-500 dark:text-emerald-300">Rp {{ number_format($stats['today_income']) }}</span>
                </div>
                <div class="flex items-center justify-between rounded-2xl border border-slate-200/70 bg-white/80 px-4 py-3 dark:border-slate-700 dark:bg-slate-900/70">
                    <span>Pengeluaran hari ini</span>
                    <span class="font-semibold text-rose-500 dark:text-rose-300">Rp {{ number_format($stats['today_expense']) }}</span>
                </div>
                <div class="flex items-center justify-between rounded-2xl border border-slate-200/70 bg-white/80 px-4 py-3 dark:border-slate-700 dark:bg-slate-900/70">
                    <span>Pengguna baru bulan ini</span>
                    <span class="font-semibold text-sky-500 dark:text-sky-300">{{ number_format($stats['new_residents']) }}</span>
                </div>
                <div class="flex items-center justify-between rounded-2xl border border-slate-200/70 bg-white/80 px-4 py-3 dark:border-slate-700 dark:bg-slate-900/70">
                    <span>Pengingat dikirim minggu ini</span>
                    <span class="font-semibold text-indigo-500 dark:text-indigo-300">{{ number_format($stats['reminders_sent']) }}</span>
                </div>
            </div>
        </div>
    </section>
</div>

