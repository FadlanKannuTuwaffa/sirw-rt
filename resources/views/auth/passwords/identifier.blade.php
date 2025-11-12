@extends('layouts.auth', ['title' => $title ?? null, 'subtitle' => $subtitle ?? null, 'site' => $site])

@section('content')
    <form method="POST" action="{{ route('password.request.submit') }}">
        @csrf
        @if (session('status'))
            <p class="form-helper text-emerald-600">{{ session('status') }}</p>
        @endif
        <div class="form-grid">
            <div>
                <label for="identifier">Email atau Username</label>
                <div class="input-wrapper" data-icon="user">
                    <input id="identifier" name="identifier" type="text" value="{{ old('identifier') }}" autofocus required placeholder="contoh: warga@example.com">
                </div>
                @error('identifier')
                    <p class="form-helper text-rose-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <p class="small-text">Kami akan mengirimkan kode OTP ke email terdaftar. Maksimal 3 permintaan reset per hari.</p>

        <button type="submit">Kirim Kode OTP</button>

        <div class="auth-alert">
            Sudah ingat password? <a href="{{ route('login') }}" class="link">Kembali ke halaman masuk</a>.
        </div>
    </form>
@endsection
