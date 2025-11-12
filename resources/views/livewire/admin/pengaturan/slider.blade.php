@php
    /** @var \Illuminate\Filesystem\FilesystemAdapter $publicDisk */
    $publicDisk = \Illuminate\Support\Facades\Storage::disk('public');
@endphp

<div class="space-y-6 font-['Inter'] text-slate-800 dark:text-slate-100" data-settings-page data-admin-stack>
    <x-admin.settings-nav current="slider" />

    <section class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition-colors duration-200 dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="space-y-3">
                <p class="text-sm font-medium text-sky-600 dark:text-sky-300">Slider landing page</p>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Kelola hero interaktif dengan mudah</h1>
                <p class="max-w-2xl text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                    Susun konten hero informatif dengan aksi jelas. Slide aktif akan menampilkan teks dan tombol sesuai urutan yang Anda tentukan.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200/80 px-3 py-1 dark:border-slate-700">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    Urutan mudah
                </span>
                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200/80 px-3 py-1 dark:border-slate-700">
                    <span class="h-2 w-2 rounded-full bg-violet-500"></span>
                    Preview seketika
                </span>
            </div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)]">
        <section class="settings-surface w-full p-6" data-motion-animated>
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-[#0284C7] dark:text-sky-300">{{ $editingId ? 'Perbarui Slide' : 'Slide Baru' }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {{ $editingId ? 'Perbarui konten slider yang sudah tampil tanpa mengganggu slide lainnya.' : 'Buat hero baru berisi judul, subjudul, tombol, serta gambar latar.' }}
                    </p>
                </div>
                @if ($editingId)
                    <button
                        type="button"
                        wire:click="cancelEdit"
                        class="inline-flex items-center gap-2 rounded-full border border-slate-200/70 px-4 py-2 text-xs font-semibold text-slate-600 transition-colors duration-200 hover:border-slate-300 hover:bg-white/70 hover:text-slate-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-300 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-slate-700/60 dark:text-slate-300 dark:hover:border-slate-500 dark:hover:bg-slate-900/70 dark:focus-visible:ring-slate-500 dark:focus-visible:ring-offset-slate-900"
                    >
                        Batal Edit
                    </button>
                @endif
            </div>

            <form wire:submit.prevent="save" class="mt-6 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Judul</label>
                    <input wire:model.defer="title" type="text" class="mt-2 settings-field" placeholder="Contoh: Transformasi Digital RT 05">
                    @error('title') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Subjudul</label>
                    <input wire:model.defer="subtitle" type="text" class="mt-2 settings-field" placeholder="Satu data untuk semua kebutuhan warga">
                    @error('subtitle') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Deskripsi</label>
                    <textarea wire:model.defer="description" rows="3" class="mt-2 settings-field min-h-[120px]" placeholder="Gunakan kalimat singkat yang menjelaskan nilai utama atau ajakan."></textarea>
                    @error('description') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Label Tombol</label>
                    <input wire:model.defer="button_label" type="text" class="mt-2 settings-field" placeholder="Mulai Lihat Agenda">
                    @error('button_label') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">URL Tombol</label>
                    <input wire:model.defer="button_url" type="url" class="mt-2 settings-field" placeholder="https://">
                    @error('button_url') <p class="mt-2 text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2 space-y-3" data-slider-upload data-slider-component="{{ $this->getId() }}">
                    <label class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Gambar Slide</label>
                    <input type="file" accept="image/*" class="text-sm text-slate-600 dark:text-slate-300">
                    <p class="text-[11px] text-slate-400 dark:text-slate-500">
                        {{ $editingId ? 'Kosongkan jika tidak ingin mengubah gambar. Disarankan 1600x900px, maksimal 3MB.' : 'Disarankan 1600x900px, maksimal 3MB.' }}
                    </p>
                    <div class="space-y-1" data-upload-progress-wrapper hidden>
                        <div class="relative h-1 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700/60">
                            <div class="absolute inset-y-0 left-0 h-full rounded-full bg-[#0284C7] transition-all duration-200 ease-out" data-upload-progress-bar style="width: 0%;"></div>
                        </div>
                        <p class="text-xs text-[#0284C7] dark:text-sky-300" data-upload-progress-text></p>
                    </div>
                    @error('imageData') <p class="text-xs text-rose-400 dark:text-rose-300">{{ $message }}</p> @enderror

                    @if ($imagePreviewUrl)
                        <div class="overflow-hidden rounded-2xl border border-slate-200/70 bg-white/70 shadow-inner shadow-slate-200/40 dark:border-slate-700/60 dark:bg-slate-900/70">
                            <img src="{{ $imagePreviewUrl }}" class="h-40 w-full object-cover" alt="Preview">
                        </div>
                    @elseif ($editingId && $currentImageUrl)
                        <div class="overflow-hidden rounded-2xl border border-slate-200/70 bg-white/70 shadow-inner shadow-slate-200/40 dark:border-slate-700/60 dark:bg-slate-900/70">
                            <img src="{{ $currentImageUrl }}" class="h-40 w-full object-cover" alt="Gambar saat ini">
                        </div>
                    @endif
                </div>
                <div class="md:col-span-2 flex flex-wrap items-center justify-end gap-3">
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        class="btn-soft-emerald text-sm"
                    >
                        <span wire:loading.remove>{{ $editingId ? 'Perbarui Slide' : 'Simpan Slide' }}</span>
                        <span wire:loading>{{ $editingId ? 'Memperbarui...' : 'Menyimpan...' }}</span>
                    </button>
                </div>
            </form>
        </section>

        <section class="settings-surface w-full p-6" data-motion-animated>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-[#0284C7] dark:text-sky-300">Daftar Slide Aktif</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Atur prioritas tampil dan status aktif sesuai kebutuhan kampanye informasi.</p>
                </div>
                <div class="inline-flex items-center gap-2 rounded-full border border-slate-200/70 bg-white/70 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-500 dark:border-slate-700/60 dark:bg-slate-900/60 dark:text-slate-300">
                    {{ $slides->count() }} Slide
                </div>
            </div>

            <div class="mt-6 space-y-4">
                @forelse ($slides as $index => $slide)
                    <div class="group flex flex-col gap-4 rounded-3xl border border-slate-200/70 bg-white/85 p-5 shadow-sm transition-colors duration-200 hover:border-sky-200 dark:border-slate-700/60 dark:bg-slate-900/60 dark:hover:border-sky-500/40">
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div class="flex items-center gap-4">
                                <div class="relative flex h-20 w-36 items-center justify-center overflow-hidden rounded-2xl border border-slate-200/70 bg-white/70 shadow-inner shadow-slate-200/40 dark:border-slate-700/60 dark:bg-slate-900/70">
                                    @if ($slide->image_path)
                                        <img src="{{ $publicDisk->url($slide->image_path) }}" class="h-full w-full object-cover" alt="Slide {{ $index + 1 }}">
                                    @else
                                        <span class="text-xs text-slate-400 dark:text-slate-500">Tanpa gambar</span>
                                    @endif
                                    <span class="absolute left-3 top-3 inline-flex h-7 w-7 items-center justify-center rounded-full bg-white/80 text-xs font-semibold text-slate-600 shadow-sm shadow-slate-200/40 dark:bg-slate-900/70 dark:text-slate-200">#{{ $index + 1 }}</span>
                                </div>
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-200">{{ $slide->title }}</h3>
                                        <span class="inline-flex items-center gap-1 rounded-full border border-slate-200/70 bg-white/70 px-2.5 py-1 text-[11px] font-semibold text-slate-500 transition-colors duration-200 group-hover:border-sky-300 group-hover:text-sky-600 dark:border-slate-700/60 dark:bg-slate-900/60 dark:text-slate-400 dark:group-hover:border-sky-400 dark:group-hover:text-sky-300">
                                            {{ $slide->is_active ? 'Aktif' : 'Draft' }}
                                        </span>
                                    </div>
                                    @if ($slide->subtitle)
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $slide->subtitle }}</p>
                                    @endif
                                    @if ($slide->description)
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ Str::limit($slide->description, 120) }}</p>
                                    @endif
                                    @if ($slide->button_label)
                                        <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">
                                            Tombol: <span class="font-semibold text-slate-600 dark:text-slate-300">{{ $slide->button_label }}</span>
                                            <span class="text-slate-400 dark:text-slate-500">— {{ $slide->button_url }}</span>
                                        </p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                <button wire:click="edit({{ $slide->id }})" class="inline-flex items-center gap-2 rounded-full border border-[#0284C7]/40 px-4 py-1.5 font-semibold text-[#0369a1] transition-colors duration-200 hover:border-[#0284C7]/60 hover:bg-[#0284C7]/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0284C7] focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-sky-500/40 dark:text-sky-300 dark:hover:border-sky-400 dark:hover:bg-sky-500/10 dark:focus-visible:ring-sky-400 dark:focus-visible:ring-offset-slate-900">
                                    Edit
                                </button>
                                <button wire:click="moveUp({{ $slide->id }})" class="inline-flex items-center gap-2 rounded-full border border-slate-200/70 px-4 py-1.5 font-semibold text-slate-600 transition-colors duration-200 hover:border-slate-300 hover:bg-white/70 dark:border-slate-700/60 dark:text-slate-300 dark:hover:border-slate-500 dark:hover:bg-slate-900/60">
                                    Naik
                                </button>
                                <button wire:click="moveDown({{ $slide->id }})" class="inline-flex items-center gap-2 rounded-full border border-slate-200/70 px-4 py-1.5 font-semibold text-slate-600 transition-colors duration-200 hover:border-slate-300 hover:bg-white/70 dark:border-slate-700/60 dark:text-slate-300 dark:hover:border-slate-500 dark:hover:bg-slate-900/60">
                                    Turun
                                </button>
                                <button wire:click="toggle({{ $slide->id }})" class="inline-flex items-center gap-2 rounded-full border px-4 py-1.5 font-semibold transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-slate-900 {{ $slide->is_active ? 'border-emerald-300/70 text-emerald-600 hover:border-emerald-400 hover:bg-emerald-50/70 focus-visible:ring-emerald-200 dark:border-emerald-500/50 dark:text-emerald-300 dark:hover:border-emerald-400 dark:hover:bg-emerald-500/10 dark:focus-visible:ring-emerald-300' : 'border-slate-200/70 text-slate-500 hover:border-slate-300 hover:bg-white/70 focus-visible:ring-slate-300 dark:border-slate-700/60 dark:text-slate-300 dark:hover:border-slate-500 dark:hover:bg-slate-900/60 dark:focus-visible:ring-slate-500' }}">
                                    {{ $slide->is_active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                                <button wire:click="delete({{ $slide->id }})" onclick="return confirm('Hapus slide ini?')" class="inline-flex items-center gap-2 rounded-full border border-rose-200/80 px-4 py-1.5 font-semibold text-rose-600 transition-colors duration-200 hover:border-rose-300 hover:bg-rose-50/80 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-200 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-rose-500/40 dark:text-rose-300 dark:hover:border-rose-400 dark:hover:bg-rose-500/10 dark:focus-visible:ring-rose-400 dark:focus-visible:ring-offset-slate-900">
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-3xl border border-dashed border-slate-200/80 bg-white/70 p-8 text-center text-xs text-slate-400 shadow-inner shadow-slate-200/40 dark:border-slate-700/60 dark:bg-slate-900/60 dark:text-slate-500">
                        Belum ada slide. Tambahkan minimal satu slide untuk menampilkan hero di landing page.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</div>

@once
    @php
        $cspNonceValue = $cspNonce ?? (app()->bound('cspNonce') ? app('cspNonce') : null);
    @endphp
    <script @if($cspNonceValue) nonce="{{ $cspNonceValue }}" @endif>
        (() => {
            const MAX_FILE_SIZE = 3 * 1024 * 1024;

            const initialise = (container) => {
                if (container.dataset.sliderUploaderBound === 'true') {
                    return;
                }

                const componentId = container.getAttribute('data-slider-component');

                if (!componentId) {
                    return;
                }

                const input = container.querySelector('input[type="file"]');
                const wrapper = container.querySelector('[data-upload-progress-wrapper]');
                const bar = container.querySelector('[data-upload-progress-bar]');
                const text = container.querySelector('[data-upload-progress-text]');

                if (!input) {
                    return;
                }

                const resetProgress = () => {
                    if (!wrapper || !bar || !text) {
                        return;
                    }
                    wrapper.hidden = true;
                    bar.style.width = '0%';
                    text.textContent = '';
                };

                input.addEventListener('change', (event) => {
                    const file = event.target.files && event.target.files[0] ? event.target.files[0] : null;

                    if (!file) {
                        Livewire.find(componentId)?.call('clearImagePayload');
                        resetProgress();
                        return;
                    }

                    if (file.size > MAX_FILE_SIZE) {
                        Livewire.find(componentId)?.call('handleImageTooLarge');
                        input.value = '';
                        resetProgress();
                        return;
                    }

                    if (wrapper && bar && text) {
                        wrapper.hidden = false;
                        bar.style.width = '0%';
                        text.textContent = 'Mengunggah gambar... 0%';
                    }

                    const reader = new FileReader();

                    reader.onprogress = (eventProgress) => {
                        if (!wrapper || !bar || !text) {
                            return;
                        }

                        if (eventProgress.lengthComputable) {
                            const percent = Math.round((eventProgress.loaded / eventProgress.total) * 100);
                            bar.style.width = `${percent}%`;
                            text.textContent = `Mengunggah gambar... ${percent}%`;
                        }
                    };

                    reader.onload = () => {
                        const component = Livewire.find(componentId);
                        if (!component) {
                            return;
                        }

                        const result = typeof reader.result === 'string' ? reader.result : '';
                        const base64 = result.includes(',') ? result.split(',')[1] : result;

                        component.call('receiveImagePayload', base64, file.type || 'image/png', file.name || 'image.png');

                        if (wrapper && bar && text) {
                            bar.style.width = '100%';
                            text.textContent = 'Unggahan selesai';
                            window.setTimeout(() => {
                                resetProgress();
                            }, 800);
                        }
                    };

                    reader.onerror = () => {
                        Livewire.find(componentId)?.call('handleImageReadError');
                        input.value = '';
                        resetProgress();
                    };

                    reader.readAsDataURL(file);
                });

                container.dataset.sliderUploaderBound = 'true';
            };

            const boot = () => {
                document.querySelectorAll('[data-slider-upload]').forEach(initialise);
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', boot);
            } else {
                boot();
            }

            document.addEventListener('livewire:load', () => {
                boot();

                if (typeof Livewire !== 'undefined' && Livewire.hook) {
                    Livewire.hook('message.processed', () => boot());
                }
            });
        })();
    </script>
@endonce


