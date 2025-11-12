

<form
    wire:submit.prevent="save"
    class="flex flex-col gap-4 rounded-2xl border border-slate-200/70 bg-white/90 px-4 py-5 text-sm shadow-sm transition-colors duration-300 dark:border-slate-700/60 dark:bg-slate-900/70"
    data-resident-card
    data-variant="muted"
    data-resident-preferences
>
    <header class="flex flex-col gap-1">
        <div class="flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-sky-500 dark:text-sky-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="m14.25 9 .338 1.353A2.25 2.25 0 0 0 16.707 12h.543a2.25 2.25 0 0 1 1.998 3.25l-.231.462a2.25 2.25 0 0 0 0 2.036l.23.462a2.25 2.25 0 0 1-1.998 3.25h-.542a2.25 2.25 0 0 0-2.12 1.647L14.25 21M9.75 15l-.338-1.353A2.25 2.25 0 0 0 7.293 12H6.75a2.25 2.25 0 0 1-1.998-3.25l.231-.462a2.25 2.25 0 0 0 0-2.036l-.23-.462A2.25 2.25 0 0 1 6.75 3.5h.542a2.25 2.25 0 0 0 2.12-1.647L9.75 3" />
            </svg>
            <h2>{{ __('resident.experience.title') }}</h2>
        </div>
        <p class="text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ __('resident.experience.description') }}</p>
        <p class="text-[11px] text-slate-400 dark:text-slate-500">
            @if ($lastSavedLabel)
                {{ __('resident.experience.saved_at') }}: {{ $lastSavedLabel }}
            @else
                {{ __('resident.experience.not_saved') }}
            @endif
        </p>
    </header>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="flex flex-col gap-2">
            <label for="preference-language" class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400 dark:text-slate-500">
                {{ __('resident.experience.language') }}
            </label>
            <select
                id="preference-language"
                wire:model="language"
                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition-colors duration-200 focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-sky-500 dark:focus:ring-sky-500/40"
            >
                @foreach (trans('resident.experience.language_options') as $code => $label)
                    <option value="{{ $code }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col gap-2">
            <span class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400 dark:text-slate-500">
                {{ __('resident.experience.text_size') }}
            </span>
            <div class="flex flex-wrap items-center gap-2">
                @foreach (trans('resident.experience.text_size_options') as $value => $label)
                    @php $active = $textSize === $value; @endphp
                    <button
                        type="button"
                        wire:click="$set('textSize', '{{ $value }}')"
                        class="{{ $active
                            ? 'inline-flex items-center gap-2 rounded-full border border-sky-300 bg-sky-100/80 px-3 py-1.5 text-sky-700 transition-colors duration-200 dark:border-sky-500/60 dark:bg-sky-500/15 dark:text-sky-200'
                            : 'inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-slate-500 transition-colors duration-200 hover:border-sky-200 hover:text-sky-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:hover:border-sky-500 dark:hover:text-sky-200' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
        <div class="flex flex-col gap-2 md:col-span-2">
            <span class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400 dark:text-slate-500">
                {{ __('resident.experience.contrast') }}
            </span>
            <div class="flex flex-wrap items-center gap-2">
                @foreach (trans('resident.experience.contrast_options') as $value => $label)
                    @php $active = $contrast === $value; @endphp
                    <button
                        type="button"
                        wire:click="$set('contrast', '{{ $value }}')"
                        class="{{ $active
                            ? 'inline-flex items-center gap-2 rounded-full border border-emerald-300 bg-emerald-100/80 px-3 py-1.5 text-emerald-700 transition-colors duration-200 dark:border-emerald-500/60 dark:bg-emerald-500/15 dark:text-emerald-200'
                            : 'inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-slate-500 transition-colors duration-200 hover:border-emerald-300 hover:text-emerald-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:hover:border-emerald-400 dark:hover:text-emerald-200' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <div class="flex items-center justify-end gap-3 pt-2">
        <button
            type="submit"
            wire:loading.attr="disabled"
            class="inline-flex items-center gap-2 rounded-full border border-transparent bg-sky-500 px-4 py-2 text-xs font-semibold text-white transition-colors duration-200 hover:bg-sky-600 focus:outline-none focus:ring-2 focus:ring-sky-300 focus:ring-offset-2 focus:ring-offset-white disabled:cursor-not-allowed disabled:opacity-60 dark:bg-sky-500/80 dark:hover:bg-sky-500"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12.75 9 16.5l10-9" />
            </svg>
            {{ __('resident.experience.save') }}
        </button>
    </div>
</form>
