<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $subject }}</title>
    <style>
        :root {
            color-scheme: light dark;
        }

        body {
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f4f7fb;
            color: #0f172a;
        }

        .wrapper {
            max-width: 640px;
            margin: 0 auto;
            padding: 32px 16px;
        }

        .card {
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.12);
            overflow: hidden;
        }

        .header {
            background: radial-gradient(circle at top left, #14b8a6, #0ea5e9);
            color: #ffffff;
            padding: 32px 36px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            line-height: 1.4;
            font-weight: 600;
        }

        .header p {
            margin: 12px 0 0;
            font-size: 15px;
            opacity: 0.9;
        }

        .body {
            padding: 36px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 14px;
            border-radius: 9999px;
            background: rgba(14, 165, 233, 0.12);
            color: #0284c7;
            font-size: 12px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-weight: 600;
        }

        .details {
            margin-top: 24px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .details-row {
            display: flex;
            flex-wrap: wrap;
            border-bottom: 1px solid #e2e8f0;
        }

        .details-row:last-child {
            border-bottom: none;
        }

        .details-label {
            flex: 0 0 160px;
            padding: 16px 20px;
            background: #f8fafc;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #64748b;
        }

        .details-value {
            flex: 1 1 240px;
            padding: 16px 20px;
            font-size: 14px;
            color: #0f172a;
            line-height: 1.6;
        }

        .cta {
            margin-top: 32px;
            text-align: center;
        }

        .cta a {
            display: inline-block;
            padding: 14px 28px;
            border-radius: 9999px;
            background: linear-gradient(135deg, #0ea5e9, #6366f1);
            color: #ffffff;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .cta a:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 30px rgba(99, 102, 241, 0.25);
        }

        .footer {
            padding: 24px 36px 36px;
            background: #0f172a;
            color: #cbd5f5;
            font-size: 13px;
            line-height: 1.6;
        }

        .footer p {
            margin: 0 0 10px;
        }

        @media (max-width: 600px) {
            .body, .header, .footer {
                padding: 28px 24px;
            }

            .details-row {
                flex-direction: column;
            }

            .details-label, .details-value {
                flex: 1 1 auto;
                padding: 14px 18px;
            }

            .details-label {
                border-bottom: 1px solid #e2e8f0;
            }
        }
    </style>
</head>
<body>
@php
    use Illuminate\Support\Arr;
    use Illuminate\Support\Str;
@endphp
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <h1>{{ $subject }}</h1>
                <p>
                    Halo {{ $notifiable->name ?? 'Warga' }}, kami mendeteksi aktivitas login yang memerlukan perhatian Anda.
                </p>
            </div>
            <div class="body">
                <span class="badge">
                    {{ Arr::get($context, 'is_new_device', false) ? 'Perangkat Baru' : 'Perangkat Dikenal - Lokasi Baru' }}
                </span>

                <div class="details">
                    <div class="details-row">
                        <div class="details-label">Akun</div>
                        <div class="details-value">
                            {{ $actor->name }} (ID #{{ $actor->id }})
                        </div>
                    </div>
                    <div class="details-row">
                        <div class="details-label">Perangkat</div>
                        <div class="details-value">
                            {{ Arr::get($context, 'device_label', 'Tidak diketahui') }}<br>
                            <span style="font-size:12px;color:#64748b;">
                                {{ Str::limit(Arr::get($context, 'user_agent', 'User agent tidak tersedia'), 140) }}
                            </span>
                        </div>
                    </div>
                    <div class="details-row">
                        <div class="details-label">Alamat IP</div>
                        <div class="details-value">
                            {{ Arr::get($context, 'ip_address', 'Tidak tersedia') }}
                            @if (Arr::get($context, 'location_hint'))
                                <br>
                                <span style="font-size:12px;color:#64748b;">
                                    Perkiraan lokasi: {{ strtoupper(Arr::get($context, 'location_hint')) }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="details-row">
                        <div class="details-label">Waktu</div>
                        <div class="details-value">
                            {{ optional(Arr::get($context, 'detected_at'))->setTimezone(config('app.timezone'))->format('d M Y H:i') }}
                        </div>
                    </div>
                </div>

                <div class="cta">
                    <a href="{{ $manageUrl }}" target="_blank" rel="noopener">
                        Amankan & Kelola Password
                    </a>
                </div>

                <p style="margin-top: 28px; font-size: 14px; color: #475569;">
                    Jika Anda merasa login ini bukan dilakukan oleh Anda, segera ganti password akun dan perbarui faktor keamanan lainnya. Setelah password diperbarui, semua sesi aktif akan keluar otomatis.
                </p>
                <p style="margin-top: 12px; font-size: 13px; color: #94a3b8;">
                    Abaikan email ini jika Anda mengenali aktivitas tersebut. Tidak ada tindakan lain yang diperlukan.
                </p>
            </div>
            <div class="footer">
                <p>Keamanan akun Anda adalah prioritas kami.</p>
                <p>Sistem Informasi RT (SIRW)</p>
            </div>
        </div>
    </div>
</body>
</html>
