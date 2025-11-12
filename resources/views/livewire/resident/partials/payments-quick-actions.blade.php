@php
    use App\Services\ResidentLanguageService as ResidentLang;

    $summary = $summary ?? [];
    $pendingQuick = $pendingQuick ?? [];
    $highlight = $summary['highlight_bill'] ?? null;
    $topBills = collect($summary['top_bills'] ?? []);
    $totalOutstanding = (int) ($summary['total_outstanding'] ?? 0);
    $outstandingCount = (int) ($summary['outstanding_count'] ?? 0);
    $primaryMethod = $tripayAvailable
        ? 'tripay'
        : ($manualAvailable
            ? 'manual'
            : null);
    $manualDefault = $manualDestinations[0] ?? null;
    $manualLabel = $manualDefault['label'] ?? ($manualDefault['bank_name'] ?? ($manualDefault['name'] ?? null));
    $manualAccount = $manualDefault['account_number'] ?? ($manualDefault['number'] ?? null);
    $manualHolder = $manualDefault['account_name'] ?? ($manualDefault['holder'] ?? null);
    $manualCopyText = collect([$manualLabel, $manualAccount, $manualHolder])->filter()->implode(' - ');

    $resolveTripay = static function (?string $code, array $channels) {
        if (! $code) {
            return null;
        }
        foreach ($channels as $channel) {
            if (is_array($channel) && ($channel['code'] ?? null) === $code) {
                return $channel;
            }
        }
        return null;
    };

    $tripayDefault = $resolveTripay($tripayDefaultChannel, $tripayChannels);
    if (! $tripayDefault && ! empty($tripayChannels)) {
        $tripayDefault = is_array($tripayChannels[0])
            ? $tripayChannels[0]
            : ['name' => (string) $tripayChannels[0]];
    }
@endphp

@if ($highlight || $outstandingCount > 0 || ! empty($pendingQuick))
    <section class="mt-6 space-y-4" data-resident-fade data-motion-animated>
        <div class="grid gap-4 md:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
            <article class="flex h-full flex-col justify-between rounded-2xl border border-slate-200/70 bg-gradient-to-br from-sky-500/15 via-white to-white p-5 shadow-sm transition-colors duration-300 dark:border-slate-700/60 dark:from-sky-500/10 dark:via-slate-900 dark:to-slate-900/90" data-resident-card>
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-[0.65rem] font-semibold uppercase tracking-[0.28em] text-slate-400 dark:text-slate-500">
                            {{ $outstandingCount > 0
                                ? ResidentLang::translate('Tagihan utama', 'Primary bill')
                                : ResidentLang::translate('Semua tagihan lunas', 'All bills are settled') }}
                        </p>
                        @if ($highlight)
                            <h3 class="mt-2 text-xl font-semibold text-slate-900 dark:text-slate-100">{{ $highlight['title'] }}</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Rp {{ $highlight['amount_formatted'] }}
                                @if ($highlight['due_label'])
                                    • {{ $highlight['due_label'] }}
                                @endif
                            </p>
                        @else
                            <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">
                                {{ ResidentLang::translate('Belum ada tagihan yang perlu ditindaklanjuti sekarang.', 'No bills require action right now.') }}
                            </p>
                        @endif
                    </div>
                    <div class="text-right text-xs text-slate-500 dark:text-slate-400">
                        <p>{{ ResidentLang::translate('Total aktif', 'Total active') }}</p>
                        <p class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                            Rp {{ number_format($totalOutstanding) }}
                        </p>
                        <p>
                            {{ ResidentLang::translateWithReplacements(':count tagihan', ':count bill(s)', ['count' => $outstandingCount]) }}
                        </p>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    @if ($highlight && $primaryMethod === 'tripay')
                        <button
                            type="button"
                            wire:click="payTripay({{ $highlight['id'] }})"
                            class="inline-flex items-center gap-2 rounded-full bg-sky-500 px-4 py-2 text-xs font-semibold text-white transition-colors duration-200 hover:bg-sky-600 focus:outline-none focus:ring-2 focus:ring-sky-300"
                        >
                            {{ ResidentLang::translate('Bayar cepat (Tripay)', 'Quick pay (Tripay)') }}
                        </button>
                    @elseif ($highlight && $primaryMethod === 'manual')
                        <button
                            type="button"
                            wire:click="payManual({{ $highlight['id'] }})"
                            class="inline-flex items-center gap-2 rounded-full bg-emerald-500 px-4 py-2 text-xs font-semibold text-white transition-colors duration-200 hover:bg-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-300"
                        >
                            {{ ResidentLang::translate('Bayar cepat (Manual)', 'Quick pay (Manual)') }}
                        </button>
                    @else
                        <button
                            type="button"
                            class="inline-flex cursor-not-allowed items-center gap-2 rounded-full bg-slate-200 px-4 py-2 text-xs font-semibold text-slate-500"
                            disabled
                        >
                            Bayar cepat tidak tersedia
                        </button>
                    @endif
                    <a
                        href="#riwayat"
                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors duration-200 hover:border-sky-200 hover:text-sky-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-sky-500"
                    >
                        Lihat semua tagihan
                    </a>
                </div>
            </article>
            <article class="flex h-full flex-col gap-3 rounded-2xl border border-slate-200/70 bg-white p-5 shadow-sm transition-colors duration-300 dark:border-slate-700/60 dark:bg-slate-900/80" data-resident-card>
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[0.65rem] font-semibold uppercase tracking-[0.28em] text-slate-400 dark:text-slate-500">Pembayaran menunggu</span>
                    @if (count($pendingQuick))
                        <span class="rounded-full bg-amber-100 px-2 py-[2px] text-[10px] font-semibold text-amber-700 dark:bg-amber-500/20 dark:text-amber-200">
                            {{ count($pendingQuick) }}
                        </span>
                    @endif
                </div>
                @if (empty($pendingQuick))
                    <p class="text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                        Tidak ada pembayaran menunggu konfirmasi.
                    </p>
                @else
                    <ul class="space-y-3 text-xs text-slate-600 dark:text-slate-300">
                        @foreach ($pendingQuick as $pending)
                            <li class="flex items-start justify-between gap-2 rounded-xl border border-slate-200/70 bg-slate-50 px-3 py-2 dark:border-slate-700/50 dark:bg-slate-900/60">
                                <div>
                                    <p class="font-semibold text-slate-800 dark:text-slate-100">
                                        {{ $pending['bill_title'] ?? 'Tagihan' }} • {{ $pending['gateway_label'] }}
                                    </p>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                        Rp {{ $pending['amount_formatted'] }} • {{ $pending['created_label'] }}
                                    </p>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <button
                                        type="button"
                                    wire:click="continuePendingPayment({{ $pending['id'] }})"
                                    class="inline-flex items-center gap-2 rounded-full bg-sky-500 px-3 py-1 text-[10px] font-semibold text-white transition-colors duration-200 hover:bg-sky-600 focus:outline-none focus:ring-2 focus:ring-sky-300"
                                >
                                    {{ ResidentLang::translate('Lanjutkan', 'Continue') }}
                                </button>
                                <button
                                    type="button"
                                    wire:click="cancelPendingPayment({{ $pending['id'] }})"
                                    class="inline-flex items-center gap-2 rounded-full border border-slate-300 px-3 py-1 text-[10px] font-semibold text-slate-500 transition-colors duration-200 hover:border-rose-300 hover:text-rose-600 dark:border-slate-700 dark:text-slate-400 dark:hover:border-rose-400 dark:hover:text-rose-300"
                                >
                                    {{ ResidentLang::translate('Batalkan', 'Cancel') }}
                                </button>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                @endif
            </article>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <article class="rounded-2xl border border-slate-200/70 bg-white p-5 shadow-sm transition-colors duration-300 dark:border-slate-700/60 dark:bg-slate-900/80" data-resident-card>
                <span data-resident-chip data-tone="{{ $manualAvailable ? 'success' : 'danger' }}">{{ __('resident.bills.manual_transfer') }}</span>
                <h3 class="mt-3 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('resident.bills.confirm_with_proof_upload') }}</h3>
                <p class="mt-2 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                    @if ($manualAvailable && $manualLabel)
                        {{ $manualLabel }} • {{ $manualAccount }}
                        @if($manualHolder)
                            {{ ResidentLang::translateWithReplacements('atas nama :holder', 'under the name of :holder', ['holder' => $manualHolder]) }}
                        @endif
                    @else
                        {{ ResidentLang::translate('Pengurus belum menyiapkan rekening transfer.', 'Management has not prepared the bank transfer account yet.') }}
                    @endif
                </p>
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        @if ($manualCopyText)
                            onclick="navigator.clipboard?.writeText('{{ addslashes($manualCopyText) }}').then(() => window.Livewire?.dispatch('notification', { body: '{{ addslashes(ResidentLang::translate('Detail rekening disalin.', 'Account details copied.')) }}' }));"
                        @else
                            disabled
                        @endif
                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors duration-200 hover:border-emerald-300 hover:text-emerald-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-emerald-400 dark:hover:text-emerald-200"
                    >
                        {{ ResidentLang::translate('Salin rekening', 'Copy account') }}
                    </button>
                    @if ($highlight && $manualAvailable)
                        <button
                            type="button"
                            wire:click="payManual({{ $highlight['id'] }})"
                            class="inline-flex items-center gap-2 rounded-full bg-emerald-500 px-4 py-2 text-xs font-semibold text-white transition-colors duration-200 hover:bg-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-300"
                        >
                            {{ ResidentLang::translate('Unggah bukti', 'Upload proof') }}
                        </button>
                    @else
                        <span class="text-[11px] text-slate-400 dark:text-slate-500">{{ ResidentLang::translate('Tidak ada tagihan manual.', 'No manual bills available.') }}</span>
                    @endif
                </div>
            </article>

            <article class="rounded-2xl border border-slate-200/70 bg-white p-5 shadow-sm transition-colors duration-300 dark:border-slate-700/60 dark:bg-slate-900/80" data-resident-card>
                <span data-resident-chip data-tone="{{ $tripayAvailable ? 'info' : 'danger' }}">{{ __('resident.bills.automatic_payment') }}</span>
                <h3 class="mt-3 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('resident.bills.checkout_via_tripay') }}</h3>
                <p class="mt-2 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                    @if ($tripayAvailable && $tripayDefault)
                        {{ ResidentLang::translate('Channel utama:', 'Primary channel:') }}
                        {{ $tripayDefault['name'] ?? ($tripayDefault['code'] ?? 'Tripay') }}
                    @else
                        {{ ResidentLang::translate('Channel Tripay belum tersedia.', 'Tripay channel is not yet available.') }}
                    @endif
                </p>
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    @if ($highlight && $tripayAvailable)
                        <button
                            type="button"
                            wire:click="payTripay({{ $highlight['id'] }})"
                            class="inline-flex items-center gap-2 rounded-full bg-sky-500 px-4 py-2 text-xs font-semibold text-white transition-colors duration-200 hover:bg-sky-600 focus:outline-none focus:ring-2 focus:ring-sky-300"
                        >
                            {{ __('resident.bills.pay_via_tripay') }}
                        </button>
                    @else
                        <span class="text-[11px] text-slate-400 dark:text-slate-500">{{ ResidentLang::translate('Tidak ada tagihan Tripay.', 'No Tripay bills available.') }}</span>
                    @endif
                    <a
                        href="https://tripay.co.id"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors duration-200 hover:border-sky-200 hover:text-sky-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-sky-500"
                    >
                        {{ ResidentLang::translate('Pelajari channel', 'Learn about channels') }}
                    </a>
                </div>
            </article>
        </div>

        @if ($topBills->isNotEmpty())
            @php
                $toneClasses = [
                    'danger' => 'border-rose-200/80 bg-rose-50 text-rose-600 dark:border-rose-500/40 dark:bg-rose-500/15 dark:text-rose-200',
                    'warning' => 'border-amber-200/70 bg-amber-50 text-amber-700 dark:border-amber-500/40 dark:bg-amber-500/15 dark:text-amber-200',
                    'info' => 'border-sky-200/70 bg-sky-50 text-sky-700 dark:border-sky-500/40 dark:bg-sky-500/15 dark:text-sky-200',
                ];
            @endphp
            <div class="flex flex-wrap gap-2">
                @foreach ($topBills as $chip)
                    @php
                        $tone = $toneClasses[$chip['urgency'] ?? 'info'] ?? $toneClasses['info'];
                        $action = $tripayAvailable ? 'payTripay(' . $chip['id'] . ')' : ($manualAvailable ? 'payManual(' . $chip['id'] . ')' : null);
                    @endphp
                    <button
                        type="button"
                        @if ($action)
                            wire:click="{{ $action }}"
                        @else
                            disabled
                        @endif
                        class="group inline-flex min-w-[8rem] flex-col gap-1 rounded-2xl border px-3 py-2 text-left text-xs font-medium transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-sky-300 {{ $tone }}"
                    >
                        <span class="truncate font-semibold text-current">{{ $chip['title'] }}</span>
                        <span class="text-sm font-semibold text-current">Rp {{ $chip['amount_formatted'] }}</span>
                        <span class="text-[10px] text-current/80">{{ $chip['due_label'] ?? ResidentLang::translate('Tanpa jatuh tempo', 'No due date') }}</span>
                    </button>
                @endforeach
            </div>
        @endif
    </section>
@endif
