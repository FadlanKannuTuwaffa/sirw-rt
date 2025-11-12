<div class="space-y-6">
    @if (session('kb_message'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/70 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-600/40 dark:bg-emerald-900/40 dark:text-emerald-100">
            {{ session('kb_message') }}
        </div>
    @endif
    @if (session('kb_warning'))
        <div class="rounded-2xl border border-amber-200 bg-amber-50/70 px-4 py-3 text-sm text-amber-700 dark:border-amber-600/40 dark:bg-amber-900/40 dark:text-amber-100">
            {{ session('kb_warning') }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <form wire:submit.prevent="ingest" class="space-y-5 rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm dark:border-slate-800/60 dark:bg-slate-900/70">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-sky-600 dark:text-sky-300">Tambah Dokumen SOP/FAQ</p>
                <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Ingest file ke knowledge base</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">Unggah berkas .md atau tempel konten manual. Sistem akan memotong otomatis dan menambahkannya ke RAG.</p>
            </div>

            <div class="space-y-2">
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">Judul dokumen</label>
                <input type="text" wire:model.defer="title" class="w-full rounded-2xl border border-slate-200/70 bg-white/90 px-4 py-2 text-sm text-slate-900 shadow-sm focus:border-sky-400 focus:outline-none dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100" placeholder="Contoh: Prosedur Surat Domisili">
                @error('title') <p class="text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-2">
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">Upload file (.md/.txt)</label>
                <input type="file" wire:model="document" class="w-full rounded-2xl border border-dashed border-slate-300 px-4 py-3 text-sm dark:border-slate-700">
                @error('document') <p class="text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-2">
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">Atau isi konten manual</label>
                <textarea wire:model.defer="manualContent" rows="5" class="w-full rounded-2xl border border-slate-200/70 bg-white/90 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-sky-400 focus:outline-none dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100" placeholder="Tempel panduan atau SOP di sini"></textarea>
                @error('manualContent') <p class="text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-sky-500 to-indigo-500 px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-sky-500/30 transition hover:shadow-indigo-500/30" wire:loading.attr="disabled">
                    <span wire:loading.remove>Ingest sekarang</span>
                    <span wire:loading>Memproses...</span>
                </button>
                <button type="button" wire:click="syncFromStorage" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200/70 bg-white/80 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-sky-300 hover:text-sky-600 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-200" wire:loading.attr="disabled">
                    <span wire:loading.remove>Sinkronkan ulang dari storage</span>
                    <span wire:loading>Sinkronisasi...</span>
                </button>
            </div>
        </form>

        <div class="space-y-4 rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm dark:border-slate-800/60 dark:bg-slate-900/70">
            <div>
                <p class="text-sm font-semibold text-slate-600 dark:text-slate-300">Riwayat dokumen</p>
                <h3 class="text-xl font-semibold text-slate-900 dark:text-white">8 dokumen terbaru</h3>
            </div>

            <div class="space-y-3 text-sm text-slate-600 dark:text-slate-300">
                @forelse ($articles as $article)
                    <div class="rounded-2xl border border-slate-100 bg-white/80 px-4 py-3 dark:border-slate-800/70 dark:bg-slate-900/60">
                        <div class="flex items-center justify-between">
                            <p class="font-semibold text-slate-900 dark:text-white">{{ $article['title'] }}</p>
                            <span class="text-xs text-slate-400 dark:text-slate-500">{{ $article['updated_at'] }}</span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $article['chunks'] }} potongan teks disimpan.</p>
                    </div>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-4 text-center text-xs text-slate-400 dark:border-slate-700 dark:text-slate-500">Belum ada dokumen dalam knowledge base.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
