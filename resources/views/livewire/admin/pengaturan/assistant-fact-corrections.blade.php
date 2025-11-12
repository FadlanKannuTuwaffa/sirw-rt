<section class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition-colors duration-300 dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card>
    <header class="mb-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Review Data Fakta</p>
            <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100">Antrian koreksi faktual</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Periksa koreksi yang dikirim user sebelum diaplikasikan ke database resmi.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <select wire:model.live="statusFilter" class="rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800">
                <option value="pending">Pending</option>
                <option value="queued">Queued</option>
                <option value="existing">Existing</option>
                <option value="applied">Applied</option>
                <option value="needs_review">Needs review</option>
                <option value="error">Error</option>
                <option value="all">Semua status</option>
            </select>
            <input wire:model.live.debounce.500ms="search" type="text" placeholder="Cari query / fingerprint..." class="rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800" />
        </div>
    </header>

    @if ($records instanceof \Illuminate\Pagination\Paginator || $records instanceof \Illuminate\Pagination\LengthAwarePaginator ? $records->count() : $records->count())
        <div class="overflow-x-auto rounded-2xl border border-slate-100 dark:border-slate-800">
            <table class="min-w-full divide-y divide-slate-100 text-sm dark:divide-slate-800">
                <thead class="bg-slate-50/80 dark:bg-slate-800/40">
                    <tr class="text-slate-600 dark:text-slate-300">
                        <th class="px-3 py-2 text-left font-semibold">ID</th>
                        <th class="px-3 py-2 text-left font-semibold">Entity</th>
                        <th class="px-3 py-2 text-left font-semibold">Value</th>
                        <th class="px-3 py-2 text-left font-semibold">Scope</th>
                        <th class="px-3 py-2 text-left font-semibold">Status</th>
                        <th class="px-3 py-2 text-left font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($records as $correction)
                        <tr class="text-slate-700 dark:text-slate-200">
                            <td class="px-3 py-2 font-semibold">#{{ $correction->id }}</td>
                            <td class="px-3 py-2">
                                <p class="font-medium">{{ Str::headline($correction->entity_type ?? 'unknown') }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $correction->field ?? '-' }}</p>
                            </td>
                            <td class="px-3 py-2 text-xs text-slate-500 dark:text-slate-300">
                                {{ Str::limit($correction->value_raw ?? $correction->value ?? '-', 60) }}
                            </td>
                            <td class="px-3 py-2 text-xs uppercase tracking-wide text-slate-400 dark:text-slate-500">
                                {{ Str::upper($correction->scope ?? 'user') }}
                            </td>
                            <td class="px-3 py-2">
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                    {{ Str::headline(str_replace('_', ' ', $correction->status)) }}
                                </span>
                                <p class="text-[10px] text-slate-400 dark:text-slate-500">{{ optional($correction->created_at)->diffForHumans() }}</p>
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex flex-wrap gap-2 text-xs">
                                    <button wire:click="setStatus({{ $correction->id }}, 'applied')" class="rounded-full border border-emerald-500 px-3 py-1 font-semibold text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/40">
                                        Tandai Applied
                                    </button>
                                    <button wire:click="setStatus({{ $correction->id }}, 'needs_review')" class="rounded-full border border-amber-500 px-3 py-1 font-semibold text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/30">
                                        Needs Review
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $records->links(data: ['scrollTo' => false]) }}
        </div>
    @else
        <p class="text-sm text-slate-500 dark:text-slate-400">Belum ada koreksi yang perlu ditinjau.</p>
    @endif
</section>
