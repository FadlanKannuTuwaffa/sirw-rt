<div class="space-y-6 font-['Inter'] text-slate-800 dark:text-slate-100" data-settings-page data-admin-stack>
    <x-admin.settings-nav current="telegram" />

    <section class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition-colors duration-200 dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="space-y-3">
                <p class="text-sm font-medium text-sky-600 dark:text-sky-300">Konfigurasi bot Telegram</p>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Hubungkan pengingat otomatis ke Telegram</h1>
                <p class="max-w-3xl text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                    Atur token, webhook, dan respons perintah tanpa meninggalkan panel admin. Bot akan menyampaikan pengingat dan informasi otomatis ke warga.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200/80 px-3 py-1 dark:border-slate-700">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    Webhook siap
                </span>
                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200/80 px-3 py-1 dark:border-slate-700">
                    <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                    Sinkron perintah
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
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-[#0284C7] dark:text-sky-300">Pengaturan Umum Bot</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Tetapkan kredensial dan kontak yang ditampilkan saat bot berinteraksi dengan warga.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" wire:click="syncCommands" wire:loading.attr="disabled" class="inline-flex items-center gap-2 rounded-full border border-slate-200/70 bg-white/80 px-4 py-2 text-xs font-semibold text-[#0284C7] shadow-sm shadow-sky-100/50 transition-colors duration-200 hover:border-sky-300 hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-300 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-slate-700/60 dark:bg-slate-900/60 dark:text-sky-300 dark:hover:border-sky-400 dark:hover:bg-slate-900 dark:focus-visible:ring-sky-400 dark:focus-visible:ring-offset-slate-900">
                    <span wire:loading.remove>Sinkronkan Perintah</span>
                    <span wire:loading>Sedang proses...</span>
                </button>
                <button type="button" wire:click="applyWebhook" wire:loading.attr="disabled" class="btn-soft-emerald text-xs sm:text-sm">
                    <span wire:loading.remove>Setel Webhook</span>
                    <span wire:loading>Memperbarui...</span>
                </button>
            </div>
        </div>

        <form wire:submit.prevent="saveSettings" class="mt-6 space-y-6">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Bot Token</label>
                    <div class="mt-2">
                        <input id="telegram_bot_token" type="password" wire:model.defer="bot_token" autocomplete="off" class="settings-field pr-12" placeholder="Token dari @BotFather">
                        <div class="mt-2 flex justify-end pr-1">
                            <button type="button" class="secret-toggle secret-toggle--surface" data-secret-toggle="telegram_bot_token" data-secret-visible="false" aria-label="Tampilkan nilai rahasia" aria-pressed="false">
                                <span class="sr-only">Tampilkan atau sembunyikan nilai rahasia</span>
                                <svg data-secret-icon="open" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z"/>
                                    <circle cx="12" cy="12" r="3.2"/>
                                </svg>
                                <svg data-secret-icon="closed" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M3 15s4-3 9-3 9 3 9 3"/>
                                    <path d="M7 13l-1 3"/>
                                    <path d="M12 12v3"/>
                                    <path d="M17 13l1 3"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">Rahasiakan token Anda. Bot akan dinonaktifkan bila token kosong.</p>
                    @error('bot_token') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Webhook URL</label>
                    <input type="url" wire:model.defer="webhook_url" class="mt-2 settings-field" placeholder="{{ route('telegram.webhook') }}">
                    <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">Biarkan kosong untuk menggunakan URL bawaan sistem.</p>
                    @error('webhook_url') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Webhook Secret</label>
                    <input type="text" wire:model.defer="webhook_secret" class="mt-2 settings-field" placeholder="Opsional - Secret token untuk webhook">
                    @error('webhook_secret') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Bahasa Default</label>
                    <select wire:model.defer="default_language" class="mt-2 settings-field">
                        <option value="id">Indonesia</option>
                        <option value="en">English</option>
                    </select>
                    @error('default_language') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Email Kontak</label>
                    <input type="email" wire:model.defer="contact_email" class="mt-2 settings-field" placeholder="support@rtsmart.id">
                    @error('contact_email') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">WhatsApp Kontak</label>
                    <input type="text" wire:model.defer="contact_whatsapp" class="mt-2 settings-field" placeholder="0812-xxxx-xxxx">
                    @error('contact_whatsapp') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
            </div>

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

    <section class="settings-surface w-full p-6" data-motion-animated>
        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-[#0284C7] dark:text-sky-300">Manajemen Perintah Bot</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Atur perintah kustom dan aktifkan/menonaktifkan perintah bawaan sesuai kebutuhan warga.</p>
            </div>
            <button type="button" wire:click="selectCommand('new')" class="inline-flex items-center gap-2 rounded-full border border-sky-200/70 bg-sky-50/70 px-4 py-2 text-xs font-semibold text-sky-600 transition-colors duration-200 hover:border-sky-300 hover:bg-sky-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-300 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-sky-500/40 dark:bg-sky-500/10 dark:text-sky-300 dark:hover:border-sky-400 dark:hover:bg-sky-500/20 dark:focus-visible:ring-sky-400 dark:focus-visible:ring-offset-slate-900">
                + Perintah Baru
            </button>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,0.45fr)_minmax(0,1fr)]">
            <aside class="rounded-2xl border border-slate-200/70 bg-white/70 p-4 shadow-inner shadow-slate-200/40 dark:border-slate-700/60 dark:bg-slate-900/60 dark:shadow-slate-900/40">
                <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Daftar Perintah</h3>
                <div class="mt-3 flex flex-col gap-2">
                    @foreach ($commandOptions as $option)
                        @php
                            $isActive = (string) ($option['id'] ?? 'new') === (string) $selectedCommandId;
                            $label = '/' . ($option['command'] ?? '');
                        @endphp
                        <button
                            type="button"
                            wire:click="selectCommand('{{ $option['id'] }}')"
                            class="w-full rounded-xl border px-3 py-2 text-left text-sm font-semibold transition-all duration-200 {{ $isActive ? 'border-[#0284C7] bg-[#0284C7]/10 text-[#0284C7] shadow-sm shadow-[#0284C7]/30 dark:border-sky-400 dark:bg-sky-500/10 dark:text-sky-200' : 'border-slate-200 bg-white text-slate-600 hover:border-sky-200 hover:text-sky-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-sky-400 dark:hover:text-sky-300' }}"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <span>{{ $label }}</span>
                                <span class="text-[11px] uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">
                                    {{ $option['is_system'] ? 'System' : 'Custom' }}
                                </span>
                            </div>
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-[11px] text-slate-400 dark:text-slate-500">
                                @if (! $option['is_system'])
                                    <span class="inline-flex items-center gap-1">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $option['is_active'] ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                        {{ $option['is_active'] ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1">
                                        <span class="h-1.5 w-1.5 rounded-full bg-sky-400"></span> Tetap aktif
                                    </span>
                                @endif
                                @if ($option['is_admin_only'])
                                    <span class="inline-flex items-center gap-1 text-amber-500">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span> Admin
                                    </span>
                                @endif
                            </div>
                        </button>
                    @endforeach
                </div>
            </aside>

            <div class="rounded-2xl border border-slate-200/70 bg-white/70 p-5 shadow-inner shadow-slate-200/40 dark:border-slate-700/60 dark:bg-slate-900/60 dark:shadow-slate-900/40">
                <form wire:submit.prevent="saveCommand" class="space-y-5">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Perintah</label>
                            <input
                                type="text"
                                wire:model.defer="commandEditor.command"
                                class="mt-2 settings-field {{ $editor['is_system'] && $selectedCommandId !== 'new' ? 'bg-slate-100/80 text-slate-500 dark:bg-slate-800/70 dark:text-slate-400' : '' }}"
                                placeholder="contoh: info_tagihan"
                                {{ $editor['is_system'] && $selectedCommandId !== 'new' ? 'disabled' : '' }}
                            >
                            @error('commandEditor.command') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Bahasa Respon</label>
                            <div class="mt-2 flex gap-2 text-xs text-slate-500 dark:text-slate-400">
                                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200/70 bg-white/70 px-3 py-1 dark:border-slate-700/60 dark:bg-slate-900/60">Indonesia</span>
                                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200/70 bg-white/70 px-3 py-1 opacity-60 dark:border-slate-700/60 dark:bg-slate-900/60">English (soon)</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Status Perintah</label>
                            <div class="mt-2 flex flex-wrap items-center gap-3">
                                @if ($editor['is_system'])
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:bg-slate-800 dark:text-slate-300">Perintah bawaan</span>
                                @else
                                    <label class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
                                        <input type="checkbox" wire:model.defer="commandEditor.is_active" class="rounded border-slate-300 text-[#0284C7] shadow-sm focus:ring-[#0284C7] dark:border-slate-600 dark:bg-slate-800">
                                        <span>Aktif</span>
                                    </label>
                                    <label class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
                                        <input type="checkbox" wire:model.defer="commandEditor.is_admin_only" class="rounded border-slate-300 text-[#F97316] shadow-sm focus:ring-[#F97316] dark:border-slate-600 dark:bg-slate-800">
                                        <span>Khusus admin</span>
                                    </label>
                                @endif
                            </div>
                            @error('commandEditor.is_active') <p class="mt-1 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                            @error('commandEditor.is_admin_only') <p class="mt-1 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Deskripsi Ringkas</label>
                            <input type="text" wire:model.defer="commandEditor.description" class="mt-2 settings-field settings-field--plain" placeholder="Penjelasan singkat perintah">
                            @error('commandEditor.description') <p class="mt-1 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Template Respon</label>
                        <textarea rows="5" wire:model.defer="commandEditor.response_template" class="mt-2 settings-field min-h-[160px]" placeholder="{{ $selectedCommandId === 'new' ? 'Tulis balasan otomatis yang ingin dikirim bot.' : 'Tambahkan kalimat tambahan untuk balasan bawaan bot (opsional).' }}"></textarea>
                        <p class="mt-2 text-[11px] text-slate-400 dark:text-slate-500">Token tersedia: <code class="font-mono text-xs text-slate-600 dark:text-slate-300">:user_name</code>, <code class="font-mono text-xs text-slate-600 dark:text-slate-300">:count</code>, <code class="font-mono text-xs text-slate-600 dark:text-slate-300">:bill_id</code>, dan lainnya sesuai konteks.</p>
                        @error('commandEditor.response_template') <p class="mt-1 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-2">
                            @if ($selectedCommandId !== 'new' && ! $editor['is_system'])
                                <button type="button" wire:click="deleteCommand" onclick="return confirm('Hapus perintah ini?')" class="inline-flex items-center rounded-full border border-rose-200/70 bg-white px-4 py-2 text-xs font-semibold text-rose-600 transition-colors duration-200 hover:border-rose-300 hover:bg-rose-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-300 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-rose-500/40 dark:bg-slate-900/60 dark:text-rose-300 dark:hover:border-rose-400 dark:hover:bg-rose-500/10 dark:focus-visible:ring-rose-400 dark:focus-visible:ring-offset-slate-900">
                                    Hapus Perintah
                                </button>
                            @endif
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" wire:click="selectCommand('new')" class="inline-flex items-center rounded-full border border-slate-200/70 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors duration-200 hover:border-slate-300 hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-300 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-slate-700/60 dark:bg-slate-900/60 dark:text-slate-300 dark:hover:border-slate-500 dark:hover:bg-slate-900 dark:focus-visible:ring-slate-500 dark:focus-visible:ring-offset-slate-900">
                                Reset Form
                            </button>
                            <button type="submit" wire:loading.attr="disabled" class="btn-soft-emerald px-4 text-xs sm:text-sm">
                                <span wire:loading.remove>Simpan Perintah</span>
                                <span wire:loading>Menyimpan...</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>


