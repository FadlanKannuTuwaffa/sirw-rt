<div class="space-y-6 font-['Inter'] text-slate-800 dark:text-slate-100" data-settings-page data-admin-stack>
    <x-admin.settings-nav current="payment" />

    <section class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition-colors duration-200 dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="space-y-3">
                <p class="text-sm font-medium text-sky-600 dark:text-sky-300">Payment gateway & rekening warga</p>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Atur metode pembayaran warga</h1>
                <p class="max-w-3xl text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                    Integrasikan Tripay untuk otomatisasi atau gunakan transfer manual dengan bukti unggah. Pilih kombinasi yang selaras dengan kesiapan warga dan kas RT.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200/80 px-3 py-1 dark:border-slate-700">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    Status realtime
                </span>
                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200/80 px-3 py-1 dark:border-slate-700">
                    <span class="h-2 w-2 rounded-full bg-sky-500"></span>
                    Pelacakan bukti
                </span>
            </div>
        </div>
    </section>

    @if (session('status'))
        <div class="settings-surface border border-emerald-200/70 bg-emerald-50/90 p-4 text-sm font-medium text-emerald-700 shadow-md shadow-emerald-200/40 dark:border-emerald-400/40 dark:bg-emerald-500/15 dark:text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="settings-surface border border-rose-200/80 bg-rose-50/95 p-4 text-sm text-rose-600 shadow-md shadow-rose-200/40 dark:border-rose-500/40 dark:bg-rose-500/15 dark:text-rose-100">
            <p class="font-semibold">Terjadi kesalahan:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-xs">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="settings-surface w-full p-6" data-motion-animated>
        <header class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-[#0284C7] dark:text-sky-300">Pengaturan Payment Gateway</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Aktifkan Tripay untuk otomatisasi atau sediakan opsi transfer manual dengan bukti pembayaran.</p>
            </div>
            <div class="inline-flex items-center gap-2 rounded-full border border-white/40 bg-white/60 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:border-slate-700/60 dark:bg-slate-900/60 dark:text-slate-300">
                Mode: {{ strtoupper($provider) }}
            </div>
        </header>

        <form wire:submit.prevent="save" class="mt-6 space-y-8">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Provider Aktif</label>
                    <select
                        wire:model.live="provider"
                        class="mt-2 settings-field"
                    >
                        <option value="manual">Manual Transfer</option>
                        <option value="tripay">Tripay</option>
                    </select>
                    @error('provider') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Callback URL</label>
                    <input type="url" wire:model.defer="callback_url" class="mt-2 settings-field" placeholder="https://rtku.id/payment/webhook">
                    <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">Biarkan kosong untuk menggunakan URL bawaan sistem.</p>
                    @error('callback_url') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
            </div>

            @php
                $manualCount = count($manual_bank_accounts) + count($manual_wallet_accounts);
                $manualDestinationLabel = $manualCount > 0 ? "Tersedia {$manualCount} tujuan" : 'Belum ada tujuan transfer';
                $activeTripayChannels = collect($tripay_channels_selected ?? [])
                    ->filter()
                    ->map(fn ($code) => collect($tripayChannels)->flatMap(fn ($channels) => $channels)->firstWhere('code', $code)['name'] ?? $code)
                    ->filter()
                    ->values()
                    ->all();
                $tripayActiveChannelLabel = count($activeTripayChannels) > 0 ? 'Channel aktif: ' . implode(', ', $activeTripayChannels) : 'Belum ada channel aktif';
                $tripayModeLabel = strtoupper($tripay_mode ?? 'SANDBOX');
                $defaultTripayChannelOptions = collect($tripayChannels)
                    ->flatMap(fn ($channels) => $channels)
                    ->filter(function ($channel) use ($tripay_channels_selected) {
                        $selected = collect($tripay_channels_selected ?? [])->filter()->all();
                        return empty($selected) || in_array($channel['code'], $selected, true);
                    })
                    ->values()
                    ->all();
            @endphp

            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-3xl border border-slate-200/70 bg-white/70 p-4 shadow-inner shadow-slate-200/40 transition hover:border-slate-200 dark:border-slate-700/60 dark:bg-slate-900/60 dark:shadow-slate-900/40">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-slate-500 dark:text-slate-400">Transfer Manual</p>
                    <p class="mt-2 text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $manualDestinationLabel }}</p>
                    <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">Warga memilih rekening/e-wallet, kemudian mengunggah bukti pembayaran.</p>
                </div>
                <div class="rounded-3xl border border-sky-200/70 bg-gradient-to-br from-sky-50/70 via-sky-100/60 to-emerald-50/60 p-4 shadow-inner shadow-sky-200/40 transition hover:border-sky-300 dark:border-sky-500/40 dark:from-slate-900/60 dark:via-slate-900/50 dark:to-slate-900/50 dark:shadow-slate-900/40">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-sky-600 dark:text-sky-400">Tripay</p>
                    <p class="mt-2 text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $tripayActiveChannelLabel }}</p>
                    <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">Mode: <span class="font-semibold text-slate-600 dark:text-slate-300">{{ $tripayModeLabel }}</span>. Integrasi penuh dengan hitung biaya otomatis.</p>
                </div>
            </div>

            @if ($provider === 'tripay')
                <div class="rounded-3xl border border-slate-200/70 bg-white/70 p-6 shadow-inner shadow-slate-200/40 transition hover:border-sky-300 dark:border-slate-700/60 dark:bg-slate-900/60 dark:shadow-slate-900/40">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900 dark:text-slate-200">Kredensial Tripay</h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Gunakan data dari dashboard Tripay untuk mengaktifkan pembayaran otomatis.</p>
                        </div>
                        <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200/70 bg-emerald-50/70 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.25em] text-emerald-600 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-300">
                            Secure
                        </span>
                    </div>

                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-[11px] font-semibold uppercase tracking-[0.28em] text-slate-500 dark:text-slate-400">Merchant Code</label>
                            <input type="text" wire:model.defer="tripay_merchant_code" class="mt-2 settings-field" placeholder="Tersedia di menu Pengaturan &gt; Informasi Akun">
                            @error('tripay_merchant_code') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-[11px] font-semibold uppercase tracking-[0.28em] text-slate-500 dark:text-slate-400">Private Key</label>
                            <input type="password" wire:model.defer="tripay_private_key" class="mt-2 settings-field" placeholder="••••••">
                            @error('tripay_private_key') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-[11px] font-semibold uppercase tracking-[0.28em] text-slate-500 dark:text-slate-400">API Key</label>
                            <input type="password" wire:model.defer="tripay_api_key" class="mt-2 settings-field" placeholder="••••••">
                            @error('tripay_api_key') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-[11px] font-semibold uppercase tracking-[0.28em] text-slate-500 dark:text-slate-400">Mode</label>
                            <select wire:model.live="tripay_mode" class="mt-2 settings-field">
                                <option value="sandbox">Sandbox (Uji Coba)</option>
                                <option value="production">Production (Live)</option>
                            </select>
                            @error('tripay_mode') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="mt-8 rounded-2xl border border-slate-200/70 bg-white/60 p-4 shadow-inner shadow-slate-200/40 dark:border-slate-700/60 dark:bg-slate-900/60 dark:shadow-slate-900/40">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Channel Pembayaran</h4>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500">Pilih channel yang ingin ditampilkan ke warga. Centang beberapa sekaligus untuk fleksibilitas.</p>
                            </div>
                            <button type="button" wire:click="selectAllTripayChannels" class="inline-flex items-center gap-2 rounded-full border border-slate-200/70 bg-white/70 px-3 py-1 text-[11px] font-semibold text-slate-500 transition hover:border-sky-300 hover:text-sky-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-300 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-slate-700/60 dark:bg-slate-900/60 dark:text-slate-300 dark:hover:border-sky-400 dark:hover:text-sky-300 dark:focus-visible:ring-sky-400 dark:focus-visible:ring-offset-slate-900">
                                Pilih Semua
                            </button>
                        </div>
                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                            @foreach ($tripayChannels as $groupLabel => $channels)
                                <div class="rounded-2xl border border-slate-200/60 bg-white/60 p-3 shadow-inner shadow-slate-200/30 dark:border-slate-700/60 dark:bg-slate-900/60 dark:shadow-slate-900/30">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">{{ $groupLabel }}</p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach ($channels as $channel)
                                            @php
                                                $isActive = in_array($channel['code'], $tripay_channels_selected ?? [], true);
                                            @endphp
                                            <label
                                                wire:key="tripay-channel-{{ $channel['code'] }}"
                                                class="group inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold transition-all duration-200 {{ $isActive ? 'border-sky-500 bg-sky-500/10 text-sky-600 shadow-sm shadow-sky-200 dark:border-sky-400/70 dark:bg-sky-500/10 dark:text-sky-300' : 'border-slate-200 bg-white text-slate-500 hover:border-sky-200 hover:text-sky-600 dark:border-slate-700/60 dark:bg-slate-900/60 dark:text-slate-400 dark:hover:border-sky-400 dark:hover:text-sky-300' }}"
                                            >
                                                <input type="checkbox" class="sr-only" value="{{ $channel['code'] }}" wire:model.live="tripay_channels_selected">
                                                <span>{{ $channel['name'] }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @error('tripay_channels_selected') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                    </div>

                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Channel Default</label>
                            <select wire:model.defer="tripay_default_channel" class="mt-2 settings-field">
                                <option value="">Otomatis (Tripay menentukan)</option>
                                @foreach ($defaultTripayChannelOptions as $channel)
                                    <option value="{{ $channel['code'] }}">
                                        {{ $channel['code'] }} — {{ $channel['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tripay_default_channel') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <label class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Fee (%)</label>
                                <input type="number" min="0" step="0.01" wire:model.live.debounce.300ms="tripay_fee_percent" class="mt-2 settings-field" placeholder="2.5">
                                @error('tripay_fee_percent') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Fee Flat (Rp)</label>
                                <input type="number" min="0" step="1" wire:model.live.debounce.300ms="tripay_fee_flat" class="mt-2 settings-field" placeholder="1000">
                                @error('tripay_fee_flat') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Minimal Fee</label>
                                <input type="number" min="0" step="1" wire:model.live.debounce.300ms="tripay_min_fee" class="mt-2 settings-field" placeholder="0">
                                @error('tripay_min_fee') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($provider === 'manual')
                <div class="rounded-3xl border border-slate-200/70 bg-white/70 p-6 shadow-inner shadow-slate-200/40 transition hover:border-slate-200 dark:border-slate-700/60 dark:bg-slate-900/60 dark:shadow-slate-900/40">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900 dark:text-slate-200">Transfer Manual & E-Wallet</h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Tambah rekening bank atau e-wallet sebagai tujuan pembayaran. Sertakan panduan agar validasi lebih cepat.</p>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" wire:click="addBankAccount" class="inline-flex items-center gap-2 rounded-full border border-sky-200/70 bg-sky-50/70 px-3 py-1.5 text-[11px] font-semibold text-sky-600 transition hover:border-sky-300 hover:bg-sky-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-300 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-sky-500/40 dark:bg-sky-500/10 dark:text-sky-300 dark:hover:border-sky-400 dark:hover:bg-sky-500/20 dark:focus-visible:ring-sky-400 dark:focus-visible:ring-offset-slate-900">
                                + Bank
                            </button>
                            <button type="button" wire:click="addWalletAccount" class="inline-flex items-center gap-2 rounded-full border border-emerald-200/70 bg-emerald-50/70 px-3 py-1.5 text-[11px] font-semibold text-emerald-600 transition hover:border-emerald-300 hover:bg-emerald-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-200 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-300 dark:hover:border-emerald-400 dark:hover:bg-emerald-500/20 dark:focus-visible:ring-emerald-300 dark:focus-visible:ring-offset-slate-900">
                                + E-Wallet
                            </button>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-6 lg:grid-cols-2">
                        <div class="space-y-4">
                            <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Rekening Bank</h4>
                            @forelse ($manual_bank_accounts as $index => $account)
                                <div wire:key="manual-bank-{{ $account['id'] ?? $index }}" class="rounded-2xl border border-slate-200/70 bg-white/70 p-4 shadow-inner shadow-slate-200/40 dark:border-slate-700/60 dark:bg-slate-900/60 dark:shadow-slate-900/30">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">Bank {{ strtoupper($account['bank'] ?? '???') }}</span>
                                        <button type="button" wire:click="removeBankAccount({{ $index }})" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-rose-200/80 bg-white text-rose-500 transition hover:border-rose-300 hover:bg-rose-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-300 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-rose-500/40 dark:bg-slate-900/60 dark:text-rose-300 dark:hover:border-rose-400 dark:hover:bg-rose-500/10 dark:focus-visible:ring-rose-400 dark:focus-visible:ring-offset-slate-900">
                                            ×
                                        </button>
                                    </div>
                                    <div class="mt-3 grid gap-3">
                                        <div>
                                            <label class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Bank</label>
                                            <input type="text" wire:model.live.debounce.300ms="manual_bank_accounts.{{ $index }}.bank" placeholder="BCA / BNI / Mandiri" class="mt-2 settings-field settings-field--plain">
                                        </div>
                                        <div>
                                            <label class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Nomor Rekening</label>
                                            <input type="text" wire:model.live.debounce.300ms="manual_bank_accounts.{{ $index }}.account_number" placeholder="1234567890" class="mt-2 settings-field settings-field--plain">
                                        </div>
                                        <div>
                                            <label class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Atas Nama</label>
                                            <input type="text" wire:model.live.debounce.300ms="manual_bank_accounts.{{ $index }}.account_name" placeholder="Nama pemilik rekening" class="mt-2 settings-field settings-field--plain">
                                        </div>
                                        <div>
                                            <label class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Catatan</label>
                                            <textarea rows="2" wire:model.live.debounce.500ms="manual_bank_accounts.{{ $index }}.notes" placeholder="Contoh: Sertakan kode unik 3 digit" class="mt-2 settings-field settings-field--plain"></textarea>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-2xl border border-dashed border-slate-300/70 bg-white/60 p-6 text-center text-xs text-slate-400 dark:border-slate-700/60 dark:bg-slate-900/60 dark:text-slate-500">
                                    Belum ada rekening bank. Tambahkan minimal satu tujuan untuk menerima pembayaran manual.
                                </div>
                            @endforelse
                        </div>

                        <div class="space-y-4">
                            <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-200">E-Wallet</h4>
                            @forelse ($manual_wallet_accounts as $index => $wallet)
                                <div wire:key="manual-wallet-{{ $wallet['id'] ?? $index }}" class="rounded-2xl border border-slate-200/70 bg-white/70 p-4 shadow-inner shadow-slate-200/40 dark:border-slate-700/60 dark:bg-slate-900/60 dark:shadow-slate-900/30">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ strtoupper($wallet['provider'] ?? 'E-Wallet') }}</span>
                                        <button type="button" wire:click="removeWalletAccount({{ $index }})" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-rose-200/80 bg-white text-rose-500 transition hover:border-rose-300 hover:bg-rose-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-300 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-rose-500/40 dark:bg-slate-900/60 dark:text-rose-300 dark:hover:border-rose-400 dark:hover:bg-rose-500/10 dark:focus-visible:ring-rose-400 dark:focus-visible:ring-offset-slate-900" title="Hapus e-wallet">
                                            ×
                                        </button>
                                    </div>
                                    <div class="mt-3 grid gap-3">
                                        <div>
                                            <label class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Provider</label>
                                            <input type="text" wire:model.live.debounce.300ms="manual_wallet_accounts.{{ $index }}.provider" placeholder="DANA / OVO / GoPay" class="mt-2 settings-field settings-field--plain">
                                        </div>
                                        <div>
                                            <label class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Nomor</label>
                                            <input type="text" wire:model.live.debounce.300ms="manual_wallet_accounts.{{ $index }}.account_number" placeholder="08xxxxxxxxxx" class="mt-2 settings-field settings-field--plain">
                                        </div>
                                        <div>
                                            <label class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Atas Nama</label>
                                            <input type="text" wire:model.live.debounce.300ms="manual_wallet_accounts.{{ $index }}.account_name" placeholder="Nama pemilik" class="mt-2 settings-field settings-field--plain">
                                        </div>
                                        <div>
                                            <label class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Catatan</label>
                                            <textarea rows="2" wire:model.live.debounce.500ms="manual_wallet_accounts.{{ $index }}.notes" placeholder="Misal: Minimal transfer Rp10.000 atau sertakan kode unik" class="mt-2 settings-field settings-field--plain"></textarea>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-2xl border border-dashed border-slate-300/70 bg-white/60 p-6 text-center text-xs text-slate-400 dark:border-slate-700/60 dark:bg-slate-900/60 dark:text-slate-500">
                                    Belum ada e-wallet. Tambahkan DANA, OVO, GoPay, ShopeePay, atau lainnya.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="mt-6">
                        <label class="text-[11px] font-semibold uppercase tracking-[0.28em] text-slate-500 dark:text-slate-400">Instruksi Pembayaran</label>
                        <textarea wire:model.live.debounce.500ms="manual_instructions" rows="4" placeholder="Contoh: Harap transfer sesuai nominal dan unggah bukti pembayaran maksimal 1x24 jam." class="mt-2 settings-field min-h-[140px]"></textarea>
                        <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">Instruksi ini akan ditampilkan pada halaman pembayaran manual warga.</p>
                        @error('manual_instructions') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                    </div>

                    @error('manual_bank_accounts') <p class="mt-4 text-xs font-semibold text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
            @endif

                <div class="flex flex-wrap items-center justify-end gap-3">
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="btn-soft-emerald px-6 text-sm"
                    >
                        <span wire:loading.remove>Simpan Pengaturan</span>
                        <span wire:loading>Menyimpan...</span>
                    </button>
                </div>
        </form>
    </section>
</div>

