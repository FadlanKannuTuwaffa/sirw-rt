@php
    $locale = app()->getLocale();
    $isIndonesian = str_starts_with($locale, 'id');
    $decimalSeparator = $isIndonesian ? ',' : '.';
    $thousandSeparator = $isIndonesian ? '.' : ',';
@endphp

<section class="bg-white/60 backdrop-blur-sm transition-colors duration-300 dark:bg-slate-900/60" wire:poll.20s="refreshStats">
    <div class="mx-auto grid w-full max-w-6xl gap-4 px-4 pt-8 pb-12 sm:px-6 md:pt-12 md:pb-16 lg:grid-cols-4 lg:gap-8 lg:px-10">
        <div class="rounded-3xl border border-white/70 bg-white/90 p-6 shadow-lg shadow-sky-100 transition-all duration-300 hover:-translate-y-1 hover:border-sky-200/70 hover:shadow-xl dark:border-slate-800/70 dark:bg-slate-900/70 dark:shadow-slate-900/40">
            <p class="text-sm uppercase tracking-[0.35em] text-slate-400 dark:text-slate-500" data-i18n="stats.total_label">Total Warga</p>
            <p class="mt-4 text-4xl font-bold text-slate-900 dark:text-slate-100">{{ number_format($residents, 0, $decimalSeparator, $thousandSeparator) }}</p>
            <p class="mt-2 text-sm font-medium text-slate-500 dark:text-slate-400">
                {{ number_format($online, 0, $decimalSeparator, $thousandSeparator) }}
                <span class="ml-1" data-i18n="stats.online_suffix">online saat ini</span>
            </p>
        </div>
        <div class="rounded-3xl border border-white/70 bg-white/90 p-6 shadow-lg shadow-sky-100 transition-all duration-300 hover:-translate-y-1 hover:border-emerald-200/80 hover:shadow-xl dark:border-slate-800/70 dark:bg-slate-900/70 dark:shadow-slate-900/40">
            <p class="text-sm uppercase tracking-[0.35em] text-slate-400 dark:text-slate-500" data-i18n="stats.paid_label">Pembayaran Bulan Ini</p>
            <p class="mt-4 text-4xl font-bold text-emerald-600 dark:text-emerald-400">Rp {{ number_format($paidThisMonth, 0, $decimalSeparator, $thousandSeparator) }}</p>
            <p class="mt-2 text-sm font-medium text-slate-500 dark:text-slate-400" data-i18n="stats.paid_desc">Kas warga tercatat realtime</p>
        </div>
        <div class="rounded-3xl border border-white/70 bg-white/90 p-6 shadow-lg shadow-sky-100 transition-all duration-300 hover:-translate-y-1 hover:border-rose-200/80 hover:shadow-xl dark:border-slate-800/70 dark:bg-slate-900/70 dark:shadow-slate-900/40">
            <p class="text-sm uppercase tracking-[0.35em] text-slate-400 dark:text-slate-500" data-i18n="stats.outstanding_label">Tagihan Belum Lunas</p>
            <p class="mt-4 text-4xl font-bold text-rose-500 dark:text-rose-300">Rp {{ number_format($outstandingBills, 0, $decimalSeparator, $thousandSeparator) }}</p>
            <p class="mt-2 text-sm font-medium text-slate-500 dark:text-slate-400" data-i18n="stats.outstanding_desc">Pengingat otomatis siap membantu</p>
        </div>
        <div class="rounded-3xl border border-white/70 bg-white/90 p-6 shadow-lg shadow-sky-100 transition-all duration-300 hover:-translate-y-1 hover:border-sky-200/70 hover:shadow-xl dark:border-slate-800/70 dark:bg-slate-900/70 dark:shadow-slate-900/40">
            <p class="text-sm uppercase tracking-[0.35em] text-slate-400 dark:text-slate-500" data-i18n="stats.remote_label">Kelola dari Mana Saja</p>
            <p class="mt-4 text-4xl font-bold text-sky-600 dark:text-sky-300">100%</p>
            <p class="mt-2 text-sm font-medium text-slate-500 dark:text-slate-400" data-i18n="stats.remote_desc">Portal dapat diakses 24/7</p>
        </div>
    </div>
</section>
