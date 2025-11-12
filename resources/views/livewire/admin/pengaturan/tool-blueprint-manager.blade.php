<div class="space-y-6 font-['Inter'] text-slate-800 dark:text-slate-100" data-settings-page data-admin-stack>
    <x-admin.settings-nav current="tool-blueprints" />

    <section class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-2">
                <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-300">Tool Blueprint Manager</p>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">Kurasi intent yang butuh tool khusus</h1>
                <p class="text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                    Blueprint dibuat otomatis ketika intent sering gagal tanpa memakai tool. Gunakan panel ini untuk menandai progres implementasi
                    atau menambahkan catatan sebelum diberikan ke tim developer.
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <label class="flex items-center gap-2 rounded-2xl border border-slate-200/70 bg-white px-3 py-2 text-sm text-slate-600 shadow-sm focus-within:border-indigo-400 dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-300">
                    <span class="text-xs uppercase tracking-wide text-slate-400 dark:text-slate-500">Cari</span>
                    <input type="search" wire:model.debounce.400ms="search" class="flex-1 border-none bg-transparent text-sm text-slate-700 placeholder:text-slate-400 focus:ring-0 dark:text-slate-200" placeholder="intent atau contoh pertanyaan..." />
                </label>
                <select wire:model="status" class="rounded-2xl border border-slate-200/70 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm focus:border-indigo-400 focus:ring-indigo-400 dark:border-slate-700/70 dark:bg-slate-900/70 dark:text-slate-200">
                    <option value="pending">Pending</option>
                    <option value="in_progress">Sedang dikerjakan</option>
                    <option value="implemented">Selesai</option>
                    <option value="rejected">Ditolak</option>
                </select>
            </div>
        </div>

        <div class="mt-6 space-y-4">
            @forelse ($blueprints as $blueprint)
                <article class="rounded-2xl border border-slate-200/70 bg-white/90 p-4 shadow-sm transition hover:border-indigo-200 dark:border-slate-800/70 dark:bg-slate-900/60">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div class="space-y-3">
                            <div class="flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">
                                <span>Intent: {{ $blueprint->intent }}</span>
                                <span>Status: {{ \Illuminate\Support\Str::headline($blueprint->status) }}</span>
                                <span>Failure: {{ number_format($blueprint->failure_rate, 1) }}%</span>
                                <span>Tool usage: {{ number_format($blueprint->tool_usage_rate, 1) }}%</span>
                                <span>Interaksi: {{ number_format($blueprint->total_interactions) }}</span>
                            </div>
                            <div class="rounded-xl border border-slate-200/70 bg-white/80 p-3 text-sm text-slate-600 shadow-inner dark:border-slate-800/70 dark:bg-slate-900/60 dark:text-slate-200">
                                <p class="font-semibold text-slate-700 dark:text-slate-100">Contoh kegagalan</p>
                                <p class="mt-1 whitespace-pre-line text-sm leading-relaxed">{{ $blueprint->sample_failure ?? 'Belum ada contoh.' }}</p>
                            </div>
                            <div class="rounded-xl border border-slate-200/70 bg-white/80 p-3 text-sm shadow-inner dark:border-slate-800/70 dark:bg-slate-900/60">
                                <p class="font-semibold text-slate-700 dark:text-slate-100">Catatan</p>
                                <textarea wire:model.defer="noteDrafts.{{ $blueprint->id }}" class="mt-2 w-full rounded-xl border border-slate-200/70 bg-white/80 px-3 py-2 text-sm text-slate-700 focus:border-indigo-400 focus:ring-0 dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-200" rows="2" placeholder="catatan pengembangan..."></textarea>
                                <div class="mt-2 text-right">
                                    <button type="button" wire:click="saveNote({{ $blueprint->id }})" class="inline-flex items-center rounded-xl bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white shadow hover:bg-slate-800 dark:bg-indigo-600 dark:hover:bg-indigo-500">
                                        Simpan Catatan
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col gap-2 text-sm font-medium">
                            <button type="button" wire:click="markStatus({{ $blueprint->id }}, 'in_progress')" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-slate-600 transition hover:border-slate-400 hover:bg-slate-100 dark:border-slate-700/60 dark:bg-slate-800/60 dark:text-slate-200">
                                Tandai sedang dikerjakan
                            </button>
                            <button type="button" wire:click="markStatus({{ $blueprint->id }}, 'implemented')" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-emerald-700 transition hover:border-emerald-400 hover:bg-emerald-100 dark:border-emerald-600/40 dark:bg-emerald-500/10 dark:text-emerald-200">
                                Tandai selesai
                            </button>
                            <button type="button" wire:click="markStatus({{ $blueprint->id }}, 'pending')" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-slate-600 transition hover:border-slate-400 dark:border-slate-700/60 dark:bg-slate-900/60 dark:text-slate-200">
                                Kembalikan ke pending
                            </button>
                            <button type="button" wire:click="markStatus({{ $blueprint->id }}, 'rejected')" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-rose-700 transition hover:border-rose-400 hover:bg-rose-100 dark:border-rose-600/40 dark:bg-rose-500/10 dark:text-rose-200">
                                Tolak
                            </button>
                        </div>
                    </div>
                </article>
            @empty
                <p class="rounded-2xl border border-slate-200/70 bg-white/80 p-6 text-center text-sm text-slate-500 dark:border-slate-800/70 dark:bg-slate-900/60 dark:text-slate-400">
                    Belum ada blueprint untuk status ini.
                </p>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $blueprints->links() }}
        </div>
    </section>
</div>
