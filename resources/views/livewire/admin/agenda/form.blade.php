<form wire:submit.prevent="save" class="space-y-8 font-['Inter'] text-slate-800" data-aos="fade-up" data-aos-delay="60">
    <section class="rounded-3xl border border-[#0284C7]/10 bg-white/95 p-6 shadow-xl shadow-sky-100/60">
        <h2 class="text-lg font-semibold text-[#0284C7]">Detail Agenda</h2>
        <p class="mt-1 text-xs text-slate-400">Agenda akan tampil di dashboard dan dikirim sebagai pengingat ke warga.</p>

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <div>
                <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Judul Kegiatan</label>
                <input wire:model.defer="title" type="text" class="mt-2 w-full rounded-xl border border-[#0284C7]/20 bg-white/95 py-3 px-4 text-sm shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white">
                @error('title') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Lokasi</label>
                <input wire:model.defer="location" type="text" class="mt-2 w-full rounded-xl border border-[#0284C7]/20 bg-white/95 py-3 px-4 text-sm shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white" placeholder="Opsional">
                @error('location') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Mulai</label>
                <input wire:model.defer="start_at" type="datetime-local" class="mt-2 w-full rounded-xl border border-[#0284C7]/20 bg-white/95 py-3 px-4 text-sm shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white">
                @error('start_at') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Selesai</label>
                <input wire:model.defer="end_at" type="datetime-local" class="mt-2 w-full rounded-xl border border-[#0284C7]/20 bg-white/95 py-3 px-4 text-sm shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white" placeholder="Opsional">
                @error('end_at') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-center gap-3 rounded-2xl border border-[#0284C7]/15 bg-white/90 p-4 shadow-inner shadow-sky-100">
                <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Sehari Penuh?</label>
                <input wire:model.defer="is_all_day" type="checkbox" class="h-5 w-5 rounded border-[#0284C7]/30 text-[#0284C7] focus:ring-[#0284C7]/40">
            </div>
            <div class="flex items-center gap-3 rounded-2xl border border-[#0284C7]/15 bg-white/90 p-4 shadow-inner shadow-sky-100">
                <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Tampilkan ke warga?</label>
                <input wire:model.defer="is_public" type="checkbox" class="h-5 w-5 rounded border-[#0284C7]/30 text-[#0284C7] focus:ring-[#0284C7]/40">
            </div>
            <div>
                <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Status</label>
                <select wire:model.defer="status" class="mt-2 w-full rounded-xl border border-[#0284C7]/20 bg-white/95 py-3 px-4 text-sm text-slate-600 shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white">
                    <option value="scheduled">Terjadwal</option>
                    <option value="completed">Selesai</option>
                    <option value="cancelled">Dibatalkan</option>
                </select>
                @error('status') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-6">
            <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Deskripsi Agenda</label>
            <textarea wire:model.defer="description" rows="4" class="mt-2 w-full rounded-2xl border border-[#0284C7]/20 bg-white/95 py-3 px-4 text-sm text-slate-600 shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white" placeholder="Rincian kegiatan, susunan acara, perlengkapan yang dibutuhkan, dll."></textarea>
            @error('description') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
        </div>
    </section>

    <section class="rounded-3xl border border-[#0284C7]/10 bg-white/95 p-6 shadow-xl shadow-sky-100/60">
        <h2 class="text-lg font-semibold text-[#0284C7]">Reminder Otomatis</h2>
        <p class="mt-1 text-xs text-slate-400">Pengelolaan jadwal pengingat kini terpusat di menu <span class="font-semibold text-[#0284C7]">Reminder Automatis</span>. Setelah menyimpan agenda ini, buka menu tersebut untuk menambahkan pengingat email/Telegram sesuai kebutuhan.</p>

        <div class="mt-5 rounded-2xl border border-[#0284C7]/15 bg-sky-50/80 p-4 text-xs text-slate-500 shadow-inner shadow-sky-100">
            <p>Semua pengingat lama tetap tersimpan dan dapat Anda kelola ulang dari menu baru. Fitur ini juga mendukung pengingat gabungan untuk tagihan dan agenda sehingga warga menerima notifikasi yang konsisten.</p>
        </div>
    </section>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('admin.agenda.index') }}" class="rounded-full border border-slate-200 px-5 py-2 text-sm font-semibold text-slate-600 transition-all duration-300 hover:-translate-y-0.5 hover:bg-slate-100">Batal</a>
        <button type="submit" class="rounded-full bg-[#0284C7] px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-[#0284C7]/40 transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#0ea5e9] focus:outline-none focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-2 focus:ring-offset-white">Simpan Agenda</button>
    </div>
</form>
