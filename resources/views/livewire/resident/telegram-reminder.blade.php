<div>
    @if ($show)
        <div
            class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/60 backdrop-blur-sm px-4 py-8"
            wire:click.self="dismiss"
        >
            <div class="w-full max-w-md rounded-3xl bg-white/95 p-6 text-slate-700 shadow-xl shadow-slate-900/20 transition-colors duration-200 dark:bg-slate-900 dark:text-slate-100">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-[#0284C7] dark:text-sky-300">{{ __('resident.telegram_reminder.title') }}</p>
                        <h2 class="mt-2 text-xl font-semibold text-slate-900 dark:text-slate-50">{{ __('resident.telegram_reminder.subtitle') }}</h2>
                    </div>
                    <button
                        type="button"
                        wire:click="dismiss"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition-colors duration-200 hover:bg-slate-200 hover:text-slate-700 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700"
                        aria-label="{{ __('resident.telegram_reminder.close_reminder') }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="mt-4 space-y-3 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    <p>{{ __('resident.telegram_reminder.description_1') }}</p>
                    <p>{{ __('resident.telegram_reminder.description_2') }}</p>
                </div>

                <label class="mt-6 flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-xs font-medium text-slate-600 transition-colors duration-200 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-300 dark:hover:bg-slate-800/60">
                    <input type="checkbox" wire:model="remindNextLogin" class="h-4 w-4 rounded border-slate-300 text-[#0284C7] focus:ring-[#0284C7]" />
                    {{ __('resident.telegram_reminder.remind_next_login') }}
                </label>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-between">
                    <button
                        type="button"
                        wire:click="connectNow"
                        class="inline-flex w-full items-center justify-center rounded-full bg-[#0284C7] px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-[#0284C7]/30 transition-colors duration-200 hover:bg-[#0271a9] focus:outline-none focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-2"
                    >
                        {{ __('resident.telegram_reminder.connect_now') }}
                    </button>
                    <button
                        type="button"
                        wire:click="dismiss"
                        class="inline-flex w-full items-center justify-center rounded-full border border-slate-200 px-5 py-2 text-sm font-semibold text-slate-600 transition-colors duration-200 hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-200 focus:ring-offset-2 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800/60 dark:hover:text-slate-100"
                    >
                        {{ __('resident.telegram_reminder.later') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
