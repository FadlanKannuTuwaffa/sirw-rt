@php
    use Illuminate\Support\Str;
@endphp

<div class="space-y-10 font-['Inter'] text-slate-800 dark:text-slate-100" data-admin-stack>
    <section class="rounded-3xl border border-slate-200/70 bg-white/95 p-8 shadow-sm transition-colors duration-200 dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card>
        <div class="space-y-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-sky-600 dark:text-sky-300">Filter Laporan</p>
                    <h2 class="mt-1 text-2xl font-semibold text-slate-900 dark:text-slate-100">Atur Periode & Fokus Data</h2>
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Sesuaikan tanggal dan kategori untuk melihat data yang paling relevan. Semua angka disinkronkan secara realtime dari buku kas.</p>
                </div>
                <div class="flex items-center gap-2 rounded-full border border-sky-200/80 bg-white/80 px-4 py-2 text-xs font-semibold text-sky-600 shadow-sm shadow-sky-200/40 backdrop-blur-md dark:border-sky-500/40 dark:bg-slate-900/70 dark:text-sky-300">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-sky-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-sky-500"></span>
                    </span>
                    Data tersinkron otomatis
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-4" wire:loading.attr="aria-busy" wire:target="from,to,category">
                <div class="space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-[0.3em] text-sky-600 dark:text-sky-400">Dari Tanggal</label>
                    <div class="relative">
                        <input wire:model.live.debounce.400ms="from" type="date" class="w-full rounded-2xl border border-slate-200 bg-white/90 py-3 pl-11 pr-4 text-sm text-slate-700 shadow-inner shadow-slate-100 transition-colors duration-200 focus:border-sky-400 focus:ring-2 focus:ring-sky-200 focus:ring-offset-1 focus:ring-offset-white dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100" />
                        <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-sky-500 dark:text-sky-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V5.5A2.5 2.5 0 0 1 10.5 3h3A2.5 2.5 0 0 1 16 5.5V7m-8 0h8m-8 0H5a2 2 0 0 0-2 2v9.5A2.5 2.5 0 0 0 5.5 21h13a2.5 2.5 0 0 0 2.5-2.5V9a2 2 0 0 0-2-2h-3m-8 4h.01M12 11h.01M16 11h.01M8 15h.01M12 15h.01M16 15h.01" />
                        </svg>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-[0.3em] text-sky-600 dark:text-sky-400">Sampai Tanggal</label>
                    <div class="relative">
                        <input wire:model.live.debounce.400ms="to" type="date" class="w-full rounded-2xl border border-slate-200 bg-white/90 py-3 pl-11 pr-4 text-sm text-slate-700 shadow-inner shadow-slate-100 transition-colors duration-200 focus:border-sky-400 focus:ring-2 focus:ring-sky-200 focus:ring-offset-1 focus:ring-offset-white dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100" />
                        <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-sky-500 dark:text-sky-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V5.5A2.5 2.5 0 0 1 10.5 3h3A2.5 2.5 0 0 1 16 5.5V7m-8 0h8m-8 0H5a2 2 0 0 0-2 2v9.5A2.5 2.5 0 0 0 5.5 21h13a2.5 2.5 0 0 0 2.5-2.5V9a2 2 0 0 0-2-2h-3m-8 4h.01M12 11h.01M16 11h.01M8 15h.01M12 15h.01M16 15h.01" />
                        </svg>
                    </div>
                </div>
                <div class="space-y-2 md:col-span-2">
                    <label class="text-xs font-semibold uppercase tracking-[0.3em] text-sky-600 dark:text-sky-400">Kategori</label>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <select wire:model.live="category" class="w-full rounded-2xl border border-slate-200 bg-white/90 py-3 px-4 text-sm text-slate-700 shadow-inner shadow-slate-100 transition-colors duration-200 focus:border-sky-400 focus:ring-2 focus:ring-sky-200 focus:ring-offset-1 focus:ring-offset-white dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100">
                            <option value="all">Semua Kategori</option>
                            @foreach ($categoryOptions as $option)
                                <option value="{{ $option['value'] }}">
                                    {{ $option['label'] }} ({{ number_format($option['total']) }})
                                </option>
                            @endforeach
                        </select>
                        <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white/80 px-4 py-3 text-xs text-slate-500 shadow-inner shadow-slate-100 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300">
                            <span>Rentang Aktif</span>
                            <span class="font-semibold text-slate-700 dark:text-slate-100">{{ \Carbon\Carbon::parse($from)->translatedFormat('d M Y') }} &ndash; {{ \Carbon\Carbon::parse($to)->translatedFormat('d M Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div wire:loading.flex wire:target="from,to,category" class="absolute inset-0 z-20 flex items-center justify-center bg-white/70 backdrop-blur-md dark:bg-slate-950/70">
            <div class="flex items-center gap-3 text-sm font-semibold text-sky-600 dark:text-sky-300">
                <svg class="h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                </svg>
                Memuat data terbaru...
            </div>
        </div>
    </section>

    <section class="grid gap-4 xl:grid-cols-3" data-motion-animated>
        <article class="space-y-4 p-6" data-metric-card data-accent="emerald">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Total Pemasukan</p>
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-500 dark:bg-emerald-500/20 dark:text-emerald-300">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 16.5v-13m0 0L6 7m-3-3.5 3-3.5M21 7.5v13m0 0L18 17m3 3.5-3 3.5M9 12l3 3L21 3" />
                    </svg>
                </span>
            </div>
            <p class="text-3xl font-semibold text-slate-900 dark:text-slate-100">Rp {{ number_format($totals['income']) }}</p>
            <p class="text-xs leading-relaxed text-slate-500 dark:text-slate-400">Seluruh transaksi positif selama periode terpilih.</p>
        </article>
        <article class="space-y-4 p-6" data-metric-card data-accent="rose">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Total Pengeluaran</p>
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-rose-500/10 text-rose-500 dark:bg-rose-500/20 dark:text-rose-300">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h18M3 9h18M3 15h18M3 21h18" />
                    </svg>
                </span>
            </div>
            <p class="text-3xl font-semibold text-slate-900 dark:text-slate-100">Rp {{ number_format($totals['expense']) }}</p>
            <p class="text-xs leading-relaxed text-slate-500 dark:text-slate-400">Termasuk seluruh biaya operasional dan aktivitas warga.</p>
        </article>
        @php
            $netTone = $totals['net'] >= 0
                ? 'text-emerald-600 dark:text-emerald-300'
                : 'text-rose-500 dark:text-rose-300';
        @endphp
        <article class="space-y-4 p-6" data-metric-card data-accent="sky">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Saldo Bersih</p>
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-sky-500/10 text-sky-500 dark:bg-sky-500/20 dark:text-sky-200">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.25 3v18m0 0L7.5 17.25M11.25 21l3.75-3.75M12.75 3v18m0 0L9 17.25M12.75 21l3.75-3.75" />
                    </svg>
                </span>
            </div>
            <p class="text-3xl font-semibold {{ $netTone }}">Rp {{ number_format($totals['net']) }}</p>
            <div class="space-y-2 rounded-2xl border border-slate-200/80 bg-slate-50/80 p-4 text-xs text-slate-500 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300">
                <div class="flex justify-between">
                    <span>Pemasukan</span>
                    <span class="font-semibold text-emerald-600 dark:text-emerald-300">Rp {{ number_format($totals['income']) }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Pengeluaran</span>
                    <span class="font-semibold text-rose-500 dark:text-rose-300">Rp {{ number_format($totals['expense']) }}</span>
                </div>
                <p class="rounded-xl bg-white/80 p-3 text-[11px] font-medium text-slate-500 shadow-inner shadow-white/40 dark:bg-slate-900/60 dark:text-slate-300 dark:shadow-black/20">
                    {{ $totals['net'] >= 0 ? 'Surplus sehat, siap dialokasikan ke prioritas RT.' : 'Defisit, periksa pengeluaran besar dan komunikasikan dengan pengurus.' }}
                </p>
            </div>
        </article>
    </section>

    <section class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition-colors duration-200 hover:bg-slate-50/80 dark:border-slate-800/70 dark:bg-slate-900/80 dark:hover:bg-slate-900/70 dark:shadow-slate-900/40" data-motion-animated>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Ringkasan Pembayaran per Metode</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Pantau kontribusi masing-masing metode pembayaran selama periode dipilih.</p>
            </div>
            <div class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                Total transaksi: {{ number_format($paymentSummary->sum('count')) }}
            </div>
        </div>
        <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @forelse ($paymentSummary as $row)
                @php
                    $label = Str::headline($row->gateway);
                    $total = (int) $row->total;
                    $count = (int) $row->count;
                @endphp
                <div class="group rounded-2xl border border-slate-200/70 bg-white/90 p-5 shadow-inner shadow-slate-200/50 transition-colors duration-200 hover:bg-slate-50/80 dark:border-slate-700 dark:bg-slate-900/70 dark:hover:bg-slate-800/70 dark:shadow-slate-900/40">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-[0.3em] text-sky-600 dark:text-sky-300">{{ strtoupper($row->gateway) }}</span>
                        <span class="rounded-full border border-sky-200/60 bg-sky-50 px-3 py-1 text-[11px] font-semibold text-sky-600 shadow-sm dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-200">{{ number_format($count) }} trx</span>
                    </div>
                    <p class="mt-3 text-lg font-semibold text-slate-800 dark:text-slate-100">Rp {{ number_format($total) }}</p>
                    <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                        @php
                            $share = $paymentSummary->sum('total') > 0 ? round(($total / $paymentSummary->sum('total')) * 100) : 0;
                        @endphp
                        <div class="h-full rounded-full bg-sky-400/90 transition-[width] duration-500" style="width: {{ $share }}%"></div>
                    </div>
                    <p class="mt-2 text-[11px] text-slate-500 dark:text-slate-400">Kontribusi {{ $share }}% dari total pembayaran.</p>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white/70 p-6 text-center text-xs text-slate-400 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-500">
                    Belum ada pembayaran pada rentang tanggal ini.
                </div>
            @endforelse
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition-colors duration-200 hover:bg-slate-50/80 dark:border-slate-800/70 dark:bg-slate-900/80 dark:hover:bg-slate-900/70 dark:shadow-slate-900/40" data-motion-animated>
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Buku Kas Realtime</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Seluruh pemasukan dan pengeluaran tercatat secara transparan.</p>
            </div>
            <div class="flex items-center gap-2 text-xs text-slate-400 dark:text-slate-500">
                <span class="flex items-center gap-1">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Pemasukan
                </span>
                <span class="flex items-center gap-1">
                    <span class="h-2 w-2 rounded-full bg-rose-500"></span> Pengeluaran
                </span>
            </div>
        </div>

        <div class="relative mt-6 overflow-hidden overflow-x-auto rounded-3xl border border-slate-200/60 shadow-inner shadow-slate-100 dark:border-slate-700 dark:shadow-slate-900/40">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-900/90 text-[11px] uppercase tracking-wide text-white/95 dark:bg-slate-800/80">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold">Tanggal</th>
                        <th class="px-5 py-3 text-left font-semibold">Keterangan</th>
                        <th class="px-5 py-3 text-left font-semibold">Aliran Dana</th>
                        <th class="px-5 py-3 text-left font-semibold">Metode</th>
                        <th class="px-5 py-3 text-right font-semibold">Nominal</th>
                        <th class="px-5 py-3 text-left font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100/80 bg-white/95 dark:divide-slate-800/60 dark:bg-slate-900/70" wire:loading.remove wire:target="from,to,category">
                    @forelse ($entries as $entry)
                        @php
                            $isIncome = $entry->amount >= 0;
                            $flowRaw = $isIncome ? ($entry->fund_destination ?? null) : ($entry->fund_source ?? null);
                            $flowValue = $flowRaw ?: 'kas';
                            $statusValue = $entry->payment?->status ?? ($entry->status ?? 'pending');
                            $methodValue = $entry->payment?->gateway ?? ($entry->method ?? '-');
                            $statusColor = match (Str::lower($statusValue)) {
                                'paid' => 'border-emerald-400 text-emerald-600 bg-emerald-50 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-200',
                                'pending' => 'border-amber-400 text-amber-600 bg-amber-50 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200',
                                'failed', 'cancelled', 'expired' => 'border-rose-400 text-rose-600 bg-rose-50 dark:border-rose-500/40 dark:bg-rose-500/10 dark:text-rose-200',
                                default => 'border-slate-300 text-slate-600 bg-slate-50 dark:border-slate-600 dark:bg-slate-800/70 dark:text-slate-300',
                            };
                        @endphp
                        <tr wire:key="ledger-row-{{ $entry->id }}" class="transition-colors duration-200 hover:bg-slate-50/70 dark:hover:bg-slate-800/60">
                            <td class="px-5 py-4 text-xs text-slate-500 dark:text-slate-400">
                                <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $entry->occurred_at?->translatedFormat('d M Y') ?? '-' }}</span>
                                <span class="ml-2 text-[11px] text-slate-400 dark:text-slate-500">{{ $entry->occurred_at?->format('H:i') ?? '' }}</span>
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-500 dark:text-slate-400">
                                <p class="font-semibold text-slate-700 dark:text-slate-100">{{ $entry->bill?->title ?? '-' }}</p>
                                @if ($entry->fund_reference)
                                    <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">Ref: {{ $entry->fund_reference }}</p>
                                @endif
                                <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">{{ $entry->notes ?: '-' }}</p>
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-500 dark:text-slate-400">
                                {{ $isIncome ? 'Masuk ke' : 'Diambil dari' }}
                                <span class="font-semibold text-slate-700 dark:text-slate-100">{{ Str::headline($flowValue) }}</span>
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-500 dark:text-slate-400">
                                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:border-slate-600 dark:bg-slate-800/70 dark:text-slate-300">
                                    {{ strtoupper($methodValue) }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right text-sm font-semibold {{ $isIncome ? 'text-emerald-500 dark:text-emerald-300' : 'text-rose-500 dark:text-rose-300' }}">
                                {{ $isIncome ? '+' : '-' }} Rp {{ number_format(abs($entry->amount)) }}
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-[11px] font-semibold uppercase tracking-wide {{ $statusColor }}">
                                    {{ Str::headline($statusValue) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-xs text-slate-400 dark:text-slate-500">Tidak ada transaksi pada rentang ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tbody class="divide-y divide-slate-100/80 bg-white/95 dark:divide-slate-800/60 dark:bg-slate-900/70" wire:loading.flex wire:target="from,to,category">
                    @foreach (range(1, 5) as $skeleton)
                        <tr class="animate-pulse">
                            <td class="px-5 py-4">
                                <div class="h-3 w-24 rounded bg-slate-100 dark:bg-slate-800/80"></div>
                                <div class="mt-2 h-2 w-16 rounded bg-slate-100 dark:bg-slate-800/80"></div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="h-3 w-40 rounded bg-slate-100 dark:bg-slate-800/80"></div>
                                <div class="mt-2 h-2 w-32 rounded bg-slate-100 dark:bg-slate-800/80"></div>
                                <div class="mt-2 h-2 w-24 rounded bg-slate-100 dark:bg-slate-800/80"></div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="h-3 w-28 rounded bg-slate-100 dark:bg-slate-800/80"></div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="h-6 w-20 rounded-full bg-slate-100 dark:bg-slate-800/80"></div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="h-3 w-16 rounded bg-slate-100 dark:bg-slate-800/80"></div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="h-6 w-20 rounded-full bg-slate-100 dark:bg-slate-800/80"></div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
