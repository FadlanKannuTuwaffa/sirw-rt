<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject ?? $purposeLabel }}</title>
    <style>
        :root {
            color-scheme: light dark;
        }
        body {
            margin: 0;
            padding: 0;
            background-color: #f0f6ff;
            font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #0f172a;
        }
        .wrapper {
            width: 100%;
            background: linear-gradient(135deg, rgba(14,165,233,0.12), rgba(15,118,110,0.18));
            padding: 24px 12px;
        }
        .container {
            max-width: 520px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 18px 40px rgba(14, 165, 233, 0.18);
        }
        .header {
            background: linear-gradient(135deg, #0284c7, #0ea5e9);
            color: #ffffff;
            padding: 32px 28px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            letter-spacing: 0.05em;
        }
        .content {
            padding: 32px 28px 24px;
        }
        .otp-box {
            background: #f0f9ff;
            border-radius: 18px;
            padding: 24px;
            text-align: center;
            border: 1px dashed rgba(14, 165, 233, 0.35);
            margin-bottom: 24px;
        }
        .otp-code {
            font-family: "SFMono-Regular", "Roboto Mono", Consolas, monospace;
            font-size: 30px;
            letter-spacing: 0.35em;
            color: #0369a1;
        }
        .meta {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .meta li {
            margin-bottom: 8px;
            font-size: 14px;
            color: #475569;
        }
        .cta {
            display: inline-block;
            margin-top: 16px;
            padding: 14px 26px;
            border-radius: 9999px;
            background: #0284c7;
            color: #ffffff !important;
            text-decoration: none;
            font-weight: 600;
        }
        .footer {
            padding: 18px 28px 32px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
        }
        @media (max-width: 560px) {
            .header, .content, .footer {
                padding-left: 20px;
                padding-right: 20px;
            }
            .otp-code {
                font-size: 26px;
                letter-spacing: 0.28em;
            }
            .container {
                border-radius: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <h1>{{ $purposeLabel }}</h1>
                <p style="margin: 10px 0 0;font-size:13px;opacity:0.9;">Verifikasi keamanan akun admin SIRW</p>
            </div>
            <div class="content">
                <p style="font-size:15px;line-height:1.6;">Halo {{ $user->name }},</p>
                <p style="font-size:15px;line-height:1.6;">{{ $description }}</p>
                <div class="otp-box">
                    <div style="font-size:13px;color:#0f172a;letter-spacing:0.08em;text-transform:uppercase;">Kode OTP Anda</div>
                    <div class="otp-code">{{ $otp }}</div>
                    <p style="font-size:13px;color:#0369a1;margin:14px 0 0;">Berlaku {{ $expiresInMinutes }} menit sejak email ini dikirim.</p>
                </div>
                @if(!empty($meta))
                    <ul class="meta">
                        @foreach($meta as $line)
                            <li>• {{ $line }}</li>
                        @endforeach
                    </ul>
                @endif
                <a href="{{ url('/admin/profile/security') }}" class="cta">Masukkan Kode Sekarang</a>
                <p style="font-size:13px;color:#64748b;margin-top:20px;">Jika Anda tidak merasa melakukan tindakan ini, segera hubungi pengurus dan ganti password akun Anda.</p>
            </div>
            <div class="footer">
                <p style="margin:0;">&copy; {{ date('Y') }} Sistem Informasi Rukun Warga. Semua hak dilindungi.</p>
            </div>
        </div>
    </div>
</body>
</html>
