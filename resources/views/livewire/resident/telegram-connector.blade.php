<div
    data-resident-card
    data-variant="muted"
    class="p-6 transition-[background-color,border-color,box-shadow,color] duration-300"
    data-motion-animated
>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-[color:var(--text)] transition-colors duration-300">{{ __('resident.telegram.telegram_integration') }}</h2>
            <p class="text-xs text-[color:var(--text-3)] transition-colors duration-300">{{ __('resident.telegram.get_bill_reminders_directly_from_the_rt_telegram_bot') }}</p>
        </div>
        @if ($isConnected)
            <span class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-600 transition-colors duration-300 dark:bg-emerald-500/15 dark:text-emerald-300">
                <span class="h-2 w-2 rounded-full bg-emerald-500 dark:bg-emerald-300"></span>
                {{ __('resident.telegram.connected') }}
            </span>
        @else
            <span class="inline-flex items-center gap-2 rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-600 transition-colors duration-300 dark:bg-rose-500/15 dark:text-rose-300">
                <span class="h-2 w-2 rounded-full bg-rose-500 dark:bg-rose-300"></span>
                {{ __('resident.telegram.not_connected') }}
            </span>
        @endif
    </div>

    @if ($isConnected && $account)
        <div class="mt-5 space-y-4 text-sm text-[color:var(--text-2)] transition-colors duration-300">
            <p>{{ __('resident.telegram.your_telegram_is_already_connected') }}
                @if ($account['username'])
                    {{ __('resident.telegram.username') }} <span class="font-semibold text-[color:var(--text)] transition-colors duration-300">{{ '@' . $account['username'] }}</span>.
                @endif
            </p>
            @if ($account['linked_at'])
                <p>{{ __('resident.telegram.connected_since') }} {{ $account['linked_at'] }}.</p>
            @endif
            <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 text-xs text-slate-600 transition-[background-color,border-color,color] duration-300 dark:border-slate-700 dark:bg-slate-900/60 dark:text-[color:var(--resident-text-muted)]">
                <p class="font-semibold text-slate-700 transition-colors duration-300 dark:text-[color:var(--resident-text-secondary)]">{{ __('resident.telegram.what_can_the_bot_do') }}</p>
                <ul class="mt-2 space-y-1 list-disc pl-5 text-[color:var(--text-3)] transition-colors duration-300 dark:text-[color:var(--resident-text-muted)]">
                    <li>{{ __('resident.telegram.view_latest_bills_with_bills_command') }} <span class="font-semibold">/bills</span>.</li>
                    <li>{{ __('resident.telegram.check_specific_bill_details_with_bill_id_command') }} <span class="font-semibold">/bill &lt;ID&gt;</span>.</li>
                    <li>{{ __('resident.telegram.set_language_with_lang_id_or_lang_en_command') }} <span class="font-semibold">/lang id</span> {{ __('resident.telegram.or') }} <span class="font-semibold">/lang en</span>.</li>
                </ul>
            </div>
            @if (! $notificationsEnabled)
                <div class="rounded-2xl border border-amber-200 bg-amber-50/80 p-4 text-xs text-amber-700 transition-[background-color,border-color,color] duration-300 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200">
                    {{ __('resident.telegram.telegram_notifications_are_disabled') }}
                </div>
            @endif
        </div>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
            @if (! $notificationsEnabled)
                <button type="button" wire:click="enableNotifications" wire:loading.attr="disabled"
                        class="inline-flex items-center justify-center rounded-full bg-[#0284C7] px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#0284C7]/40 transition-colors duration-200 hover:bg-[#0ea5e9]">
                    <span wire:loading.remove>{{ __('resident.telegram.enable_telegram_reminders') }}</span>
                    <span wire:loading>{{ __('resident.telegram.enabling') }}</span>
                </button>
            @endif
            <button type="button" wire:click="disconnect" wire:loading.attr="disabled" wire:target="disconnect"
                    class="inline-flex items-center justify-center gap-2 rounded-full bg-rose-500 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-rose-200/60 transition-colors duration-200 hover:bg-rose-600 disabled:cursor-not-allowed disabled:opacity-80">
                <span>{{ __('resident.telegram.disconnect') }}</span>
                <svg wire:loading wire:target="disconnect" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle class="opacity-25" cx="12" cy="12" r="10" />
                    <path class="opacity-75" d="M12 2a10 10 0 0 1 10 10" />
                </svg>
            </button>
        </div>
    @else
        <div class="mt-5 space-y-5 text-sm text-[color:var(--text-2)] transition-colors duration-300">
            <p>{{ __('resident.telegram.connect_your_telegram_account_to_receive_due_reminders') }}</p>
            <ol class="space-y-2 rounded-2xl border border-slate-200 bg-slate-50/70 p-4 text-xs text-slate-600 transition-[background-color,border-color,color] duration-300 dark:border-slate-700 dark:bg-slate-900/60 dark:text-[color:var(--resident-text-muted)]">
                <li>{{ __('resident.telegram.press_the_generate_link_code_button_below') }}</li>
                <li>{{ __('resident.telegram.open_the_telegram_app_and_search_for_your_rt_bot') }}</li>
                <li>{{ __('resident.telegram.type_the_command_start_link_xxxx_and_send_the_code_that_appears') }}</li>
            </ol>
            <button type="button" wire:click="generateToken" wire:loading.attr="disabled"
                    class="inline-flex items-center justify-center rounded-full bg-[#0284C7] px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-[#0284C7]/40 transition-colors duration-200 hover:bg-[#0ea5e9]">
                <span wire:loading.remove>{{ __('resident.telegram.generate_link_code') }}</span>
                <span wire:loading>{{ __('resident.telegram.preparing') }}</span>
            </button>
            @if ($linkToken)
                <div class="rounded-2xl border border-[#0284C7]/20 bg-[#0284C7]/5 p-4 text-center text-xs font-semibold tracking-[0.3em] text-[#0369A1] transition-[background-color,border-color,color] duration-300 dark:border-[#38BDF8]/40 dark:bg-[#38BDF8]/10 dark:text-sky-200">
                    {{ $linkToken }}
                </div>
                <p class="text-xs text-[color:var(--text-3)] transition-colors duration-300">{{ __('resident.telegram.send_the_code_above_to_the_telegram_bot_within_30_minutes') }}</p>
            @endif
        </div>
    @endif
</div>
