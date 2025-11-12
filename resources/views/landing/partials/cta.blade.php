<section id="cta" class="relative overflow-hidden bg-gradient-to-br from-sky-600 via-sky-600 to-emerald-500 py-12 text-white md:py-16">
    <div class="absolute inset-0 -z-10 opacity-30 mix-blend-screen">
        <div class="absolute -top-16 right-10 h-48 w-48 rounded-full bg-white/40 blur-3xl"></div>
        <div class="absolute bottom-0 left-16 h-40 w-40 rounded-full bg-white/30 blur-2xl"></div>
    </div>
    <div class="container-app">
        <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,22rem)] lg:items-center">
            <div class="space-y-4">
                <p class="text-xs font-semibold uppercase tracking-[0.35em] text-white/80" data-i18n="cta.kicker">Waktunya Paperless</p>
                <h2 class="text-3xl font-semibold md:text-4xl" data-i18n="cta.title">Siap modernisasi pengelolaan RT/RW Anda?</h2>
                <p class="text-sm text-white/90 md:text-base" data-i18n="cta.description">Aktifkan dashboard warga, integrasikan pencatatan kas, dan hadirkan transparansi yang mudah dipahami oleh semua anggota keluarga.</p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:justify-end lg:flex-col lg:items-end">
                <a href="{{ route('register') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-semibold text-sky-600 shadow-lg shadow-sky-900/40 transition-transform duration-300 hover:-translate-y-0.5 hover:bg-white/95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/60 sm:w-auto" data-i18n="cta.primary">Daftar gratis sekarang</a>
                <a href="{{ route('landing.contact') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-white/40 bg-transparent px-5 py-3 text-sm font-semibold text-white transition-transform duration-300 hover:-translate-y-0.5 hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/60 sm:w-auto" data-i18n="cta.secondary">Diskusikan dengan pengurus</a>
            </div>
        </div>
    </div>
</section>
