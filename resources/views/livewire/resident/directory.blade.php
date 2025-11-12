<div class="space-y-10 font-['Inter'] text-slate-700 dark:text-slate-200" data-resident-stack>

    <section
        class="rounded-3xl border border-slate-200/70 bg-white/95 p-8 shadow-sm transition-colors duration-200 hover:bg-slate-50/80 dark:border-slate-800/70 dark:bg-slate-900/70 dark:hover:bg-slate-900/60"
        data-motion-animated
        data-resident-card
    >
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ __('resident.directory.resident_directory') }}</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{__('resident.directory.only_active_residents_can_view') }}</p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row">
                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    wire:loading.attr="disabled"
                    placeholder="{{ __('resident.directory.search_placeholder') }}"
                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-600 shadow-inner transition-colors duration-200 focus:border-sky-400 focus:ring-2 focus:ring-sky-200 focus:ring-offset-1 focus:ring-offset-white dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200 dark:focus:border-sky-400"
                >
                <select
                    wire:model.live="status"
                    wire:loading.attr="disabled"
                    class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-600 shadow-inner transition-colors duration-200 focus:border-sky-400 focus:ring-2 focus:ring-sky-200 focus:ring-offset-1 focus:ring-offset-white dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200"
                >
                    <option value="aktif">{{ __('resident.directory.active') }}</option>
                    <option value="pindah">{{ __('resident.directory.moved') }}</option>
                    <option value="nonaktif">{{ __('resident.directory.inactive') }}</option>
                    <option value="semua">{{ __('resident.directory.all') }}</option>
                </select>
            </div>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-3" data-motion-animated data-resident-card>
        <article class="space-y-3 rounded-2xl border border-slate-200/70 bg-white/95 p-5 shadow-sm transition-colors duration-200 hover:bg-slate-50/80 dark:border-slate-800/70 dark:bg-slate-900/70 dark:hover:bg-slate-900/60">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('resident.directory.active') }}</p>
            <p class="text-3xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format($counts['aktif']) }}</p>
        </article>
        <article class="space-y-3 rounded-2xl border border-slate-200/70 bg-white/95 p-5 shadow-sm transition-colors duration-200 hover:bg-slate-50/80 dark:border-slate-800/70 dark:bg-slate-900/70 dark:hover:bg-slate-900/60">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('resident.directory.moved') }}</p>
            <p class="text-3xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format($counts['pindah']) }}</p>
        </article>
        <article class="space-y-3 rounded-2xl border border-slate-200/70 bg-white/95 p-5 shadow-sm transition-colors duration-200 hover:bg-slate-50/80 dark:border-slate-800/70 dark:bg-slate-900/70 dark:hover:bg-slate-900/60">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('resident.directory.inactive') }}</p>
            <p class="text-3xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format($counts['nonaktif']) }}</p>
        </article>
    </section>

    <section
        class="rounded-3xl border border-slate-200/70 bg-white/95 p-8 shadow-sm transition-colors duration-200 hover:bg-slate-50/80 dark:border-slate-800/70 dark:bg-slate-900/70 dark:hover:bg-slate-900/60"
        data-motion-animated
        data-resident-card
        wire:transition
    >
        <div
            wire:loading.flex
            class="min-h-[6rem] items-center justify-center rounded-2xl border border-dashed border-sky-300/40 bg-white/80 text-xs font-semibold uppercase tracking-[0.3em] text-sky-600/80 dark:border-sky-500/30 dark:bg-slate-900/60 dark:text-sky-300/80"
        >
            {{ __('resident.directory.loading_resident_data') }}
        </div>

        <div wire:loading.remove>
            <div class="grid gap-5 md:grid-cols-2">
                @forelse ($residents as $resident)
                    @php
                        $statusLabel = match ($resident->status) {
                            'aktif' => trans('resident.directory.active'),
                            'pindah' => trans('resident.directory.moved'),
                            'nonaktif' => trans('resident.directory.inactive'),
                            default => Str::headline($resident->status),
                        };
                    @endphp
                    <article class="rounded-2xl border border-slate-200/70 bg-white/95 px-6 py-5 shadow-sm transition-colors duration-200 hover:bg-slate-50/80 dark:border-slate-800/70 dark:bg-slate-900/70 dark:hover:bg-slate-900/60">
                        <div class="flex items-center gap-4">
                            <div class="h-14 w-14 overflow-hidden rounded-full border border-slate-200/80 bg-slate-100 shadow-inner dark:border-slate-700 dark:bg-slate-800">
                                @if ($resident->profile_photo_url)
                                    <img src="{{ $resident->profile_photo_url }}" class="h-full w-full object-cover" alt="{{ __('resident.directory.avatar') }}">
                                @else
                                    <div class="flex h-full w-full items-center justify-center text-sm font-semibold text-sky-600 dark:text-sky-300">
                                        {{ Str::of($resident->name)->trim()->explode(' ')->map(fn ($part) => Str::substr($part, 0, 1))->take(2)->implode('') }}
                                    </div>
                                @endif
                            </div>
                            <div>
                                <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ $resident->name }}</h2>
                            </div>
                        </div>

                        <p class="mt-4 text-xs text-slate-500 dark:text-slate-400">{{ __('resident.directory.address') }} {{ $resident->alamat ?? '-' }}</p>
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('resident.directory.phone') }} {{ $resident->masked_phone ?? '-' }}</p>

                        <span class="mt-3 inline-flex rounded-full border border-emerald-200 bg-emerald-50/80 px-3 py-1 text-xs font-semibold text-emerald-600 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
                            {{ $statusLabel }}
                        </span>
                    </article>
                @empty
                    <p class="rounded-2xl border border-dashed border-sky-300/40 bg-white/80 p-6 text-center text-sm font-medium text-slate-400 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-500 md:col-span-2">
                        {{ __('resident.directory.no_resident_data_matches_filter') }}
                    </p>
                @endforelse
            </div>

            <div class="mt-6">
                {{ $residents->links() }}
            </div>
        </div>
    </section>

</div>
