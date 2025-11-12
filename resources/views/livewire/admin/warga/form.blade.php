{{-- Flash message --}}
@if (session('status'))
  <div class="mb-5 rounded-2xl border border-[#22C55E]/30 bg-[#22C55E]/10 p-4 text-sm font-medium text-[#166534] shadow-sm shadow-emerald-200/50">
    {{ session('status') }}
  </div>
@endif

{{-- Error summary --}}
@if ($errors->any())
  <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50/90 p-4 text-sm text-rose-600 shadow-sm shadow-rose-100">
    <p class="font-semibold">Terjadi kesalahan:</p>
    <ul class="mt-2 list-disc space-y-1 pl-5 text-xs">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<form wire:submit.prevent="save" class="space-y-8 font-['Inter'] text-slate-800" data-aos="fade-up" data-aos-delay="60">
  <div class="grid gap-6 md:grid-cols-2">
    <div>
      <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Nama Lengkap</label>
      <input type="text" wire:model.defer="name"
             class="mt-2 w-full rounded-xl border border-[#0284C7]/20 bg-white/95 py-3 px-4 text-sm text-slate-700 shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white">
      @error('name') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
    </div>

    <div>
      <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Email</label>
      <input type="email" wire:model.defer="email"
             class="mt-2 w-full rounded-xl border border-[#0284C7]/20 bg-white/95 py-3 px-4 text-sm text-slate-700 shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white">
      @error('email') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
    </div>

    <div>
      <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">NIK (16 digit)</label>
      <input type="text" wire:model.defer="nik" maxlength="16" inputmode="numeric" pattern="[0-9]*"
             class="mt-2 w-full rounded-xl border border-[#0284C7]/20 bg-white/95 py-3 px-4 text-sm text-slate-700 shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white">
      @error('nik') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
    </div>

    <div>
      <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Nomor Telepon</label>
      <input type="tel" wire:model.defer="phone" inputmode="tel"
             class="mt-2 w-full rounded-xl border border-[#0284C7]/20 bg-white/95 py-3 px-4 text-sm text-slate-700 shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white">
      <p class="mt-1 text-[10px] uppercase tracking-[0.25em] text-slate-400">10-14 digit angka</p>
      @error('phone') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
    </div>

    <div>
      <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Status</label>
      <select wire:model.defer="status"
              class="mt-2 w-full rounded-xl border border-[#0284C7]/20 bg-white/95 py-3 px-4 text-sm text-slate-700 shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white">
        <option value="aktif">Aktif</option>
        <option value="pindah">Pindah</option>
        <option value="nonaktif">Nonaktif</option>
      </select>
      @error('status') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
    </div>
  </div>

  <div>
    <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Alamat Lengkap</label>
    <textarea wire:model.defer="alamat" rows="3"
              class="mt-2 w-full rounded-xl border border-[#0284C7]/20 bg-white/95 py-3 px-4 text-sm text-slate-700 shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white"></textarea>
    @error('alamat') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
  </div>

  <div>
    <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Catatan (opsional)</label>
    <textarea wire:model.defer="notes" rows="3"
              class="mt-2 w-full rounded-xl border border-[#0284C7]/20 bg-white/95 py-3 px-4 text-sm text-slate-700 shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white"></textarea>
    @error('notes') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
  </div>

  <p class="rounded-2xl border border-[#0284C7]/20 bg-sky-50/70 p-4 text-xs text-slate-500 shadow-inner shadow-sky-100">
    Data ini akan digunakan sebagai verifikasi otomatis saat warga melakukan registrasi mandiri.
    Pastikan nama, email, alamat, dan NIK sesuai dengan dokumen resmi.
  </p>

  <div class="flex items-center justify-end gap-3">
    <a href="{{ route('admin.warga.index') }}"
       class="rounded-full border border-slate-200 px-5 py-2 text-sm font-semibold text-slate-600 transition-all duration-300 hover:-translate-y-0.5 hover:bg-slate-100">
      Batal
    </a>
    <button type="submit"
            class="rounded-full bg-[#22C55E] px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-emerald-200/60 transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#16a34a] focus:outline-none focus:ring-2 focus:ring-[#22C55E]/40 focus:ring-offset-2 focus:ring-offset-white"
            wire:loading.attr="disabled">
      <span wire:loading.remove>Simpan</span>
      <span wire:loading>Menyimpan...</span>
    </button>
  </div>
</form>
