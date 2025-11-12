<div class="grid gap-6 font-['Inter'] text-slate-800 lg:grid-cols-3" data-aos="fade-up" data-aos-delay="60">
    <section class="rounded-3xl border border-[#0284C7]/10 bg-white/95 p-6 text-center shadow-xl shadow-sky-100/60 transition-all duration-300 hover:-translate-y-1.5 hover:border-[#0284C7]/30 hover:shadow-2xl">
        <div class="flex flex-col items-center">
            <div class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-full border border-[#0284C7]/15 bg-gradient-to-br from-white via-sky-50 to-[#0284C7]/10 shadow-inner shadow-sky-100">
                @if ($user?->profile_photo_url)
                    <img src="{{ $user->profile_photo_url }}" class="h-full w-full object-cover" alt="Avatar">
                @else
                    <div class="text-2xl font-semibold text-[#0284C7]">
                        {{ Str::of($user->name)->trim()->explode(' ')->map(fn ($part) => Str::substr($part, 0, 1))->take(2)->implode('') }}
                    </div>
                @endif
            </div>
            <h1 class="mt-4 text-xl font-semibold text-slate-900">{{ $user->name }}</h1>
            <p class="text-xs text-slate-400">Administrator Utama</p>
            <p class="mt-2 text-xs text-slate-500">Terakhir aktif {{ $user->last_seen_at?->diffForHumans() ?? 'Belum pernah login' }}</p>
            <a href="{{ route('admin.profile.edit') }}" class="mt-5 inline-flex items-center justify-center rounded-full bg-[#0284C7] px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-[#0284C7]/40 transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#0ea5e9] focus:outline-none focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-2 focus:ring-offset-white">Edit Profil</a>
        </div>
    </section>

    <section class="rounded-3xl border border-[#0284C7]/10 bg-white/95 p-6 shadow-xl shadow-sky-100/60 transition-all duration-300 hover:-translate-y-1.5 hover:border-[#0284C7]/30 hover:shadow-2xl lg:col-span-2">
        <h2 class="text-lg font-semibold text-[#0284C7]">Informasi Akun</h2>
        <div class="mt-5 grid gap-4 md:grid-cols-2">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Nama Lengkap</p>
                <p class="mt-2 text-sm font-semibold text-slate-800">{{ $user->name }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Username</p>
                <p class="mt-2 text-sm font-semibold text-slate-800">{{ $user->username ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Email</p>
                <p class="mt-2 text-sm font-semibold text-slate-800">{{ $user->email ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Nomor Telepon</p>
                <p class="mt-2 text-sm font-semibold text-slate-800">{{ $user->phone ?? '-' }}</p>
            </div>
        </div>

        <div class="mt-8 rounded-2xl border border-[#0284C7]/10 bg-sky-50/70 p-4 shadow-inner shadow-sky-100">
            <h3 class="text-sm font-semibold text-[#0284C7]">Catatan Admin</h3>
            <p class="mt-2 text-sm text-slate-600">{{ $user->notes ?? 'Belum ada catatan.' }}</p>
        </div>
    </section>
    <div class="lg:col-span-3">
        <livewire:admin.profil.telegram-connector />
    </div>
</div>
