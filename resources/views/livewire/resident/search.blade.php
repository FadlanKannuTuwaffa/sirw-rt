<div class="font-['Inter'] text-slate-700 dark:text-slate-200" data-resident-stack>
    <section class="p-6" data-resident-card data-variant="muted" data-resident-fade data-motion-animated>
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="space-y-2">
                {{-- <p class="text-[0.65rem] font-semibold uppercase tracking-[0.35em] text-slate-400 dark:text-slate-500"><!-- {{ __('resident.search.global_search') }} --></p> --}}
                {{-- <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100"><!-- {{ __('resident.search.find_important_data_quickly') }} --></h1> --}}
                <p class="max-w-xl text-xs text-slate-500 dark:text-slate-400">
                    <!-- {{ __('resident.search.search_information_prompt') }} -->
                </p>
            </div>
            <div class="relative w-full max-w-lg">
                <input
                    wire:model.live.debounce.300ms="q"
                    wire:loading.attr="disabled"
                    type="search"
                    placeholder="{{ __('resident.search.search_placeholder') }}"
                    data-resident-control
                    class="w-full pl-12"
                >
                <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-[#0284C7]/70 dark:text-sky-300/80" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 3.478 9.772l3.875 3.875a.75.75 0 1 0 1.06-1.06l-3.875-3.875A5.5 5.5 0 0 0 9 3.5Zm-4 5.5a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z" clip-rule="evenodd" />
                </svg>
            </div>
        </div>
        <div class="mt-6 flex flex-wrap items-center gap-4 text-xs">
            @if ($term === '')
                <span class="inline-flex items-center gap-2 rounded-full bg-sky-100/60 px-4 py-1.5 font-semibold text-[#0284C7] dark:bg-slate-800/70 dark:text-sky-300">
                    {{ __('resident.search.enter_keyword_to_start_search') }}
                </span>
            @else
                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-100/70 px-4 py-1.5 font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200">
                    {{ __('resident.search.found_n_results_for', ['count' => number_format($totalResults), 'term' => $term]) }}
                </span>
                <a href="{{ route('resident.search') }}" class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-4 py-1.5 font-semibold text-slate-500 transition-colors duration-200 hover:border-sky-300 hover:bg-slate-50/80 hover:text-sky-600 dark:border-slate-700/60 dark:text-slate-300 dark:hover:border-sky-500/40 dark:hover:bg-slate-800/60 dark:hover:text-sky-300">
                    {{ __('resident.search.reset_search') }}
                </a>
            @endif
        </div>
    </section>

    <section class="space-y-8" data-resident-fade data-motion-animated wire:transition>
        <div wire:loading.flex class="min-h-[6rem] items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white/60 text-xs font-semibold uppercase tracking-[0.3em] text-slate-400 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-500">
            {{ __('resident.search.loading_results') }}
        </div>
        <div wire:loading.remove>
            @if ($term === '')
                <div data-resident-card data-variant="muted" class="p-10 text-center text-sm text-slate-500 dark:text-slate-400">
                    {{ __('resident.search.start_by_typing_a_keyword') }}
                </div>
            @elseif ($totalResults === 0)
                <div data-resident-card data-variant="muted" class="p-10 text-center text-sm font-semibold text-rose-500 dark:text-rose-200">
                    {{ __('resident.search.no_data_found_for', ['term' => $term]) }}
                </div>
            @else
                @if ($bills->isNotEmpty())
                <div data-resident-card data-variant="muted" class="p-6">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('resident.search.your_bills') }}</h2>
                        <a href="{{ route('resident.bills') }}" class="text-xs font-semibold text-[#0284C7] transition-colors hover:text-[#0369A1] dark:text-sky-300 dark:hover:text-sky-400">{{ __('resident.search.see_all') }}</a>
                    </div>
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        @foreach ($bills as $bill)
                            <article data-resident-card data-variant="muted" class="p-5">
                                <div class="flex items-center justify-between gap-3 text-xs uppercase tracking-wide text-slate-400 dark:text-slate-500">
                                    <span>{{ $bill->invoice_number }}</span>
                                    <span class="rounded-full bg-slate-100/70 px-3 py-1 font-semibold text-[#0284C7] dark:bg-slate-800/70 dark:text-sky-300">{{ Str::headline($bill->status) }}</span>
                                </div>
                                <h3 class="mt-3 text-base font-semibold text-slate-900 dark:text-slate-100">{{ $bill->title }}</h3>
                                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('resident.search.due_date') }} {{ $bill->due_date?->translatedFormat('d M Y') }}</p>
                                <p class="mt-2 text-sm font-semibold text-[#0284C7] dark:text-sky-300">{{ __('resident.dashboard.currency_rp') }}{{ number_format($bill->payable_amount) }}</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ __('resident.search.bill_amount') }} {{ __('resident.dashboard.currency_rp') }}{{ number_format($bill->amount) }}
                                    @if ($bill->gateway_fee > 0)
                                        - {{ __('resident.search.admin_fee') }} {{ __('resident.dashboard.currency_rp') }}{{ number_format($bill->gateway_fee) }}
                                    @endif
                                </p>
                                @if ($bill->description)
                                    <p class="mt-2 text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ Str::limit($bill->description, 120) }}</p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($payments->isNotEmpty())
                <div data-resident-card data-variant="muted" class="p-6">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('resident.search.payment_history') }}</h2>
                        <a href="{{ route('resident.bills') }}#riwayat" class="text-xs font-semibold text-[#22C55E] transition-colors hover:text-[#16A34A] dark:text-emerald-300 dark:hover:text-emerald-200">{{ __('resident.search.view_history') }}</a>
                    </div>
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        @foreach ($payments as $payment)
                            <article data-resident-card data-variant="muted" class="p-5 text-sm text-slate-600 dark:text-slate-300">
                                <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $payment->bill?->title }}</p>
                                <p>{{ __('resident.search.amount') }} {{ __('resident.dashboard.currency_rp') }}{{ number_format($payment->customer_total ?? ($payment->amount + $payment->fee_amount)) }}</p>
                                <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                                    {{ __('resident.search.bill_amount') }} {{ __('resident.dashboard.currency_rp') }}{{ number_format($payment->amount) }}
                                    @if ($payment->fee_fee_amount > 0)
                                        - {{ __('resident.search.admin_fee') }} {{ __('resident.dashboard.currency_rp') }}{{ number_format($payment->fee_amount) }}
                                    @endif
                                </p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $payment->paid_at?->translatedFormat('d M Y H:i') }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($agendas->isNotEmpty())
                <div data-resident-card data-variant="muted" class="p-6">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('resident.search.resident_agenda') }}</h2>
                        <a href="{{ route('resident.dashboard') }}" class="text-xs font-semibold text-[#0284C7] transition-colors hover:text-[#0369A1] dark:text-sky-300 dark:hover:text-sky-400">{{ __('resident.search.see_all_agendas') }}</a>
                    </div>
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        @foreach ($agendas as $event)
                            <article data-resident-card data-variant="muted" class="p-5">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ $event->start_at?->translatedFormat('l, d M Y H:i') }}</p>
                                <h3 class="mt-2 text-base font-semibold text-slate-900 dark:text-slate-100">{{ $event->title }}</h3>
                                @if ($event->description)
                                    <p class="mt-2 text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ Str::limit($event->description, 140) }}</p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($residents->isNotEmpty())
                <div data-resident-card data-variant="muted" class="p-6">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('resident.search.resident_data') }}</h2>
                        <a href="{{ route('resident.directory') }}" class="text-xs font-semibold text-[#0284C7] transition-colors hover:text-[#0369A1] dark:text-sky-300 dark:hover:text-sky-400">{{ __('resident.search.view_directory') }}</a>
                    </div>
                    <div class="mt-4 space-y-3">
                        @if (! $sensitiveUnlocked)
                            <div class="rounded-2xl border border-dashed border-amber-300/60 bg-amber-50/70 p-4 text-xs text-amber-700 dark:border-amber-400/40 dark:bg-amber-500/10 dark:text-amber-200">
                                <p class="font-semibold uppercase tracking-[0.28em] text-amber-600 dark:text-amber-200">{{ __('resident.search.sensitive_details_locked') }}</p>
                                <p class="mt-2 leading-relaxed text-amber-700/80 dark:text-amber-100/80">
                                    {{ __('resident.search.to_view_full_address_and_contact') }}
                                </p>
                                @if ($showUnlockForm)
                                    <form wire:submit.prevent="unlockSensitive" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                                        <div class="flex-1">
                                            <label class="text-[0.65rem] font-semibold uppercase tracking-[0.3em] text-amber-600/80 dark:text-amber-200/80">{{ __('resident.search.account_password') }}</label>
                                            <input type="password" wire:model.defer="unlockPassword" autocomplete="current-password" data-resident-control class="mt-2 bg-white focus:ring-amber-400 dark:bg-slate-900">
                                            @error('unlockPassword') <p class="mt-2 text-[0.68rem] font-semibold text-rose-500 dark:text-rose-300">{{ $message }}</p> @enderror
                                        </div>
                                        <div class="flex gap-2">
                                            <button type="submit" class="inline-flex items-center justify-center rounded-full bg-amber-500 px-4 py-2 text-xs font-semibold text-white transition-colors duration-200 hover:bg-amber-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/60 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:bg-amber-400 dark:text-amber-950 dark:hover:bg-amber-300 dark:focus-visible:ring-amber-300/60 dark:focus-visible:ring-offset-slate-900">
                                                {{ __('resident.search.confirm_password') }}
                                            </button>
                                            <button type="button" wire:click="cancelUnlock" class="inline-flex items-center justify-center rounded-full border border-amber-300 px-4 py-2 text-xs font-semibold text-amber-600 transition-colors duration-200 hover:border-amber-400 hover:bg-amber-50/70 hover:text-amber-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-amber-400/40 dark:text-amber-200 dark:hover:border-amber-300/60 dark:hover:bg-amber-400/10 dark:hover:text-amber-100 dark:focus-visible:ring-amber-300/60 dark:focus-visible:ring-offset-slate-900">
                                                {{ __('resident.search.cancel') }}
                                            </button>
                                        </div>
                                    </form>
                                @else
                                    <button type="button" wire:click="requestUnlock" class="mt-4 inline-flex items-center gap-2 rounded-full bg-amber-500/90 px-4 py-2 text-xs font-semibold text-white transition-colors duration-200 hover:bg-amber-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/70 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:bg-amber-400/80 dark:text-amber-950 dark:hover:bg-amber-300 dark:focus-visible:ring-amber-300/60 dark:focus-visible:ring-offset-slate-900">
                                        {{ __('resident.search.confirm_password_to_unlock_details') }}
                                    </button>
                                @endif
                            </div>
                        @else
                            <div class="rounded-2xl border border-emerald-300/60 bg-emerald-50/70 p-4 text-xs text-emerald-700 dark:border-emerald-400/40 dark:bg-emerald-500/10 dark:text-emerald-200">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="font-semibold uppercase tracking-[0.28em] text-emerald-600 dark:text-emerald-200">{{ __('resident.search.sensitive_details_unlocked') }}</p>
                                        <p class="mt-1 leading-relaxed text-emerald-700/80 dark:text-emerald-100/80">{{ __('resident.search.full_address_and_phone_number_are_now_visible') }}</p>
                                    </div>
                                    <button type="button" wire:click="lockSensitive" class="inline-flex items-center justify-center rounded-full border border-emerald-300 px-4 py-2 text-xs font-semibold text-emerald-600 transition-colors duration-200 hover:border-emerald-400 hover:bg-emerald-50/70 hover:text-emerald-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/60 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-emerald-400/40 dark:text-emerald-200 dark:hover:border-emerald-300/60 dark:hover:bg-emerald-400/10 dark:hover:text-emerald-100 dark:focus-visible:ring-emerald-300/60 dark:focus-visible:ring-offset-slate-900">
                                        {{ __('resident.search.lock_again') }}
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        @foreach ($residents as $resident)
                            <article data-resident-card data-variant="muted" class="p-5">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[#0284C7]/10 text-xs font-semibold uppercase text-[#0284C7] shadow-inner shadow-[#0284C7]/20 dark:bg-sky-500/20 dark:text-sky-200">
                                        {{ Str::of($resident->name)->trim()->explode(' ')->map(fn ($part) => Str::substr($part, 0, 1))->take(2)->implode('') }}
                                    </div>
                                    <div>
                                        <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ $resident->name }}</h3>
                                        <p class="text-[0.7rem] uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">{{ Str::headline($resident->status) }}</p>
                                    </div>
                                </div>
                                <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
                                    {{ __('resident.search.address') }}
                                    @if ($sensitiveUnlocked)
                                        {{ $resident->alamat ?: '-' }}
                                    @else
                                        <span class="font-semibold text-slate-500/70 dark:text-slate-300/80">{{ __('resident.search.locked') }}</span>
                                    @endif
                                </p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ __('resident.search.phone') }}
                                    @if ($sensitiveUnlocked)
                                        {{ $resident->phone ?: '-' }}
                                    @else
                                        {{ $resident->masked_phone ?: '-' }}
                                    @endif
                                </p>
                            </article>
                        @endforeach
                    </div>
                </div>
                @endif
            @endif
        </div>
    </section>
</div>
