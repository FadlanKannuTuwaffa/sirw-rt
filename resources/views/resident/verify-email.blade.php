@extends('layouts.auth', [
    'title' => $title ?? 'Verifikasi Email Warga',
    'subtitle' => $subtitle ?? null,
    'site' => $site ?? [],
    'backRoute' => $backRoute ?? null,
    'backLabel' => $backLabel ?? null,
])

@section('welcome')
    <p class="welcome-text">{{ $welcome ?? 'Masukkan kode OTP untuk menyelesaikan proses verifikasi email Anda.' }}</p>
@endsection

@section('content')
    @if (session('status'))
        <div class="auth-alert">
            {{ session('status') }}
        </div>
    @endif

    <div class="auth-alert" style="margin-top: 1.25rem;">
        <p class="form-helper" style="margin-bottom: 0.75rem;">
            {{ $isChange
                ? 'Kami mengirim kode OTP ke email baru yang ingin Anda gunakan. Pastikan perubahan ini benar-benar Anda lakukan.'
                : 'Kami mengirim kode OTP ke email akun Anda. Gunakan kode tersebut untuk memastikan akses portal dilakukan oleh Anda.'
            }}
        </p>
        <p class="form-helper" style="margin-bottom: 0.5rem;">
            <strong>Email tujuan:</strong> {{ $maskedEmail }}
        </p>
        @if ($expiresAt)
            <p class="form-helper">
                <strong>Berlaku hingga:</strong> {{ $expiresAt->timezone(config('app.timezone'))->format('d M Y H:i') }}
            </p>
        @endif
    </div>

    <form method="POST" action="{{ route('resident.verification.verify') }}" style="margin-top: 1.75rem;">
        @csrf
        <input type="hidden" name="context" value="{{ $context }}">
        <div class="form-grid">
            <div>
                <label for="otp">Kode OTP</label>
                <div class="input-wrapper" data-icon="password">
                    <input
                        id="otp"
                        name="otp"
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        maxlength="6"
                        value="{{ old('otp') }}"
                        placeholder="123456"
                        required
                        autocomplete="one-time-code"
                    >
                </div>
                @error('otp')
                    <p class="form-helper text-rose-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <button type="submit" class="btn-success" style="margin-top: 1.75rem;">
            {{ $buttonLabel ?? ($isChange ? 'Konfirmasi Email Baru' : 'Verifikasi Sekarang') }}
        </button>
    </form>

    <form method="POST" action="{{ route('resident.verification.resend', ['context' => $context]) }}" id="resend-verification-form">
        @csrf
        <input type="hidden" name="context" value="{{ $context }}">
    </form>

    <p class="small-text" style="margin-top: 1.5rem;">
        {{ $resendPrompt ?? 'Belum menerima email verifikasi?' }}
        <a href="#" class="link" onclick="event.preventDefault(); document.getElementById('resend-verification-form').submit();">
            Kirim ulang kode
        </a>
    </p>
@endsection
