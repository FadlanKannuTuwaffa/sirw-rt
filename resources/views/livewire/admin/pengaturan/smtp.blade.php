<div class="space-y-6 font-['Inter'] text-slate-800 dark:text-slate-100" data-settings-page data-admin-stack>
    <x-admin.settings-nav current="smtp" />

    <section class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition-colors duration-200 dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card>
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="space-y-3">
                <p class="text-sm font-medium text-sky-600 dark:text-sky-300">Konfigurasi SMTP terpusat</p>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Kelola kredensial email tanpa menyunting .env</h1>
                <p class="max-w-2xl text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                    Sistem akan memakai pengaturan terbaru untuk setiap pengiriman surel otomatis sehingga pembaruan bisa dilakukan langsung dari panel admin.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200/80 px-3 py-1 dark:border-slate-700">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    Verified sender
                </span>
                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200/80 px-3 py-1 dark:border-slate-700">
                    <span class="h-2 w-2 rounded-full bg-sky-500"></span>
                    Audit friendly
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
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-[#0284C7] dark:text-sky-300">Kredensial SMTP</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Pastikan data sesuai dengan penyedia layanan email yang digunakan.</p>
            </div>
            <span class="inline-flex items-center gap-2 rounded-full border border-white/40 bg-white/60 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:border-slate-700/60 dark:bg-slate-900/60 dark:text-slate-300">
                TLS Ready
            </span>
        </div>

        <form wire:submit.prevent="save" class="mt-6 space-y-8">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Mailer Default</label>
                    <select wire:model.defer="mailer" class="mt-2 settings-field">
                        <option value="smtp">SMTP</option>
                        <option value="sendmail">Sendmail</option>
                        <option value="log">Log</option>
                        <option value="ses">Amazon SES</option>
                        <option value="postmark">Postmark</option>
                        <option value="resend">Resend</option>
                    </select>
                    @error('mailer') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Hostname</label>
                    <input type="text" wire:model.defer="host" placeholder="mail.domain.com" class="mt-2 settings-field">
                    @error('host') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Port</label>
                    <input type="number" wire:model.defer="port" min="1" max="65535" class="mt-2 settings-field">
                    @error('port') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Enkripsi</label>
                    <select wire:model.defer="encryption" class="mt-2 settings-field">
                        <option value="">Tanpa Enkripsi</option>
                        <option value="tls">TLS</option>
                        <option value="ssl">SSL</option>
                    </select>
                    @error('encryption') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Username</label>
                    <input type="text" wire:model.defer="username" class="mt-2 settings-field" placeholder="nama@domain.com">
                    @error('username') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Password</label>
                    <div class="relative mt-2">
                        <input
                            id="smtp_password"
                            type="password"
                            wire:model.defer="password"
                            placeholder="********"
                            class="settings-field pr-12"
                        >
                        <button type="button" class="secret-toggle secret-toggle--inline absolute right-2 top-1/2 -translate-y-1/2" data-secret-toggle="smtp_password" data-secret-visible="false" aria-label="Tampilkan nilai rahasia" aria-pressed="false">
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
                    <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">Kosongkan jika tidak ingin mengubah password saat ini.</p>
                    @error('password') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Timeout (detik)</label>
                    <input type="number" wire:model.defer="timeout" min="0" max="600" class="mt-2 settings-field">
                    @error('timeout') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Email Pengirim</label>
                    <input type="email" wire:model.defer="from_address" class="mt-2 settings-field" placeholder="system@rtsmart.id">
                    @error('from_address') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Nama Pengirim</label>
                    <input type="text" wire:model.defer="from_name" class="mt-2 settings-field" placeholder="SIRW - Sistem Informasi Rukun Warga">
                    @error('from_name') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="btn-soft-emerald text-sm"
                >
                    <span wire:loading.remove>Simpan Pengaturan</span>
                    <span wire:loading>Menyimpan...</span>
                </button>
            </div>
        </form>
    </section>
</div>

