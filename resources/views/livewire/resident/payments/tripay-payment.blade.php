@php
    $activeMeta = $selected_channel ? ($channel_meta[$selected_channel] ?? null) : null;
    $activeLabel = $activeMeta['name'] ?? ($selected_channel ? \Illuminate\Support\Str::upper($selected_channel) : null);
@endphp

<div class="space-y-10 font-['Inter'] text-slate-700 dark:text-slate-200" data-resident-stack>
    <section class="p-6" data-resident-card data-variant="muted" data-resident-fade data-motion-animated>
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Pembayaran Tripay</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">Pilih channel Tripay dan lanjutkan ke halaman checkout.</p>
            </div>
            <a href="{{ route('resident.bills') }}" class="inline-flex items-center gap-2 rounded-full border border-transparent bg-[#0284C7]/15 px-4 py-2 text-xs font-semibold text-[#0284C7] transition-colors duration-200 hover:bg-[#0284C7] hover:text-white dark:bg-sky-500/15 dark:text-sky-200 dark:hover:bg-sky-500 dark:hover:text-slate-900">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17.25 4.5 12l5.25-5.25M4.5 12h15" />
                </svg>
                Kembali ke daftar tagihan
            </a>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-3">
            <div data-resident-card data-variant="muted" class="p-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">Tagihan</p>
                <p class="mt-1 text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $bill->title }}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Invoice: {{ $bill->invoice_number ?? 'INV-' . $bill->id }}</p>
            </div>
            <div data-resident-card data-variant="muted" class="p-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">Total via Tripay</p>
                <p class="mt-1 text-xl font-semibold text-[#0284C7]">Rp {{ number_format($tripay_total ?? $bill->amount) }}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Tagihan: Rp {{ number_format($bill->amount) }}
                    @if ($selected_channel)
                        @if ($tripay_fee > 0)
                            - Biaya admin {{ $activeLabel ?? 'Tripay' }}: Rp {{ number_format($tripay_fee) }}
                        @else
                            - Channel {{ $activeLabel ?? 'Tripay' }} tanpa biaya admin tambahan
                        @endif
                    @else
                        - Pilih channel Tripay untuk melihat biaya admin
                    @endif
                </p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Jatuh tempo: {{ optional($bill->due_date)->translatedFormat('d M Y') ?? 'Tidak ditentukan' }}</p>
            </div>
            <div data-resident-card data-variant="muted" class="p-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">Status</p>
                <p class="mt-1 text-sm font-semibold text-slate-700 dark:text-slate-200">{{ \Illuminate\Support\Str::headline($bill->status) }}</p>
                @if ($checkout)
                    @php $expiresDisplay = ($checkout['expires_at'] ?? null) ? optional(\Carbon\Carbon::parse($checkout['expires_at']))->translatedFormat('d M Y H:i') : null; @endphp
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Checkout aktif sampai {{ $expiresDisplay ?? '-' }}</p>
                @else
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Belum ada transaksi Tripay. Pilih channel di bawah.</p>
                @endif
            </div>
        </div>

        @if (session('status'))
            <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50/90 p-4 text-xs font-medium text-emerald-700 shadow-sm shadow-emerald-100">
                {{ session('status') }}
            </div>
        @endif
    </section>

    @php
        $checkoutSummary = null;
        if ($checkout) {
            $checkoutSummary = [
                'method' => $checkout['method'] ?? ($activeLabel ?? 'Tripay'),
                'amount' => $checkout['amount'] ?? ($tripay_total ?? $bill->amount),
                'fee' => $checkout['fee_amount'] ?? ($tripay_fee ?? 0),
                'expires_at' => $checkout['expires_at'] ?? null,
                'expires_display' => ($checkout['expires_at'] ?? null) ? \Carbon\Carbon::parse($checkout['expires_at'])->translatedFormat('d M Y H:i') : null,
                'url' => $checkout['checkout_url'] ?? '#',
                'reference' => $checkout['tripay_reference'] ?? null,
                'category' => $activeMeta['group_label'] ?? ($activeMeta['category'] ?? null),
                'notes' => $activeMeta['notes'] ?? null,
            ];
        }
    @endphp

    <section class="p-6" data-resident-card data-variant="muted" data-resident-fade data-motion-animated>
        <h2 class="text-sm font-semibold uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">
            {{ $checkout ? 'Informasi Pembayaran Tripay' : 'Pilih Channel Tripay' }}
        </h2>

        @if ($checkout && $checkoutSummary)
            <div class="mt-4 space-y-6">
                <div class="rounded-2xl border border-slate-200/70 bg-white/80 p-5 shadow-inner shadow-slate-100 dark:border-slate-700 dark:bg-slate-900/60">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-[0.65rem] font-semibold uppercase tracking-[0.25em] text-[#0284C7]">Metode Pembayaran</p>
                            <p class="text-lg font-semibold text-slate-800 dark:text-slate-100">{{ $checkoutSummary['method'] }}</p>
                            @if ($checkoutSummary['category'])
                                <p class="text-xs uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">{{ $checkoutSummary['category'] }}</p>
                            @endif
                            @if ($checkoutSummary['notes'])
                                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ $checkoutSummary['notes'] }}</p>
                            @endif
                            @if ($checkoutSummary['reference'])
                                <p class="mt-3 text-xs font-medium text-slate-500">Referensi Tripay: <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $checkoutSummary['reference'] }}</span></p>
                            @endif
                        </div>
                        <div class="rounded-2xl bg-[#0284C7]/10 px-4 py-3 text-right text-[#0284C7] shadow-sm shadow-[#0284C7]/10">
                            <p class="text-[0.65rem] font-semibold uppercase tracking-[0.25em]">Total Dibayar</p>
                            <p class="mt-1 text-xl font-semibold">Rp {{ number_format($checkoutSummary['amount']) }}</p>
                            <p class="mt-1 text-[11px] uppercase tracking-[0.2em] text-[#0284C7]/80">Biaya Admin Rp {{ number_format($checkoutSummary['fee']) }}</p>
                        </div>
                    </div>
                    <dl class="mt-6 grid gap-4 text-sm text-slate-600 dark:text-slate-300 sm:grid-cols-3">
                        <div>
                            <dt class="text-[10px] font-semibold uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">Tagihan</dt>
                            <dd class="mt-1 font-semibold text-slate-700 dark:text-slate-200">Rp {{ number_format($bill->amount) }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-semibold uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">Biaya Admin</dt>
                            <dd class="mt-1 font-semibold text-slate-700 dark:text-slate-200">Rp {{ number_format($checkoutSummary['fee']) }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-semibold uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">Kedaluwarsa</dt>
                            <dd class="mt-1 font-semibold text-slate-700 dark:text-slate-200">{{ $checkoutSummary['expires_display'] ?? '-' }}</dd>
                        </div>
                    </dl>
                    <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
                        Simpan informasi ini. Jika pop up instruksi tertutup, gunakan tombol di bawah untuk melanjutkan pembayaran.
                    </p>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <button type="button" wire:click="openCheckoutModal" class="inline-flex items-center gap-2 rounded-full border border-[#0284C7]/50 bg-white/90 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7] transition-colors duration-200 hover:bg-[#0284C7]/10 dark:border-sky-500/40 dark:bg-slate-900/70 dark:text-sky-200 dark:hover:bg-sky-500/20">
                        Tampilkan Instruksi Pembayaran
                    </button>
                    <div class="flex flex-wrap items-center gap-3">
                        <a href="{{ $checkoutSummary['url'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-full bg-[#22C55E] px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-[#22C55E]/30 transition-colors duration-200 hover:bg-[#16A34A]">
                            Buka Checkout Tripay
                        </a>
                        <button type="button" wire:click="cancelCheckout" wire:loading.attr="disabled" class="inline-flex items-center gap-2 rounded-full border border-rose-400 bg-white/70 px-4 py-2 text-sm font-semibold text-rose-600 shadow-sm transition-colors duration-200 hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-300 focus:ring-offset-2 focus:ring-offset-white dark:border-rose-500/40 dark:bg-slate-900/70 dark:text-rose-300 dark:hover:bg-rose-500/20 dark:focus:ring-rose-500/60 dark:focus:ring-offset-slate-900">
                            Batalkan Transaksi
                        </button>
                    </div>
                </div>
            </div>
        @else
            @php
                $availableCategories = [];
                foreach ($channel_groups as $key => $groupChannels) {
                    if (! empty($groupChannels)) {
                        $availableCategories[$key] = $category_labels[$key] ?? ucwords(str_replace('_', ' ', $key));
                    }
                }
                $activeCategory = $selected_category;
                if (! $activeCategory || empty($channel_groups[$activeCategory] ?? [])) {
                    $activeCategory = array_key_first($availableCategories);
                }
                $channelsInCategory = $activeCategory ? ($channel_groups[$activeCategory] ?? []) : [];
            @endphp

            @if (! empty($availableCategories))
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($availableCategories as $key => $label)
                        @php $isActiveCategory = $activeCategory === $key; @endphp
                        <button
                            type="button"
                            wire:click="$set('selected_category', '{{ $key }}')"
                            wire:key="tripay-category-{{ $key }}"
                            @class([
                                'inline-flex items-center rounded-full border px-4 py-1.5 text-[11px] font-semibold uppercase tracking-[0.22em] transition-colors duration-200',
                                'border-[#0284C7] bg-[#0284C7]/15 text-[#0284C7] shadow-inner shadow-[#0284C7]/10 dark:border-sky-500 dark:bg-sky-500/20 dark:text-sky-200' => $isActiveCategory,
                                'border-slate-200 bg-white/80 text-slate-500 hover:bg-[#0284C7]/8 hover:text-[#0284C7] dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300 dark:hover:bg-sky-500/15 dark:hover:text-sky-200' => ! $isActiveCategory,
                            ])
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            @endif

            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                @forelse ($channelsInCategory as $channel)
                    @php
                        $meta = $channel_meta[$channel] ?? [];
                        $isActive = $selected_channel === $channel;
                        $label = $meta['name'] ?? $channel;
                        $categoryLabel = $meta['group_label'] ?? ($category_labels[$activeCategory] ?? ($meta['category'] ?? 'Channel'));
                        $notes = $meta['notes'] ?? null;
                    @endphp
                    <button
                        type="button"
                        wire:click="$set('selected_channel', '{{ $channel }}')"
                        wire:key="tripay-channel-{{ $channel }}"
                        @class([
                            'flex w-full flex-col gap-3 rounded-2xl border px-4 py-4 text-left transition-colors duration-200',
                            'border-[#0284C7] bg-[#0284C7]/8 shadow-lg shadow-[#0284C7]/20 ring-1 ring-[#0284C7]/20' => $isActive,
                            'border-slate-200 bg-white/80 hover:bg-[#0284C7]/5 dark:border-slate-700 dark:bg-slate-900/70' => ! $isActive,
                        ])
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-[0.65rem] font-semibold uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">{{ $categoryLabel }}</p>
                                <p class="text-sm font-semibold text-slate-700 dark:text-slate-100">{{ $label }}</p>
                            </div>
                            @if ($isActive)
                                <span class="rounded-full bg-[#0284C7] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] text-white">Dipilih</span>
                            @endif
                        </div>
                        @if ($notes)
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $notes }}</p>
                        @endif
                    </button>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-300 bg-white/60 p-6 text-center text-xs text-slate-400 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-500">
                        Channel Tripay belum diatur oleh pengurus.
                    </p>
                @endforelse
            </div>

            @error('selected_channel') <p class="mt-3 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror

            <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
                <div class="text-xs text-slate-500 dark:text-slate-400">
                    Setelah checkout dibuat, Anda akan diarahkan ke halaman pembayaran Tripay.
                </div>
                <button type="button" wire:click="startCheckout" wire:loading.attr="disabled" class="inline-flex items-center gap-2 rounded-full bg-[#0284C7] px-6 py-2 text-sm font-semibold text-white shadow-lg shadow-[#0284C7]/40 transition-colors duration-200 hover:bg-[#0ea5e9] focus:outline-none focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-2 focus:ring-offset-white">
                    <span wire:loading.remove>Buat Pembayaran Tripay</span>
                    <span wire:loading>Memproses...</span>
                </button>
            </div>
        @endif
    </section>

    @if ($show_checkout_modal && $checkoutSummary)
        <div class="fixed inset-0 z-[70] flex items-center justify-center">
            <button type="button" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" wire:click="closeCheckoutModal" aria-label="Tutup pop up"></button>
            <div class="relative z-[80] w-full max-w-2xl scale-100 rounded-3xl bg-white p-6 shadow-xl shadow-slate-900/30 transition-colors duration-200 dark:bg-slate-900">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[0.65rem] font-semibold uppercase tracking-[0.3em] text-[#0284C7]">Instruksi Pembayaran Tripay</p>
                        <h3 class="mt-2 text-xl font-semibold text-slate-900 dark:text-slate-100">{{ $checkoutSummary['method'] }}</h3>
                        @if ($checkoutSummary['category'])
                            <p class="text-xs uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">{{ $checkoutSummary['category'] }}</p>
                        @endif
                    </div>
                    <button type="button" wire:click="closeCheckoutModal" class="rounded-full border border-slate-200 bg-white p-1 text-slate-400 transition-colors duration-200 hover:bg-slate-100 hover:text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-500 dark:hover:bg-slate-800/70 dark:hover:text-slate-300" aria-label="Tutup">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                @if ($checkoutSummary['reference'])
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Referensi Tripay: <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $checkoutSummary['reference'] }}</span></p>
                @endif

                <div class="mt-4 grid gap-3 rounded-2xl border border-slate-200/70 bg-slate-50/60 p-4 text-sm text-slate-600 shadow-inner dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-300 sm:grid-cols-3">
                    <div>
                        <p class="text-[0.65rem] font-semibold uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Total Dibayar</p>
                        <p class="mt-1 text-lg font-semibold text-slate-900 dark:text-slate-100">Rp {{ number_format($checkoutSummary['amount']) }}</p>
                    </div>
                    <div>
                        <p class="text-[0.65rem] font-semibold uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Biaya Admin</p>
                        <p class="mt-1 font-semibold text-slate-700 dark:text-slate-200">Rp {{ number_format($checkoutSummary['fee']) }}</p>
                    </div>
                    <div>
                        <p class="text-[0.65rem] font-semibold uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Kedaluwarsa</p>
                        <p class="mt-1 font-semibold text-slate-700 dark:text-slate-200">{{ $checkoutSummary['expires_display'] ?? '-' }}</p>
                    </div>
                </div>

                <div class="mt-4 space-y-2 text-sm text-slate-600 dark:text-slate-300">
                    <p>Ikuti langkah berikut untuk menyelesaikan pembayaran:</p>
                    <ol class="list-decimal space-y-1 pl-5">
                        <li>Buka halaman checkout Tripay pada tab baru.</li>
                        <li>Ikuti instruksi pembayaran sesuai metode yang dipilih.</li>
                        <li>Setelah pembayaran selesai, status tagihan akan diperbarui otomatis.</li>
                    </ol>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Jika pembayaran gagal atau kedaluwarsa, Anda dapat membatalkan transaksi ini kemudian membuat checkout baru.</p>
                </div>

                <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
                    <button type="button" wire:click="cancelCheckout" wire:loading.attr="disabled" class="inline-flex items-center gap-2 rounded-full border border-rose-400 bg-white/70 px-4 py-2 text-sm font-semibold text-rose-600 shadow-sm transition-colors duration-200 hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-300 focus:ring-offset-2 focus:ring-offset-white dark:border-rose-500/40 dark:bg-slate-900/70 dark:text-rose-300 dark:hover:bg-rose-500/20 dark:focus:ring-rose-500/60 dark:focus:ring-offset-slate-900">
                        Batalkan Transaksi
                    </button>
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ $checkoutSummary['url'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-full bg-[#22C55E] px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-[#22C55E]/30 transition-colors duration-200 hover:bg-[#16A34A]">
                            Buka Checkout Tripay
                        </a>
                        <button type="button" wire:click="closeCheckoutModal" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition-colors duration-200 hover:bg-slate-50 hover:text-slate-800 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800/60 dark:hover:text-slate-100">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
