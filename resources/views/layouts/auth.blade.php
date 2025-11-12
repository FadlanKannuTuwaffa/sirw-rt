<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ($title ?? 'Masuk') . ' - SIRW' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @include('layouts.partials.theme-initializer')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page">
    <div class="auth-wrapper">
        <div class="auth-info">
            <div>
                <h1>{{ $site['name'] ?? 'Sistem Informasi RT' }}</h1>
                <p>{{ $site['tagline'] ?? 'Kelola lingkungan dengan satu dashboard terpadu.' }}</p>
            </div>
            <div class="auth-quick-info">
                <div>
                    <p class="label">Kontak Pengurus</p>
                    <p>{{ $site['contact_phone'] ?? 'Nomor belum tersedia' }}</p>
                    <p>{{ $site['contact_email'] ?? 'Email belum tersedia' }}</p>
                </div>
                <div>
                    <p class="label">Alamat</p>
                    <p>{{ $site['address'] ?? 'Silakan perbarui melalui panel admin.' }}</p>
                </div>
            </div>
        </div>
        <div class="auth-form">
            <div class="auth-form-inner">
                @php
                    $authBackRoute = $backRoute ?? route('landing');
                    $authBackLabel = $backLabel ?? 'Kembali ke beranda';
                @endphp
                <a href="{{ $authBackRoute }}" class="back-link">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 15.75 3 12m0 0 3.75-3.75M3 12h18" />
                    </svg>
                    {{ $authBackLabel }}
                </a>
                <h2>{{ $title ?? 'Masuk' }}</h2>
                @hasSection('welcome')
                    @yield('welcome')
                @else
                    <p class="welcome-text">{{ $subtitle ?? 'Silakan masuk untuk mengakses layanan digital warga.' }}</p>
                @endif
                @hasSection('content')
                    @yield('content')
                @else
                    {{ $slot ?? '' }}
                @endif
            </div>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
