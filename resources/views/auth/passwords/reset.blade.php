@extends('layouts.auth', ['title' => $title ?? null, 'subtitle' => $subtitle ?? null, 'site' => $site])

@section('content')
    <form method="POST" action="{{ route('password.reset.perform', ['token' => $token]) }}">
        @csrf

        @if (session('status'))
            <p class="form-helper text-emerald-600">{{ session('status') }}</p>
        @endif

        <div class="form-grid">
            <div>
                <label for="password">Password Baru</label>
                <div class="input-wrapper" data-icon="password">
                    <input id="password" name="password" type="password" required placeholder="Minimal 8 karakter">
                </div>
                <div class="password-strength" data-password-strength="password">
                    <div class="password-strength__track">
                        <div class="password-strength__bar" data-strength-bar></div>
                    </div>
                    <p class="password-strength__label" data-strength-text>Masukkan password untuk mengetahui kekuatannya.</p>
                </div>
                @error('password')
                    <p class="form-helper text-rose-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation">Konfirmasi Password</label>
                <div class="input-wrapper" data-icon="password">
                    <input id="password_confirmation" name="password_confirmation" type="password" required placeholder="Ulangi password baru">
                </div>
            </div>
        </div>

        <div class="auth-checkbox">
            <input type="checkbox" class="toggle-password" data-password-target="password" data-password-target-second="password_confirmation">
            <span>Tampilkan password</span>
        </div>

        <button type="submit">Simpan Password Baru</button>

        <div class="auth-alert">
            Setelah password diganti, Anda dapat masuk kembali dari <a href="{{ route('login') }}" class="link">halaman login</a>.
        </div>
    </form>
@endsection

@push('scripts')
@php
    $cspNonceValue = $cspNonce ?? (app()->bound('cspNonce') ? app('cspNonce') : null);
@endphp
<script @if($cspNonceValue) nonce="{{ $cspNonceValue }}" @endif>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.toggle-password').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            var targets = [checkbox.dataset.passwordTarget, checkbox.dataset.passwordTargetSecond]
                .map(function (id) { return document.getElementById(id); })
                .filter(Boolean);

            targets.forEach(function (target) {
                target.setAttribute('type', checkbox.checked ? 'text' : 'password');
            });
        });
    });

    if (window.SIRW && window.SIRW.passwordStrength) {
        window.SIRW.passwordStrength.init();
    }
});
</script>
@endpush
