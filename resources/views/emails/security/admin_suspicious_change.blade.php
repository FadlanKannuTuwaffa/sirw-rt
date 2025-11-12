<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Peringatan Keamanan Admin</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #0f172a;
            font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #0f172a;
        }
        .wrapper {
            width: 100%;
            padding: 28px 12px;
            background: radial-gradient(circle at top, rgba(239, 68, 68, 0.12), rgba(14, 165, 233, 0.08));
        }
        .card {
            max-width: 540px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 24px 60px rgba(148, 163, 184, 0.32);
        }
        .hero {
            background: linear-gradient(135deg, #ef4444, #f97316);
            color: #ffffff;
            padding: 32px 30px;
        }
        .hero h1 {
            margin: 0;
            font-size: 22px;
            letter-spacing: 0.06em;
        }
        .hero p {
            margin: 12px 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .content {
            padding: 30px 28px 26px;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(239, 68, 68, 0.1);
            color: #b91c1c;
            border-radius: 9999px;
            padding: 10px 18px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .history {
            margin-top: 24px;
            border-top: 1px solid rgba(148, 163, 184, 0.35);
            padding-top: 20px;
        }
        .history-item {
            margin-bottom: 16px;
            font-size: 13px;
            color: #475569;
        }
        .cta {
            margin-top: 24px;
            display: inline-block;
            padding: 14px 26px;
            border-radius: 9999px;
            background: #ef4444;
            color: #ffffff !important;
            text-decoration: none;
            font-weight: 600;
        }
        .footer {
            text-align: center;
            padding: 20px 28px 32px;
            font-size: 12px;
            color: #94a3b8;
        }
        @media (max-width: 560px) {
            .hero, .content, .footer {
                padding-left: 22px;
                padding-right: 22px;
            }
            .hero h1 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="hero">
                <h1>⚠️ Aktivitas Mencurigakan Terdeteksi</h1>
                <p>Sistem mendeteksi lebih dari {{ $attempts }} percobaan dalam 10 menit untuk {{ $actionLabel }}.</p>
            </div>
            <div class="content">
                <div class="badge">Sesi admin otomatis dikeluarkan</div>
                <p style="margin-top:18px;font-size:15px;line-height:1.6;">Halo {{ $admin->name }},</p>
                <p style="font-size:15px;line-height:1.6;">Akun admin atas nama <strong>{{ $actor->name }}</strong> (ID #{{ $actor->id }}) baru saja dibatasi sementara karena sistem mendeteksi percobaan keamanan yang tidak biasa.</p>
                <p style="font-size:14px;line-height:1.6;color:#475569;">Mohon login ulang dan pastikan bahwa permintaan perubahan berasal dari Anda. Segera ganti password jika merasa ada akses tidak sah.</p>
                @if(!empty($history))
                    <div class="history">
                        <h3 style="margin:0 0 12px;font-size:14px;text-transform:uppercase;letter-spacing:0.12em;color:#0f172a;">Ringkasan Percobaan</h3>
                        @foreach($history as $item)
                            <div class="history-item">
                                <strong>{{ strtoupper(str_replace('_', ' ', $item['action'])) }}</strong><br>
                                <span>Waktu: {{ \Carbon\Carbon::parse($item['attempted_at'])->setTimezone(config('app.timezone'))->format('d M Y H:i') }}</span><br>
                                @if(!empty($item['meta']['ip_address']))
                                    <span>IP: {{ $item['meta']['ip_address'] }}</span><br>
                                @endif
                                @if(!empty($item['meta']['device']))
                                    <span>Perangkat: {{ $item['meta']['device'] }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
                <a href="{{ url('/login') }}" class="cta">Login Ulang Sekarang</a>
            </div>
            <div class="footer">
                <p style="margin:0;">Pesan otomatis dari Sistem Informasi Rukun Warga</p>
            </div>
        </div>
    </div>
</body>
</html>



