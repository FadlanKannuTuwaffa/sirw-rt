@extends('layouts.auth', ['title' => $title ?? null, 'subtitle' => $subtitle ?? null, 'site' => $site])

@section('welcome')
    <p class="welcome-text">Buat Akun Warga Baru &#127969;<br><span class="small-text">Lengkapi data di bawah agar pengurus dapat memverifikasi keanggotaan Anda.</span></p>
@endsection

@section('content')
<form method="POST" action="{{ route('register') }}">
    @csrf
    <div class="form-grid two-cols">
        <div>
            <label for="name">Nama Lengkap</label>
            <div class="input-wrapper" data-icon="user">
                <input id="name" name="name" type="text" value="{{ old('name') }}" required placeholder="Nama sesuai identitas">
            </div>
            @error('name')
                <p class="form-helper text-rose-500">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="nik">NIK (16 digit)</label>
            <div class="input-wrapper" data-icon="nik">
                <input id="nik" name="nik" type="text" maxlength="16" value="{{ old('nik') }}" required placeholder="3201xxxxxxxxxxxx">
            </div>
            @error('nik')
                <p class="form-helper text-rose-500">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="email">Email</label>
            <div class="input-wrapper" data-icon="email">
                <input id="email" name="email" type="email" value="{{ old('email') }}" required placeholder="email@domain.com">
            </div>
            @error('email')
                <p class="form-helper text-rose-500">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="username">Username (opsional)</label>
            <div class="input-wrapper" data-icon="username">
                <input id="username" name="username" type="text" value="{{ old('username') }}" placeholder="username unik">
            </div>
            @error('username')
                <p class="form-helper text-rose-500">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="phone">Nomor Telepon</label>
            <div class="input-wrapper" data-icon="phone">
                <input id="phone" name="phone" type="text" value="{{ old('phone') }}" placeholder="08xxxxxxxxxx">
            </div>
            @error('phone')
                <p class="form-helper text-rose-500">{{ $message }}</p>
            @enderror
        </div>
        <div class="two-span">
            <label for="alamat">Alamat Lengkap</label>
            <div class="input-wrapper" data-icon="home">
                <textarea id="alamat" name="alamat" rows="3" placeholder="Nama jalan, nomor rumah, RT/RW">{{ old('alamat') }}</textarea>
            </div>
            @error('alamat')
                <p class="form-helper text-rose-500">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="register_password">Password</label>
            <div class="input-wrapper" data-icon="password">
                <input id="register_password" name="password" type="password" required placeholder="Minimal 8 karakter">
            </div>
            <div class="password-strength" data-password-strength="register_password">
                <div class="password-strength__track">
                    <div class="password-strength__bar" data-strength-bar></div>
                </div>
                <p class="password-strength__label" data-strength-text>Masukkan password untuk mengetahui kekuatannya.</p>
            </div>
            <label class="auth-checkbox">
                <input type="checkbox" class="toggle-password" data-password-target="register_password">
                <span>Tampilkan password</span>
            </label>
            @error('password')
                <p class="form-helper text-rose-500">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="register_password_confirmation">Konfirmasi Password</label>
            <div class="input-wrapper" data-icon="password">
                <input id="register_password_confirmation" name="password_confirmation" type="password" required placeholder="Ulangi password">
            </div>
            <label class="auth-checkbox">
                <input type="checkbox" class="toggle-password" data-password-target="register_password_confirmation">
                <span>Tampilkan password</span>
            </label>
        </div>
    </div>

    <p class="auth-alert">Pastikan NIK Anda sudah dimasukkan oleh pengurus. Jika belum, pendaftaran akan ditolak secara otomatis dan Anda diminta menghubungi admin RT.</p>

    <button type="submit" class="btn-success">Daftar Sekarang</button>

    <p class="small-text">Sudah punya akun? <a href="{{ route('login') }}" class="link">Masuk</a></p>
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

    if (window.SIRW && window.SIRW.passwordStrength) {
        window.SIRW.passwordStrength.init();
    }
});
</script>
@endpush
