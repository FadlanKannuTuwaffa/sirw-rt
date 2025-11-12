@extends('layouts.auth', [
    'title' => $title ?? 'Konfirmasi Password',
    'subtitle' => 'Demi keamanan, masukkan password Anda sebelum melanjutkan.',
    'site' => $site ?? [],
])

@section('content')
    <form method="POST" action="{{ route('password.confirm.store') }}">
        @csrf

        <div class="form-grid">
            <div>
                <label for="password">Password Akun</label>
                <div class="input-wrapper" data-icon="lock">
                    <input id="password" name="password" type="password" required autocomplete="current-password" placeholder="Masukkan password Anda">
                </div>
                @error('password')
                    <p class="form-helper text-rose-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <button type="submit" class="auth-button">
            Konfirmasi &amp; Lanjutkan
        </button>

        <p class="form-helper text-slate-400">
            Jika lupa password, silakan lakukan <a href="{{ route('password.request') }}" class="text-sky-600 hover:text-sky-700">reset password</a>.
        </p>
    </form>
@endsection
