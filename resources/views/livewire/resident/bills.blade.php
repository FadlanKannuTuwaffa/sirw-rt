

<div class="font-['Inter'] text-slate-700 dark:text-slate-200" id="riwayat" data-resident-stack>
    <section class="p-6" data-resident-card data-variant="muted" data-resident-fade data-motion-animated>
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ __('resident.bills.my_bills') }}</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('resident.bills.manage_household_bills') }}</p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row" data-resident-filter>
                <div class="flex flex-col gap-1">
                    <label for="filter-type" class="text-[0.65rem] font-semibold uppercase tracking-[0.28em] text-slate-400 dark:text-slate-500">{{ __('resident.bills.type') }}</label>
                    <select id="filter-type" wire:model.live="type" wire:loading.attr="disabled" data-resident-control class="min-w-[12rem]">
                        <option value="all">{{ __('resident.bills.all_types') }}</option>
                        <option value="iuran">{{ __('resident.bills.monthly_contribution') }}</option>
                        <option value="sumbangan">{{ __('resident.bills.donation') }}</option>
                        <option value="lainnya">{{ __('resident.bills.other') }}</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <label for="filter-status" class="text-[0.65rem] font-semibold uppercase tracking-[0.28em] text-slate-400 dark:text-slate-500">{{ __('resident.bills.status') }}</label>
                    <select id="filter-status" wire:model.live="status" wire:loading.attr="disabled" data-resident-control class="min-w-[12rem]">
                        <option value="all">{{ __('resident.bills.all_statuses') }}</option>
                        <option value="pending">{{ __('resident.bills.pending') }}</option>
                        <option value="unpaid">{{ __('resident.bills.unpaid') }}</option>
                        <option value="paid">{{ __('resident.bills.paid') }}</option>
                        <option value="overdue">{{ __('resident.bills.overdue') }}</option>
                    </select>
                </div>
            </div>
        </div>
    </section>



    @php
        $manualTone = $manual_available ? 'success' : 'danger';
        $manualSummary = $manual_available
            ? trans('resident.bills.available_n_destinations', ['count' => count($manual_destinations)])
            : trans('resident.bills.management_has_not_provided_bank');
        $tripayChannelsCount = max(count($tripay_channels), $tripay_default_channel ? 1 : 0);
        $tripayTone = $tripay_available ? 'info' : 'danger';
        $tripaySummary = $tripay_available
            ? trans('resident.bills.n_tripay_channels_available', ['count' => $tripayChannelsCount])
            : trans('resident.bills.tripay_is_not_enabled_yet');
    @endphp

    @include('livewire.resident.partials.payments-quick-actions', [
        'summary' => $quickSummary,
        'pendingQuick' => $quickPendingPayments,
        'manualAvailable' => $manual_available,
        'manualDestinations' => $manual_destinations,
        'tripayAvailable' => $tripay_available,
        'tripayChannels' => $tripay_channels,
        'tripayDefaultChannel' => $tripay_default_channel,
    ])

    <section class="grid gap-4 md:grid-cols-2" data-resident-fade data-motion-animated>
        <article data-resident-card data-variant="muted" class="p-5">
            <span data-resident-chip data-tone="{{ $manualTone }}">{{ __('resident.bills.manual_transfer') }}</span>
            <h3 class="mt-3 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('resident.bills.confirm_with_proof_upload') }}</h3>
            <p class="mt-2 text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ $manualSummary }}</p>
        </article>
        <article data-resident-card data-variant="muted" class="p-5">
            <span data-resident-chip data-tone="{{ $tripayTone }}">{{ __('resident.bills.automatic_payment') }}</span>
            <h3 class="mt-3 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('resident.bills.checkout_via_tripay') }}</h3>
            <p class="mt-2 text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ $tripaySummary }}</p>
        </article>
    </section>

    <section class="space-y-4" data-resident-fade data-motion-animated wire:transition>
        <div class="flex flex-wrap items-center justify-between gap-3" wire:loading.class="opacity-60">
            <h2 class="text-sm font-semibold uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">{{ __('resident.bills.active_bills') }}</h2>
            @if ($bills->count() > 0 && method_exists($bills, 'total'))
                <span class="text-xs text-slate-400 dark:text-slate-500">{{ __('resident.bills.showing_n_bills', ['count' => number_format($bills->total())]) }}</span>
            @endif
        </div>

        <div wire:loading.flex class="min-h-[6rem] items-center justify-center rounded-3xl border border-dashed border-slate-300 bg-white/70 text-xs font-semibold uppercase tracking-[0.3em] text-slate-400 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-500">
            {{ __('resident.bills.loading_bills') }}
        </div>

        <div wire:loading.remove class="space-y-4">
            @forelse ($bills as $bill)
                @php
                    $statusMeta = match ($bill->status) {
                        'paid' => ['tone' => 'success', 'label' => trans('resident.bills.paid')],
                        'pending' => ['tone' => 'info', 'label' => trans('resident.bills.pending')],
                        'unpaid' => ['tone' => 'danger', 'label' => trans('resident.bills.unpaid')],
                        'overdue' => ['tone' => 'danger', 'label' => trans('resident.bills.overdue')],
                        default => ['tone' => 'neutral', 'label' => Str::headline($bill->status)],
                    };
                    $pendingPayment = $bill->payments->first(fn ($payment) => $payment->status === 'pending');
                    $pendingGatewayLabel = $pendingPayment
                        ? match ($pendingPayment->gateway) {
                            'tripay' => 'Tripay',
                            'manual' => trans('resident.bills.manual_transfer_long'),
                            default => Str::headline($pendingPayment->gateway ?? '-'),
                        }
                        : null;
                @endphp
                <article data-resident-card data-variant="muted" class="p-6">
                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-3">
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $bill->title }}</h3>
                                <span data-resident-chip data-tone="{{ $statusMeta['tone'] }}">{{ $statusMeta['label'] }}</span>
                                <span class="rounded-full border border-slate-200/60 bg-white/70 px-2.5 py-1 text-[0.65rem] font-semibold uppercase tracking-[0.25em] text-slate-400 dark:border-slate-700/60 dark:bg-slate-900/70 dark:text-slate-500">{{ $bill->invoice_number ?? 'INV-'.$bill->id }}</span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ __('resident.bills.due_date') }}: {{ optional($bill->due_date)->translatedFormat('d M Y') ?? __('resident.bills.not_specified') }} - {{ __('resident.bills.status') }}: {{ $statusMeta['label'] }}
                            </p>
                            @if ($bill->description)
                                <p class="text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ Str::limit($bill->description, 160) }}</p>
                            @endif
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">{{ __('resident.bills.total') }}</p>
                            <p class="text-xl font-semibold text-[#0284C7]">{{ __('resident.dashboard.currency_rp') }}{{ number_format($bill->amount) }}</p>
                            @if ($bill->gateway_fee > 0)
                                <p class="text-[11px] text-slate-400 dark:text-slate-500">{{ __('resident.bills.estimated_tripay_fee', ['amount' => number_format($bill->gateway_fee)]) }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="mt-5">
                        @if ($bill->status !== 'paid')
                            @if ($pendingPayment)
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    {{ __('resident.bills.pending_transaction_notice', ['gateway' => $pendingGatewayLabel ?? __('resident.bills.gateway')]) }}
                                </p>
                                <div class="mt-3 flex flex-wrap items-center gap-3">
                                    <button type="button" wire:click="continuePendingPayment({{ $pendingPayment->id }})" wire:loading.attr="disabled" class="inline-flex items-center gap-2 rounded-full border border-transparent bg-[#0284C7] px-5 py-2 text-sm font-semibold text-white shadow-sm shadow-[#0284C7]/25 transition-colors duration-200 hover:bg-[#0ea5e9] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0284C7]/40 focus-visible:ring-offset-1 focus-visible:ring-offset-white">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.5 12h15m0 0-5.25-5.25M19.5 12l-5.25 5.25" />
                                        </svg>
                                        {{ __('resident.bills.continue_transaction') }}
                                    </button>
                                    <button type="button" wire:click="cancelPendingPayment({{ $pendingPayment->id }})" wire:loading.attr="disabled" class="inline-flex items-center gap-2 rounded-full border border-rose-300 bg-white px-5 py-2 text-sm font-semibold text-rose-600 shadow-sm transition-colors duration-200 hover:bg-rose-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-300 focus-visible:ring-offset-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18 18 6M6 6l12 12" />
                                        </svg>
                                        {{ __('resident.bills.cancel_transaction') }}
                                    </button>
                                </div>
                            @else
                                <div class="flex flex-wrap items-center gap-3">
                                    @if ($manual_available)
                                        <button type="button" wire:click="payManual({{ $bill->id }})" wire:loading.attr="disabled" class="inline-flex items-center gap-2 rounded-full border border-slate-200/70 bg-white px-5 py-2 text-sm font-semibold text-slate-600 shadow-sm transition-colors duration-200 hover:bg-sky-50/80 hover:text-[#0284C7] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0284C7]/30 focus-visible:ring-offset-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10.5 12 4l9 6.5M5 11v9h4v-5h6v5h4v-9" />
                                            </svg>
                                            {{ __('resident.bills.manual_transfer') }}
                                        </button>
                                    @else
                                        <button type="button" class="inline-flex cursor-not-allowed items-center gap-2 rounded-full border border-dashed border-slate-300 bg-white px-5 py-2 text-sm font-semibold text-slate-400" disabled>
                                            {{ __('resident.bills.manual_transfer_unavailable') }}
                                        </button>
                                    @endif

                                    @if ($tripay_available)
                                        <button type="button" wire:click="payTripay({{ $bill->id }})" wire:loading.attr="disabled" class="inline-flex items-center gap-2 rounded-full border border-transparent bg-[#0284C7] px-5 py-2 text-sm font-semibold text-white shadow-sm shadow-[#0284C7]/25 transition-colors duration-200 hover:bg-[#0ea5e9] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0284C7]/40 focus-visible:ring-offset-1 focus-visible:ring-offset-white">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7h5l2 4h6l2-4h3M5 17h14M7 17c0 1.105-.895 2-2 2s-2-.895-2-2 .895-2 2-2 2 .895 2 2zm12 0c0 1.105-.895 2-2 2s-2-.895-2-2 .895-2 2-2 2 .895 2 2z" />
                                            </svg>
                                            {{ __('resident.bills.pay_via_tripay') }}
                                        </button>
                                    @else
                                        <button type="button" class="inline-flex cursor-not-allowed items-center gap-2 rounded-full border border-slate-300 bg-slate-100 px-5 py-2 text-sm font-semibold text-slate-400" disabled>
                                            {{ __('resident.bills.tripay_not_available') }}
                                        </button>
                                    @endif
                                </div>
                            @endif
                        @else
                            <a href="{{ route('resident.bills.receipt', $bill->id) }}" data-receipt-link class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-5 py-2 text-sm font-semibold text-emerald-600 transition-colors duration-200 hover:bg-emerald-100">
                                {{ __('resident.bills.payment_receipt') }}
                            </a>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white/70 p-8 text-center text-sm text-slate-400 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-500">
                    {{ __('resident.bills.no_bills_for_now') }}
                </div>
            @endforelse

            <div class="pt-2">
                {{ $bills->links() }}
            </div>
        </div>
    </section>

    <section class="p-6" data-resident-card data-variant="muted" data-resident-fade data-motion-animated wire:transition>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between" wire:loading.class="opacity-60">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('resident.bills.payment_history') }}</h2>
            <p class="text-xs text-slate-400 dark:text-slate-500">{{ __('resident.bills.track_payment_status') }}</p>
        </div>
        <div class="mt-6" data-resident-table>
            <div wire:loading.flex class="min-h-[6rem] items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white/60 text-xs font-semibold uppercase tracking-[0.3em] text-slate-400 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-500">
                {{ __('resident.bills.loading_history') }}
            </div>
            <div wire:loading.remove>
                <table class="min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">{{ __('resident.bills.bill') }}</th>
                            <th class="px-4 py-3 text-left font-semibold">{{ __('resident.bills.total_paid') }}</th>
                            <th class="px-4 py-3 text-left font-semibold">{{ __('resident.bills.method') }}</th>
                            <th class="px-4 py-3 text-left font-semibold">{{ __('resident.bills.status') }}</th>
                            <th class="px-4 py-3 text-left font-semibold">{{ __('resident.bills.date') }}</th>
                            <th class="px-4 py-3 text-left font-semibold">{{ __('resident.bills.proof') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $payment)
                            @php
                                $paymentMeta = match ($payment->status) {
                                    'paid' => ['tone' => 'success', 'label' => trans('resident.bills.paid')],
                                    'pending' => ['tone' => 'info', 'label' => trans('resident.bills.pending')],
                                    'failed' => ['tone' => 'danger', 'label' => trans('resident.bills.failed')],
                                    default => ['tone' => 'neutral', 'label' => Str::headline($payment->status)],
                                };
                            @endphp
                            <tr>
                                <td class="px-4 py-4 text-slate-600 dark:text-slate-300">
                                    <p class="font-semibold">{{ $payment->bill?->title ?? '-' }}</p>
                                    <p class="text-[11px] text-slate-400 dark:text-slate-500">{{ __('resident.bills.invoice') }} {{ $payment->bill?->invoice_number ?? '-' }}</p>
                                </td>
                                <td class="px-4 py-4 text-slate-900 dark:text-slate-200">
                                    <div class="font-semibold">{{ __('resident.dashboard.currency_rp') }}{{ number_format($payment->customer_total ?? ($payment->amount + $payment->fee_amount)) }}</div>
                                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        {{ __('resident.bills.bill_amount') }} {{ __('resident.dashboard.currency_rp') }}{{ number_format($payment->amount) }}
                                        @if ($payment->fee_amount > 0)
                                            <span class="block">{{ __('resident.bills.admin_fee') }} {{ __('resident.dashboard.currency_rp') }}{{ number_format($payment->fee_amount) }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-slate-600 dark:text-slate-300">
                                    {{ $payment->gateway === 'manual' ? trans('resident.bills.manual_transfer') : 'Tripay' }}
                                    @if ($payment->gateway === 'manual' && $payment->manual_channel)
                                        <span class="block text-[11px] text-slate-400 dark:text-slate-500">{{ __('resident.bills.channel') }} {{ $payment->manual_channel }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-slate-600 dark:text-slate-300">
                                    @if ($payment->status === 'pending')
                                        <button type="button" wire:click="showPendingPayment({{ $payment->id }})" class="inline-flex items-center rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-300">
                                            <span data-resident-chip data-tone="{{ $paymentMeta['tone'] }}" class="pointer-events-none">{{ $paymentMeta['label'] }}</span>
                                        </button>
                                    @else
                                        <span data-resident-chip data-tone="{{ $paymentMeta['tone'] }}">{{ $paymentMeta['label'] }}</span>
                                    @endif
                                    @if ($payment->status === 'failed')
                                        @php
                                            $reason = data_get($payment->raw_payload, 'manual_validation.notes');
                                        @endphp
                                        @if ($reason)
                                            <span class="mt-2 block text-[11px] text-rose-500">{{ __('resident.bills.reason') }} {{ $reason }}</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-xs text-slate-500 dark:text-slate-400">
                                    <p>{{ __('resident.bills.created') }} {{ optional($payment->created_at)->translatedFormat('d M Y H:i') ?? '-' }}</p>
                                    <p>{{ __('resident.bills.confirmed') }} {{ optional($payment->paid_at)->translatedFormat('d M Y H:i') ?? '-' }}</p>
                                </td>
                                <td class="px-4 py-4 text-xs text-slate-500 dark:text-slate-400">
                                    @if ($payment->status === 'paid')
                                        <div class="flex flex-wrap gap-2">
                                            <a href="{{ route('resident.bills.receipt', $payment->bill_id) }}" data-receipt-link class="inline-flex items-center gap-2 rounded-full border border-transparent bg-[#0284C7]/15 px-3 py-1.5 font-semibold text-[#0284C7] transition-colors duration-200 hover:bg-[#0284C7] hover:text-white">{{ __('resident.bills.view') }}</a>
                                            <a href="{{ route('resident.bills.receipt.download', $payment->bill_id) }}" class="inline-flex items-center gap-2 rounded-full border border-transparent bg-[#22C55E]/15 px-3 py-1.5 font-semibold text-[#22C55E] transition-colors duration-200 hover:bg-[#22C55E] hover:text-white">{{ __('resident.bills.download') }}</a>
                                        </div>
                                    @elseif ($payment->gateway === 'manual' && $payment->manual_proof_path)
                                        <span class="inline-flex items-center rounded-full border border-amber-300/50 bg-amber-50 px-3 py-1.5 text-[11px] font-semibold text-amber-500 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200">
                                            {{ __('resident.bills.proof_awaiting_verification') }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-xs text-slate-400 dark:text-slate-500">{{ __('resident.bills.no_payments_yet') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-6" wire:loading.remove>
            {{ $payments->links() }}
        </div>
    </section>
</div>

@if ($showPaymentModal && !empty($modalPaymentData))
    @php
        $paymentData = $modalPaymentData;
        $billData = $paymentData['bill'] ?? [];
        $isManual = ($paymentData['gateway'] ?? '') === 'manual';
        $isTripay = ($paymentData['gateway'] ?? '') === 'tripay';
        $manualDestination = $paymentData['manual_destination'] ?? [];
        $rawPayload = $paymentData['raw_payload'] ?? [];
        $tripayCheckoutUrl = data_get($rawPayload, 'response.data.checkout_url') ?? data_get($rawPayload, 'checkout.checkout_url');
        $createdAtIso = $paymentData['created_at'] ?? null;
        $createdAtDisplay = $createdAtIso ? \Illuminate\Support\Carbon::parse($createdAtIso)->translatedFormat('d M Y H:i') : null;
    @endphp
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 px-4 py-8 backdrop-blur-sm" wire:click.self="closePaymentModal" wire:keydown.escape.window="closePaymentModal">
        <div class="relative w-full max-w-2xl rounded-3xl border border-slate-200/70 bg-white shadow-xl shadow-slate-200/80 dark:border-slate-700 dark:bg-slate-900 dark:shadow-slate-900/50" wire:key="resident-pending-payment-modal">
            <button type="button" wire:click="closePaymentModal" class="absolute right-5 top-5 inline-flex h-10 w-10 items-center justify-center rounded-full border border-transparent text-slate-500 transition-colors duration-200 hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-300 dark:text-slate-400 dark:hover:bg-slate-800/80 dark:hover:text-slate-200" aria-label="{{ __('resident.bills.close') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
            <div class="space-y-6 p-8">
                <header class="flex flex-col gap-2">
                    <span data-resident-chip data-tone="info">{{ __('resident.bills.pending_payment') }}</span>
                    <h3 class="text-xl font-semibold text-slate-900 dark:text-slate-100">{{ $billData['title'] ?? __('resident.bills.bill') }}</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('resident.bills.invoice') }} {{ $billData['invoice_number'] ?? 'INV-' . ($billData['id'] ?? '-') }}</p>
                </header>

                <section class="grid gap-4 md:grid-cols-2">
                    <article class="rounded-2xl border border-slate-200/70 bg-slate-50/70 p-4 dark:border-slate-700 dark:bg-slate-900/70">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">{{ __('resident.bills.method') }}</p>
                        <p class="mt-1 text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $isManual ? __('resident.bills.manual_transfer') : ($isTripay ? 'Tripay' : Str::headline($paymentData['gateway'] ?? '-')) }}</p>
                        @if ($isManual && !empty($manualDestination))
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                {{ __('resident.bills.destination') }} {{ $manualDestination['label'] ?? ($manualDestination['account_name'] ?? '-') }}<br>
                                {{ __('resident.bills.account') }} {{ $manualDestination['account_number'] ?? '-' }}
                            </p>
                        @elseif ($isTripay && $tripayCheckoutUrl)
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                {{ __('resident.bills.you_can_proceed_to_tripay') }}
                            </p>
                        @endif
                    </article>
                    <article class="rounded-2xl border border-slate-200/70 bg-slate-50/70 p-4 dark:border-slate-700 dark:bg-slate-900/70">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">{{ __('resident.bills.summary') }}</p>
                        <dl class="mt-2 space-y-1 text-sm text-slate-600 dark:text-slate-300">
                            <div class="flex items-center justify-between">
                                <dt>{{ __('resident.bills.bill_amount') }}</dt>
                                <dd class="font-semibold text-slate-800 dark:text-slate-100">{{ __('resident.dashboard.currency_rp') }}{{ number_format($paymentData['amount'] ?? 0) }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt>{{ __('resident.bills.admin_fee') }}</dt>
                                <dd>{{ __('resident.dashboard.currency_rp') }}{{ number_format($paymentData['fee_amount'] ?? 0) }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt>{{ __('resident.bills.total_paid') }}</dt>
                                <dd class="font-semibold text-[#0284C7]">{{ __('resident.dashboard.currency_rp') }}{{ number_format($paymentData['customer_total'] ?? 0) }}</dd>
                            </div>
                        </dl>
                    </article>
                </section>

                <section class="rounded-2xl border border-slate-200/70 bg-white/90 p-4 text-xs leading-relaxed text-slate-500 shadow-inner shadow-slate-100 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300">
                    <p class="font-semibold text-slate-600 dark:text-slate-200">{{ __('resident.bills.status') }}</p>
                    <p class="mt-1">{{ __('resident.bills.payment_created_on') }} {{ $createdAtDisplay ?? '-' }} {{ __('resident.bills.and_is_still_awaiting_your_action') }}</p>
                    @if ($isTripay && $tripayCheckoutUrl)
                        <p class="mt-2">{{ __('resident.bills.if_tripay_expires') }}</p>
                    @endif
                    @if ($isManual)
                        <p class="mt-2">{{ __('resident.bills.please_complete_transfer') }}</p>
                    @endif
                </section>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="cancelPendingPayment" wire:loading.attr="disabled" class="inline-flex items-center justify-center rounded-full border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-600 transition-colors duration-200 hover:bg-rose-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-300 dark:border-rose-500/40 dark:bg-rose-500/10 dark:text-rose-200">
                        {{ __('resident.bills.cancel_payment') }}
                    </button>
                    <button type="button" wire:click="continuePendingPayment" wire:loading.attr="disabled" class="inline-flex items-center justify-center rounded-full bg-[#0284C7] px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-[#0284C7]/30 transition-colors duration-200 hover:bg-[#0ea5e9] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0284C7]/40 focus-visible:ring-offset-2 focus-visible:ring-offset-white">
                        {{ __('resident.bills.continue_payment') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

@once
    @push('scripts')
        <script>
            (function () {
                const SELECTOR = '[data-receipt-link]';

                const openInWindow = (event) => {
                    if (event && typeof event.preventDefault === 'function') {
                        event.preventDefault();
                    }

                    const link = event.currentTarget;
                    if (!link) {
                        return;
                    }

                    const url = link.getAttribute('href');
                    if (!url) {
                        return;
                    }

                    window.location.href = url;
                };

                const bindLinks = () => {
                    document.querySelectorAll(SELECTOR).forEach((link) => {
                        if (link.dataset.receiptBound === '1') {
                            return;
                        }
                        link.addEventListener('click', openInWindow, { capture: true });
                        link.dataset.receiptBound = '1';
                        link.removeAttribute('target');
                    });
                };

                if (typeof document !== 'undefined') {
                    document.addEventListener('DOMContentLoaded', bindLinks);
                    document.addEventListener('livewire:load', bindLinks);
                }

                if (typeof window !== 'undefined' && window.Livewire) {
                    window.Livewire.hook('message.processed', bindLinks);
                }
            })();
        </script>
    @endpush
@endonce

