<section class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition-colors duration-300 dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card wire:poll.keep-alive.30s>
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-sm font-medium text-fuchsia-600 dark:text-fuchsia-300">Manual Correction Override</p>
            <h2 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Paksa alias & ejaan khusus</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Tambahkan koreksi permanen agar asisten langsung memetakan slang / istilah tertentu ke nilai kanonik tanpa menunggu interaksi pengguna lain.
            </p>
        </div>
        <div class="rounded-2xl border border-slate-200/70 bg-white/70 px-4 py-2 text-sm text-slate-600 shadow-sm dark:border-slate-800/50 dark:bg-slate-900/60 dark:text-slate-300">
            {{ $corrections->count() }} override aktif
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <form wire:submit.prevent="save" class="space-y-4 rounded-2xl border border-slate-100 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/40">
            @if (session()->has('assistant_corrections_status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 px-3 py-2 text-sm font-medium text-emerald-700 dark:border-emerald-500/40 dark:bg-emerald-900/40 dark:text-emerald-200">
                    {{ session('assistant_corrections_status') }}
                </div>
            @endif

            <div class="grid gap-4 md:grid-cols-2">
                <label class="text-sm">
                    <span class="text-slate-600 dark:text-slate-300">Alias / slang <span class="text-rose-500">*</span></span>
                    <input type="text" wire:model.defer="alias" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 focus:border-sky-500 focus:ring-sky-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" placeholder="contoh: duit sampah" />
                    @error('alias')
                        <span class="mt-1 block text-xs text-rose-500">{{ $message }}</span>
                    @enderror
                </label>

                <label class="text-sm">
                    <span class="text-slate-600 dark:text-slate-300">Nilai kanonik <span class="text-rose-500">*</span></span>
                    <input type="text" wire:model.defer="canonical" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 focus:border-sky-500 focus:ring-sky-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" placeholder="contoh: iuran kebersihan" />
                    @error('canonical')
                        <span class="mt-1 block text-xs text-rose-500">{{ $message }}</span>
                    @enderror
                </label>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="text-sm">
                    <span class="text-slate-600 dark:text-slate-300">Catatan</span>
                    <input type="text" wire:model.defer="notes" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 focus:border-sky-500 focus:ring-sky-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" placeholder="opsional" />
                    @error('notes')
                        <span class="mt-1 block text-xs text-rose-500">{{ $message }}</span>
                    @enderror
                </label>

                <label class="text-sm">
                    <span class="text-slate-600 dark:text-slate-300">Tanggal kadaluarsa</span>
                    <input type="date" wire:model.defer="expires_at" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 focus:border-sky-500 focus:ring-sky-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100" />
                    @error('expires_at')
                        <span class="mt-1 block text-xs text-rose-500">{{ $message }}</span>
                    @enderror
                </label>
            </div>

            <div class="flex items-center justify-end gap-3">
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 dark:ring-offset-slate-900">
                    Simpan Override
                </button>
            </div>
        </form>

        <div class="space-y-4 rounded-2xl border border-slate-100 bg-white/80 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/40">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-slate-800 dark:text-slate-100">Daftar Override</h3>
                <span class="text-xs text-slate-400">Auto-refresh tiap 30 detik</span>
            </div>
            <div class="space-y-3">
                @forelse ($corrections as $correction)
                    <article class="rounded-xl border border-slate-200/80 bg-white/80 p-3 text-sm shadow-sm dark:border-slate-700 dark:bg-slate-900/50" wire:key="correction-{{ $correction->id }}">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-slate-400">Alias</p>
                                <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $correction->alias }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs uppercase tracking-wide text-slate-400">Kanonik</p>
                                <p class="font-semibold text-emerald-700 dark:text-emerald-300">{{ $correction->canonical }}</p>
                            </div>
                        </div>
                        @if ($correction->notes)
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ $correction->notes }}</p>
                        @endif
                        <div class="mt-3 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-500 dark:text-slate-400">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center gap-1 rounded-full {{ $correction->is_active ? 'bg-emerald-100/80 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }} px-2 py-0.5">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $correction->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    {{ $correction->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                                @if ($correction->expires_at)
                                    <span>Kadaluarsa {{ $correction->expires_at->timezone(config('app.timezone', 'UTC'))->format('d M Y') }}</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" wire:click="toggle({{ $correction->id }})" class="rounded-lg border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-600 transition hover:border-sky-500 hover:text-sky-600 dark:border-slate-700 dark:text-slate-300">
                                    {{ $correction->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                                <button type="button" wire:click="delete({{ $correction->id }})" class="rounded-lg border border-rose-200 px-3 py-1 text-xs font-semibold text-rose-600 transition hover:bg-rose-50 dark:border-rose-900/60 dark:text-rose-300">
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="text-sm text-slate-500 dark:text-slate-400">Belum ada override. Tambahkan minimal satu alias agar bisa dipakai seluruh warga.</p>
                @endforelse
            </div>
        </div>
    </div>
</section>
