<section class="rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-sm transition-colors duration-200 dark:border-slate-800/60 dark:bg-slate-900/70" data-motion-card>
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-[#0284C7]">Integrasi Bot Telegram</h2>
            <p class="text-xs text-slate-500">Hubungkan akun Telegram admin agar menerima notifikasi sistem secara instan.</p>
        </div>

        @if ($isConnected)
            <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-600">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                Bot Terhubung
            </span>
        @else
            <span class="inline-flex items-center gap-2 rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-600">
                <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                Belum Terhubung
            </span>
        @endif
    </div>

    @if ($isConnected && $account)
        <div class="mt-5 space-y-4 text-sm text-slate-700">
            <p>Bot Telegram sudah terhubung
                @if ($account['username'])
                    sebagai <span class="font-semibold text-slate-900">{{ '@' . $account['username'] }}</span>.
                @else
                    .
                @endif
            </p>
            @if ($account['linked_at'])
                <p>Dihubungkan pada {{ $account['linked_at'] }}.</p>
            @endif
            <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 text-xs text-slate-600">
                <p class="font-semibold text-slate-700">Perintah penting:</p>
                <ul class="mt-2 space-y-1 list-disc pl-5">
                    <li><span class="font-semibold">/broadcast</span> untuk menyiapkan siaran (khusus admin).</li>
                    <li><span class="font-semibold">/bills</span> melihat ringkasan tagihan warga.</li>
                    <li><span class="font-semibold">/help</span> menampilkan daftar perintah lengkap.</li>
                </ul>
            </div>
            @if (! $notificationsEnabled)
                <div class="rounded-2xl border border-amber-200 bg-amber-50/80 p-4 text-xs text-amber-700">
                    Notifikasi bot sedang nonaktif karena pernah dihentikan. Aktifkan lagi agar admin tetap menerima pengingat pembayaran dan laporan.
                </div>
            @endif
        </div>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
            @if (! $notificationsEnabled)
                <button type="button" wire:click="enableNotifications" wire:loading.attr="disabled"
                        class="inline-flex items-center justify-center rounded-full bg-[#0284C7] px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-[#0284C7]/30 transition-colors duration-200 hover:bg-[#0ea5e9]">
                    <span wire:loading.remove>Aktifkan Notifikasi Bot</span>
                    <span wire:loading>Memproses...</span>
                </button>
            @endif
            <button type="button" wire:click="disconnect" wire:loading.attr="disabled" wire:target="disconnect"
                    class="inline-flex items-center justify-center gap-2 rounded-full bg-rose-500 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-rose-200/50 transition-colors duration-200 hover:bg-rose-600 disabled:cursor-not-allowed disabled:opacity-80">
                <span>Putuskan Koneksi</span>
                <svg wire:loading wire:target="disconnect" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle class="opacity-25" cx="12" cy="12" r="10" />
                    <path class="opacity-75" d="M12 2a10 10 0 0 1 10 10" />
                </svg>
            </button>
        </div>
    @else
        <div class="mt-5 space-y-5 text-sm text-slate-700">
            <p>Gunakan kode tautan unik untuk menghubungkan akun Telegram admin ke bot resmi RT.</p>
            <ol class="space-y-2 rounded-2xl border border-slate-200 bg-slate-50/70 p-4 text-xs text-slate-600">
                <li>1. Tekan tombol <span class="font-semibold text-slate-900">"Buat Kode LINK"</span>.</li>
                <li>2. Buka aplikasi Telegram dan cari bot RT Anda.</li>
                <li>3. Ketik <span class="font-semibold text-slate-900">/start {{ $linkToken ? $linkToken : 'LINK-XXXX' }}</span> lalu kirim kode.</li>
            </ol>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <button type="button" wire:click="generateToken" wire:loading.attr="disabled"
                        class="inline-flex items-center justify-center rounded-full bg-[#0284C7] px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-[#0284C7]/30 transition-colors duration-200 hover:bg-[#0ea5e9]">
                    <span wire:loading.remove>Buat Kode LINK</span>
                    <span wire:loading>Menyiapkan...</span>
                </button>
                @if ($linkToken)
                    <button type="button" x-data x-ref="codeBtn" x-on:click="navigator.clipboard.writeText('{{ $linkToken }}')" class="inline-flex items-center justify-center rounded-full border border-[#0284C7]/20 px-5 py-2.5 text-sm font-semibold text-[#0284C7] transition-colors duration-200 hover:bg-sky-50">
                        Salin Kode
                    </button>
                @endif
            </div>
            @if ($linkToken)
                <div class="rounded-2xl border border-[#0284C7]/20 bg-[#0284C7]/5 p-4 text-center text-xs font-semibold tracking-[0.3em] text-[#0369A1]">
                    {{ $linkToken }}
                </div>
                <p class="text-xs text-slate-500">Kode hanya aktif selama 30 menit sejak dibuat.</p>
            @endif
        </div>
    @endif
</section>
