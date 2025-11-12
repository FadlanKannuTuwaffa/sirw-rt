@php
    use Carbon\Carbon;

    $isIuran = $type === 'iuran';
    $periodLabel = null;

    if ($isIuran && filled($iuran_period)) {
        try {
            $periodLabel = Carbon::createFromFormat('Y-m', $iuran_period)->locale('id')->translatedFormat('F Y');
        } catch (\Throwable $e) {
            $periodLabel = null;
        }
    }

    $amountValue = max(0, (int) ($amount ?? 0));
    $targetCount = 0;
    $selectedNames = collect();
    $hasMoreSelected = false;

    if ($mode === 'create' && $isIuran) {
        $residentCollection = collect($residentDirectory ?? []);
        $activeResidents = $residentCollection->where('status', 'aktif');
        $selectedIds = collect($selected_resident_ids ?? [])->filter()->values();

        $targetCount = $selectedIds->isNotEmpty() ? $selectedIds->count() : $activeResidents->count();
        $selectedNames = $selectedIds->isNotEmpty()
            ? $residentCollection->whereIn('id', $selectedIds->all())->pluck('name')->take(6)
            : collect();
        $hasMoreSelected = $selectedIds->count() > 6;
    }

    $totalProjection = $targetCount * $amountValue;
@endphp

<form wire:submit.prevent="save" class="space-y-8 font-['Inter'] text-slate-800" data-aos="fade-up" data-aos-delay="60">
    <section class="rounded-3xl border border-[#0284C7]/10 bg-white/95 p-6 shadow-xl shadow-sky-100/60">
        <h2 class="text-lg font-semibold text-[#0284C7]">Detail Tagihan</h2>
        <p class="mt-1 text-xs text-slate-400">Susun tagihan dengan informasi lengkap. Pengingat otomatis kini dapat dikelola melalui menu <span class="font-semibold text-[#0284C7]">Reminder Automatis</span>.</p>

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <div>
                <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Warga</label>
                <select wire:model.live="user_id" class="mt-2 w-full rounded-xl border border-[#0284C7]/20 bg-white/95 py-3 px-4 text-sm text-slate-600 shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white">
                    <option value="">Pilih warga...</option>
                    @if ($mode === 'create')
                        <option value="all">Semua warga aktif</option>
                    @endif
                    @foreach ($residents as $resident)
                        <option value="{{ $resident['id'] }}">{{ $resident['name'] }}</option>
                    @endforeach
                </select>
                @error('user_id') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                @if ($mode === 'create' && $user_id === 'all')
                    <p class="mt-2 text-[11px] text-slate-400">Tagihan akan dibuat massal. Batasi penerima pada bagian <span class="font-semibold text-[#0284C7]">Target Iuran</span> bila diperlukan.</p>
                @endif
            </div>
            <div>
                <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Jenis Tagihan</label>
                <select wire:model.live="type" class="mt-2 w-full rounded-xl border border-[#0284C7]/20 bg-white/95 py-3 px-4 text-sm text-slate-600 shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white">
                    <option value="iuran">Iuran Bulanan</option>
                    <option value="sumbangan">Sumbangan</option>
                    <option value="lainnya">Lainnya</option>
                </select>
                @error('type') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>

            @if ($isIuran)
                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Periode Iuran</label>
                    <input wire:model.live="iuran_period" type="month" class="mt-2 w-full rounded-xl border border-[#0284C7]/20 bg-white/95 py-3 px-4 text-sm text-slate-600 shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white">
                    @error('iuran_period') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                    <p class="mt-2 text-[11px] text-slate-400">Jatuh tempo otomatis mengikuti akhir bulan pada periode tersebut.</p>
                </div>
            @endif

            <div>
                <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Judul Tagihan</label>
                <input wire:model.live="title" type="text" class="mt-2 w-full rounded-xl border border-[#0284C7]/20 bg-white/95 py-3 px-4 text-sm shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white">
                @error('title') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Nominal (Rp)</label>
                <input wire:model.live="amount" type="number" min="0" class="mt-2 w-full rounded-xl border border-[#0284C7]/20 bg-white/95 py-3 px-4 text-sm text-slate-600 shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#22C55E] focus:ring-2 focus:ring-[#22C55E]/30 focus:ring-offset-1 focus:ring-offset-white">
                @error('amount') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Tanggal Diterbitkan</label>
                <input wire:model.live="issued_at" type="date" class="mt-2 w-full rounded-xl border border-[#0284C7]/20 bg-white/95 py-3 px-4 text-sm text-slate-600 shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white">
                @error('issued_at') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Jatuh Tempo</label>
                <input wire:model.live="due_date" type="date" @if($isIuran) readonly @endif class="mt-2 w-full rounded-xl border border-[#0284C7]/20 bg-white/95 py-3 px-4 text-sm text-slate-600 shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white">
                @error('due_date') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                @if($isIuran)
                    <p class="mt-2 text-[11px] text-slate-400">Periode: {{ $periodLabel ?? 'Belum dipilih' }}.</p>
                @endif
            </div>
            <div>
                <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Status</label>
                <select wire:model.live="status" class="mt-2 w-full rounded-xl border border-[#0284C7]/20 bg-white/95 py-3 px-4 text-sm text-slate-600 shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white">
                    <option value="unpaid">Belum Lunas</option>
                    <option value="paid">Sudah Lunas</option>
                    <option value="cancelled">Dibatalkan</option>
                </select>
                @error('status') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-6">
            <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Deskripsi / Rincian</label>
            <textarea wire:model.live="description" rows="4" class="mt-2 w-full rounded-2xl border border-[#0284C7]/20 bg-white/95 py-3 px-4 text-sm text-slate-600 shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white" placeholder="Tuliskan rincian penggunaan dana, nomor rekening, atau informasi tambahan"></textarea>
            @error('description') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            @if ($type === 'lainnya')
                <p class="mt-2 text-[11px] text-amber-500">Jenis "Lainnya" wajib mencantumkan keterangan yang jelas agar warga memahami tagihan.</p>
            @endif
        </div>
    </section>

    @if ($mode === 'create' && $isIuran)
        <section class="rounded-3xl border border-[#0284C7]/10 bg-white/95 p-6 shadow-xl shadow-sky-100/60">
            <h2 class="text-lg font-semibold text-[#0284C7]">Target Iuran Bulanan</h2>
            <p class="mt-1 text-xs text-slate-400">Statistik ini akan diperbarui secara realtime ketika Anda mengubah periode, nominal, atau daftar warga.</p>

            <div class="mt-6 grid gap-4 md:grid-cols-4">
                <div class="rounded-3xl border border-white/20 bg-gradient-to-br from-[#0284C7] via-[#0ea5e9] to-[#38bdf8] p-4 text-center text-white shadow-lg shadow-[#0284C7]/30 transition-all duration-300 hover:-translate-y-1 hover:border-white/40 hover:shadow-2xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-white/80">Periode</p>
                    <p class="mt-3 text-xl font-semibold drop-shadow-sm">{{ $periodLabel ?? 'Pilih periode' }}</p>
                </div>
                <div class="rounded-3xl border border-white/20 bg-gradient-to-br from-[#22C55E] via-emerald-500 to-[#16a34a] p-4 text-center text-white shadow-lg shadow-emerald-200/60 transition-all duration-300 hover:-translate-y-1 hover:border-white/40 hover:shadow-2xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-white/80">Nominal per Warga</p>
                    <p class="mt-3 text-xl font-semibold drop-shadow-sm">Rp {{ number_format($amountValue) }}</p>
                </div>
                <div class="rounded-3xl border border-[#0284C7]/15 bg-gradient-to-br from-white via-slate-50 to-sky-50/80 p-4 text-center shadow-lg shadow-sky-100/60 transition-all duration-300 hover:-translate-y-1 hover:border-[#0284C7]/30 hover:shadow-2xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Target Warga</p>
                    <p class="mt-3 text-xl font-semibold text-[#0284C7]">{{ number_format($targetCount) }} orang</p>
                </div>
                <div class="rounded-3xl border border-white/20 bg-gradient-to-br from-[#0f172a] via-[#1e293b] to-[#334155] p-4 text-center text-white shadow-lg shadow-slate-900/40 transition-all duration-300 hover:-translate-y-1 hover:border-white/40 hover:shadow-2xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-white/70">Proyeksi Dana</p>
                    <p class="mt-3 text-xl font-semibold drop-shadow-sm">Rp {{ number_format($totalProjection) }}</p>
                </div>
            </div>

            @if ($user_id === 'all')
                <div class="mt-6">
                    <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Batasi Warga (opsional)</label>
                    <p class="text-[11px] text-slate-400">Kosongkan untuk seluruh warga aktif. Gunakan pencarian browser (Ctrl+F) untuk mempercepat.</p>

                    <div class="mt-3 max-h-64 overflow-y-auto rounded-2xl border border-[#0284C7]/15 bg-white/95 p-3 text-sm text-slate-600 shadow-inner shadow-sky-100">
                        @foreach ($residentDirectory as $resident)
                            <label class="flex items-center justify-between gap-3 border-b border-slate-100/80 py-2 last:border-0">
                                <div>
                                    <span class="font-semibold text-slate-800">{{ $resident['name'] }}</span>
                                    <span class="ml-2 rounded-full border border-[#0284C7]/20 bg-sky-50 px-2 py-0.5 text-[10px] font-semibold text-[#0284C7]">
                                        {{ \Illuminate\Support\Str::headline($resident['status']) }}
                                    </span>
                                </div>
                                <input type="checkbox" value="{{ $resident['id'] }}" wire:model.live="selected_resident_ids" class="h-4 w-4 rounded border-[#0284C7]/30 text-[#0284C7] focus:ring-[#0284C7]/40">
                            </label>
                        @endforeach
                    </div>
                    @error('selected_resident_ids') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror

                    @if ($selectedNames->isNotEmpty())
                        <div class="mt-4 rounded-2xl border border-[#0284C7]/15 bg-sky-50/70 p-4 text-xs text-slate-500 shadow-inner shadow-sky-100">
                            <p class="font-semibold text-slate-600">Warga terpilih ({{ number_format($targetCount) }}):</p>
                            <p class="mt-1 text-[11px] text-slate-500">
                                {{ $selectedNames->join(', ') }}@if($hasMoreSelected){{ ', dan lainnya.' }}@endif
                            </p>
                        </div>
                    @endif
                </div>
            @else
                <div class="mt-6 rounded-2xl border border-[#0284C7]/15 bg-sky-50/80 p-4 text-xs text-slate-500 shadow-inner shadow-sky-100">
                    <p>Untuk membuat iuran massal, pilih opsi <span class="font-semibold text-[#0284C7]">"Semua warga aktif"</span> pada kolom warga di atas.</p>
                </div>
            @endif
        </section>
    @endif

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('admin.tagihan.index') }}" class="rounded-full border border-slate-200 px-5 py-2 text-sm font-semibold text-slate-600 transition-all duration-300 hover:-translate-y-0.5 hover:bg-slate-100">Batal</a>
        <button type="submit" class="rounded-full bg-[#0284C7] px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-[#0284C7]/40 transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#0ea5e9] focus:outline-none focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-2 focus:ring-offset-white">
            Simpan Tagihan
        </button>
    </div>
</form>

