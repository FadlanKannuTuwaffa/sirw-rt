@extends('layouts.auth', ['title' => $title ?? null, 'subtitle' => $subtitle ?? null, 'site' => $site])

@section('content')
    <form method="POST" action="{{ route('password.otp.verify', ['token' => $token]) }}">
        @csrf

        @if (session('status'))
            <p class="form-helper text-emerald-600">{{ session('status') }}</p>
        @endif

        <div class="form-grid">
            <div>
                <label for="otp">Kode OTP</label>
                <div class="input-wrapper" data-icon="password">
                    <input id="otp" name="otp" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" autofocus required placeholder="Masukkan 6 digit kode">
                </div>
                <p class="form-helper">Kode dikirim ke {{ $maskedEmail }}. Berlaku selama 10 menit.</p>
                @error('otp')
                    <p class="form-helper text-rose-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <button type="submit">Verifikasi Kode</button>

        <div class="auth-alert">
            Tidak menerima kode? <a href="{{ route('password.request') }}" class="link">Kirim ulang OTP</a>.
        </div>
    </form>
@endsection
