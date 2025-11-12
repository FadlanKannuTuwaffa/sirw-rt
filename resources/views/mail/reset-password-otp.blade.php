<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode OTP Reset Password</title>
    <style>
        @media only screen and (max-width: 600px) {
            body {
                padding: 16px !important;
            }

            .email-container {
                border-radius: 12px !important;
            }

            .email-header {
                padding: 24px 20px !important;
            }

            .email-section {
                padding: 24px 20px !important;
            }

            .email-highlight {
                padding: 20px !important;
            }

            .email-columns {
                display: block !important;
            }

            .email-columns > div {
                margin-bottom: 16px !important;
            }

            .email-columns > div:last-child {
                margin-bottom: 0 !important;
            }
        }
    </style>
</head>
<body style="margin: 0; font-family: 'Segoe UI', Arial, sans-serif; background-color: #f5f7fb; padding: 32px; color: #1f2933;">
    <div class="email-container" style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12);">
        <div class="email-header" style="background: linear-gradient(135deg, #f97316, #ef4444); padding: 28px 32px; color: #ffffff;">
            <p style="margin: 0; font-size: 16px;">Halo, {{ $user->name ?? 'Warga' }} &#128075;</p>
            <h1 style="margin: 12px 0 0; font-size: 26px; font-weight: 600;">Kami siap bantu reset password kamu.</h1>
            <p style="margin: 12px 0 0; font-size: 16px; opacity: 0.9;">
                Gunakan kode satu kali berikut untuk lanjut ke langkah pengaturan password baru.
            </p>
        </div>

        <div class="email-section" style="padding: 32px;">
            <div class="email-highlight" style="background-color: #fff7ed; border: 1px solid #fed7aa; border-radius: 14px; padding: 24px; text-align: center; margin-bottom: 28px;">
                <p style="margin: 0; text-transform: uppercase; letter-spacing: 0.25em; font-size: 12px; color: #c2410c;">Kode OTP</p>
                <p style="margin: 16px 0 0; font-size: 40px; letter-spacing: 0.35em; font-weight: 700; color: #9a3412;">
                    {{ trim(chunk_split($otp, 1, ' ')) }}
                </p>
                <p style="margin: 18px 0 0; font-size: 14px; color: #9a3412;">
                    Berlaku hingga {{ $expiresAt->timezone(config('app.timezone'))->format('d M Y H:i') }}. Setelah itu kamu bisa minta kode baru.
                </p>
            </div>

            <p style="margin: 0 0 20px; font-size: 14px; color: #475569; text-align: center;">
                Klik tombol di bawah ini untuk membuka halaman input OTP secara otomatis.
            </p>
            <p style="margin: 0 0 32px; text-align: center;">
                <a href="{{ route('password.otp.show', ['token' => $token]) }}" style="display: inline-block; padding: 12px 28px; border-radius: 9999px; background: linear-gradient(135deg, #f97316, #ef4444); color: #ffffff; text-decoration: none; font-weight: 600; text-decoration: none;">
                    Masukkan Kode OTP
                </a>
            </p>

            <div class="email-columns" style="display: grid; gap: 16px; margin-bottom: 28px;">
                <div style="padding: 16px 20px; border-radius: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0;">
                    <p style="margin: 0; font-size: 14px; color: #0f172a; font-weight: 600;">Langkah selanjutnya</p>
                    <p style="margin: 8px 0 0; font-size: 14px; color: #475569;">
                        Masukkan kode di halaman reset password, buat password baru yang kuat, lalu simpan info login di tempat aman.
                    </p>
                </div>
                <div style="padding: 16px 20px; border-radius: 12px; background-color: #fee2e2; border: 1px solid #fecaca;">
                    <p style="margin: 0; font-size: 14px; color: #991b1b; font-weight: 600;">Keamanan akun</p>
                    <p style="margin: 8px 0 0; font-size: 14px; color: #991b1b;">
                        Jangan bagikan kode ini kepada siapa pun. Jika kamu merasa ada aktivitas mencurigakan, segera hubungi pengurus RT.
                    </p>
                </div>
            </div>

            <p style="margin: 0; font-size: 14px; color: #475569;">
                Tidak merasa meminta reset? Abaikan email ini &mdash; password lamamu tetap aman.
            </p>
        </div>

        <div style="background-color: #0f172a; padding: 20px 32px; color: #cbd5f5; font-size: 13px;">
            <p style="margin: 0;">
                Terima kasih sudah menjaga keamanan akun warga. Kami siap mendukung kebutuhanmu 24/7.
            </p>
            <p style="margin: 12px 0 0; color: #94a3b8;">
                Salam hangat,<br>
                Pengurus RT
            </p>
        </div>
    </div>
</body>
</html>
