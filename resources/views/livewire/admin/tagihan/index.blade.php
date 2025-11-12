@php
    use Carbon\CarbonInterface;
    use Illuminate\Support\Str;
@endphp

<div class="space-y-10 font-['Inter'] text-slate-800 dark:text-slate-100" data-admin-stack>
    @php
        $metricCards = [
            [
                'label' => 'Total tagihan',
                'value' => number_format($stats['total']),
                'format' => 'count',
                'accent' => 'sky',
            ],
            [
                'label' => 'Nilai terbayar',
                'value' => 'Rp ' . number_format($stats['paid']),
                'format' => 'currency',
                'accent' => 'emerald',
            ],
            [
                'label' => 'Belum lunas',
                'value' => 'Rp ' . number_format($stats['outstanding']),
                'format' => 'currency',
                'accent' => 'amber',
            ],
            [
                'label' => 'Tagihan overdue',
                'value' => number_format($stats['overdue']),
                'format' => 'count',
                'accent' => 'rose',
            ],
        ];
    @endphp

    <section class="rounded-3xl border border-slate-200/70 bg-white/95 p-8 shadow-sm transition-colors duration-300 dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card>
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="space-y-4">
                <p class="text-sm font-medium text-sky-600 dark:text-sky-300">Dashboard tagihan</p>
                <h1 class="text-3xl font-semibold text-slate-900 dark:text-slate-100">Kendalikan arus tagihan & pembayaran</h1>
                <p class="max-w-2xl text-sm leading-relaxed text-slate-500 dark:text-slate-400">Kelola kebutuhan tagihan, pantau status pembayaran, dan kirim reminder dalam sekali klik. Semua data tersinkron otomatis dengan portal warga.</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($metricCards as $card)
                    <article class="space-y-2 p-4" data-metric-card data-accent="{{ $card['accent'] }}">
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $card['label'] }}</p>
                        <p class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ $card['value'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition-colors duration-200 hover:border-sky-200 dark:border-slate-700 dark:bg-slate-900/80" data-motion-card>
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Daftar Tagihan</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Pantau status pembayaran dan tindak lanjuti secara cepat.</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center" data-admin-filters>
                <div class="relative w-full sm:w-64">
                    <input wire:model.debounce.500ms="search" type="search" placeholder="Cari warga, invoice, judul" class="w-full rounded-full border border-slate-200 bg-white/90 py-2.5 pl-10 pr-3 text-sm text-slate-600 shadow-inner shadow-slate-100 transition-all duration-300 focus:border-sky-400 focus:ring-2 focus:ring-sky-200 focus:ring-offset-1 focus:ring-offset-white dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200 dark:placeholder-slate-500">
                    <svg class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400 dark:text-slate-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 3.478 9.772l3.875 3.875a.75.75 0 0 0 1.06-1.06l-3.875-3.875A5.5 5.5 0 0 0 9 3.5Zm-4 5.5a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z" clip-rule="evenodd" />
                    </svg>
                </div>
                <select wire:model.live="type" wire:loading.attr="disabled" class="w-full rounded-full border border-slate-200 bg-white/90 py-2.5 px-4 text-sm text-slate-600 shadow-inner shadow-slate-100 transition-all duration-300 focus:border-sky-400 focus:ring-2 focus:ring-sky-200 focus:ring-offset-1 focus:ring-offset-white sm:w-auto dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200">
                    <option value="all">Semua Jenis</option>
                    <option value="iuran">Iuran Bulanan</option>
                    <option value="sumbangan">Sumbangan</option>
                    <option value="lainnya">Lainnya</option>
                </select>
                <select wire:model.live="status" wire:loading.attr="disabled" class="w-full rounded-full border border-slate-200 bg-white/90 py-2.5 px-4 text-sm text-slate-600 shadow-inner shadow-slate-100 transition-all duration-300 focus:border-sky-400 focus:ring-2 focus:ring-sky-200 focus:ring-offset-1 focus:ring-offset-white sm:w-auto dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200">
                    <option value="unpaid">Belum Lunas</option>
                    <option value="paid">Sudah Lunas</option>
                    <option value="overdue">Terlambat</option>
                    <option value="all">Semua Status</option>
                </select>
                <a wire:navigate href="{{ route('admin.tagihan.create') }}" class="btn-soft-emerald w-full text-xs sm:w-auto sm:text-sm">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    + Buat Tagihan
                </a>
            </div>
        </div>

        <div class="mt-6 overflow-hidden overflow-x-auto rounded-3xl border border-slate-200/70 shadow-inner shadow-slate-100 dark:border-slate-700 dark:shadow-slate-900/40">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-900/90 text-[11px] uppercase tracking-wide text-white/95 dark:bg-slate-800/80">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold">Warga</th>
                        <th class="px-5 py-3 text-left font-semibold">Deskripsi</th>
                        <th class="px-5 py-3 text-left font-semibold">Jatuh Tempo</th>
                        <th class="px-5 py-3 text-left font-semibold">Status</th>
                        <th class="px-5 py-3 text-right font-semibold">Nominal</th>
                        <th class="px-5 py-3 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100/80 bg-white/95 dark:divide-slate-800/60 dark:bg-slate-900/70" wire:loading.remove wire:target="type,status,search,perPage,page">
                    @forelse ($bills as $bill)
                        @php
                            $statusColor = match ($bill->status) {
                                'paid' => 'bg-emerald-50 border-emerald-300 text-emerald-600 dark:border-emerald-500/40 dark:text-emerald-200',
                                'unpaid' => 'bg-amber-50 border-amber-300 text-amber-600 dark:border-amber-500/40 dark:text-amber-200',
                                'overdue' => 'bg-rose-50 border-rose-300 text-rose-600 dark:border-rose-500/40 dark:text-rose-200',
                                default => 'bg-slate-100 border-slate-300 text-slate-600 dark:border-slate-600 dark:text-slate-300',
                            };
                        @endphp
                        <tr wire:key="bill-{{ $bill->id }}" class="transition-all duration-300 hover:bg-slate-50/70 dark:hover:bg-slate-800/60">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-800 dark:text-slate-100">{{ $bill->user?->name ?? '-' }}</p>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500">Invoice: {{ $bill->invoice_number }}</p>
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-500 dark:text-slate-400">
                                <p>{{ $bill->title }}</p>
                                <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">Jenis: {{ Str::headline($bill->type) }}</p>
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-500 dark:text-slate-400">
                                {{ $bill->due_date?->translatedFormat('d M Y') ?? '-' }}
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center rounded-full border px-3 py-1 text-[11px] font-semibold uppercase tracking-wide {{ $statusColor }}">
                                    {{ Str::headline($bill->status) }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right text-sm font-semibold text-slate-700 dark:text-slate-200">Rp {{ number_format($bill->amount) }}</td>
                            <td class="px-5 py-4 text-right text-xs">
                                <div class="flex items-center justify-end gap-2">
                                    <a wire:navigate href="{{ route('admin.tagihan.edit', $bill) }}" class="rounded-full border border-sky-300 px-3 py-1 font-semibold text-sky-600 transition-colors duration-200 hover:border-sky-400 hover:bg-sky-50 dark:border-sky-500/40 dark:text-sky-200 dark:hover:border-sky-400 dark:hover:bg-sky-500/10">Edit</a>
                                    <button type="button" wire:click="showDetail({{ $bill->id }})" wire:loading.attr="disabled" wire:target="showDetail({{ $bill->id }})" class="rounded-full border border-slate-200 px-3 py-1 font-semibold text-slate-600 transition-colors duration-200 hover:border-sky-200 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-300 dark:border-slate-600 dark:text-slate-300 dark:hover:border-slate-500 dark:hover:bg-slate-800/60">Detail</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-xs text-slate-400 dark:text-slate-500">Belum ada data tagihan sesuai filter.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tbody class="divide-y divide-slate-100/80 bg-white/95 dark:divide-slate-800/60 dark:bg-slate-900/70" wire:loading.flex wire:target="type,status,search,perPage,page">
                    @foreach (range(1, 5) as $placeholder)
                        <tr class="animate-pulse">
                            <td class="px-5 py-4">
                                <div class="h-3 w-32 rounded bg-slate-100 dark:bg-slate-800/70"></div>
                                <div class="mt-2 h-2 w-24 rounded bg-slate-100 dark:bg-slate-800/70"></div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="space-y-2">
                                    <div class="h-2 w-40 rounded bg-slate-100 dark:bg-slate-800/70"></div>
                                    <div class="h-2 w-32 rounded bg-slate-100 dark:bg-slate-800/70"></div>
                                    <div class="h-2 w-36 rounded bg-slate-100 dark:bg-slate-800/70"></div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="h-3 w-24 rounded bg-slate-100 dark:bg-slate-800/70"></div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="h-6 w-20 rounded-full bg-slate-100 dark:bg-slate-800/70"></div>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="h-3 w-16 rounded bg-slate-100 dark:bg-slate-800/70"></div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <div class="h-6 w-16 rounded-full bg-slate-100 dark:bg-slate-800/70"></div>
                                    <div class="h-6 w-16 rounded-full bg-slate-100 dark:bg-slate-800/70"></div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex flex-col items-center justify-between gap-4 text-xs text-slate-500 dark:text-slate-400 md:flex-row">
            <div class="rounded-full border border-slate-200/80 bg-white/80 px-4 py-2 shadow-inner shadow-slate-100 dark:border-slate-700 dark:bg-slate-900/70">Menampilkan {{ $bills->firstItem() ?? 0 }}-{{ $bills->lastItem() ?? 0 }} dari {{ $bills->total() }} tagihan</div>
            <div class="text-sm">
                {{ $bills->onEachSide(1)->links() }}
            </div>
        </div>
    </section>

    <section class="grid gap-6 lg:grid-cols-2" data-aos="fade-up" data-aos-delay="160">
        <div class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition-colors duration-200 hover:border-sky-200 dark:border-slate-700 dark:bg-slate-900/80" data-motion-card>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Tagihan Terbaru</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">Lima tagihan terakhir yang dibuat.</p>
            <ul class="mt-4 space-y-3 text-sm">
                @forelse ($recentBills ?? [] as $recent)
                    @php
                        $recentTitle = data_get($recent, 'title');
                        $recentUserName = data_get($recent, 'user.name');
                        $recentAmount = (int) data_get($recent, 'amount', 0);
                        $recentDueDate = data_get($recent, 'due_date');
                        $recentDueDateFormatted = $recentDueDate instanceof CarbonInterface
                            ? $recentDueDate->translatedFormat('d M Y')
                            : (is_string($recentDueDate) ? $recentDueDate : null);
                    @endphp
                    <li class="rounded-2xl border border-slate-200/70 bg-white/90 p-4 shadow-inner shadow-slate-100 transition-colors duration-200 hover:border-sky-200 dark:border-slate-700 dark:bg-slate-900/70 dark:shadow-slate-900/40 dark:hover:border-sky-500/40">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-slate-800 dark:text-slate-100">{{ $recentTitle ?? '-' }}</p>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500">{{ $recentUserName ?? '-' }}</p>
                            </div>
                            <span class="rounded-full border border-sky-200/70 bg-sky-50 px-3 py-1 text-[11px] font-semibold text-sky-600 dark:border-sky-500/40 dark:bg-sky-500/10 dark:text-sky-200">Rp {{ number_format($recentAmount) }}</span>
                        </div>
                        <p class="mt-2 text-[11px] text-slate-400 dark:text-slate-500">Jatuh tempo {{ $recentDueDateFormatted ?? '-' }}</p>
                    </li>
                @empty
                    <li class="rounded-2xl border border-dashed border-slate-200 p-6 text-center text-xs text-slate-400 dark:border-slate-700/70 dark:text-slate-500">Belum ada tagihan terbaru.</li>
                @endforelse
            </ul>
        </div>
        <div class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition-colors duration-200 hover:border-sky-200 dark:border-slate-700 dark:bg-slate-900/80" data-motion-card>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Ringkasan Status</h2>
            <div class="mt-4 space-y-3 text-xs text-slate-500 dark:text-slate-400">
                <div class="flex items-center justify-between rounded-2xl border border-slate-200/70 bg-white/80 px-4 py-3 dark:border-slate-700 dark:bg-slate-900/70">
                    <span>Sudah Lunas</span>
                    <span class="font-semibold text-emerald-500 dark:text-emerald-300">{{ number_format($statsByStatus['paid'] ?? 0) }}</span>
                </div>
                <div class="flex items-center justify-between rounded-2xl border border-slate-200/70 bg-white/80 px-4 py-3 dark:border-slate-700 dark:bg-slate-900/70">
                    <span>Menunggu</span>
                    <span class="font-semibold text-amber-500 dark:text-amber-300">{{ number_format($statsByStatus['unpaid'] ?? 0) }}</span>
                </div>
                <div class="flex items-center justify-between rounded-2xl border border-slate-200/70 bg-white/80 px-4 py-3 dark:border-slate-700 dark:bg-slate-900/70">
                    <span>Overdue</span>
                    <span class="font-semibold text-rose-500 dark:text-rose-300">{{ number_format($statsByStatus['overdue'] ?? 0) }}</span>
                </div>
            </div>
        </div>
    </section>
    @if($showDetailModal)
    @php
        $statusTone = $detailBill['status_tone'] ?? 'info';
        $toneClasses = [
            'success' => 'border-emerald-200 bg-emerald-50 text-emerald-600 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200',
            'warning' => 'border-amber-200 bg-amber-50 text-amber-600 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200',
            'danger' => 'border-rose-200 bg-rose-50 text-rose-600 dark:border-rose-500/40 dark:bg-rose-500/10 dark:text-rose-200',
            'info' => 'border-sky-200 bg-sky-50 text-sky-600 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-200',
        ];
        $statusClass = $toneClasses[$statusTone] ?? $toneClasses['info'];
    @endphp
    <div class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-900/70 px-4 py-10 backdrop-blur-sm" wire:click.self="closeDetailModal" wire:keydown.escape.window="closeDetailModal">
        <div class="relative flex w-full max-w-4xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900 sm:max-w-3xl" style="max-height: calc(100vh - 4rem);">
            <div class="w-full flex-1 overflow-y-auto">
                <button type="button" wire:click="closeDetailModal" class="absolute right-5 top-5 inline-flex h-10 w-10 items-center justify-center rounded-full border border-transparent text-slate-500 transition-colors duration-200 hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-300 dark:text-slate-400 dark:hover:bg-slate-800/80 dark:hover:text-slate-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            @if($detailBill)
                <div class="space-y-8 px-6 py-8 sm:p-8">
                    <header class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.32em] text-slate-400 dark:text-slate-500">Detail Tagihan</p>
                            <h3 class="mt-1 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ $detailBill['title'] ?? 'Tanpa Judul' }}</h3>
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                                Invoice: <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $detailBill['invoice'] ?? '-' }}</span>
                            </p>
                        </div>
                        <div class="flex flex-col items-start gap-2 md:items-end">
                            <span class="text-xs uppercase tracking-[0.28em] text-slate-400 dark:text-slate-500">Status</span>
                            <span class="inline-flex items-center gap-2 rounded-full border px-4 py-1.5 text-sm font-semibold {{ $statusClass }}">
                                <span class="h-2 w-2 rounded-full bg-current opacity-70"></span>
                                {{ $detailBill['status'] ?? 'Tidak diketahui' }}
                            </span>
                        </div>
                    </header>

                    <section class="grid gap-6 md:grid-cols-2">
                        <article class="rounded-2xl border border-slate-200/70 bg-slate-50/70 p-5 shadow-inner shadow-slate-100 dark:border-slate-700 dark:bg-slate-900/70 dark:shadow-slate-900/30">
                            <h4 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Informasi Warga</h4>
                            <dl class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-300">
                                <div>
                                    <dt class="font-semibold text-slate-500 dark:text-slate-400">Nama</dt>
                                    <dd>{{ $detailBill['user']['name'] ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="font-semibold text-slate-500 dark:text-slate-400">Email</dt>
                                    <dd>{{ $detailBill['user']['email'] ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="font-semibold text-slate-500 dark:text-slate-400">Dibuat oleh</dt>
                                    <dd>{{ $detailBill['creator'] ?? '-' }}</dd>
                                </div>
                            </dl>
                        </article>

                        <article class="rounded-2xl border border-slate-200/70 bg-white p-5 shadow-inner shadow-slate-100 dark:border-slate-700 dark:bg-slate-900/70 dark:shadow-slate-900/30">
                            <h4 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Info Tagihan</h4>
                            <dl class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-300">
                                <div>
                                    <dt class="font-semibold text-slate-500 dark:text-slate-400">Jenis</dt>
                                    <dd>{{ $detailBill['type'] ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="font-semibold text-slate-500 dark:text-slate-400">Jatuh Tempo</dt>
                                    <dd>{{ $detailBill['due_date'] ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="font-semibold text-slate-500 dark:text-slate-400">Diterbitkan</dt>
                                    <dd>{{ $detailBill['issued_at'] ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="font-semibold text-slate-500 dark:text-slate-400">Terbayar</dt>
                                    <dd>{{ $detailBill['paid_at'] ?? '-' }}</dd>
                                </div>
                            </dl>
                        </article>
                    </section>

                    <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="rounded-2xl border border-slate-200/70 bg-white/90 p-4 text-sm shadow-inner shadow-slate-100 dark:border-slate-700 dark:bg-slate-900/70 dark:shadow-slate-900/30">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-slate-400 dark:text-slate-500">Nominal</p>
                            <p class="mt-2 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $detailBill['amount'] ?? 'Rp 0' }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200/70 bg-white/90 p-4 text-sm shadow-inner shadow-slate-100 dark:border-slate-700 dark:bg-slate-900/70 dark:shadow-slate-900/30">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-slate-400 dark:text-slate-500">Biaya Layanan</p>
                            <p class="mt-2 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $detailBill['gateway_fee'] ?? 'Rp 0' }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200/70 bg-white/90 p-4 text-sm shadow-inner shadow-slate-100 dark:border-slate-700 dark:bg-slate-900/70 dark:shadow-slate-900/30">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-slate-400 dark:text-slate-500">Total Ditagihkan</p>
                            <p class="mt-2 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $detailBill['payable_amount'] ?? $detailBill['total_amount'] ?? 'Rp 0' }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200/70 bg-white/90 p-4 text-sm shadow-inner shadow-slate-100 dark:border-slate-700 dark:bg-slate-900/70 dark:shadow-slate-900/30 sm:col-span-2 lg:col-span-1">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-slate-400 dark:text-slate-500">Sisa Tertagih</p>
                            <p class="mt-2 text-lg font-semibold text-rose-500 dark:text-rose-300">{{ $detailBill['outstanding_amount'] ?? 'Rp 0' }}</p>
                        </div>
                    </section>

                    @if(!empty($detailBill['description']))
                        <section class="rounded-2xl border border-slate-200/70 bg-white p-5 shadow-inner shadow-slate-100 dark:border-slate-700 dark:bg-slate-900/70 dark:shadow-slate-900/30">
                            <h4 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Deskripsi</h4>
                            <div class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                                {!! nl2br(e($detailBill['description'])) !!}
                            </div>
                        </section>
                    @endif

                    <section class="rounded-2xl border border-slate-200/70 bg-white p-5 shadow-inner shadow-slate-100 dark:border-slate-700 dark:bg-slate-900/70 dark:shadow-slate-900/30">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Riwayat Pembayaran Terakhir</h4>
                            @if(empty($detailBill['payments']))
                                <span class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400 dark:text-slate-500">Belum Ada</span>
                            @endif
                        </div>
                        @if(!empty($detailBill['payments']))
                            <div class="mt-4 space-y-3">
                                @foreach ($detailBill['payments'] as $payment)
                                    <article class="rounded-2xl border border-slate-200/60 bg-slate-50/60 p-4 text-sm transition-colors duration-200 hover:border-sky-200 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300 dark:hover:border-sky-500/40">
                                        <div class="flex flex-wrap items-start justify-between gap-2">
                                            <div>
                                                <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $payment['amount'] ?? '-' }}</p>
                                                <p class="text-[11px] uppercase tracking-[0.28em] text-slate-400 dark:text-slate-500">{{ $payment['status'] ?? '-' }}</p>
                                            </div>
                                            <div class="text-right text-xs text-slate-500 dark:text-slate-400">
                                                <p>{{ $payment['paid_at'] ?? '-' }}</p>
                                                <p>{{ $payment['channel'] ?? '-' }}</p>
                                            </div>
                                        </div>
                                        <div class="mt-3 grid gap-2 text-xs text-slate-500 dark:text-slate-400 sm:grid-cols-3">
                                            <div>Biaya: <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $payment['fee'] ?? '-' }}</span></div>
                                            <div>Total Dibayar: <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $payment['customer_total'] ?? '-' }}</span></div>
                                            <div>Referensi: <span class="font-mono text-[11px] text-slate-500 dark:text-slate-400">{{ $payment['reference'] ?? '-' }}</span></div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">Belum ada pembayaran tercatat untuk tagihan ini.</p>
                        @endif
                    </section>

                    <div class="flex items-center justify-end gap-2">
                        <button type="button" wire:click="closeDetailModal" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-500 transition-colors duration-200 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-300 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800/70">Tutup</button>
                        @if(!empty($detailBill['id']))
                            <a href="{{ route('admin.tagihan.edit', $detailBill['id']) }}" class="rounded-full bg-sky-600 px-4 py-2 text-sm font-semibold text-white transition-colors duration-200 hover:bg-sky-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-300 dark:bg-sky-500 dark:hover:bg-sky-400">Buka Halaman Edit</a>
                        @endif
                    </div>
                </div>
            @else
                <div class="flex flex-col items-center justify-center gap-3 p-16 text-sm text-slate-500 dark:text-slate-400">
                    <svg class="h-8 w-8 animate-spin text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Memuat detail tagihan...
                </div>
            @endif
            </div> {{-- scrollable area --}}
        </div> {{-- modal container --}}
    </div> {{-- backdrop --}}
@endif

</div>
