<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email Warga</title>
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
        <div class="email-header" style="background: linear-gradient(135deg, #6366f1, #0ea5e9); padding: 28px 32px; color: #ffffff;">
            <p style="margin: 0; font-size: 16px;">Halo, {{ $name }} &#128075;</p>
            <h1 style="margin: 12px 0 0; font-size: 26px; font-weight: 600;">Verifikasi email kamu dulu yuk.</h1>
            <p style="margin: 12px 0 0; font-size: 16px; opacity: 0.9;">
                Masukkan kode berikut di halaman verifikasi untuk memastikan email ini benar milikmu.
            </p>
        </div>

        <div class="email-section" style="padding: 32px;">
            <div class="email-highlight" style="background-color: #f1f5ff; border: 1px solid #e0e7ff; border-radius: 14px; padding: 24px; text-align: center; margin-bottom: 28px;">
                <p style="margin: 0; text-transform: uppercase; letter-spacing: 0.25em; font-size: 12px; color: #4338ca;">Kode OTP Kamu</p>
                <p style="margin: 16px 0 0; font-size: 40px; letter-spacing: 0.35em; font-weight: 700; color: #312e81;">
                    {{ trim(chunk_split($otp, 1, ' ')) }}
                </p>
                <p style="margin: 18px 0 0; font-size: 14px; color: #475569;">
                    Berlaku selama {{ $validMinutes }} menit &mdash; gunakan sebelum waktunya habis ya.
                </p>
            </div>

            <div class="email-columns" style="display: grid; gap: 16px; margin-bottom: 28px;">
                <div style="padding: 16px 20px; border-radius: 12px; background-color: #f8fafc; border: 1px solid #e2e8f0;">
                    <p style="margin: 0; font-size: 14px; color: #0f172a; font-weight: 600;">Mengapa saya menerima email ini?</p>
                    <p style="margin: 8px 0 0; font-size: 14px; color: #475569;">
                        Kamu baru saja daftar akun, login pertama kali, atau mengganti email. Kode ini memastikan semua perubahan aman.
                    </p>
                </div>
                <div style="padding: 16px 20px; border-radius: 12px; background-color: #fff7ed; border: 1px solid #fed7aa;">
                    <p style="margin: 0; font-size: 14px; color: #9a3412; font-weight: 600;">Tips keamanan</p>
                    <p style="margin: 8px 0 0; font-size: 14px; color: #9a3412;">
                        Jangan bagikan kode ini ke siapa pun, termasuk admin. Jika bukan kamu yang mengajukan, abaikan email ini.
                    </p>
                </div>
            </div>

            <p style="margin: 0; font-size: 14px; color: #475569;">
                Masih butuh bantuan? Balas email ini atau hubungi pengurus RT untuk verifikasi manual.
            </p>
        </div>

        <div style="background-color: #0f172a; padding: 20px 32px; color: #cbd5f5; font-size: 13px;">
            <p style="margin: 0;">
                Terima kasih sudah menjaga keamanan akun dan ikut membangun ekosistem digital warga.
            </p>
            <p style="margin: 12px 0 0; color: #94a3b8;">
                Salam hangat,<br>
                Pengurus RT
            </p>
        </div>
    </div>
</body>
</html>
