@extends('layouts.landing_min', ['title' => $title ?? null, 'site' => $site])

@section('content')
<section class="pt-8 pb-12 sm:pt-10 sm:pb-14 md:pt-12 md:pb-16 bg-white transition-colors duration-300 dark:bg-slate-950">
    <div class="container-app max-w-5xl">
        @php
            $visionText = $site['vision'] ?? null;
            $missionItems = collect(preg_split('/\r\n|\n|\r/', $site['mission'] ?? ''))->filter(fn ($line) => trim($line) !== '');
        @endphp
        <h1 class="text-3xl font-semibold text-slate-900 transition-colors duration-300 dark:text-slate-100"><span data-i18n="about.heading_prefix">Tentang</span> {{ $site['name'] ?? 'Sistem Informasi RT' }}</h1>
        <p class="mt-4 text-sm text-slate-600 transition-colors duration-300 dark:text-slate-300">
            @if (! empty($site['about']))
                {{ $site['about'] }}
            @else
                <span data-i18n="about.default_description">SIRW adalah portal digital yang membantu pengurus dan warga mengelola kegiatan lingkungan secara transparan dan kolaboratif.</span>
            @endif
        </p>

        <div class="mt-10 grid gap-6 md:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6 transition-colors duration-300 dark:border-slate-800/70 dark:bg-slate-900/70">
                <h2 class="text-xl font-semibold text-slate-900 transition-colors duration-300 dark:text-slate-100" data-i18n="about.vision_title">Visi</h2>
                <p class="mt-3 text-sm text-slate-600 transition-colors duration-300 dark:text-slate-300">
                    @if ($visionText)
                        {{ $visionText }}
                    @else
                        <span data-i18n="about.default_vision">Mewujudkan lingkungan yang sehat, aman, dan transparan dengan dukungan teknologi yang mudah diakses oleh setiap warga.</span>
                    @endif
                </p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6 transition-colors duration-300 dark:border-slate-800/70 dark:bg-slate-900/70">
                <h2 class="text-xl font-semibold text-slate-900 transition-colors duration-300 dark:text-slate-100" data-i18n="about.mission_title">Misi</h2>
                <ul class="mt-3 space-y-2 text-sm text-slate-600 transition-colors duration-300 dark:text-slate-300">
                    @if ($missionItems->isNotEmpty())
                        @foreach ($missionItems as $index => $missionLine)
                            <li>{{ $index + 1 }}. {{ trim($missionLine) }}</li>
                        @endforeach
                    @else
                        <li data-i18n="about.default_mission_1">1. Menyediakan sistem pencatatan iuran dan keuangan yang terbuka.</li>
                        <li data-i18n="about.default_mission_2">2. Menyebarkan informasi agenda dan pengumuman secara cepat.</li>
                        <li data-i18n="about.default_mission_3">3. Menghadirkan kanal komunikasi terpusat antara warga dan pengurus.</li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="mt-12 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition-colors duration-300 dark:border-slate-800/70 dark:bg-slate-900/80 dark:shadow-slate-900/40">
            <h2 class="text-xl font-semibold text-slate-900 transition-colors duration-300 dark:text-slate-100" data-i18n="about.managers_title">Pengurus Utama</h2>
            <p class="mt-2 text-sm text-slate-500 transition-colors duration-300 dark:text-slate-400" data-i18n="about.managers_description">Hubungi pengurus berikut untuk bantuan dan aktivasi akun.</p>
            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                @forelse ($managers as $manager)
                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4 transition-colors duration-300 dark:border-slate-800/70 dark:bg-slate-900/70">
                        <h3 class="text-lg font-semibold text-slate-900 transition-colors duration-300 dark:text-slate-100">{{ $manager->name }}</h3>
                        <p class="text-xs text-slate-500 transition-colors duration-300 dark:text-slate-400">Admin</p>
                        <p class="mt-2 text-sm text-slate-600 transition-colors duration-300 dark:text-slate-300">Email: {{ $manager->email ?? 'Belum tersedia' }}</p>
                        <p class="text-sm text-slate-600 transition-colors duration-300 dark:text-slate-300">Telepon: {{ $manager->phone ?? 'Belum tersedia' }}</p>
                    </div>
                @empty
                    <p class="rounded-xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-400 transition-colors duration-300 dark:border-slate-700 dark:text-slate-500" data-i18n="about.no_managers">Data pengurus belum tersedia.</p>
                @endforelse
            </div>
        </div>
    </div>
</section>
@endsection
