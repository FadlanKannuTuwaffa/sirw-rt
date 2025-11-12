@extends('layouts.landing_min', ['title' => $title ?? null, 'site' => $site])

@section('content')
<section class="pt-8 pb-12 sm:pt-10 sm:pb-14 md:pt-12 md:pb-16 bg-white transition-colors duration-300 dark:bg-slate-950">
    <div class="container-app max-w-4xl">
        <h1 class="text-3xl font-semibold text-slate-900 transition-colors duration-300 dark:text-slate-100" data-i18n="contact.page_title">Hubungi Pengurus</h1>
        <p class="mt-4 text-sm text-slate-600 transition-colors duration-300 dark:text-slate-300" data-i18n="contact.page_description">Gunakan informasi berikut untuk menghubungi admin atau pengurus RT terkait pendaftaran akun dan pertanyaan lainnya.</p>

        <div class="mt-10 grid gap-6 md:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6 transition-colors duration-300 dark:border-slate-800/70 dark:bg-slate-900/70">
                <h2 class="text-xl font-semibold text-slate-900 transition-colors duration-300 dark:text-slate-100" data-i18n="contact.card_primary_title">Kontak Utama</h2>
                <ul class="mt-4 space-y-3 text-sm text-slate-600 transition-colors duration-300 dark:text-slate-300">
                    <li>
                        <strong data-i18n="contact.phone_label">Telepon/WhatsApp:</strong>
                        @if (! empty($site['contact_phone']))
                            {{ $site['contact_phone'] }}
                        @else
                            <span data-i18n="contact.unavailable">Belum tersedia</span>
                        @endif
                    </li>
                    <li>
                        <strong data-i18n="contact.email_label">Email:</strong>
                        @if (! empty($site['contact_email']))
                            {{ $site['contact_email'] }}
                        @else
                            <span data-i18n="contact.unavailable">Belum tersedia</span>
                        @endif
                    </li>
                    <li>
                        <strong data-i18n="contact.address_label">Alamat:</strong>
                        @if (! empty($site['address']))
                            {{ $site['address'] }}
                        @else
                            <span data-i18n="contact.unavailable">Belum tersedia</span>
                        @endif
                    </li>
                </ul>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6 transition-colors duration-300 dark:border-slate-800/70 dark:bg-slate-900/70">
                <h2 class="text-xl font-semibold text-slate-900 transition-colors duration-300 dark:text-slate-100" data-i18n="contact.service_title">Jam Pelayanan</h2>
                <p class="mt-3 text-sm text-slate-600 transition-colors duration-300 dark:text-slate-300">
                    @if (! empty($site['service_hours']))
                        {{ $site['service_hours'] }}
                    @else
                        <span data-i18n="contact.default_service_hours">Senin - Sabtu, 08.00 - 17.00 WIB. Pengurus akan merespon pesan Anda maksimal 1x24 jam kerja.</span>
                    @endif
                </p>
                <p class="mt-3 text-sm text-slate-600 transition-colors duration-300 dark:text-slate-300" data-i18n="contact.emergency_note">Untuk urusan darurat, harap hubungi ketua RT secara langsung melalui nomor telepon yang tercantum.</p>
            </div>
        </div>

        <div class="mt-12 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition-colors duration-300 dark:border-slate-800/70 dark:bg-slate-900/80 dark:shadow-slate-900/40">
            <h2 class="text-xl font-semibold text-slate-900 transition-colors duration-300 dark:text-slate-100" data-i18n="contact.form_title">Formulir Pesan</h2>
            <p class="mt-2 text-sm text-slate-500 transition-colors duration-300 dark:text-slate-400" data-i18n="contact.form_description">Silakan kirim pesan singkat dan pihak pengurus akan menghubungi Anda kembali melalui email.</p>

            @if (session('status'))
                <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-700 transition-colors duration-300 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-200">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700 transition-colors duration-300 dark:border-rose-500/40 dark:bg-rose-500/10 dark:text-rose-200">
                    <p class="font-semibold" data-i18n="contact.error_heading">Terjadi kesalahan:</p>
                    <ul class="mt-1 list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('contact.store') }}" class="mt-6 grid gap-4 md:grid-cols-2">
                @csrf
                <input type="text" name="name" value="{{ old('name') }}" placeholder="Nama Anda" data-i18n-placeholder="contact.form_name_placeholder" class="md:col-span-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600 transition-colors duration-300 focus:border-[#0284C7] focus:bg-white focus:outline-none dark:border-slate-700/70 dark:bg-slate-900/70 dark:text-slate-200 dark:placeholder:text-slate-500 dark:focus:border-[#0284C7] dark:focus:bg-slate-900">
                <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" data-i18n-placeholder="contact.form_email_placeholder" class="md:col-span-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600 transition-colors duration-300 focus:border-[#0284C7] focus:bg-white focus:outline-none dark:border-slate-700/70 dark:bg-slate-900/70 dark:text-slate-200 dark:placeholder:text-slate-500 dark:focus:border-[#0284C7] dark:focus:bg-slate-900">
                <input type="text" name="phone" value="{{ old('phone') }}" placeholder="Nomor Telepon (opsional)" data-i18n-placeholder="contact.form_phone_placeholder" class="md:col-span-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600 transition-colors duration-300 focus:border-[#0284C7] focus:bg-white focus:outline-none dark:border-slate-700/70 dark:bg-slate-900/70 dark:text-slate-200 dark:placeholder:text-slate-500 dark:focus:border-[#0284C7] dark:focus:bg-slate-900">
                <textarea rows="4" name="message" placeholder="Pesan Anda" data-i18n-placeholder="contact.form_message_placeholder" class="md:col-span-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600 transition-colors duration-300 focus:border-[#0284C7] focus:bg-white focus:outline-none dark:border-slate-700/70 dark:bg-slate-900/70 dark:text-slate-200 dark:placeholder:text-slate-500 dark:focus:border-[#0284C7] dark:focus:bg-slate-900">{{ old('message') }}</textarea>
                <button type="submit" class="md:col-span-2 inline-flex w-full items-center justify-center rounded-full bg-[#0284C7] px-5 py-3 text-sm font-semibold text-white transition-all duration-300 hover:-translate-y-0.5 hover:bg-[#0284C7]/85 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0284C7] focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:shadow-slate-900/40 dark:focus-visible:ring-sky-400 dark:focus-visible:ring-offset-slate-900 sm:w-auto" data-i18n="contact.form_submit">Kirim Pesan</button>
            </form>
        </div>
    </div>
</section>
@endsection

