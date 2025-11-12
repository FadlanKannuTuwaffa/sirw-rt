@php
    $title = 'Pengaturan';
    $titleClass = 'text-white';
@endphp

<div class="space-y-6 font-['Inter'] text-slate-800 dark:text-slate-100" data-settings-page data-admin-stack>
    <x-admin.settings-nav current="general" />

    <section class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition-colors duration-200 dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card>
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="space-y-3">
                <p class="text-sm font-medium text-sky-600 dark:text-sky-300">Profil RT/RW & branding digital</p>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Bangun identitas yang konsisten</h1>
                <p class="max-w-2xl text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                    Perubahan pada data di bawah akan tersinkron otomatis ke landing page dan dashboard warga sehingga warga selalu melihat informasi terbaru.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200/80 px-3 py-1 dark:border-slate-700">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    Pratinjau waktu nyata
                </span>
                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200/80 px-3 py-1 dark:border-slate-700">
                    <span class="h-2 w-2 rounded-full bg-sky-500"></span>
                    Sinkron otomatis
                </span>
            </div>
        </div>
    </section>

    @if (session('status'))
        <div class="settings-surface border border-emerald-200/70 bg-emerald-50/90 p-4 text-sm font-medium text-emerald-700 shadow-md shadow-emerald-200/40 dark:border-emerald-400/40 dark:bg-emerald-500/15 dark:text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="settings-surface border border-rose-200/80 bg-rose-50/95 p-4 text-sm text-rose-600 shadow-md shadow-rose-200/40 dark:border-rose-500/40 dark:bg-rose-500/15 dark:text-rose-100">
            <p class="font-semibold">Terjadi kesalahan:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-xs">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form wire:submit.prevent="save" class="space-y-6" data-motion-animated>
        <section class="settings-surface w-full p-6">
            <header class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Identitas website</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Data ini ditampilkan di halaman depan dan dashboard warga sebagai referensi resmi.</p>
                </div>
                <div class="inline-flex items-center gap-2 rounded-full border border-slate-200/80 bg-white px-3 py-1.5 text-xs font-semibold text-slate-500 dark:border-slate-700/60 dark:bg-slate-900/60 dark:text-slate-300">
                    <span class="inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                    Live
                </div>
            </header>

            <div class="mt-6 grid gap-4 md:grid-cols-3">
                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Nama Website</label>
                    <input wire:model.defer="site_name" type="text" placeholder="Contoh: Portal RT 05" class="mt-2 settings-field">
                    @error('site_name') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Tagline</label>
                    <input wire:model.defer="tagline" type="text" placeholder="Hadir untuk warga yang terhubung" class="mt-2 settings-field">
                    @error('tagline') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.35em] text-slate-500 dark:text-slate-400">Inisial Logo</label>
                    <input wire:model.defer="logo_initials" type="text" maxlength="4" class="mt-2 settings-field text-center uppercase tracking-[0.6em]">
                    @error('logo_initials') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-6 space-y-5">
                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Tentang Kami</label>
                    <textarea wire:model.defer="about" rows="3" placeholder="Gambaran singkat RT/RW secara umum." class="mt-2 settings-field min-h-[120px]"></textarea>
                    @error('about') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Visi</label>
                        <textarea wire:model.defer="vision" rows="3" placeholder="Tulis visi organisasi..." class="mt-2 settings-field min-h-[120px]"></textarea>
                        @error('vision') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Misi</label>
                        <textarea wire:model.defer="mission" rows="3" placeholder="Pisahkan poin misi per baris." class="mt-2 settings-field min-h-[120px]"></textarea>
                        @error('mission') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Email Kontak</label>
                    <input wire:model.defer="contact_email" type="email" placeholder="kontak@rtsmart.id" class="mt-2 settings-field">
                    @error('contact_email') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Nomor Telepon / WhatsApp</label>
                    <input wire:model.defer="contact_phone" type="text" placeholder="0812-xxxx-xxxx" class="mt-2 settings-field">
                    @error('contact_phone') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Jam Pelayanan</label>
                    <input wire:model.defer="service_hours" type="text" placeholder="Contoh: Senin - Sabtu, 08.00 - 17.00 WIB" class="mt-2 settings-field">
                    @error('service_hours') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Alamat</label>
                    <textarea wire:model.defer="address" rows="2" class="mt-2 settings-field min-h-[110px]" placeholder="Cantumkan alamat lengkap kantor sekretariat."></textarea>
                    @error('address') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="settings-surface w-full p-6">
            <header class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-[#0284C7] dark:text-sky-300">Branding & Media Sosial</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Logo dan tautan media sosial membantu warga menemukan kanal resmi komunitas.</p>
                </div>
                <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M4.318 6.318a9 9 0 1 1 12.728 12.728A9 9 0 0 1 4.318 6.318Z" />
                    </svg>
                    <span>Gunakan logo berformat persegi untuk hasil optimal.</span>
                </div>
            </header>

            <div class="mt-6 grid gap-6 lg:grid-cols-[auto_minmax(0,1fr)]">
                <div class="flex h-28 w-28 items-center justify-center overflow-hidden rounded-2xl border border-slate-200/70 bg-white/70 shadow-inner shadow-slate-200/40 dark:border-slate-700/60 dark:bg-slate-900/70">
                    @if ($logo)
                        <img src="{{ $logo->temporaryUrl() }}" class="h-full w-full object-cover" alt="Preview logo">
                    @elseif ($logo_url)
                        <img src="{{ $logo_url }}" class="h-full w-full object-cover" alt="Logo saat ini">
                    @else
                        <div class="flex h-full w-full items-center justify-center text-xs text-slate-400 dark:text-slate-500">Belum ada logo</div>
                    @endif
                </div>
                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Unggah Logo</label>
                    <input wire:model="logo" type="file" accept="image/*" class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                    <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">Format: jpg, png, webp. Maksimal 2MB. Disarankan ukuran minimal 400x400px.</p>
                    @error('logo') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-3">
                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Facebook</label>
                    <input wire:model.defer="facebook" type="url" placeholder="https://facebook.com/..." class="mt-2 settings-field settings-field--plain">
                    @error('facebook') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Instagram</label>
                    <input wire:model.defer="instagram" type="url" placeholder="https://instagram.com/..." class="mt-2 settings-field settings-field--plain">
                    @error('instagram') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">YouTube</label>
                    <input wire:model.defer="youtube" type="url" placeholder="https://youtube.com/..." class="mt-2 settings-field settings-field--plain">
                    @error('youtube') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <div class="flex flex-wrap items-center justify-end gap-3">
            <button
                type="submit"
                wire:loading.attr="disabled"
                class="btn-soft-emerald px-6 text-sm"
            >
                <span wire:loading.remove>Simpan Pengaturan</span>
                <span wire:loading>Menyimpan...</span>
            </button>
        </div>
    </form>
</div>
