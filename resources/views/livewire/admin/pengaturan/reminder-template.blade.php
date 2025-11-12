<div class="space-y-6 font-['Inter'] text-slate-800 dark:text-slate-100" data-settings-page data-admin-stack>
    <x-admin.settings-nav current="reminder" />

    <section class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition-colors duration-200 dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="space-y-3">
                <p class="text-sm font-medium text-sky-600 dark:text-sky-300">Template email reminder</p>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Personalisasi pengingat otomatis</h1>
                <p class="max-w-3xl text-sm leading-relaxed text-slate-500 dark:text-slate-400">Gunakan token dinamis untuk menyampaikan informasi tagihan dan agenda secara personal sehingga warga selalu mendapat pesan yang relevan.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200/80 px-3 py-1 dark:border-slate-700">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    Token siap pakai
                </span>
                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200/80 px-3 py-1 dark:border-slate-700">
                    <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                    Dukungan rich text
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
        <header class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-[#0284C7] dark:text-sky-300">Template Pengingat Email</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Gunakan token berikut untuk memasukkan data dinamis: <span class="font-mono text-xs text-slate-600 dark:text-slate-300">{{ collect($tokens)->join(', ') }}</span></p>
            </div>
            <span class="inline-flex items-center gap-2 rounded-full border border-white/40 bg-white/60 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:border-slate-700/60 dark:bg-slate-900/60 dark:text-slate-300">
                Multi Context
            </span>
        </header>

        <form wire:submit.prevent="save" class="mt-6 space-y-6">
            <div class="rounded-3xl border border-sky-200/70 bg-sky-50/70 p-5 shadow-inner shadow-sky-200/40 dark:border-sky-500/40 dark:bg-slate-900/60 dark:shadow-slate-900/40">
                <h3 class="text-sm font-semibold text-[#0284C7] dark:text-sky-300">Tagihan / Iuran</h3>
                <div class="mt-4 space-y-4">
                    <div>
                        <label class="text-[11px] font-semibold uppercase tracking-[0.28em] text-slate-500 dark:text-slate-400">Subjek Email</label>
                        <input wire:model.defer="bill_subject" type="text" class="mt-2 settings-field" placeholder="Pengingat Tagihan :bill_name">
                        @error('bill_subject') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-[11px] font-semibold uppercase tracking-[0.28em] text-slate-500 dark:text-slate-400">Isi Email</label>
                        <textarea wire:model.defer="bill_body" rows="5" class="mt-2 settings-field min-h-[140px]" placeholder="Halo :resident_name, berikut tagihan terbaru..."></textarea>
                        @error('bill_body') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-amber-200/70 bg-amber-50/80 p-5 shadow-inner shadow-amber-200/40 dark:border-amber-400/40 dark:bg-slate-900/60 dark:shadow-slate-900/40">
                <h3 class="text-sm font-semibold text-amber-600 dark:text-amber-300">Agenda / Event</h3>
                <div class="mt-4 space-y-4">
                    <div>
                        <label class="text-[11px] font-semibold uppercase tracking-[0.28em] text-slate-500 dark:text-slate-400">Subjek Email</label>
                        <input wire:model.defer="event_subject" type="text" class="mt-2 settings-field" placeholder="Pengingat Agenda :event_title">
                        @error('event_subject') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-[11px] font-semibold uppercase tracking-[0.28em] text-slate-500 dark:text-slate-400">Isi Email</label>
                        <textarea wire:model.defer="event_body" rows="5" class="mt-2 settings-field min-h-[140px]" placeholder="Halo :resident_name, jangan lupa agenda..."></textarea>
                        @error('event_body') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="btn-soft-emerald px-6 text-sm"
                >
                    <span wire:loading.remove>Simpan Template</span>
                    <span wire:loading>Menyimpan...</span>
                </button>
            </div>
        </form>
    </section>
</div>
