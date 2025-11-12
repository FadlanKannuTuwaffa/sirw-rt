@php
    use App\Services\ResidentLanguageService as ResidentLang;

    $ledgerCategoryMap = [
        'kas' => ResidentLang::translate('Kas', 'Cash'),
        'sumbangan' => ResidentLang::translate('Sumbangan', 'Donation'),
        'donasi' => ResidentLang::translate('Donasi', 'Donation'),
        'lainnya' => ResidentLang::translate('Lainnya', 'Other'),
    ];

    $ledgerFlowMap = [
        'kas' => ResidentLang::translate('Kas', 'Cash'),
        'sumbangan' => ResidentLang::translate('Sumbangan', 'Donation'),
        'donasi' => ResidentLang::translate('Donasi', 'Donation'),
        'operasional' => ResidentLang::translate('Operasional', 'Operating fund'),
    ];

    $ledgerNotesMap = [
        'Pembayaran otomatis melalui gateway' => ResidentLang::translate('Pembayaran otomatis melalui gateway', 'Automatic payment via gateway'),
        'Konfirmasi manual oleh pengurus' => ResidentLang::translate('Konfirmasi manual oleh pengurus', 'Manually confirmed by administrators'),
    ];
@endphp

<div class="font-['Inter'] text-slate-700 dark:text-slate-200" data-resident-stack>
    <section class="p-6" data-resident-card data-variant="muted" data-resident-fade data-motion-animated>
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ __('resident.reports.resident_financial_summary') }}</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('resident.reports.data_is_taken_directly_from_the_real_time_ledger') }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-3 text-sm">
                <button type="button" wire:click="exportExcel" class="inline-flex items-center gap-2 rounded-full border border-transparent bg-[#0284C7] px-4 py-2 font-semibold text-white shadow-sm shadow-[#0284C7]/25 transition-colors duration-200 hover:bg-[#0ea5e9] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0284C7]/40 focus-visible:ring-offset-2 focus-visible:ring-offset-white">
                    {{ __('resident.reports.download_excel') }}
                </button>
                <button type="button" wire:click="exportPdf" class="inline-flex items-center gap-2 rounded-full border border-transparent bg-[#22C55E] px-4 py-2 font-semibold text-white shadow-sm shadow-[#22C55E]/25 transition-colors duration-200 hover:bg-[#16A34A] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#22C55E]/40 focus-visible:ring-offset-2 focus-visible:ring-offset-white">
                    {{ __('resident.reports.download_pdf') }}
                </button>
            </div>
        </div>
        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="flex flex-wrap items-center gap-3 text-sm text-slate-600 dark:text-slate-300" data-resident-filter>
                <label for="report-from" class="text-[0.65rem] font-semibold uppercase tracking-[0.28em] text-slate-400 dark:text-slate-500">{{ __('resident.reports.period') }}</label>
                <input type="date" id="report-from" wire:model.live="from" wire:loading.attr="disabled" data-resident-control class="min-w-[12rem]">
                <span class="text-xs font-semibold text-slate-400 dark:text-slate-500">{{ __('resident.reports.to') }}</span>
                <input type="date" wire:model.live="to" wire:loading.attr="disabled" data-resident-control class="min-w-[12rem]">
            </div>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-3" data-resident-fade data-motion-animated>
        <article data-resident-card data-variant="muted" class="p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('resident.reports.total_income') }}</p>
            <p class="mt-3 text-3xl font-semibold text-[#22C55E] dark:text-emerald-300">{{ __('resident.dashboard.currency_rp') }}{{ number_format($totals['income']) }}</p>
        </article>
        <article data-resident-card data-variant="muted" class="p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('resident.reports.total_expense') }}</p>
            <p class="mt-3 text-3xl font-semibold text-rose-600 dark:text-rose-300">{{ __('resident.dashboard.currency_rp') }}{{ number_format($totals['expense']) }}</p>
        </article>
        <article data-resident-card data-variant="muted" class="p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('resident.reports.net_balance') }}</p>
            <p class="mt-3 text-3xl font-semibold {{ $totals['net'] >= 0 ? 'text-slate-900 dark:text-slate-100' : 'text-rose-600 dark:text-rose-300' }}">{{ __('resident.dashboard.currency_rp') }}{{ number_format($totals['net']) }}</p>
        </article>
    </section>

    <section class="p-6" data-resident-card data-variant="muted" data-resident-fade data-motion-animated wire:transition>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('resident.reports.ledger_details') }}</h2>
        <div class="mt-5" data-resident-table>
            <div wire:loading.flex class="min-h-[6rem] items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white/60 text-xs font-semibold uppercase tracking-[0.3em] text-slate-400 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-500">
                {{ __('resident.reports.loading_recap') }}
            </div>
            <div wire:loading.remove>
                <table class="min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left">{{ __('resident.reports.date') }}</th>
                            <th class="px-4 py-3 text-left">{{ __('resident.reports.description') }}</th>
                            <th class="px-4 py-3 text-left">{{ __('resident.reports.category') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('resident.reports.amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($entries as $entry)
                            @php
                                $isIncome = $entry->amount >= 0;
                                $flowRaw = $isIncome ? ($entry->fund_destination ?? null) : ($entry->fund_source ?? null);
                                $flowValue = $flowRaw ?: 'kas';
                                $statusValue = $entry->payment?->status ?? ($entry->status ?? 'pending');
                                $methodValue = $entry->payment?->gateway ?? ($entry->method ?? '-');
                            @endphp
                            <tr>
                                <td class="px-4 py-4 text-xs text-slate-500 dark:text-slate-400">{{ $entry->occurred_at?->translatedFormat('d M Y H:i') }}</td>
                                <td class="px-4 py-4 text-sm text-slate-600 dark:text-slate-300">
                                    <p class="font-semibold text-slate-800 dark:text-slate-100">{{ $entry->bill?->title ?? '-' }}</p>
                                    @if ($entry->fund_reference)
                                        <p class="text-xs text-slate-400 dark:text-slate-500">{{ __('resident.reports.reference') }} {{ $entry->fund_reference }}</p>
                                    @endif
                                    <p class="text-xs text-slate-400 dark:text-slate-500">{{ $entry->notes ?: '-' }}</p>
                                    <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">
                                        {{ __('resident.reports.method') }} {{ strtoupper($methodValue) }} - {{ __('resident.reports.status') }} {{ Str::headline($statusValue) }}
                                    </p>
                                    <p class="text-[11px] text-slate-400 dark:text-slate-500">
                                        {{ $isIncome ? __('resident.reports.into') : __('resident.reports.from') }}: {{ Str::headline($flowValue) }}
                                    </p>
                                </td>
                                <td class="px-4 py-4 text-xs text-slate-500 dark:text-slate-400">{{ Str::headline($entry->category) }}</td>
                                <td class="px-4 py-4 text-right text-sm font-semibold {{ $entry->amount >= 0 ? 'text-emerald-600 dark:text-emerald-300' : 'text-rose-600 dark:text-rose-300' }}">{{ $entry->amount >= 0 ? '+' : '-' }} {{ __('resident.dashboard.currency_rp') }}{{ number_format(abs($entry->amount)) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-xs text-slate-400 dark:text-slate-500">{{ __('resident.reports.no_data_for_this_period') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

