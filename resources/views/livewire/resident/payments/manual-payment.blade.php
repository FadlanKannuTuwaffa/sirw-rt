@php
    use App\Services\ResidentLanguageService as ResidentLang;
@endphp

<div class="space-y-10 font-['Inter'] text-slate-700 dark:text-slate-200" data-resident-stack>
    <section class="p-6" data-resident-card data-variant="muted" data-resident-fade data-motion-animated>
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ ResidentLang::translate('Pembayaran Manual', 'Manual Payment') }}</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ ResidentLang::translate('Unggah bukti transfer untuk diverifikasi oleh pengurus.', 'Upload your transfer receipt so the committee can verify it.') }}</p>
            </div>
            <a href="{{ route('resident.bills') }}" class="inline-flex items-center gap-2 rounded-full border border-transparent bg-[#0284C7]/15 px-4 py-2 text-xs font-semibold text-[#0284C7] transition-colors duration-200 hover:bg-[#0284C7] hover:text-white dark:bg-sky-500/15 dark:text-sky-200 dark:hover:bg-sky-500 dark:hover:text-slate-900">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17.25 4.5 12l5.25-5.25M4.5 12h15" />
                </svg>
                {{ ResidentLang::translate('Kembali ke daftar tagihan', 'Back to bill list') }}
            </a>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-3">
            <div data-resident-card data-variant="muted" class="p-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">{{ ResidentLang::translate('Tagihan', 'Bill') }}</p>
                <p class="mt-1 text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $bill->title }}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ ResidentLang::translate('Invoice', 'Invoice') }}: {{ $bill->invoice_number ?? 'INV-' . $bill->id }}</p>
            </div>
            <div data-resident-card data-variant="muted" class="p-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">{{ ResidentLang::translate('Nominal', 'Amount') }}</p>
                <p class="mt-1 text-xl font-semibold text-[#0284C7]">Rp {{ number_format($bill->amount) }}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    {{ ResidentLang::translate('Jatuh tempo', 'Due date') }}:
                    {{ optional($bill->due_date)->translatedFormat('d M Y') ?? ResidentLang::translate('Tidak ditentukan', 'Not specified') }}
                </p>
            </div>
            <div data-resident-card data-variant="muted" class="p-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">{{ ResidentLang::translate('Status', 'Status') }}</p>
                <p class="mt-1 text-sm font-semibold text-slate-700 dark:text-slate-200">{{ \Illuminate\Support\Str::headline($bill->status) }}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    @if ($payment->manual_proof_path)
                        {{ ResidentLang::translate('Bukti sudah diunggah pada', 'Proof uploaded at') }} {{ optional($payment->manual_proof_uploaded_at)->translatedFormat('d M Y H:i') ?? '-' }}.
                    @else
                        {{ ResidentLang::translate('Menunggu bukti transfer untuk diverifikasi.', 'Waiting for transfer proof to be verified.') }}
                    @endif
                </p>
            </div>
        </div>

        @if (session('status'))
            <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50/90 p-4 text-xs font-medium text-emerald-700 shadow-sm shadow-emerald-100">
                {{ session('status') }}
            </div>
        @endif
    </section>

    <section class="p-6" data-resident-card data-variant="muted" data-resident-fade data-motion-animated>
        <h2 class="text-sm font-semibold uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">{{ ResidentLang::translate('Pilih Tujuan Transfer', 'Select Transfer Destination') }}</h2>
        <div class="mt-4 grid gap-4 lg:grid-cols-2">
            @foreach ($destinations as $destination)
                @php
                    $isActive = $selected_channel === ($destination['id'] ?? null);
                    $accountName = $destination['account_name'] ?? null;
                    $accountNumber = $destination['account_number'] ?? null;
                @endphp
                <button
                    type="button"
                    wire:click="$set('selected_channel', '{{ $destination['id'] }}')"
                    wire:key="manual-destination-{{ $destination['id'] }}"
                    @class([
                        'flex w-full flex-col gap-3 rounded-2xl border px-4 py-4 text-left transition-colors duration-200',
                        'border-[#0284C7] bg-[#0284C7]/8 shadow-lg shadow-[#0284C7]/20 ring-1 ring-[#0284C7]/20' => $isActive,
                        'border-slate-200 bg-white/80 hover:bg-[#0284C7]/5 dark:border-slate-700 dark:bg-slate-900/70' => ! $isActive,
                    ])
                >
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[0.65rem] font-semibold uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">{{ \Illuminate\Support\Str::upper($destination['type'] ?? 'bank') }}</p>
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-100">{{ $destination['label'] ?? $accountNumber }}</p>
                        </div>
                        @if ($isActive)
                            <span class="rounded-full bg-[#0284C7] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] text-white">Dipilih</span>
                        @endif
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400">
                        @if ($accountNumber)
                            <p>Nomor: <span class="font-semibold text-slate-700 dark:text-slate-100">{{ $accountNumber }}</span></p>
                        @endif
                        @if ($accountName)
                            <p>a.n {{ $accountName }}</p>
                        @endif
                        @if (!empty($destination['notes']))
                            <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">{{ $destination['notes'] }}</p>
                        @endif
                    </div>
                </button>
            @endforeach
        </div>
    </section>

    @if ($manual_instructions)
    <section class="p-6 text-sm text-amber-700 dark:text-amber-200" data-resident-card data-variant="muted" data-resident-fade data-motion-animated>
            <h3 class="text-xs font-semibold uppercase tracking-[0.3em] text-amber-500 dark:text-amber-300">{{ ResidentLang::translate('Instruksi Pembayaran', 'Payment Instructions') }}</h3>
            <p class="mt-2 whitespace-pre-line leading-relaxed">{{ $manual_instructions }}</p>
        </section>
    @endif

    <section class="p-6" data-resident-card data-variant="muted" data-resident-fade data-motion-animated>
        <h2 class="text-sm font-semibold uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">{{ ResidentLang::translate('Unggah Bukti Transfer', 'Upload Transfer Proof') }}</h2>
        <form wire:submit.prevent="submitProof" x-data="manualProofUploader()" class="mt-5 space-y-5">
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white/70 p-6 text-sm text-slate-500 transition-colors duration-200 hover:bg-slate-50/80 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-300 dark:hover:bg-slate-900/60">
                <label for="proof-upload" class="flex cursor-pointer flex-col items-center justify-center gap-3" x-on:dragover.prevent x-on:drop.prevent="handleDroppedFile($event)">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-[#0284C7]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9 4.5-4.5m0 0 4.5 4.5m-4.5-4.5V15" />
                    </svg>
                    <div class="text-center">
                        <p class="font-semibold text-slate-700 dark:text-slate-200">{{ ResidentLang::translate('Tarik berkas ke sini atau pilih dari perangkat', 'Drag a file here or choose from your device') }}</p>
                        <p class="mt-1 text-xs text-slate-400">{{ ResidentLang::translate('Format yang didukung: JPG, JPEG, PNG, PDF. Maks 5 MB.', 'Supported formats: JPG, JPEG, PNG, PDF. Max 5 MB.') }}</p>
                    </div>
                    <input id="proof-upload" type="file" x-ref="input" @change="handleChange" class="hidden" accept=".jpg,.jpeg,.png,.pdf">
                </label>
                @error('proof') <p class="mt-3 text-xs font-semibold text-rose-500">{{ $message }}</p> @enderror
                @if ($encoded_proof_name)
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-3 rounded-xl bg-slate-50 px-4 py-2 text-xs text-slate-500 dark:bg-slate-800/60 dark:text-slate-300">
                        <div>
                            <p class="font-semibold text-slate-600 dark:text-slate-100">{{ $encoded_proof_name }}</p>
                            @if ($encoded_proof_size)
                                <p class="mt-0.5 text-[11px] text-slate-400">{{ number_format($encoded_proof_size / 1024, 1) }} KB</p>
                            @endif
                        </div>
                        <button type="button" class="inline-flex items-center gap-1 rounded-full border border-slate-300 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500 transition-colors duration-200 hover:bg-rose-50/80 hover:text-rose-500 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-rose-500/10" @click.prevent="clearSelection">
                            {{ ResidentLang::translate('Hapus', 'Remove') }}
                        </button>
                    </div>
                @endif
            </div>

            <div>
                <label class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">{{ ResidentLang::translate('Catatan kepada pengurus (opsional)', 'Notes to management (optional)') }}</label>
                <textarea wire:model.defer="additional_notes" rows="3" data-resident-control class="mt-2 w-full text-sm text-slate-700 dark:text-slate-200"></textarea>
                @error('additional_notes') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>

            @if ($payment->manual_proof_path)
                <div class="rounded-2xl border border-slate-200 bg-white/90 p-4 text-xs text-slate-500 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300">
                    <p class="font-semibold text-slate-600 dark:text-slate-200">{{ ResidentLang::translate('Bukti sebelumnya', 'Previous proof') }}</p>
                    <p class="mt-1">{{ ResidentLang::translate('Anda dapat mengganti bukti dengan mengunggah berkas baru.', 'You can replace the proof by uploading a new file.') }}</p>
                    <a href="{{ route('resident.bills.manual-proof', $payment) }}" target="_blank" class="mt-2 inline-flex items-center gap-2 rounded-full border border-[#0284C7]/30 bg-[#0284C7]/10 px-4 py-1.5 text-[11px] font-semibold uppercase tracking-[0.2em] text-[#0284C7] transition-colors duration-200 hover:bg-[#0284C7] hover:text-white">
                        {{ ResidentLang::translate('Lihat Bukti Unggahan', 'View Uploaded Proof') }}
                    </a>
                </div>
            @endif

            <div class="flex items-center justify-end">
                <button type="submit" wire:loading.attr="disabled" class="inline-flex items-center gap-2 rounded-full bg-[#0284C7] px-6 py-2 text-sm font-semibold text-white shadow-lg shadow-[#0284C7]/40 transition-colors duration-200 hover:bg-[#0ea5e9] focus:outline-none focus:ring-2 focus:ring-[#0284C7]/40 focus:ring-offset-2 focus:ring-offset-white">
                    <span wire:loading.remove>{{ ResidentLang::translate('Kirim Bukti Pembayaran', 'Submit Payment Proof') }}</span>
                    <span wire:loading>{{ ResidentLang::translate('Mengunggah...', 'Uploading...') }}</span>
                </button>
            </div>
        </form>
    </section>
</div>

@push('scripts')
    @php
        $cspNonceValue = $cspNonce ?? (app()->bound('cspNonce') ? app('cspNonce') : null);
    @endphp
    <script @if($cspNonceValue) nonce="{{ $cspNonceValue }}" @endif>
        function manualProofUploader() {
            return {
                handleChange(event) {
                    const file = event.target.files?.[0] ?? null;
                    this.processFile(file);
                    this.resetInput();
                },
                handleDroppedFile(event) {
                    const file = event.dataTransfer?.files?.[0] ?? null;
                    this.processFile(file);
                    this.resetInput();
                },
                processFile(file) {
                    if (!file) {
                        this.$wire.clearEncodedProofFromClient();
                        return;
                    }

                    const allowed = ['image/jpeg', 'image/png', 'application/pdf'];
                    const limit = 5 * 1024 * 1024;

                    if (!allowed.includes(file.type)) {
                        this.notifyClientError('{{ addslashes(ResidentLang::translate('Format bukti harus JPG, JPEG, PNG, atau PDF.', 'Proof must be JPG, JPEG, PNG, or PDF.')) }}');
                        return;
                    }

                    if (file.size > limit) {
                        this.notifyClientError('{{ addslashes(ResidentLang::translate('Ukuran bukti maksimal 5 MB.', 'Maximum file size is 5 MB.')) }}');
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = () => {
                        const result = reader.result ?? '';
                        const base64 = result.includes(',')
                            ? result.split(',')[1]
                            : result;

                        if (!base64) {
                            this.notifyClientError('{{ addslashes(ResidentLang::translate('Gagal memproses bukti, silakan coba lagi.', 'Failed to process proof, please try again.')) }}');
                            return;
                        }

                        this.$wire.receiveEncodedProof(base64, file.name, file.type, file.size);
                    };
                    reader.onerror = () => {
                        this.notifyClientError('{{ addslashes(ResidentLang::translate('Gagal membaca berkas, silakan coba lagi.', 'Failed to read file, please try again.')) }}');
                    };

                    reader.readAsDataURL(file);
                },
                clearSelection() {
                    this.resetInput();
                    this.$wire.clearEncodedProofFromClient();
                },
                resetInput() {
                    if (this.$refs.input) {
                        this.$refs.input.value = '';
                    }
                },
                notifyClientError(message) {
                    this.$wire.reportProofClientError(message);
                }
            };
        }
    </script>
@endpush
