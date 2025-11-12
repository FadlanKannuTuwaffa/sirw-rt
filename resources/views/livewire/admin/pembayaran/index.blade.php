<div class="font-['Inter'] text-slate-800 space-y-8" data-admin-stack>
    @php
        $title = 'Pembayaran';
    @endphp
    @php
        $avgConfirmation = $stats['avg_confirmation'];
        $manualShare = $stats['total_paid'] > 0 ? round(($stats['manual_paid'] / $stats['total_paid']) * 100) : 0;
        $onlineShare = $stats['total_paid'] > 0 ? round(($stats['online_paid'] / $stats['total_paid']) * 100) : 0;

        $metricCards = [
            [
                'label' => 'Pembayaran lunas',
                'value' => 'Rp ' . number_format($stats['total_paid']),
                'accent' => 'sky',
                'hint' => number_format($stats['paid_count']) . ' transaksi berhasil diproses.',
            ],
            [
                'label' => 'Pembayaran hari ini',
                'value' => 'Rp ' . number_format($stats['paid_today']),
                'accent' => 'emerald',
                'hint' => 'Pastikan verifikasi manual segera dilakukan.',
            ],
            [
                'label' => 'Menunggu konfirmasi',
                'value' => number_format($stats['pending']) . ' transaksi',
                'accent' => 'amber',
                'hint' => 'Nilai tertahan: Rp ' . number_format($stats['pending_amount']),
            ],
            [
                'label' => 'Kecepatan konfirmasi',
                'value' => $avgConfirmation !== null ? $avgConfirmation . ' menit' : '-',
                'accent' => 'purple',
                'hint' => "Manual {$manualShare}% | Gateway {$onlineShare}%",
            ],
        ];
    @endphp

    <section class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition-colors duration-200 dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($metricCards as $card)
                <article class="space-y-2 p-5" data-metric-card data-accent="{{ $card['accent'] }}">
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $card['label'] }}</p>
                    <p class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ $card['value'] }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $card['hint'] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="grid gap-4 lg:grid-cols-5" data-motion-animated>
        <div class="lg:col-span-3 rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition-colors duration-200 dark:border-slate-700 dark:bg-slate-900/80" data-motion-card>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-sky-600 dark:text-sky-300">Catat Pembayaran Manual</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-500">Gunakan untuk transaksi tunai/transfer yang diverifikasi langsung.</p>
                </div>
                <span class="admin-chip" data-tone="accent">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m3 10 4-4m0 0 4 4M7 6v12m14-4-4 4m0 0-4-4m4 4V6" />
                    </svg>
                    Auto sinkron ke buku kas
                </span>
            </div>
            <form wire:submit.prevent="recordPayment" class="mt-6 space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-600 dark:text-sky-300">Pilih Tagihan</label>
                        <select wire:model="bill_id" class="admin-field mt-2 w-full rounded-xl py-3 px-4 text-sm">
                            <option value="">Cari berdasarkan warga / invoice...</option>
                            @foreach ($availableBills as $item)
                                <option value="{{ $item['id'] }}">{{ $item['user'] }} - {{ $item['title'] }} ({{ $item['invoice'] }}) - Rp {{ number_format($item['amount']) }} - Jatuh tempo {{ $item['due_date'] }}</option>
                            @endforeach
                        </select>
                        @error('bill_id') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                        @if ($pendingPaymentMeta)
                            @php
                                $uploadedAt = $pendingPaymentMeta['proof_uploaded_at']
                                    ? \Carbon\Carbon::parse($pendingPaymentMeta['proof_uploaded_at'])->translatedFormat('d M Y H:i')
                                    : 'Belum diunggah';
                                $proofUrl = !empty($pendingPaymentMeta['proof_path'] ?? null) && !empty($pendingPaymentMeta['payment_id'] ?? null)
                                    ? route('admin.pembayaran.manual-proof', $pendingPaymentMeta['payment_id'])
                                    : null;
                                $destination = $pendingPaymentMeta['manual_destination'] ?? [];
                            @endphp
                            <div class="admin-surface-muted mt-3 rounded-xl p-4 text-xs" data-tone="warning">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="space-y-1">
                                        <p class="text-sm font-semibold text-amber-800 dark:text-amber-100">Bukti transfer manual ditemukan</p>
                                        <p>Referensi: {{ $pendingPaymentMeta['reference'] ?? '-' }}</p>
                                        <p>Channel: {{ $pendingPaymentMeta['channel'] ?? 'Manual' }}</p>
                                        @if (!empty($destination))
                                            <p>Tujuan: {{ $destination['label'] ?? ($destination['account_number'] ?? '-') }}</p>
                                        @endif
                                        <p>Nominal: Rp {{ number_format($pendingPaymentMeta['amount'] ?? 0) }}</p>
                                        <p>Diunggah: {{ $uploadedAt }}</p>
                                    </div>
                                    @if ($proofUrl)
                                        <a href="{{ $proofUrl }}" target="_blank" class="admin-chip" data-tone="warning">
                                            Lihat Bukti
                                        </a>
                                    @endif
                                </div>
                                <p class="mt-3 text-[11px] text-amber-600 dark:text-amber-200">Nilai formulir diisi otomatis dari transaksi ini, ubah bila perlu sebelum konfirmasi.</p>
                            </div>
                        @endif
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-600 dark:text-sky-300">Nominal</label>
                        <div class="admin-field-group mt-2 flex items-center rounded-xl px-4" data-tone="accent">
                            <span class="text-sm font-semibold text-sky-500 dark:text-sky-300">Rp</span>
                            <input wire:model.defer="payment_amount" type="number" min="1000" step="1000" class="admin-field flex-1 border-0 bg-transparent pl-3 text-sm" inputmode="numeric">
                        </div>
                        @error('payment_amount') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-600 dark:text-sky-300">Tanggal Bayar</label>
                        <input wire:model.defer="payment_date" type="datetime-local" class="admin-field mt-2 w-full rounded-xl py-3 px-4 text-sm">
                        @error('payment_date') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-600 dark:text-sky-300">Referensi</label>
                        <input wire:model.defer="reference" type="text" class="admin-field mt-2 w-full rounded-xl py-3 px-4 text-sm" placeholder="Nomor transaksi / bukti transfer">
                        @error('reference') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-600 dark:text-sky-300">Catatan</label>
                        <input wire:model.defer="notes" type="text" class="admin-field mt-2 w-full rounded-xl py-3 px-4 text-sm" placeholder="Opsional, contoh: setor oleh ketua RT">
                        @error('notes') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                        @if ($pendingPaymentId)
                            <p class="mt-2 text-[11px] text-slate-500 dark:text-slate-500">Catatan akan tersimpan sebagai alasan validasi saat pembayaran ditolak.</p>
                        @endif
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <button type="submit" class="btn-soft-emerald text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        {{ $pendingPaymentId ? 'Konfirmasi Pembayaran' : 'Simpan Pembayaran' }}
                    </button>
                    @if ($pendingPaymentId)
                        <button type="button" wire:click="rejectPendingPayment" class="inline-flex items-center gap-2 rounded-full border border-rose-200 bg-rose-50/90 px-4 py-2 text-sm font-semibold text-rose-500 shadow-sm transition hover:border-rose-300 hover:bg-rose-100 hover:text-rose-600 dark:border-rose-500/40 dark:bg-rose-500/10 dark:text-rose-200 dark:hover:border-rose-400 dark:hover:bg-rose-500/20">
                            Tolak Bukti
                        </button>
                        <span class="text-xs text-slate-500 dark:text-slate-500">Periksa bukti lalu pilih valid atau tolak dengan catatan.</span>
                    @else
                        <span class="text-xs text-slate-500 dark:text-slate-500">Langsung menutup tagihan terkait.</span>
                    @endif
                </div>
            </form>
        </div>
        <div class="lg:col-span-2 admin-surface rounded-3xl p-6" data-admin-surface data-admin-fade data-delay="0.3">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-50">Snapshot Gateway</h2>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-500">Pantau performa manual vs otomatis.</p>
            <div class="mt-5 space-y-4">
                @forelse ($gatewayBreakdown as $gateway)
                    @php
                        $totalCount = $gateway['total_count'] ?? 0;
                        $successRate = $totalCount > 0 ? round(($gateway['paid_count'] / $totalCount) * 100) : 0;
                        $pendingRate = $totalCount > 0 ? round(($gateway['pending_count'] / $totalCount) * 100) : 0;
                    @endphp
                    <div class="admin-surface-muted rounded-2xl p-4">
                        <div class="flex items-center justify-between text-sm font-semibold text-slate-700 dark:text-slate-200">
                            <span>{{ $gateway['label'] }}</span>
                            <span class="text-xs text-slate-500 dark:text-slate-500">{{ $successRate }}% sukses</span>
                        </div>
                        <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-500">{{ $gateway['description'] }}</p>
                        @php
                            $tone = $gateway['status_tone'] ?? 'muted';
                            $badgeTone = match ($tone) {
                                'success' => 'success',
                                'warning' => 'warning',
                                'danger' => 'danger',
                                default => 'muted',
                            };
                            $messageTone = match ($tone) {
                                'success' => 'success',
                                'warning' => 'warning',
                                'danger' => 'danger',
                                default => 'muted',
                            };
                        @endphp
                        <span class="admin-chip mt-3 text-[11px] font-semibold" data-tone="{{ $badgeTone }}">
                            {{ $gateway['status_label'] ?? 'Status' }}
                        </span>
                        <p class="mt-2 text-[11px]" data-admin-tone="{{ $messageTone }}">{{ $gateway['status_message'] ?? '' }}</p>
                        <div class="mt-3 h-2.5 w-full overflow-hidden rounded-full bg-white/90 dark:bg-slate-800">
                            <div class="h-full bg-emerald-400/90" style="width: {{ $successRate }}%"></div>
                        </div>
                        <div class="mt-3 grid gap-2 text-[11px] text-slate-500 dark:text-slate-400">
                            <p>Berhasil: {{ number_format($gateway['paid_count']) }} trx - Rp {{ number_format($gateway['paid_amount']) }}</p>
                            <p>Menunggu: {{ number_format($gateway['pending_count']) }} trx ({{ $pendingRate }}%) - Rp {{ number_format($gateway['pending_amount']) }}</p>
                            <p>Gagal: {{ number_format($gateway['failed_count']) }} trx</p>
                        </div>
                        @if (!empty($gateway['channels']))
                            <div class="admin-surface-muted mt-4 rounded-xl p-3 text-xs">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-500">Per Channel</p>
                                <ul class="mt-2 space-y-2">
                                    @foreach ($gateway['channels'] as $channel)
                                        @php
                                            $channelTone = $channel['status_tone'] ?? 'muted';
                                            $channelToneAttr = match ($channelTone) {
                                                'success' => 'success',
                                                'warning' => 'warning',
                                                'danger' => 'danger',
                                                default => 'muted',
                                            };
                                        @endphp
                                        <li class="flex flex-col gap-1 text-[11px] text-slate-600 dark:text-slate-400">
                                            <div class="flex items-start justify-between gap-3">
                                                <div>
                                                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $channel['label'] }}</span>
                                                    <span class="ml-2 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] text-slate-500 dark:bg-slate-800/70 dark:text-slate-500">{{ number_format($channel['total_count']) }} trx</span>
                                                </div>
                                                <div class="text-right">
                                                    <p class="font-semibold text-slate-600 dark:text-slate-300">Rp {{ number_format($channel['paid_amount'] ?? 0) }}</p>
                                                </div>
                                            </div>
                                            <p class="text-[10px] text-slate-500 dark:text-slate-500">Sukses {{ number_format($channel['paid_count'] ?? 0) }} | Pending {{ number_format($channel['pending_count'] ?? 0) }} | Gagal {{ number_format($channel['failed_count'] ?? 0) }}</p>
                                            <p class="text-[10px]" data-admin-tone="{{ $channelToneAttr }}">{{ $channel['status_message'] ?? '' }}</p>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @if (!empty($gateway['unassigned']))
                            @php
                                $unassigned = $gateway['unassigned'];
                                $unassignedTone = match ($unassigned['status_tone'] ?? 'warning') {
                                    'danger' => 'danger',
                                    'success' => 'success',
                                    default => 'warning',
                                };
                            @endphp
                            <p class="mt-3 text-[11px]" data-admin-tone="{{ $unassignedTone }}">{{ $unassigned['status_message'] }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-slate-500 dark:text-slate-500">Belum ada data gateway yang bisa dianalisis.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="admin-surface rounded-3xl p-6" data-admin-surface data-admin-fade data-delay="0.35">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-50">Riwayat Pembayaran</h2>
                <p class="text-xs text-slate-500 dark:text-slate-500">Filter transaksi dan tindak lanjuti lebih cepat.</p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:gap-3">
                <div class="relative" data-admin-fade data-delay="0.4">
                    <input wire:model.debounce.400ms="search" type="search" placeholder="Cari nama warga / invoice..." class="admin-field w-full rounded-full py-2.5 pl-9 pr-3 text-sm" data-admin-control>
                    <svg class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400 transition-colors duration-300 dark:text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m21 21-4.35-4.35m0 0a7.5 7.5 0 1 0-10.607 0 7.5 7.5 0 0 0 10.607 0Z" />
                    </svg>
                </div>
                <select wire:model.live="status" wire:loading.attr="disabled" class="admin-field w-full rounded-full py-2.5 px-4 text-sm sm:w-auto">
                    <option value="paid">Sudah Lunas</option>
                    <option value="pending">Menunggu</option>
                    <option value="failed">Gagal</option>
                    <option value="all">Semua Status</option>
                </select>
                <select wire:model.live="gateway" wire:loading.attr="disabled" class="admin-field w-full rounded-full py-2.5 px-4 text-sm sm:w-auto">
                    <option value="all">Semua Metode</option>
                    <option value="manual">Manual (Semua)</option>
                    <option value="manual_bank">Manual - Bank</option>
                    <option value="manual_virtual">Manual - E-Wallet</option>
                    <option value="tripay">Tripay</option>
                </select>
            </div>
        </div>

        <div class="admin-table mt-6 overflow-hidden overflow-x-auto rounded-3xl" data-admin-table>
            <table class="min-w-full text-sm">
                <thead class="text-[11px] uppercase tracking-wide" data-admin-table-head>
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold">Warga</th>
                        <th class="px-5 py-3 text-left font-semibold">Tagihan</th>
                        <th class="px-5 py-3 text-left font-semibold">Metode</th>
                        <th class="px-5 py-3 text-left font-semibold">Status</th>
                        <th class="px-5 py-3 text-right font-semibold">Nominal</th>
                        <th class="px-5 py-3 text-left font-semibold">Timeline</th>
                        <th class="px-5 py-3 text-left font-semibold">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100/80 bg-white/95 dark:divide-slate-800/60 dark:bg-slate-900/70" wire:loading.remove wire:target="status,gateway,search,page">
                    @forelse ($payments as $payment)
                        @php
                            $badgeTone = match ($payment->status) {
                                'paid' => 'success',
                                'pending' => 'warning',
                                'failed', 'cancelled', 'expired' => 'danger',
                                default => 'muted',
                            };
                        @endphp
                        <tr wire:key="payment-row-{{ $payment->id }}" class="admin-row" data-admin-row>
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-800 dark:text-slate-100">{{ $payment->user?->name ?? '—' }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-500">Invoice: {{ $payment->bill?->invoice_number ?? '-' }}</p>
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-500 dark:text-slate-400">
                                <p>{{ $payment->bill?->title ?? '—' }}</p>
                                <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-500">Jenis: {{ $payment->bill?->type ? Str::headline($payment->bill->type) : '-' }}</p>
                            </td>
                            <td class="px-5 py-4 text-xs font-semibold text-sky-600 dark:text-sky-300">
                                {{ Str::upper($payment->gateway) }}
                                @if ($payment->gateway === 'manual')
                                    @php
                                        $manualType = data_get($payment->manual_destination, 'type');
                                        $channel = $payment->manual_channel;
                                    @endphp
                                    <span class="mt-1 block text-[11px] font-normal text-slate-500 dark:text-slate-500">
                                        {{ $manualType === 'wallet' ? 'E-Wallet' : 'Bank' }}{{ $channel ? ' - ' . $channel : '' }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <span class="admin-chip text-[11px]" data-tone="{{ $badgeTone }}">{{ Str::headline($payment->status) }}</span>
                            </td>
                            <td class="px-5 py-4 text-right font-semibold text-emerald-600 dark:text-emerald-300">Rp {{ number_format($payment->amount) }}</td>
                        <td class="px-5 py-4 text-xs text-slate-500 dark:text-slate-400">
                            <p>Dibuat: {{ optional($payment->created_at)->translatedFormat('d M Y H:i') ?? '-' }}</p>
                            <p>Dibayar: {{ optional($payment->paid_at)->translatedFormat('d M Y H:i') ?? '-' }}</p>
                            @if ($payment->reference)
                                <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-500">Ref: {{ $payment->reference }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-xs text-slate-500 dark:text-slate-400">
                            @php
                                $manualPending = $payment->gateway === 'manual' && $payment->status === 'pending';
                                $needsProof = $manualPending && ! $payment->manual_proof_path;
                                $canDelete = in_array($payment->status, $deletableStatuses, true);
                            @endphp
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($manualPending && $payment->manual_proof_path)
                                    <button type="button" wire:click="reviewManualPayment({{ $payment->id }})" class="admin-chip cursor-pointer" data-tone="accent">
                                        Validasi
                                    </button>
                                @elseif ($needsProof)
                                    <span class="admin-chip" data-tone="warning">Menunggu bukti</span>
                                @endif

                                @if ($canDelete)
                                    <button type="button" wire:click="confirmDeletePayment({{ $payment->id }})" class="admin-chip cursor-pointer" data-tone="danger">
                                        Hapus
                                    </button>
                                @endif

                                @if (! $manualPending && ! $canDelete)
                                    <span class="text-slate-500">-</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-12 text-center text-sm text-slate-500 dark:text-slate-500">Belum ada transaksi sesuai filter.</td>
                    </tr>
                    @endforelse
                </tbody>
                <tbody class="divide-y divide-slate-100/80 bg-white/95 dark:divide-slate-800/60 dark:bg-slate-900/70" wire:loading.flex wire:target="status,gateway,search,page">
                    @foreach (range(1, 5) as $placeholder)
                        <tr class="animate-pulse">
                            <td class="px-5 py-4">
                                <div class="space-y-2">
                                    <div class="h-3 w-32 rounded bg-slate-100"></div>
                                    <div class="h-2 w-24 rounded bg-slate-100"></div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="space-y-2">
                                    <div class="h-2 w-48 rounded bg-slate-100"></div>
                                    <div class="h-2 w-36 rounded bg-slate-100"></div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="h-6 w-24 rounded-full bg-slate-100"></div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="h-6 w-24 rounded-full bg-slate-100"></div>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="h-3 w-16 rounded bg-slate-100"></div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="space-y-2">
                                    <div class="h-2 w-40 rounded bg-slate-100"></div>
                                    <div class="h-2 w-32 rounded bg-slate-100"></div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex gap-2">
                                    <div class="h-6 w-20 rounded-full bg-slate-100"></div>
                                    <div class="h-6 w-20 rounded-full bg-slate-100"></div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tbody class="divide-y divide-slate-100/80 bg-white/95 dark:divide-slate-800/60 dark:bg-slate-900/70" wire:loading.flex wire:target="status,gateway,search,page">
                    @foreach (range(1, 5) as $placeholder)
                        <tr class="animate-pulse">
                            <td class="px-5 py-4">
                                <div class="space-y-2">
                                    <div class="h-3 w-32 rounded bg-slate-100"></div>
                                    <div class="h-2 w-24 rounded bg-slate-100"></div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="space-y-2">
                                    <div class="h-2 w-48 rounded bg-slate-100"></div>
                                    <div class="h-2 w-36 rounded bg-slate-100"></div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="h-6 w-24 rounded-full bg-slate-100"></div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="h-6 w-24 rounded-full bg-slate-100"></div>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="h-3 w-16 rounded bg-slate-100"></div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="space-y-2">
                                    <div class="h-2 w-40 rounded bg-slate-100"></div>
                                    <div class="h-2 w-32 rounded bg-slate-100"></div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex gap-2">
                                    <div class="h-6 w-20 rounded-full bg-slate-100"></div>
                                    <div class="h-6 w-20 rounded-full bg-slate-100"></div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex flex-col gap-3 text-xs text-slate-500 dark:text-slate-500 sm:flex-row sm:items-center sm:justify-between">
            <div>Menampilkan {{ $payments->firstItem() ?? 0 }}-{{ $payments->lastItem() ?? 0 }} dari {{ $payments->total() }} transaksi</div>
            <div class="text-sm">
                {{ $payments->onEachSide(1)->links() }}
            </div>
        </div>
    </section>
    @if ($manualModalOpen && $manualPayment)
        @php
            $proofPath = $manualPayment->manual_proof_path;
            $proofUrl = $proofPath ? route('admin.pembayaran.manual-proof', $manualPayment) : null;
            $isImage = $proofPath ? \Illuminate\Support\Str::endsWith(strtolower($proofPath), ['.jpg', '.jpeg', '.png', '.webp']) : false;
            $destination = $manualPayment->manual_destination ?? [];
        @endphp
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-sm">
            <div class="flex min-h-full items-center justify-center px-4 py-8 sm:px-6">
                <div class="relative w-full max-w-4xl admin-surface rounded-3xl p-6 sm:max-h-[90vh] sm:overflow-y-auto" data-admin-surface>
                <button type="button" wire:click="closeManualModal" class="absolute right-4 top-4 inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-slate-500 shadow transition-colors duration-200 hover:text-rose-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-400 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:bg-slate-800/80 dark:text-slate-300 dark:focus-visible:ring-offset-slate-900" aria-label="Tutup dialog">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18 18 6m0 12L6 6" />
                    </svg>
                </button>

                <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100">Validasi Pembayaran Manual</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Periksa detail transfer dan catatan warga sebelum konfirmasi.</p>

                <div class="mt-5 grid gap-4 md:grid-cols-3">
                    <div class="admin-surface-muted rounded-2xl p-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-500">Warga</p>
                        <p class="mt-1 text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $manualPayment->user?->name ?? '-' }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Invoice: {{ $manualPayment->bill?->invoice_number ?? '-' }}</p>
                    </div>
                    <div class="admin-surface-muted rounded-2xl p-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-500">Nominal</p>
                        <p class="mt-1 text-lg font-semibold text-[#0284C7]">Rp {{ number_format($manualPayment->amount) }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Dibuat: {{ optional($manualPayment->created_at)->translatedFormat('d M Y H:i') ?? '-' }}</p>
                    </div>
                    <div class="admin-surface-muted rounded-2xl p-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-500">Tujuan Transfer</p>
                        <p class="mt-1 text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $manualPayment->manual_channel ?? ($destination['label'] ?? '-') }}</p>
                        @if (!empty($destination['account_number']))
                            <p class="text-xs text-slate-500 dark:text-slate-400">No: {{ $destination['account_number'] }}</p>
                        @endif
                        @if (!empty($destination['account_name']))
                            <p class="text-xs text-slate-500 dark:text-slate-400">a.n {{ $destination['account_name'] }}</p>
                        @endif
                    </div>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div class="admin-surface-muted rounded-2xl p-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-500">Bukti Transfer</p>
                        @if ($proofUrl)
                            @if ($isImage)
                                <img src="{{ $proofUrl }}" alt="Bukti transfer" class="mt-3 w-full rounded-2xl border border-transparent object-contain shadow-sm" loading="lazy" decoding="async">
                            @else
                                <a href="{{ $proofUrl }}" target="_blank" class="mt-3 inline-flex items-center gap-2 rounded-full border border-[#0284C7]/40 bg-[#0284C7]/10 px-4 py-1.5 text-xs font-semibold text-[#0284C7] transition hover:bg-[#0284C7] hover:text-white">
                                    Lihat Berkas Bukti
                                </a>
                            @endif
                        @else
                            <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">Belum ada bukti yang diunggah.</p>
                        @endif
                    </div>
                    <div class="admin-surface-muted rounded-2xl p-4">
                        <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-500">Catatan Admin</label>
                        <textarea wire:model.defer="manualNotes" rows="6" class="admin-field mt-2 w-full rounded-2xl py-2.5 px-3 text-sm" placeholder="Opsional: catat informasi validasi atau alasan penolakan."></textarea>
                        @if (!empty($manualPayment->raw_payload['manual']['notes']))
                            <p class="mt-3 text-[11px] text-slate-500">Catatan warga: {{ $manualPayment->raw_payload['manual']['notes'] }}</p>
                        @endif
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap items-center justify-end gap-3">
                    <button type="button" wire:click="rejectManualPayment" class="inline-flex items-center gap-2 rounded-full border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-600 transition-colors duration-200 hover:border-rose-400 hover:bg-rose-100">
                        Tolak Pembayaran
                    </button>
                    <button type="button" wire:click="approveManualPayment" class="inline-flex items-center gap-2 rounded-full bg-emerald-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-emerald-500/20 transition-colors duration-200 hover:bg-emerald-600">
                        Tandai Lunas
                    </button>
                </div>
                </div>
            </div>
        </div>
    @endif
    @if ($deleteModalOpen && $paymentToDelete)
        @php
            $deleteBill = $paymentToDelete->bill;
        @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 px-4 py-8 backdrop-blur-sm">
            <div class="relative w-full max-w-lg rounded-3xl border border-slate-100/60 bg-white/95 p-6 shadow-xl shadow-slate-900/10 dark:border-slate-800/70 dark:bg-slate-900/80">
                <button type="button" wire:click="closeDeleteModal" class="absolute right-4 top-4 inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-slate-500 shadow transition-colors duration-200 hover:text-rose-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-400 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:bg-slate-800/80 dark:text-slate-300 dark:focus-visible:ring-offset-slate-900" aria-label="Tutup dialog hapus">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18 18 6m0 12L6 6" />
                    </svg>
                </button>

                <h2 class="text-lg font-semibold text-slate-800 dark:text-slate-100">Hapus Transaksi</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Transaksi gagal/kedaluwarsa akan dihapus permanen dan tidak lagi muncul di daftar pembayaran.</p>

                <div class="mt-4 space-y-3 text-sm text-slate-600 dark:text-slate-300">
                    <div class="admin-surface-muted rounded-2xl p-4">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-500">Warga</p>
                        <p class="mt-1 text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $paymentToDelete->user?->name ?? '-' }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Invoice: {{ $deleteBill?->invoice_number ?? '-' }}</p>
                    </div>
                    <div class="admin-surface-muted rounded-2xl p-4">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-500">Detail Transaksi</p>
                        <p class="mt-1 text-sm font-semibold text-[#0284C7]">{{ Str::headline($paymentToDelete->status) }} &middot; Rp {{ number_format($paymentToDelete->amount) }}</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Dibuat: {{ optional($paymentToDelete->created_at)->translatedFormat('d M Y H:i') ?? '-' }}</p>
                        @if ($paymentToDelete->reference)
                            <p class="text-xs text-slate-500 dark:text-slate-400">Ref: {{ $paymentToDelete->reference }}</p>
                        @endif
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap items-center justify-end gap-3">
                    <button type="button" wire:click="closeDeleteModal" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition-colors duration-200 hover:border-slate-300 hover:text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-slate-600 dark:hover:text-slate-200">
                        Batal
                    </button>
                    <button type="button" wire:click="deletePayment" class="inline-flex items-center gap-2 rounded-full bg-rose-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-rose-500/20 transition-colors duration-200 hover:bg-rose-600">
                        Hapus Sekarang
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
