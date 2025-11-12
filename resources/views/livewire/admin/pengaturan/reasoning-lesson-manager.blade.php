<div class="space-y-6 font-['Inter'] text-slate-800 dark:text-slate-100" data-settings-page data-admin-stack>
    <x-admin.settings-nav current="reasoning-lessons" />

    <section class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-2">
                <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-300">Reasoning Lesson Manager</p>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">Koleksi langkah bernalar terstruktur</h1>
                <p class="text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                    Tambahkan rangkaian langkah reasoning untuk intent tertentu agar GuidedLLMReasoner punya contoh bernalar yang konsisten.
                    Input satu langkah per baris, misal: "Identifikasi periode tagihan → Ambil data tagihan → Jelaskan hasil & follow-up".
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <label class="flex items-center gap-2 rounded-2xl border border-slate-200/70 bg-white px-3 py-2 text-sm text-slate-600 shadow-sm focus-within:border-indigo-400 dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-300">
                    <span class="text-xs uppercase tracking-wide text-slate-400 dark:text-slate-500">Cari</span>
                    <input type="search" wire:model.debounce.400ms="search" class="flex-1 border-none bg-transparent text-sm text-slate-700 placeholder:text-slate-400 focus:ring-0 dark:text-slate-200" placeholder="intent atau catatan..." />
                </label>
                <button type="button" wire:click="startCreate" class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-slate-800 dark:bg-indigo-600 dark:hover:bg-indigo-500">
                    Tambah Lesson
                </button>
            </div>
        </div>

        <form class="mt-6 grid gap-3 rounded-2xl border border-slate-200/70 bg-white/80 p-4 shadow-inner dark:border-slate-800/70 dark:bg-slate-900/60" wire:submit.prevent="saveLesson">
            <div class="grid gap-3 md:grid-cols-2">
                <label class="space-y-1 text-sm">
                    <span class="font-semibold text-slate-600 dark:text-slate-300">Intent</span>
                    <input type="text" wire:model.defer="intent" class="w-full rounded-xl border border-slate-200/70 bg-white/90 px-3 py-2 text-sm text-slate-700 focus:border-indigo-400 focus:ring-0 dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-200" placeholder="mis. bills, payments, agenda" />
                    @error('intent') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </label>
                <label class="space-y-1 text-sm">
                    <span class="font-semibold text-slate-600 dark:text-slate-300">Judul Lesson</span>
                    <input type="text" wire:model.defer="title" class="w-full rounded-xl border border-slate-200/70 bg-white/90 px-3 py-2 text-sm text-slate-700 focus:border-indigo-400 focus:ring-0 dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-200" placeholder="mis. Menjawab tagihan multi-periode" />
                    @error('title') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </label>
            </div>

            <label class="space-y-1 text-sm">
                <span class="font-semibold text-slate-600 dark:text-slate-300">Langkah Reasoning (satu baris setiap langkah)</span>
                <textarea wire:model.defer="stepsInput" rows="5" class="w-full rounded-xl border border-slate-200/70 bg-white/90 px-3 py-2 text-sm text-slate-700 focus:border-indigo-400 focus:ring-0 dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-200" placeholder="1. Identifikasi konteks data&#10;2. Tarik data realtime&#10;3. Jelaskan hasil dan follow-up"></textarea>
                @error('stepsInput') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
            </label>

            <div class="grid gap-3 md:grid-cols-3">
                <label class="space-y-1 text-sm">
                    <span class="font-semibold text-slate-600 dark:text-slate-300">Status</span>
                    <select wire:model.defer="status" class="w-full rounded-xl border border-slate-200/70 bg-white/90 px-3 py-2 text-sm text-slate-700 focus:border-indigo-400 focus:ring-0 dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-200">
                        <option value="active">Active</option>
                        <option value="draft">Draft</option>
                        <option value="archived">Archived</option>
                    </select>
                    @error('status') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </label>
                <label class="space-y-1 text-sm">
                    <span class="font-semibold text-slate-600 dark:text-slate-300">Priority</span>
                    <input type="number" wire:model.defer="priority" min="0" class="w-full rounded-xl border border-slate-200/70 bg-white/90 px-3 py-2 text-sm text-slate-700 focus:border-indigo-400 focus:ring-0 dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-200" />
                    @error('priority') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </label>
                <label class="space-y-1 text-sm">
                    <span class="font-semibold text-slate-600 dark:text-slate-300">Sumber</span>
                    <input type="text" wire:model.defer="source" class="w-full rounded-xl border border-slate-200/70 bg-white/90 px-3 py-2 text-sm text-slate-700 focus:border-indigo-400 focus:ring-0 dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-200" placeholder="optional: link dokumen/internal" />
                    @error('source') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </label>
            </div>

            <label class="space-y-1 text-sm">
                <span class="font-semibold text-slate-600 dark:text-slate-300">Catatan tambahan</span>
                <textarea wire:model.defer="notes" rows="2" class="w-full rounded-xl border border-slate-200/70 bg-white/90 px-3 py-2 text-sm text-slate-700 focus:border-indigo-400 focus:ring-0 dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-200" placeholder="informasi tambahan, asumsi, dsb."></textarea>
            </label>

            <div class="flex justify-end gap-2 text-sm">
                @if ($editingId)
                    <button type="button" wire:click="startCreate" class="rounded-xl border border-slate-300 px-4 py-2 text-slate-600 transition hover:border-slate-400 dark:border-slate-700 dark:text-slate-300">Batal</button>
                @endif
                <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 font-semibold text-white shadow hover:bg-slate-800 dark:bg-indigo-600 dark:hover:bg-indigo-500">
                    {{ $editingId ? 'Update Lesson' : 'Simpan Lesson' }}
                </button>
            </div>
        </form>

        <div class="mt-6 space-y-3">
            @forelse ($lessons as $lesson)
                <article class="rounded-2xl border border-slate-200/70 bg-white/90 p-4 shadow-sm transition hover:border-indigo-200 dark:border-slate-800/70 dark:bg-slate-900/60">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div class="space-y-2">
                            <div class="flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">
                                <span>Intent: {{ $lesson->intent }}</span>
                                <span>Status: {{ \Illuminate\Support\Str::headline($lesson->status) }}</span>
                                <span>Priority: {{ $lesson->priority }}</span>
                            </div>
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $lesson->title }}</h3>
                            <ol class="list-decimal space-y-1 pl-5 text-sm text-slate-600 dark:text-slate-300">
                                @foreach ($lesson->steps ?? [] as $step)
                                    <li>{{ $step }}</li>
                                @endforeach
                            </ol>
                            @if ($lesson->notes)
                                <p class="text-xs text-slate-500 dark:text-slate-400">Catatan: {{ $lesson->notes }}</p>
                            @endif
                        </div>
                        <div class="flex gap-2 text-sm font-medium">
                            <button type="button" wire:click="editLesson({{ $lesson->id }})" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-600 transition hover:border-slate-400 dark:border-slate-700/60 dark:bg-slate-900/60 dark:text-slate-200">
                                Edit
                            </button>
                            <button type="button" wire:click="deleteLesson({{ $lesson->id }})" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-rose-700 transition hover:border-rose-400 hover:bg-rose-100 dark:border-rose-600/40 dark:bg-rose-500/10 dark:text-rose-200" onclick="return confirm('Yakin hapus lesson ini?')">
                                Hapus
                            </button>
                        </div>
                    </div>
                </article>
            @empty
                <p class="rounded-2xl border border-slate-200/70 bg-white/80 p-6 text-center text-sm text-slate-500 dark:border-slate-800/70 dark:bg-slate-900/60 dark:text-slate-400">
                    Belum ada lesson untuk intent ini.
                </p>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $lessons->links() }}
        </div>
    </section>
</div>
