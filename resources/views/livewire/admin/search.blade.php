@php use Illuminate\Support\Str; @endphp
<div class="font-['Inter'] text-slate-800" data-admin-stack>
    <div class="rounded-3xl border border-slate-200 bg-white/90 p-5 shadow-sm shadow-slate-200/60 transition dark:border-slate-800/60 dark:bg-slate-900/70 dark:shadow-slate-900/40">
        <form action="{{ route('admin.search') }}" method="GET" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Pencarian</p>
                @if ($term !== '')
                    <h1 class="mt-1 text-lg font-semibold text-slate-900 dark:text-slate-100">
                        Hasil untuk “{{ $term }}”
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ $totalResults }} data ditemukan di dalam modul admin.
                    </p>
                @else
                    <h1 class="mt-1 text-lg font-semibold text-slate-900 dark:text-slate-100">
                        Jelajahi Data Admin
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Masukkan kata kunci untuk menemukan warga, tagihan, pembayaran, agenda, dan lainnya.
                    </p>
                @endif
            </div>
            <div class="relative w-full sm:w-72">
                <input
                    name="q"
                    type="search"
                    value="{{ old('q', $term) }}"
                    placeholder="Cari nama warga, invoice, agenda..."
                    class="w-full rounded-full border border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-600 shadow-inner shadow-slate-200/60 transition focus:border-[#0284C7] focus:outline-none focus:ring focus:ring-[#0284C7]/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:placeholder:text-slate-500 dark:focus:border-[#0284C7]"
                >
                <svg class="pointer-events-none absolute left-3 top-3 h-4 w-4 text-slate-400 dark:text-slate-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m0 0a7.5 7.5 0 1 0-10.607 0 7.5 7.5 0 0 0 10.607 0Z" />
                </svg>
            </div>
        </form>
    </div>

    @if ($term === '')
        <div class="rounded-3xl border border-dashed border-slate-200 bg-white/80 p-6 text-center text-sm text-slate-500 shadow-inner dark:border-slate-700/60 dark:bg-slate-900/60 dark:text-slate-400">
            Mulai dengan memasukkan kata kunci untuk menampilkan hasil.
        </div>
    @elseif ($totalResults === 0)
        <div class="rounded-3xl border border-dashed border-slate-200 bg-white/80 p-6 text-center text-sm text-slate-500 shadow-inner dark:border-slate-700/60 dark:bg-slate-900/60 dark:text-slate-400">
            Tidak ada data yang cocok untuk “{{ $term }}”.
        </div>
    @else
        <div class="grid gap-5 lg:grid-cols-2">
            <section class="space-y-4 rounded-3xl border border-slate-200 bg-white/90 p-5 shadow-sm shadow-slate-200/60 transition dark:border-slate-800/60 dark:bg-slate-900/70 dark:shadow-slate-900/40">
                <header class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-[#0284C7]">Warga</h2>
                        <p class="text-xs text-slate-400 dark:text-slate-500">Data akun dan identitas warga</p>
                    </div>
                    <a href="{{ route('admin.warga.index', ['search' => $term]) }}" class="text-xs font-semibold text-[#0284C7] hover:text-[#0369a1] dark:text-sky-300">
                        Lihat semua
                    </a>
                </header>
                <div class="space-y-3">
                    @forelse ($users as $user)
                        <a
                            wire:navigate
                            href="{{ route('admin.warga.edit', $user) }}"
                            class="flex items-center justify-between rounded-2xl border border-slate-200/70 bg-white px-4 py-3 text-sm shadow-inner shadow-slate-200/60 transition hover:-translate-y-0.5 hover:border-[#0284C7]/60 hover:shadow-md dark:border-slate-700/70 dark:bg-slate-800/70 dark:text-slate-200 dark:hover:border-sky-400/60"
                        >
                            <div>
                                <p class="font-semibold text-slate-700 dark:text-slate-100">{{ $user->name }}</p>
                                <p class="text-xs text-slate-400 dark:text-slate-500">{{ $user->email }} @if($user->masked_phone) · {{ $user->masked_phone }} @endif</p>
                                @if ($user->masked_nik)
                                    <p class="text-[11px] text-slate-400 dark:text-slate-500">NIK: {{ $user->masked_nik }}</p>
                                @endif
                            </div>
                            <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[11px] font-semibold text-slate-500 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300">
                                {{ strtoupper($user->status ?? 'aktif') }}
                            </span>
                        </a>
                    @empty
                        <p class="rounded-2xl border border-dashed border-slate-200 p-4 text-center text-xs text-slate-400 dark:border-slate-700/60 dark:text-slate-500">
                            Tidak ada hasil warga.
                        </p>
                    @endforelse
                </div>
            </section>

            <section class="space-y-4 rounded-3xl border border-slate-200 bg-white/90 p-5 shadow-sm shadow-slate-200/60 transition dark:border-slate-800/60 dark:bg-slate-900/70 dark:shadow-slate-900/40">
                <header class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-[#0284C7]">Tagihan</h2>
                        <p class="text-xs text-slate-400 dark:text-slate-500">Invoice dan status pembayaran</p>
                    </div>
                    <a href="{{ route('admin.tagihan.index', ['search' => $term]) }}" class="text-xs font-semibold text-[#0284C7] hover:text-[#0369a1] dark:text-sky-300">
                        Lihat semua
                    </a>
                </header>
                <div class="space-y-3">
                    @forelse ($bills as $bill)
                        <a
                            wire:navigate
                            href="{{ route('admin.tagihan.edit', $bill) }}"
                            class="flex items-center justify-between rounded-2xl border border-slate-200/70 bg-white px-4 py-3 text-sm shadow-inner shadow-slate-200/60 transition hover:-translate-y-0.5 hover:border-[#0284C7]/60 hover:shadow-md dark:border-slate-700/70 dark:bg-slate-800/70 dark:text-slate-200 dark:hover:border-sky-400/60"
                        >
                            <div>
                                <p class="font-semibold text-slate-700 dark:text-slate-100">{{ $bill->title }}</p>
                                <p class="text-xs text-slate-400 dark:text-slate-500">
                                    {{ $bill->user?->name ?? 'Tanpa Warga' }} · Invoice {{ $bill->invoice_number }}
                                </p>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500">
                                    Jatuh tempo: {{ optional($bill->due_date)->translatedFormat('d M Y') ?? '–' }}
                                </p>
                            </div>
                            <div class="text-right text-xs">
                                <p class="font-semibold text-[#0284C7] dark:text-sky-300">Rp {{ number_format($bill->amount) }}</p>
                                <span class="inline-flex rounded-full border border-slate-200 px-2 py-0.5 text-[11px] font-semibold text-slate-500 dark:border-slate-700 dark:text-slate-300">
                                    {{ strtoupper($bill->status) }}
                                </span>
                            </div>
                        </a>
                    @empty
                        <p class="rounded-2xl border border-dashed border-slate-200 p-4 text-center text-xs text-slate-400 dark:border-slate-700/60 dark:text-slate-500">
                            Tidak ada hasil tagihan.
                        </p>
                    @endforelse
                </div>
            </section>

            <section class="space-y-4 rounded-3xl border border-slate-200 bg-white/90 p-5 shadow-sm shadow-slate-200/60 transition dark:border-slate-800/60 dark:bg-slate-900/70 dark:shadow-slate-900/40">
                <header class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-[#0284C7]">Pembayaran</h2>
                        <p class="text-xs text-slate-400 dark:text-slate-500">Transaksi kas dan gateway</p>
                    </div>
                    <a href="{{ route('admin.pembayaran.index', ['search' => $term]) }}" class="text-xs font-semibold text-[#0284C7] hover:text-[#0369a1] dark:text-sky-300">
                        Lihat semua
                    </a>
                </header>
                <div class="space-y-3">
                    @forelse ($payments as $payment)
                        <div class="rounded-2xl border border-slate-200/70 bg-white px-4 py-3 text-sm shadow-inner shadow-slate-200/60 transition hover:-translate-y-0.5 hover:border-[#0284C7]/60 hover:shadow-md dark:border-slate-700/70 dark:bg-slate-800/70 dark:text-slate-200 dark:hover:border-sky-400/60">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-slate-700 dark:text-slate-100">{{ $payment->user?->name ?? 'Tanpa Warga' }}</p>
                                    <p class="text-xs text-slate-400 dark:text-slate-500">{{ $payment->bill?->title ?? 'Tanpa Tagihan' }}</p>
                                </div>
                                <div class="text-right text-xs">
                                    <p class="font-semibold text-emerald-600 dark:text-emerald-300">Rp {{ number_format($payment->amount) }}</p>
                                    <span class="inline-flex rounded-full border border-slate-200 px-2 py-0.5 text-[11px] font-semibold text-slate-500 dark:border-slate-700 dark:text-slate-300">
                                        {{ strtoupper($payment->status) }}
                                    </span>
                                </div>
                            </div>
                            <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px] text-slate-400 dark:text-slate-500">
                                <span>Gateway: {{ strtoupper($payment->gateway) }}</span>
                                @if ($payment->reference)
                                    <span>Ref: {{ $payment->reference }}</span>
                                @endif
                                @if ($payment->paid_at)
                                    <span>{{ $payment->paid_at->translatedFormat('d M Y H:i') }}</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="rounded-2xl border border-dashed border-slate-200 p-4 text-center text-xs text-slate-400 dark:border-slate-700/60 dark:text-slate-500">
                            Tidak ada hasil pembayaran.
                        </p>
                    @endforelse
                </div>
            </section>

            <section class="space-y-4 rounded-3xl border border-slate-200 bg-white/90 p-5 shadow-sm shadow-slate-200/60 transition dark:border-slate-800/60 dark:bg-slate-900/70 dark:shadow-slate-900/40">
                <header class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-[#0284C7]">Agenda</h2>
                        <p class="text-xs text-slate-400 dark:text-slate-500">Kegiatan dan jadwal rapat</p>
                    </div>
                    <a href="{{ route('admin.agenda.index', ['search' => $term]) }}" class="text-xs font-semibold text-[#0284C7] hover:text-[#0369a1] dark:text-sky-300">
                        Lihat semua
                    </a>
                </header>
                <div class="space-y-3">
                    @forelse ($events as $event)
                        <a
                            wire:navigate
                            href="{{ route('admin.agenda.edit', $event) }}"
                            class="block rounded-2xl border border-slate-200/70 bg-white px-4 py-3 text-sm shadow-inner shadow-slate-200/60 transition hover:-translate-y-0.5 hover:border-[#0284C7]/60 hover:shadow-md dark:border-slate-700/70 dark:bg-slate-800/70 dark:text-slate-200 dark:hover:border-sky-400/60"
                        >
                            <p class="font-semibold text-slate-700 dark:text-slate-100">{{ $event->title }}</p>
                            <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">
                                {{ optional($event->start_at)->translatedFormat('d M Y H:i') }} @if ($event->location) · {{ $event->location }} @endif
                            </p>
                            @if ($event->description)
                                <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">
                                    {{ Str::limit($event->description, 120) }}
                                </p>
                            @endif
                        </a>
                    @empty
                        <p class="rounded-2xl border border-dashed border-slate-200 p-4 text-center text-xs text-slate-400 dark:border-slate-700/60 dark:text-slate-500">
                            Tidak ada hasil agenda.
                        </p>
                    @endforelse
                </div>
            </section>
        </div>
    @endif
</div>
