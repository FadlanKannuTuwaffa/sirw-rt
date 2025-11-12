<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Tagihan Berhasil</title>
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
                text-align: left !important;
            }

            .email-section {
                padding: 24px 20px !important;
            }

            .email-status {
                padding: 20px !important;
            }

            .email-table td {
                display: block !important;
                width: 100% !important;
                box-sizing: border-box;
                padding: 8px 0 !important;
                text-align: left !important;
            }

            .email-table td.label {
                color: #64748b !important;
                font-weight: 600 !important;
                text-transform: uppercase;
                letter-spacing: 0.02em;
                font-size: 12px !important;
            }

            .email-callout {
                padding: 16px !important;
                font-size: 13px !important;
            }
        }
    </style>
</head>
<body style="margin: 0; font-family: 'Segoe UI', Arial, sans-serif; background-color: #f5f7fb; padding: 32px; color: #1f2933;">
    <div class="email-container" style="max-width: 560px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12);">
        <div class="email-header" style="background: linear-gradient(135deg, #2563eb, #10b981); padding: 28px 32px; color: #ffffff;">
            <p style="margin: 0; font-size: 16px;">Halo, {{ $user->name ?? 'Warga' }} &#128075;</p>
            <h1 style="margin: 12px 0 0; font-size: 26px; font-weight: 600;">Pembayaranmu sudah kami terima!</h1>
            <p style="margin: 12px 0 0; font-size: 16px; opacity: 0.9;">
                Terima kasih telah melakukan pembayaran tepat waktu. Detail transaksi ada di bawah ini untuk catatanmu.
            </p>
        </div>

        <div class="email-section" style="padding: 32px;">
            <div class="email-status" style="background-color: #f0f9ff; border: 1px solid #dbeafe; border-radius: 12px; padding: 24px; text-align: center; margin-bottom: 28px;">
                <p style="margin: 0; text-transform: uppercase; letter-spacing: 0.2em; font-size: 12px; color: #2563eb;">Status Pembayaran</p>
                <p style="margin: 8px 0 0; font-size: 32px; font-weight: 700; color: #1d4ed8;">{{ $totalFormatted }}</p>
                <p style="margin: 12px 0 0; font-size: 14px; color: #334155;">
                    Diterima pada {{ $paidAtFormatted }}
                </p>
            </div>

            <table class="email-table" style="width: 100%; border-collapse: collapse;">
                <tbody>
                <tr>
                    <td class="label" style="padding: 12px 0; font-size: 14px; color: #64748b; width: 40%;">Nomor Tagihan :</td>
                    <td style="padding: 12px 0; font-size: 16px; font-weight: 600; color: #0f172a;">{{ $invoice }}</td>
                </tr>
                @if($bill && $bill->title)
                    <tr>
                        <td class="label" style="padding: 12px 0; font-size: 14px; color: #64748b;">Judul Tagihan :</td>
                        <td style="padding: 12px 0; font-size: 16px; font-weight: 600; color: #0f172a;">{{ $bill->title }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="label" style="padding: 12px 0; font-size: 14px; color: #64748b;">Nominal Dasar :</td>
                    <td style="padding: 12px 0; font-size: 16px; font-weight: 600; color: #0f172a;">{{ $amountFormatted }}</td>
                </tr>
                @if($feeFormatted)
                    <tr>
                        <td class="label" style="padding: 12px 0; font-size: 14px; color: #64748b;">Biaya Admin :</td>
                        <td style="padding: 12px 0; font-size: 16px; font-weight: 600; color: #0f172a;">{{ $feeFormatted }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="label" style="padding: 12px 0; font-size: 14px; color: #64748b;">Metode :</td>
                    <td style="padding: 12px 0; font-size: 16px; font-weight: 600; color: #0f172a;">{{ $channel }}</td>
                </tr>
                @if($reference)
                    <tr>
                        <td class="label" style="padding: 12px 0; font-size: 14px; color: #64748b;">Referensi :</td>
                        <td style="padding: 12px 0; font-size: 16px; font-weight: 600; color: #0f172a;">{{ $reference }}</td>
                    </tr>
                @endif
                </tbody>
            </table>

            <div class="email-callout" style="margin-top: 28px; padding: 20px; border: 1px solid #e2e8f0; border-radius: 12px; background-color: #f8fafc; color: #475569; font-size: 14px; line-height: 1.7;">
                <strong style="color: #1e293b;">Catatan:</strong>
                <p style="margin: 12px 0 0;">
                    Simpan email ini sebagai arsip pribadi. Jika kamu membutuhkan bukti pembayaran tambahan,
                    bisa kamu cek langsung di website kita atau hubungi pengurus RT.
                </p>
            </div>
        </div>

        <div style="background-color: #0f172a; padding: 20px 32px; color: #cbd5f5; font-size: 13px;">
            <p style="margin: 0;">
                Tetap terhubung dengan informasi terbaru dari lingkungan kita. Semangat menjaga kebersamaan!
            </p>
            <p style="margin: 12px 0 0; color: #94a3b8;">
                Salam hangat,<br>
                Pengurus RT
            </p>
        </div>
    </div>
</body>
</html>
