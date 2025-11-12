@extends('layouts.auth', ['title' => $title ?? null, 'subtitle' => $subtitle ?? null, 'site' => $site])

@section('welcome')
    <p class="welcome-text">Selamat datang kembali.<br><span class="small-text">Masuk untuk melanjutkan pengelolaan lingkungan Anda.</span></p>
@endsection

@section('content')
<form method="POST" action="{{ route('login') }}">
    @csrf
    <div class="form-grid">
        <div>
            <label for="identifier">Email atau Username</label>
            <div class="input-wrapper" data-icon="user">
                <input id="identifier" name="identifier" type="text" value="{{ old('identifier') }}" autofocus required placeholder="contoh: warga@example.com" autocomplete="username">
            </div>
            @error('identifier')
                <p class="form-helper text-rose-500">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="login_password">Password</label>
            <div class="input-wrapper" data-icon="password">
                <input id="login_password" name="password" type="password" required placeholder="********" autocomplete="current-password">
            </div>
            @error('password')
                <p class="form-helper text-rose-500">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="auth-checkbox">
        <input type="checkbox" class="toggle-password" data-password-target="login_password">
        <span>Tampilkan password</span>
    </div>

    <div class="flex-between">
        <label class="auth-checkbox">
            <input type="checkbox" name="remember">
            <span>Ingat saya di perangkat ini</span>
        </label>
        <span class="small-text">
            Belum punya akun? <a href="{{ route('register') }}" class="link">Daftar</a><br>
            <a href="{{ route('password.request') }}" class="link">Lupa password?</a>
        </span>
    </div>

    <button type="submit">Masuk</button>

    <div class="auth-alert">
        Jika Anda warga baru namun belum terdaftar, hubungi pengurus dengan menyertakan NIK untuk aktivasi data.
    </div>
</form>
@endsection

@push('scripts')
@php
    $cspNonceValue = $cspNonce ?? (app()->bound('cspNonce') ? app('cspNonce') : null);
@endphp
<script @if($cspNonceValue) nonce="{{ $cspNonceValue }}" @endif>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-password-target]').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            var target = document.getElementById(checkbox.dataset.passwordTarget);
            if (!target) {
                return;
            }
            target.setAttribute('type', checkbox.checked ? 'text' : 'password');
        });
    });
});
</script>
@endpush
