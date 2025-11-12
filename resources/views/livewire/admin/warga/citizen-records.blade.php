<div class="font-['Inter'] text-slate-800" data-admin-stack>
    <section class="rounded-3xl border border-[#0284C7]/10 bg-white/95 p-6 shadow-xl shadow-sky-100/60 transition-all duration-300 hover:-translate-y-1.5 hover:border-[#0284C7]/30 hover:shadow-2xl" data-aos="fade-up" data-aos-delay="40">
        <h1 class="text-xl font-semibold text-[#0284C7]">Data Penduduk Siap Pendaftaran</h1>
        <p class="mt-1 text-xs text-slate-500">Masukkan NIK dan nama warga agar proses registrasi mandiri dapat dilakukan.</p>
        <form wire:submit.prevent="save" class="mt-6 grid gap-4 md:grid-cols-2">
            <div>
                <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">NIK</label>
                <input type="text" wire:model.defer="nik" maxlength="16" class="mt-2 w-full rounded-xl border border-[#0284C7]/20 bg-white/95 px-4 py-3 text-sm text-slate-700 shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white">
                @error('nik') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Nama Lengkap</label>
                <input type="text" wire:model.defer="nama" class="mt-2 w-full rounded-xl border border-[#0284C7]/20 bg-white/95 px-4 py-3 text-sm text-slate-700 shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white">
                @error('nama') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Email (opsional)</label>
                <input type="email" wire:model.defer="email" class="mt-2 w-full rounded-xl border border-[#0284C7]/20 bg-white/95 px-4 py-3 text-sm text-slate-700 shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white">
                @error('email') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Alamat (opsional)</label>
                <input type="text" wire:model.defer="alamat" class="mt-2 w-full rounded-xl border border-[#0284C7]/20 bg-white/95 px-4 py-3 text-sm text-slate-700 shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white">
                @error('alamat') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2">
                <button type="submit" class="inline-flex items-center rounded-full bg-[#22C55E] px-6 py-2 text-sm font-semibold text-white shadow-lg shadow-emerald-200/60 transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#16a34a] focus:outline-none focus:ring-2 focus:ring-[#22C55E]/40 focus:ring-offset-2 focus:ring-offset-white">Tambah Data</button>
            </div>
        </form>
    </section>

    <section class="rounded-3xl border border-[#0284C7]/10 bg-white/95 p-6 shadow-xl shadow-sky-100/60 transition-all duration-300 hover:-translate-y-1.5 hover:border-[#0284C7]/30 hover:shadow-2xl" data-aos="fade-up" data-aos-delay="120">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4 text-sm text-slate-600">
                <input type="search" wire:model.debounce.500ms="search" placeholder="Cari nama atau NIK" class="rounded-xl border border-[#0284C7]/20 bg-white/95 px-4 py-2.5 text-sm text-slate-600 shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white">
                <select wire:model="status" class="rounded-xl border border-[#0284C7]/20 bg-white/95 px-4 py-2.5 text-sm text-slate-600 shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white">
                    <option value="available">Available</option>
                    <option value="claimed">Claimed</option>
                    <option value="semua">Semua</option>
                </select>
            </div>
            <p class="text-xs text-slate-400">Total: {{ number_format($records->total()) }} data</p>
        </div>

        <div class="mt-8 overflow-hidden overflow-x-auto rounded-3xl border border-[#0284C7]/10 shadow-sm shadow-sky-100/60">
            <table class="min-w-full text-sm">
                <thead class="bg-[#0284C7]/90 text-[11px] uppercase tracking-wide text-white/95">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">NIK</th>
                        <th class="px-4 py-3 text-left font-semibold">Nama</th>
                        <th class="px-4 py-3 text-left font-semibold">Email</th>
                        <th class="px-4 py-3 text-left font-semibold">Status</th>
                        <th class="px-4 py-3 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100/80 bg-white/95">
                    @forelse ($records as $record)
                        <tr class="transition-all duration-300 hover:bg-slate-50/80">
                            <td class="px-4 py-4 text-sm text-slate-600">{{ $record->nik }}</td>
                            <td class="px-4 py-4 text-sm text-slate-600">{{ $record->nama }}</td>
                            <td class="px-4 py-4 text-sm text-slate-600">{{ $record->email ?? '-' }}</td>
                            <td class="px-4 py-4 text-xs font-semibold text-[#0284C7]">{{ Str::headline($record->status) }}</td>
                            <td class="px-4 py-4 text-right text-xs">
                                <div class="flex justify-end gap-2">
                                    @if ($record->status === 'claimed')
                                        <button wire:click="markAvailable({{ $record->id }})" class="rounded-full border border-[#22C55E]/30 px-4 py-1 font-semibold text-[#166534] transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#22C55E]/10">Set Available</button>
                                    @endif
                                    <button wire:click="delete({{ $record->id }})" class="rounded-full border border-rose-200 px-4 py-1 font-semibold text-rose-600 transition-all duration-300 hover:-translate-y-0.5 hover:bg-rose-50">Hapus</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-xs text-slate-400">Belum ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex justify-end">
            {{ $records->links() }}
        </div>
    </section>
</div>
