<div class="font-['Inter'] text-slate-800 space-y-8" wire:poll.12s data-admin-stack>
    @php
        $summaryCards = [
            [
                'label' => 'Saldo buku kas',
                'value' => 'Rp ' . number_format($overview['balance']),
                'description' => 'Ikhtisar saldo kas terkini yang tampil di portal warga.',
                'accent' => 'sky',
            ],
            [
                'label' => 'Pemasukan total',
                'value' => 'Rp ' . number_format($overview['income']),
                'description' => 'Termasuk pembayaran otomatis dan pencatatan manual.',
                'accent' => 'emerald',
            ],
            [
                'label' => 'Total pengeluaran',
                'value' => 'Rp ' . number_format($overview['expense']),
                'description' => 'Semua biaya operasional yang tercatat pada periode berjalan.',
                'accent' => 'amber',
            ],
            [
                'label' => 'Periode dipilih',
                'value' => 'Rp ' . number_format($overview['range_net']),
                'description' => 'Pemasukan Rp ' . number_format($overview['range_income']) . ' - Pengeluaran Rp ' . number_format($overview['range_expense']),
                'accent' => 'purple',
            ],
        ];
    @endphp

    <section class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition-colors duration-200 dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card>
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-3">
                <p class="text-sm font-medium text-sky-600 dark:text-sky-300">Ringkasan kas RT</p>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Pantau arus kas secara realtime</h1>
                <p class="max-w-2xl text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                    Data berikut memperbarui otomatis setiap 12 detik sehingga pengurus dapat mengambil keputusan keuangan dengan cepat.
                </p>
            </div>
            <dl class="inline-flex items-center gap-2 rounded-full border border-slate-200/80 bg-white px-4 py-2 text-xs font-semibold text-sky-600 shadow-inner shadow-slate-200/40 dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-sky-300">
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-sky-400 opacity-75"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-sky-500"></span>
                </span>
                Pembaruan realtime aktif
            </dl>
        </div>
        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($summaryCards as $card)
                <article class="space-y-3 p-5" data-metric-card data-accent="{{ $card['accent'] }}">
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $card['label'] }}</p>
                    <p class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ $card['value'] }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $card['description'] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="grid gap-4 xl:grid-cols-5" data-motion-animated>
        @php
            $peak = max(1, collect($trendData)->map(fn ($point) => abs($point['total']))->max() ?: 1);
        @endphp
        <div class="xl:col-span-3 rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm shadow-slate-200/40 transition-colors duration-200 hover:bg-slate-50/80 dark:border-slate-800/70 dark:bg-slate-900/80 dark:hover:bg-slate-900/70 dark:shadow-slate-900/40">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-50">Tren 7 Hari Terakhir</h2>
                    <p class="text-xs text-slate-400 dark:text-slate-500">Pantau arus kas terkini secara realtime.</p>
                </div>
                <span class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50/80 px-3 py-1.5 text-xs font-semibold text-sky-700 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-200">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-sky-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-sky-500"></span>
                    </span>
                    Pembaruan realtime aktif
                </span>
            </div>
            <div class="mt-6 grid gap-4 sm:grid-cols-7">
                @foreach ($trendData as $point)
                    @php
                        $height = round((abs($point['total']) / $peak) * 100);
                        $isIncome = $point['total'] >= 0;
                    @endphp
                    <div class="flex flex-col items-center rounded-2xl border border-slate-100/80 bg-slate-50/70 p-3 text-center dark:border-slate-800/60 dark:bg-slate-900/60">
                        <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $point['label'] }}</span>
                        <div class="mt-3 flex h-24 w-12 items-end rounded-xl bg-gradient-to-t from-slate-200/60 via-slate-200/20 to-transparent p-1 dark:from-slate-800/60 dark:via-slate-800/30">
                            <div class="w-full rounded-lg {{ $isIncome ? 'bg-emerald-400/80' : 'bg-rose-400/80' }}" style="height: {{ max(8, $height) }}%"></div>
                        </div>
                        <p class="mt-3 text-xs font-semibold text-slate-600 dark:text-slate-200">{{ $point['date'] }}</p>
                        <p class="text-[11px] text-slate-400 dark:text-slate-500">{{ $isIncome ? '+' : '-' }} Rp {{ number_format(abs($point['total'])) }}</p>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="xl:col-span-2 rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm shadow-slate-200/40 transition-colors duration-200 hover:bg-slate-50/80 dark:border-slate-800/70 dark:bg-slate-900/80 dark:hover:bg-slate-900/70 dark:shadow-slate-900/40">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-50">Distribusi Kategori</h2>
            <p class="text-xs text-slate-400 dark:text-slate-500">Lihat sebaran pemasukan & pengeluaran per kategori.</p>
            <div class="mt-4 space-y-4">
                @foreach ($categoryBreakdown as $category)
                    @php
                        $categoryTotal = max(1, $category['income'] + $category['expense']);
                        $totalAll = max(1, $overview['income'] + $overview['expense']);
                        $percentage = round((($category['income'] + $category['expense']) / $totalAll) * 100);
                        $incomePct = round(($category['income'] / $categoryTotal) * 100);
                        $expensePct = round(($category['expense'] / $categoryTotal) * 100);
                    @endphp
                    <div class="rounded-2xl border border-slate-100 bg-white/60 p-4 dark:border-slate-800/60 dark:bg-slate-900/60">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $category['category'] }}</h3>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500">{{ $percentage }}% dari total arus kas</p>
                            </div>
                            <span class="rounded-full bg-sky-500/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-sky-600 dark:bg-sky-500/10 dark:text-sky-200">{{ $percentage }}%</span>
                        </div>
                        <div class="mt-4 space-y-3">
                            <div>
                                <div class="flex items-center justify-between text-[11px] font-medium text-emerald-500 dark:text-emerald-300">
                                    <span class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
                                        <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                                        Pemasukan
                                    </span>
                                    <span>{{ $incomePct }}%</span>
                                </div>
                                <div class="mt-1 h-2 rounded-full bg-emerald-100/60 dark:bg-emerald-900/40">
                                    <div class="h-full rounded-full bg-emerald-400/90" style="width: {{ $incomePct }}%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex items-center justify-between text-[11px] font-medium text-rose-500 dark:text-rose-300">
                                    <span class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
                                        <span class="h-2 w-2 rounded-full bg-rose-400"></span>
                                        Pengeluaran
                                    </span>
                                    <span>{{ $expensePct }}%</span>
                                </div>
                                <div class="mt-1 h-2 rounded-full bg-rose-100/60 dark:bg-rose-900/40">
                                    <div class="h-full rounded-full bg-rose-400/90" style="width: {{ $expensePct }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
                @if ($categoryBreakdown->isEmpty())
                    <p class="text-sm text-slate-400 dark:text-slate-500">Belum ada catatan kas pada periode ini.</p>
                @endif
            </div>
        </div>
    </section>

    <section class="grid gap-4 lg:grid-cols-2" data-motion-animated>
        <div class="rounded-3xl border border-sky-200/70 bg-white/95 p-6 shadow-sm shadow-sky-100/60 transition-colors duration-200 hover:bg-sky-50/80 dark:border-slate-800/70 dark:bg-slate-900/80 dark:hover:bg-slate-900/70 dark:shadow-slate-900/40">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-50">Catat Kas / Pengeluaran</h2>
            <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">Tambah pemasukan atau biaya secara manual. Sistem otomatis menghitung saldo.</p>
            <form wire:submit.prevent="createEntry" class="mt-5 space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-300">Tipe Catatan</label>
                    <select wire:model.live="entry_type" class="mt-2 w-full rounded-xl border border-sky-200/60 bg-white/95 py-3 px-4 text-sm text-slate-600 shadow-inner shadow-sky-100 transition-colors duration-200 focus:border-sky-400 focus:ring-2 focus:ring-sky-200 focus:ring-offset-1 focus:ring-offset-white dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-200">
                            <option value="income">Pemasukan</option>
                            <option value="expense">Pengeluaran</option>
                        </select>
                        @error('entry_type') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-300">Metode</label>
                        <select wire:model.live="entry_method" class="mt-2 w-full rounded-xl border border-sky-200/60 bg-white/95 py-3 px-4 text-sm text-slate-600 shadow-inner shadow-sky-100 transition-colors duration-200 focus:border-sky-400 focus:ring-2 focus:ring-sky-200 focus:ring-offset-1 focus:ring-offset-white dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-200">
                            <option value="transfer">Transfer</option>
                            <option value="cash">Cash / Tunai</option>
                        </select>
                        <p class="mt-2 text-[11px] text-slate-400 dark:text-slate-500">Transfer untuk transaksi via bank/e-wallet, cash untuk serah terima langsung.</p>
                        @error('entry_method') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-300">Kategori</label>
                        <input wire:model.defer="entry_category" type="text" class="mt-2 w-full rounded-xl border border-sky-200/60 bg-white/95 py-3 px-4 text-sm text-slate-600 shadow-inner shadow-sky-100 transition-colors duration-200 focus:border-sky-400 focus:ring-2 focus:ring-sky-200 focus:ring-offset-1 focus:ring-offset-white dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-200" placeholder="Contoh: Iuran keamanan / Operasional kebersihan">
                        @error('entry_category') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-300">Status</label>
                        <select wire:model.live="entry_status" class="mt-2 w-full rounded-xl border border-sky-200/60 bg-white/95 py-3 px-4 text-sm text-slate-600 shadow-inner shadow-sky-100 transition-colors duration-200 focus:border-sky-400 focus:ring-2 focus:ring-sky-200 focus:ring-offset-1 focus:ring-offset-white dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-200">
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                        </select>
                        <p class="mt-2 text-[11px] text-slate-400 dark:text-slate-500">Status paid tidak dapat dikembalikan ke pending.</p>
                        @error('entry_status') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-300">Nominal</label>
                        <div class="mt-2 flex items-center rounded-xl border border-sky-200/60 bg-white/95 px-4 shadow-inner shadow-sky-100 transition-colors duration-200 focus-within:border-sky-400 focus-within:ring-2 focus-within:ring-sky-200 focus-within:ring-offset-1 focus-within:ring-offset-white dark:border-slate-700/60 dark:bg-slate-900/70">
                            <span class="text-sm font-semibold text-sky-500 dark:text-sky-300">Rp</span>
                            <input wire:model.defer="entry_amount" type="number" min="1000" step="1000" class="h-10 flex-1 bg-transparent pl-3 text-sm text-slate-600 transition-colors duration-200 focus:border-0 focus:outline-none focus:ring-0 dark:text-slate-200">
                        </div>
                        @error('entry_amount') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-300">Waktu Terjadi</label>
                        <input wire:model.defer="entry_occurred_at" type="datetime-local" class="mt-2 w-full rounded-xl border border-sky-200/60 bg-white/95 py-3 px-4 text-sm text-slate-600 shadow-inner shadow-sky-100 transition-colors duration-200 focus:border-sky-400 focus:ring-2 focus:ring-sky-200 focus:ring-offset-1 focus:ring-offset-white dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-200">
                        @error('entry_occurred_at') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="rounded-2xl border border-sky-100 bg-sky-50/60 px-4 py-4 shadow-inner shadow-sky-100 dark:border-slate-700/60 dark:bg-slate-900/60">
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-300">Aliran Dana</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $entry_type === 'income' ? 'Dana akan dicatat masuk ke:' : 'Dana akan diambil dari:' }}</p>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <label class="flex items-center gap-3 rounded-xl border border-sky-200/70 bg-white/95 px-4 py-3 shadow-sm transition-colors duration-200 hover:bg-sky-50/80 dark:border-slate-700/60 dark:bg-slate-900/70 dark:hover:bg-slate-800/70">
                            <input type="radio" wire:model.live="entry_bucket" value="kas" class="h-4 w-4 text-sky-500 focus:ring-sky-400 dark:border-slate-700 dark:bg-slate-900">
                            <div>
                                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Kas Utama</p>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500">Digunakan untuk kebutuhan kas rutin RT.</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 rounded-xl border border-sky-200/70 bg-white/95 px-4 py-3 shadow-sm transition-colors duration-200 hover:bg-sky-50/80 dark:border-slate-700/60 dark:bg-slate-900/70 dark:hover:bg-slate-800/70">
                            <input type="radio" wire:model.live="entry_bucket" value="sumbangan" class="h-4 w-4 text-sky-500 focus:ring-sky-400 dark:border-slate-700 dark:bg-slate-900">
                            <div>
                                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Sumbangan</p>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500">Tulis tujuan sumbangan agar aliran dana jelas.</p>
                            </div>
                        </label>
                    </div>
                    @error('entry_bucket') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                    @if ($entry_bucket === 'sumbangan')
                        <div class="mt-4 space-y-3 rounded-xl border border-sky-200/50 bg-white/95 px-4 py-4 shadow-inner shadow-sky-100 dark:border-slate-700/60 dark:bg-slate-900/70">
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-300">Pilih Sumbangan</label>
                                <select wire:model="entry_bucket_bill_id" class="mt-2 w-full rounded-xl border border-sky-200/60 bg-white/95 py-2.5 px-4 text-sm text-slate-600 shadow-inner shadow-sky-100 transition-colors duration-200 focus:border-sky-400 focus:ring-2 focus:ring-sky-200 focus:ring-offset-1 focus:ring-offset-white dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-200">
                                    <option value="">Pilih sumbangan terdaftar...</option>
                                    @foreach ($donationOptions as $donation)
                                        <option value="{{ $donation['id'] }}">{{ $donation['title'] }}</option>
                                    @endforeach
                                </select>
                                @error('entry_bucket_bill_id') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-300">atau tulis nama sumbangan</label>
                                <input wire:model.defer="entry_bucket_reference" type="text" class="mt-2 w-full rounded-xl border border-sky-200/60 bg-white/95 py-2.5 px-4 text-sm text-slate-600 shadow-inner shadow-sky-100 transition-colors duration-200 focus:border-sky-400 focus:ring-2 focus:ring-sky-200 focus:ring-offset-1 focus:ring-offset-white dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-200" placeholder="Contoh: Dana Sosial 17 Agustus">
                                @error('entry_bucket_reference') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    @endif
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-300">Catatan</label>
                    <textarea wire:model.defer="entry_notes" rows="3" class="mt-2 w-full rounded-xl border border-sky-200/60 bg-white/95 py-3 px-4 text-sm text-slate-600 shadow-inner shadow-sky-100 transition-colors duration-200 focus:border-sky-400 focus:ring-2 focus:ring-sky-200 focus:ring-offset-1 focus:ring-offset-white dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-200" placeholder="Keterangan transparan untuk warga (opsional)."></textarea>
                    @error('entry_notes') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <button type="submit" class="btn-soft-emerald text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Simpan Catatan
                    </button>
                    <span class="text-xs text-slate-400 dark:text-slate-500">Disinkronkan otomatis ke laporan warga.</span>
                </div>
            </form>
        </div>
        <div class="rounded-3xl border border-slate-200/70 bg-white/95 shadow-sm shadow-slate-200/40 transition-colors duration-200 hover:bg-slate-50/80 dark:border-slate-800/70 dark:bg-slate-900/80 dark:hover:bg-slate-900/70 dark:shadow-slate-900/40">
            <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 dark:border-slate-800/60">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-50">Filter & Tampilan</h2>
                <p class="text-xs text-slate-400 dark:text-slate-500">Susun data sesuai kebutuhan audit dan publikasi.</p>
            </div>
            <div class="space-y-4 px-6 py-5 text-xs text-slate-500 dark:text-slate-300">
                <div>
                    <label class="font-semibold uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Rentang</label>
                    <select wire:model.live="range" class="mt-2 w-full rounded-xl border border-slate-200/80 bg-white/95 py-2.5 px-3 text-sm text-slate-600 shadow-inner shadow-slate-100 transition-colors duration-200 focus:border-sky-400 focus:ring-2 focus:ring-sky-200 focus:ring-offset-1 focus:ring-offset-white dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-200">
                        <option value="today">Hari ini</option>
                        <option value="7d">7 hari</option>
                        <option value="month">Bulan ini</option>
                        <option value="90d">90 hari</option>
                        <option value="year">Tahun ini</option>
                        <option value="all">Semua waktu</option>
                    </select>
                </div>
                <div>
                    <label class="font-semibold uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Tipe</label>
                    <select wire:model.live="type" class="mt-2 w-full rounded-xl border border-slate-200/80 bg-white/95 py-2.5 px-3 text-sm text-slate-600 shadow-inner shadow-slate-100 transition-colors duration-200 focus:border-sky-400 focus:ring-2 focus:ring-sky-200 focus:ring-offset-1 focus:ring-offset-white dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-200">
                        <option value="all">Semua</option>
                        <option value="income">Pemasukan</option>
                        <option value="expense">Pengeluaran</option>
                    </select>
                </div>
                <div>
                    <label class="font-semibold uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Pencarian</label>
                    <input type="text" wire:model.live.debounce.500ms="search" placeholder="Kategori, invoice, catatan" class="mt-2 w-full rounded-xl border border-slate-200/80 bg-white/95 py-2.5 px-3 text-sm shadow-inner shadow-slate-100 transition-colors duration-200 focus:border-sky-400 focus:ring-2 focus:ring-sky-200 focus:ring-offset-1 focus:ring-offset-white dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-200">
                </div>
                <div>
                    <label class="font-semibold uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Urutkan</label>
                    <select wire:model.live="sort" class="mt-2 w-full rounded-xl border border-slate-200/80 bg-white/95 py-2.5 px-3 text-sm text-slate-600 shadow-inner shadow-slate-100 transition-colors duration-200 focus:border-sky-400 focus:ring-2 focus:ring-sky-200 focus:ring-offset-1 focus:ring-offset-white dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-200">
                        <option value="latest">Terbaru</option>
                        <option value="oldest">Terlama</option>
                    </select>
                </div>
                <div>
                    <label class="font-semibold uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Tampilan Data</label>
                    <select wire:model.live="perPage" class="mt-2 w-full rounded-xl border border-slate-200/80 bg-white/95 py-2.5 px-3 text-sm text-slate-600 shadow-inner shadow-slate-100 transition-colors duration-200 focus:border-sky-400 focus:ring-2 focus:ring-sky-200 focus:ring-offset-1 focus:ring-offset-white dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-200">
                        <option value="10">10 baris</option>
                        <option value="25">25 baris</option>
                        <option value="50">50 baris</option>
                    </select>
                </div>
                <div class="rounded-2xl border border-sky-100 bg-sky-50/70 px-5 py-4 text-sky-600 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-200">
                    <p class="text-sm font-semibold">Tips Transparansi</p>
                    <p class="mt-1 text-xs">Gunakan catatan singkat yang mudah dipahami warga dan perbarui status segera setelah transaksi terjadi.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-3xl border border-slate-200/70 bg-white/95 shadow-sm shadow-slate-200/40 transition-colors duration-200 hover:bg-slate-50/80 dark:border-slate-800/70 dark:bg-slate-900/80 dark:hover:bg-slate-900/70 dark:shadow-slate-900/40" data-motion-animated>
        <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 text-sm font-medium text-slate-600 dark:border-slate-800/60 dark:text-slate-200">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-2 rounded-full bg-sky-100/80 px-3 py-1 text-[11px] font-semibold uppercase tracking-widest text-sky-700 dark:bg-sky-500/20 dark:text-sky-200">
                    <span class="h-1.5 w-1.5 rounded-full bg-sky-500"></span>
                    Data realtime
                </span>
                <span># Buku Kas Terbaru</span>
            </div>
            <p class="text-xs text-slate-400 dark:text-slate-500">Gunakan filter di atas untuk melihat riwayat yang relevan.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800/70">
                <thead class="bg-slate-900/90 text-[11px] uppercase tracking-wide text-white/90 dark:bg-slate-800/80">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold">Waktu</th>
                        <th class="px-5 py-3 text-left font-semibold">Keterangan</th>
                        <th class="px-5 py-3 text-left font-semibold">Aliran Dana</th>
                        <th class="px-5 py-3 text-left font-semibold">Metode</th>
                        <th class="px-5 py-3 text-right font-semibold">Nominal</th>
                        <th class="px-5 py-3 text-left font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100/70 bg-white/90 dark:divide-slate-800/70 dark:bg-slate-900/70">
                    @forelse ($entries as $entry)
                        @php
                            $isIncome = $entry->amount >= 0;
                            $flowRaw = $isIncome ? ($entry->fund_destination ?? null) : ($entry->fund_source ?? null);
                            $flowValue = $flowRaw ?: 'kas';
                            $flowLabel = \Illuminate\Support\Str::headline($flowValue);
                            $flowPrefix = $isIncome ? 'Masuk ke' : 'Diambil dari';
                            $methodLabel = $entry->payment
                                ? \Illuminate\Support\Str::upper($entry->payment->gateway)
                                : ($entry->method ? \Illuminate\Support\Str::headline($entry->method) : 'Manual');
                            $statusKey = $entry->payment
                                ? \Illuminate\Support\Str::lower($entry->payment->status)
                                : \Illuminate\Support\Str::lower($entry->status ?? 'pending');
                            $statusLabel = $entry->payment
                                ? \Illuminate\Support\Str::upper($entry->payment->status)
                                : \Illuminate\Support\Str::headline($entry->status ?? 'pending');
                            $statusClass = match ($statusKey) {
                                'paid' => 'border-emerald-200 bg-emerald-500/10 text-emerald-600 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300',
                                'pending' => 'border-amber-200 bg-amber-500/10 text-amber-600 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300',
                                'failed', 'cancelled' => 'border-rose-200 bg-rose-500/10 text-rose-600 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300',
                                default => 'border-slate-200 bg-slate-500/10 text-slate-500 dark:border-slate-700/60 dark:bg-slate-800/60 dark:text-slate-300',
                            };
                            $canMarkPaid = ! $entry->payment && \Illuminate\Support\Str::lower($entry->status ?? 'pending') === 'pending';
                        @endphp
                        <tr class="transition-colors duration-200 hover:bg-slate-50/80 dark:hover:bg-slate-800/60">
                            <td class="px-5 py-4 text-xs text-slate-500 dark:text-slate-400 align-top">
                                <p class="font-semibold text-slate-700 dark:text-slate-200">{{ optional($entry->occurred_at)->translatedFormat('d M Y') }}</p>
                                <p class="text-[11px]">{{ optional($entry->occurred_at)->format('H:i') }}</p>
                            </td>
                            <td class="px-5 py-4 align-top">
                                <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $entry->category ?? 'Tanpa kategori' }}</p>
                                @if (filled($entry->notes))
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $entry->notes }}</p>
                                @else
                                    <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">Tidak ada catatan</p>
                                @endif
                                @if ($entry->bill)
                                    <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">Tagihan: {{ $entry->bill->title }} ({{ $entry->bill->invoice_number }})</p>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-500 dark:text-slate-400 align-top">
                                <p>{{ $flowPrefix }} <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $flowLabel }}</span></p>
                                @if ($entry->fund_reference)
                                    <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">Ref: {{ $entry->fund_reference }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-500 dark:text-slate-400 align-top">
                                @if ($entry->payment)
                                    <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-emerald-600 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
                                        {{ $methodLabel }}
                                    </span>
                                    @if ($entry->payment->reference)
                                        <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">Ref: {{ $entry->payment->reference }}</p>
                                    @endif
                                @else
                                    <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:border-slate-700/60 dark:bg-slate-800/60 dark:text-slate-300">
                                        {{ $methodLabel }}
                                    </span>
                                    <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">Dicatat manual</p>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right text-sm font-semibold {{ $isIncome ? 'text-emerald-600 dark:text-emerald-300' : 'text-rose-500 dark:text-rose-300' }}">
                                Rp {{ number_format(abs($entry->amount)) }}
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-500 dark:text-slate-400 align-top">
                                <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-[11px] font-semibold uppercase tracking-wide {{ $statusClass }}">
                                    {{ $statusLabel }}
                                </span>
                                @if ($canMarkPaid)
                                    <div class="mt-2">
                                        <button type="button" wire:click="markEntryAsPaid({{ $entry->id }})" wire:loading.attr="disabled" class="inline-flex items-center gap-2 rounded-full border border-emerald-300 px-3 py-1 text-[11px] font-semibold text-emerald-600 transition-colors duration-200 hover:bg-emerald-50/80 focus:outline-none focus:ring-2 focus:ring-emerald-300 focus:ring-offset-1 focus:ring-offset-white dark:border-emerald-500/40 dark:text-emerald-300 dark:hover:bg-emerald-500/10">
                                            Tandai Paid
                                        </button>
                                    </div>
                                @elseif (! $entry->payment)
                                    <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">Status final dicatat manual.</p>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-400 dark:text-slate-500">Belum ada data sesuai filter yang dipilih.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="flex items-center justify-between px-6 py-4 text-xs text-slate-400 dark:text-slate-500">
            <div>Menampilkan {{ $entries->firstItem() ?? 0 }}-{{ $entries->lastItem() ?? 0 }} dari {{ $entries->total() }} catatan</div>
            <div class="text-sm">
                {{ $entries->onEachSide(1)->links() }}
            </div>
        </div>
    </section>
</div>


