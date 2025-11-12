@php
    use Illuminate\Support\Str;
@endphp

<div class="space-y-10 font-['Inter'] text-slate-800 dark:text-slate-100" data-admin-stack wire:poll.20s>
    <section class="rounded-3xl border border-slate-200/70 bg-white/95 p-8 shadow-sm transition-colors duration-300 dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card>
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="space-y-4">
                <p class="text-sm font-medium text-sky-600 dark:text-sky-300">Dashboard data warga</p>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Potret komunitas Anda</h1>
                <p class="max-w-xl text-sm leading-relaxed text-slate-500 dark:text-slate-400">Statistik diperbarui secara realtime. Gunakan filter di bawah untuk memfokuskan data dan lakukan tindakan cepat langsung dari daftar warga.</p>
            </div>
            <dl class="flex items-center gap-3 rounded-full border border-slate-200/80 bg-white px-5 py-3 text-sm font-medium text-sky-600 shadow-inner shadow-slate-200/50 dark:border-sky-500/40 dark:bg-slate-900/70 dark:text-sky-300">
                <dt class="sr-only">Warga online</dt>
                <dd>{{ number_format($stats['online']) }} warga aktif dalam 3 menit terakhir</dd>
            </dl>
        </div>
    </section>

    <section class="grid gap-4 lg:grid-cols-4" data-motion-animated>
        <article class="space-y-3 p-6" data-metric-card data-accent="sky">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total warga</p>
            <p class="text-3xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format($stats['total']) }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Jumlah keseluruhan warga terdaftar.</p>
        </article>
        <article class="space-y-3 p-6" data-metric-card data-accent="emerald">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Status aktif</p>
            <p class="text-3xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format($stats['aktif']) }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Warga yang masih tinggal dan statusnya aktif.</p>
        </article>
        <article class="space-y-3 p-6" data-metric-card data-accent="amber">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Telah pindah</p>
            <p class="text-3xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format($stats['pindah']) }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Warga yang sudah pindah domisili.</p>
        </article>
        <article class="space-y-3 p-6" data-metric-card data-accent="purple">
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Arsip</p>
            <p class="text-3xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format($stats['arsip']) }}</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Data warga yang tersimpan di arsip.</p>
        </article>
    </section>

    <section class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition-colors duration-200 hover:border-sky-200 dark:border-slate-700 dark:bg-slate-900/80" data-motion-card>
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Kelola Data Warga</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Cari, filter, dan lakukan tindakan terhadap data warga secara instan.</p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center" data-admin-filters>
                <div class="relative w-full sm:w-64">
                    <input wire:model.debounce.500ms="search" type="search" placeholder="Cari nama, email, atau 4 digit terakhir kontak..." class="w-full rounded-full border border-slate-200 bg-white/90 py-2.5 pl-10 pr-3 text-sm text-slate-600 shadow-inner shadow-slate-100 transition-all duration-300 focus:border-sky-400 focus:ring-2 focus:ring-sky-200 focus:ring-offset-1 focus:ring-offset-white dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200 dark:placeholder-slate-500">
                    <svg class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400 dark:text-slate-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 3.478 9.772l3.875 3.875a.75.75 0 1 0 1.06-1.06l-3.875-3.875A5.5 5.5 0 0 0 9 3.5Zm-4 5.5a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z" clip-rule="evenodd" />
                    </svg>
                </div>
                <select wire:model.live="status" wire:loading.attr="disabled" class="w-full rounded-full border border-slate-200 bg-white/90 py-2.5 px-4 text-sm text-slate-600 shadow-inner shadow-slate-100 transition-all duration-300 focus:border-sky-400 focus:ring-2 focus:ring-sky-200 focus:ring-offset-1 focus:ring-offset-white sm:w-auto dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200">
                    <option value="aktif">Aktif</option>
                    <option value="pindah">Pindah</option>
                    <option value="nonaktif">Nonaktif</option>
                    <option value="arsip">Arsip</option>
                    <option value="semua">Semua</option>
                </select>
                <a wire:navigate href="{{ route('admin.warga.create') }}" class="btn-soft-emerald w-full text-xs sm:w-auto sm:text-sm">
                    + Tambah Warga
                </a>
            </div>
        </div>
        <div class="mt-6 overflow-hidden overflow-x-auto rounded-3xl border border-slate-200/60 shadow-inner shadow-slate-100 dark:border-slate-700 dark:shadow-slate-900/40">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-900/90 text-[11px] uppercase tracking-wide text-white/95 dark:bg-slate-800/80">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold">Identitas</th>
                        <th class="px-5 py-3 text-left font-semibold">Kontak</th>
                        <th class="px-5 py-3 text-left font-semibold">Status</th>
                        <th class="px-5 py-3 text-left font-semibold">Terakhir Aktif</th>
                        <th class="px-5 py-3 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100/80 bg-white/95 dark:divide-slate-800/60 dark:bg-slate-900/70" wire:loading.remove wire:target="status,search,perPage,page">
                    @forelse ($warga as $user)
                        <tr wire:key="user-{{ $user->id }}" class="transition-all duration-300 hover:bg-slate-50/70 dark:hover:bg-slate-800/60">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-sky-100 via-white to-sky-200 text-sm font-semibold uppercase text-sky-600 shadow-inner shadow-sky-100 dark:from-slate-800 dark:via-slate-900 dark:to-slate-800 dark:text-sky-300">
                                        {{ Str::of($user->name)->trim()->explode(' ')->map(fn ($part) => Str::substr($part, 0, 1))->take(2)->implode('') }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-800 dark:text-slate-100">{{ $user->name }}</p>
                                        <p class="text-xs text-slate-400 dark:text-slate-500">NIK: {{ $user->masked_nik ?? '********' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-500 dark:text-slate-400">
                                <p>Email: {{ $user->email ?? '-' }}</p>
                                <p>Telepon: {{ $user->masked_phone ?? '-' }}</p>
                                <p>Alamat: {{ Str::limit($user->alamat, 40) }}</p>
                            </td>
                            <td class="px-5 py-4">
                                @php
                                    $statusClass = match ($user->status) {
                                        'aktif' => 'bg-emerald-50 border-emerald-300 text-emerald-700 dark:border-emerald-500/40 dark:text-emerald-200',
                                        'pindah' => 'bg-sky-50 border-sky-300 text-sky-700 dark:border-sky-500/40 dark:text-sky-200',
                                        'nonaktif' => 'bg-amber-50 border-amber-300 text-amber-600 dark:border-amber-500/40 dark:text-amber-200',
                                        default => 'bg-slate-100 border-slate-300 text-slate-600 dark:border-slate-600 dark:text-slate-300',
                                    };
                                @endphp
                                <span class="inline-flex items-center gap-2 rounded-full border px-4 py-1.5 text-xs font-semibold shadow-sm shadow-slate-200/70 transition-all duration-300 {{ $statusClass }}">
                                    @php
                                        $dotClass = $user->is_online
                                            ? 'bg-emerald-500 animate-pulse shadow-emerald-500/50'
                                            : 'bg-slate-400 dark:bg-slate-600';
                                    @endphp
                                    <span class="h-2 w-2 rounded-full transition-all duration-300 dark:shadow-lg dark:shadow-slate-900/20 {{ $dotClass }}"></span>
                                    {{ Str::headline($user->status) }}
                                </span>
                                @if ($user->trashed())
                                    <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">Diarsipkan pada {{ optional($user->deleted_at)->translatedFormat('d M Y') }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-500 dark:text-slate-400">
                                {{ $user->last_seen_at ? $user->last_seen_at->diffForHumans() : 'Belum pernah login' }}
                            </td>
                            <td class="px-5 py-4 text-right text-xs">
                                <div class="flex items-center justify-end gap-2">
                                    @if ($user->trashed())
                                        <button wire:click="restoreStatus({{ $user->id }})" class="rounded-full border border-emerald-300 px-4 py-1 font-semibold text-emerald-600 transition-all duration-300 hover:-translate-y-0.5 hover:bg-emerald-50 dark:border-emerald-500/40 dark:text-emerald-200 dark:hover:bg-emerald-500/10">Pulihkan</button>
                                    @else
                                        <a wire:navigate href="{{ route('admin.warga.edit', $user) }}" class="rounded-full border border-sky-300 px-4 py-1 font-semibold text-sky-600 transition-all duration-300 hover:-translate-y-0.5 hover:bg-sky-50 dark:border-sky-500/40 dark:text-sky-200 dark:hover:bg-sky-500/10">Edit</a>
                                        <button wire:click="markAsMoved({{ $user->id }})" class="rounded-full border border-slate-200 px-4 py-1 font-semibold text-slate-600 transition-all duration-300 hover:-translate-y-0.5 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800/60">Pindah</button>
                                        <button wire:click="markAsInactive({{ $user->id }})" class="rounded-full border border-amber-300 px-4 py-1 font-semibold text-amber-600 transition-all duration-300 hover:-translate-y-0.5 hover:bg-amber-50 dark:border-amber-500/40 dark:text-amber-200 dark:hover:bg-amber-500/10">Nonaktif</button>
                                        <button wire:click="deleteUser({{ $user->id }})" class="rounded-full border border-rose-300 px-4 py-1 font-semibold text-rose-600 transition-all duration-300 hover:-translate-y-0.5 hover:bg-rose-50 dark:border-rose-500/40 dark:text-rose-200 dark:hover:bg-rose-500/10">Arsip</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-xs text-slate-400 dark:text-slate-500">
                                @if ($term !== '')
                                    Data “{{ $term }}” tidak ditemukan.
                                @else
                                    Belum ada data warga sesuai filter.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tbody class="divide-y divide-slate-100/80 bg-white/95 dark:divide-slate-800/60 dark:bg-slate-900/70" wire:loading.flex wire:target="status,search,perPage,page">
                    @foreach (range(1, 5) as $skeleton)
                        <tr class="animate-pulse">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-11 w-11 rounded-full bg-slate-100 dark:bg-slate-800/70"></div>
                                    <div class="space-y-2">
                                        <div class="h-3 w-32 rounded bg-slate-100 dark:bg-slate-800/70"></div>
                                        <div class="h-2 w-24 rounded bg-slate-100 dark:bg-slate-800/70"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="space-y-2">
                                    <div class="h-2 w-48 rounded bg-slate-100 dark:bg-slate-800/70"></div>
                                    <div class="h-2 w-40 rounded bg-slate-100 dark:bg-slate-800/70"></div>
                                    <div class="h-2 w-44 rounded bg-slate-100 dark:bg-slate-800/70"></div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="h-6 w-24 rounded-full bg-slate-100 dark:bg-slate-800/70"></div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="h-3 w-20 rounded bg-slate-100 dark:bg-slate-800/70"></div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <div class="h-6 w-16 rounded-full bg-slate-100 dark:bg-slate-800/70"></div>
                                    <div class="h-6 w-16 rounded-full bg-slate-100 dark:bg-slate-800/70"></div>
                                    <div class="h-6 w-16 rounded-full bg-slate-100 dark:bg-slate-800/70"></div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex flex-col items-center justify-between gap-4 text-xs text-slate-500 dark:text-slate-400 md:flex-row">
            <div class="rounded-full border border-slate-200/80 bg-white/80 px-4 py-2 shadow-inner shadow-slate-100 dark:border-slate-700 dark:bg-slate-900/70">Menampilkan {{ $warga->firstItem() ?? 0 }}-{{ $warga->lastItem() ?? 0 }} dari {{ $warga->total() }} warga</div>
            <div class="text-sm">
                {{ $warga->onEachSide(1)->links() }}
            </div>
        </div>
    </section>
</div>
