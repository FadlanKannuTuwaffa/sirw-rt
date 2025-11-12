<div class="space-y-8 font-[\'Inter\'] text-slate-800" data-admin-stack data-aos="fade-up" data-aos-delay="60">
    <form wire:submit.prevent="updateProfile" class="space-y-6">
        <section class="rounded-3xl border border-[#0284C7]/10 bg-white/95 p-6 shadow-xl shadow-sky-100/60">
            <h2 class="text-lg font-semibold text-[#0284C7]">Informasi Dasar</h2>
            <p class="mt-1 text-xs text-slate-400">Perbarui data identitas admin yang tampil di dashboard dan laporan.</p>

            <div class="mt-6 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Nama Lengkap</label>
                    <input wire:model.defer="name" type="text" class="mt-2 w-full rounded-xl border border-[#0284C7]/20 bg-white/95 py-3 px-4 text-sm shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white">
                    @error('name') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Username</label>
                    <input wire:model.defer="username" type="text" class="mt-2 w-full rounded-xl border border-[#0284C7]/20 bg-white/95 py-3 px-4 text-sm shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white">
                    @error('username') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Nomor Telepon</label>
                    <input wire:model.defer="phone" type="text" class="mt-2 w-full rounded-xl border border-[#0284C7]/20 bg-white/95 py-3 px-4 text-sm shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white">
                    @error('phone') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Catatan</label>
                    <textarea wire:model.defer="notes" rows="3" class="mt-2 w-full rounded-2xl border border-[#0284C7]/20 bg-white/95 py-3 px-4 text-sm text-slate-600 shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white" placeholder="Informasi tambahan mengenai admin"></textarea>
                    @error('notes') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-[#0284C7]/10 bg-white/95 p-6 shadow-xl shadow-sky-100/60">
            <h2 class="text-lg font-semibold text-[#0284C7]">Foto Profil</h2>
            <p class="mt-1 text-xs text-slate-400">Gunakan foto terbaru agar warga mudah mengenali admin.</p>

            <div class="mt-6 flex flex-wrap items-center gap-5">
                <div class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-full border border-[#0284C7]/15 bg-gradient-to-br from-white via-sky-50 to-[#0284C7]/10 shadow-inner shadow-sky-100">
                    @if ($profilePhoto)
                        <img src="{{ $profilePhoto->temporaryUrl() }}" class="h-full w-full object-cover" alt="Preview">
                    @elseif ($user?->profile_photo_url)
                        <img src="{{ $user->profile_photo_url }}" class="h-full w-full object-cover" alt="Avatar">
                    @else
                        <div class="text-xs text-slate-400">Belum ada foto</div>
                    @endif
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Unggah Foto Profil</label>
                    <input wire:model="profilePhoto" type="file" accept="image/*" class="mt-2 text-sm text-slate-600">
                    <p class="mt-1 text-[11px] text-slate-400">Format jpg, jpeg, png, webp. Maksimal 2MB.</p>
                    @error('profilePhoto') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.profile.view') }}" class="rounded-full border border-slate-200 px-5 py-2 text-sm font-semibold text-slate-600 transition-all duration-300 hover:-translate-y-0.5 hover:bg-slate-100">Batal</a>
            <button type="submit" class="rounded-full bg-[#0284C7] px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-[#0284C7]/40 transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#0ea5e9] focus:outline-none focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-2 focus:ring-offset-white">Simpan Informasi</button>
        </div>
    </form>

    <form wire:submit.prevent="{{ $emailOtpSent ? 'verifyEmailOtp' : 'initiateEmailChange' }}" class="rounded-3xl border border-[#0284C7]/10 bg-white/95 p-6 shadow-xl shadow-sky-100/60">
        <h2 class="text-lg font-semibold text-[#0284C7]">Keamanan Email Admin</h2>
        <p class="mt-1 text-xs text-slate-400">Perubahan email membutuhkan konfirmasi password dan kode OTP yang dikirim ke email lama.</p>

        <div class="mt-5 rounded-2xl border border-[#0284C7]/15 bg-[#e0f2fe]/60 p-4">
            <p class="text-xs text-slate-500">Email saat ini:</p>
            <p class="text-sm font-semibold text-slate-700">{{ $currentEmail ?? 'Belum diatur' }}</p>
            @if($pendingEmail && $emailOtpSent)
                <p class="mt-2 text-xs text-[#0284C7]">Email baru menunggu verifikasi: <span class="font-semibold">{{ $pendingEmail }}</span></p>
            @endif
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <div>
                <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Email Baru</label>
                <input wire:model.defer="newEmail" type="email" {{ $emailOtpSent ? 'disabled' : '' }} class="mt-2 w-full rounded-xl border border-[#0284C7]/20 bg-white/95 py-3 px-4 text-sm shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white">
                @error('newEmail') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Password Saat Ini</label>
                <input wire:model.defer="emailCurrentPassword" type="password" {{ $emailOtpSent ? 'disabled' : '' }} class="mt-2 w-full rounded-xl border border-[#0284C7]/20 bg-white/95 py-3 px-4 text-sm shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white">
                @error('emailCurrentPassword') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>

            @if($emailOtpSent)
                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Kode OTP</label>
                    <input wire:model.defer="emailOtp" type="text" inputmode="numeric" maxlength="6" class="mt-2 w-full rounded-xl border border-[#0284C7]/20 bg-white/95 py-3 px-4 text-center text-lg font-semibold tracking-[0.4em] text-[#0369a1] shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white">
                    @error('emailOtp') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
                <div class="rounded-2xl border border-dashed border-[#0284C7]/30 bg-[#f0f9ff]/80 p-4 text-xs text-slate-500">
                    <p class="font-semibold text-[#0284C7]">OTP terkirim ke {{ $currentEmail }}</p>
                    @if($emailOtpExpiresAt)
                        <p class="mt-2">Berlaku hingga {{ $emailOtpExpiresAt }} WIB.</p>
                    @else
                        <p class="mt-2">Kode berlaku selama 10 menit sejak dikirim.</p>
                    @endif
                    <p class="mt-2">Masukkan kode untuk menyelesaikan perubahan email admin.</p>
                </div>
            @endif
        </div>

        <div class="mt-6 flex items-center justify-end gap-3">
            @if($emailOtpSent)
                <button type="button" wire:click="cancelEmailOtp" class="rounded-full border border-slate-200 px-5 py-2 text-sm font-semibold text-slate-600 transition-all duration-300 hover:-translate-y-0.5 hover:bg-slate-100">Batalkan</button>
            @endif
            <button type="submit" class="rounded-full bg-[#0284C7] px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-[#0284C7]/40 transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#0ea5e9] focus:outline-none focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-2 focus:ring-offset-white">
                {{ $emailOtpSent ? 'Verifikasi & Ganti Email' : 'Kirim OTP ke Email Lama' }}
            </button>
        </div>
    </form>

    <form wire:submit.prevent="{{ $passwordOtpSent ? 'verifyPasswordOtp' : 'initiatePasswordChange' }}" class="rounded-3xl border border-[#0284C7]/10 bg-white/95 p-6 shadow-xl shadow-sky-100/60">
        <h2 class="text-lg font-semibold text-[#0284C7]">Keamanan Password Admin</h2>
        <p class="mt-1 text-xs text-slate-400">Wajib verifikasi OTP (email/telegram) dan password lama sebelum mengganti password baru.</p>

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <div>
                <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Password Saat Ini</label>
                <input wire:model.defer="passwordCurrent" type="password" {{ $passwordOtpSent ? 'disabled' : '' }} class="mt-2 w-full rounded-xl border border-[#0284C7]/20 bg-white/95 py-3 px-4 text-sm shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white">
                @error('passwordCurrent') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Password Baru</label>
                <input id="admin_new_password" wire:model.defer="passwordNew" type="password" {{ $passwordOtpSent ? 'disabled' : '' }} class="mt-2 w-full rounded-xl border border-[#0284C7]/20 bg-white/95 py-3 px-4 text-sm shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white">
                @error('passwordNew') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                <div class="password-strength mt-3" data-password-strength="admin_new_password">
                    <div class="password-strength__track">
                        <div class="password-strength__bar" data-strength-bar></div>
                    </div>
                    <p class="password-strength__label" data-strength-text>Masukkan password untuk mengetahui kekuatannya.</p>
                </div>
            </div>
            <div>
                <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Konfirmasi Password</label>
                <input wire:model.defer="passwordNewConfirmation" type="password" {{ $passwordOtpSent ? 'disabled' : '' }} class="mt-2 w-full rounded-xl border border-[#0284C7]/20 bg-white/95 py-3 px-4 text-sm shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white">
                @error('passwordNewConfirmation') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>

            @if($passwordOtpSent)
                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.2em] text-[#0284C7]">Kode OTP</label>
                    <input wire:model.defer="passwordOtp" type="text" inputmode="numeric" maxlength="6" class="mt-2 w-full rounded-xl border border-[#0284C7]/20 bg-white/95 py-3 px-4 text-center text-lg font-semibold tracking-[0.4em] text-[#0369a1] shadow-inner shadow-sky-100 transition-all duration-300 focus:border-[#0284C7] focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-1 focus:ring-offset-white">
                    @error('passwordOtp') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
                <div class="rounded-2xl border border-dashed border-[#0284C7]/30 bg-[#f0f9ff]/80 p-4 text-xs text-slate-500">
                    <p class="font-semibold text-[#0284C7]">OTP dikirim ke email dan Telegram admin yang aktif.</p>
                    <p class="mt-2">Masukkan kode untuk menyelesaikan penggantian password.</p>
                </div>
            @endif
        </div>

        <div class="mt-6 flex items-center justify-end gap-3">
            @if($passwordOtpSent)
                <button type="button" wire:click="cancelPasswordOtp" class="rounded-full border border-slate-200 px-5 py-2 text-sm font-semibold text-slate-600 transition-all duration-300 hover:-translate-y-0.5 hover:bg-slate-100">Batalkan</button>
            @endif
            <button type="submit" class="rounded-full bg-[#0284C7] px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-[#0284C7]/40 transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#0ea5e9] focus:outline-none focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-2 focus:ring-offset-white">
                {{ $passwordOtpSent ? 'Verifikasi & Ganti Password' : 'Kirim OTP Keamanan' }}
            </button>
        </div>
    </form>
</div>
