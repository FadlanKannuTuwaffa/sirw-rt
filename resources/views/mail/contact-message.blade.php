@php
    $submittedAt = $contactMessage->created_at
        ? $contactMessage->created_at->timezone(config('app.timezone'))->format('d M Y H:i')
        : now()->format('d M Y H:i');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Baru dari Form Kontak</title>
    <style>
        :root {
            color-scheme: light dark;
        }

        body,
        table,
        td,
        div,
        p {
            margin: 0;
            padding: 0;
        }

        body {
            width: 100% !important;
            height: 100% !important;
            background-color: #0f172a;
            font-family: 'Helvetica Neue', Arial, sans-serif;
            color: #111827;
        }

        table {
            border-collapse: collapse;
        }

        img {
            border: 0;
            line-height: 100%;
            text-decoration: none;
            -ms-interpolation-mode: bicubic;
        }

        .wrapper {
            width: 100%;
            padding: 24px 12px;
        }

        .inner {
            width: 100%;
            max-width: 640px;
            margin: 0 auto;
            border-radius: 16px;
            overflow: hidden;
            background-color: #111827;
        }

        .header {
            background: linear-gradient(135deg, #1d4ed8, #0ea5e9);
            text-align: center;
            color: #f8fafc;
            padding: 32px 24px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 0.4px;
        }

        .header p {
            margin: 10px 0 0;
            font-size: 15px;
            opacity: 0.88;
        }

        .body-section {
            padding: 28px 24px 24px;
        }

        .info-block {
            width: 100%;
        }

        .info-item {
            background-color: rgba(15, 23, 42, 0.85);
            border-radius: 14px;
            padding: 16px 20px;
            border: 1px solid rgba(148, 163, 184, 0.35);
        }

        .info-label {
            display: block;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #94a3b8;
            margin-bottom: 6px;
        }

        .info-value {
            display: block;
            font-size: 16px;
            font-weight: 600;
            color: #f8fafc;
            word-break: break-word;
        }

        .message-box {
            background-color: rgba(30, 41, 59, 0.95);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            padding: 20px 22px;
            color: #e2e8f0;
            line-height: 1.6;
        }

        .message-box h2 {
            margin: 0 0 12px;
            font-size: 18px;
            color: #f8fafc;
        }

        .message-body {
            margin: 0;
        }

        .cta a {
            display: inline-block;
            width: 100%;
            padding: 14px 20px;
            border-radius: 9999px;
            background: linear-gradient(135deg, #1d4ed8, #2563eb);
            color: #ffffff !important;
            font-weight: 600;
            text-decoration: none;
            letter-spacing: 0.02em;
            text-align: center;
        }

        .cta a span {
            color: inherit;
            text-decoration: none;
        }

        .meta {
            padding: 18px 24px;
            background-color: #0f172a;
            color: #cbd5f5;
            font-size: 13px;
        }

        .meta span {
            display: block;
            margin-bottom: 6px;
            letter-spacing: 0.03em;
        }

        .footer {
            padding: 16px 22px 24px;
            background-color: #0b1220;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            line-height: 1.6;
        }

        @media screen and (max-width: 480px) {
            .wrapper {
                padding: 16px 8px;
            }

            .header {
                padding: 28px 16px;
            }

            .header h1 {
                font-size: 22px;
            }

            .body-section {
                padding: 24px 16px;
            }

            .cta a {
                padding: 16px;
            }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td>
                <div class="inner">
                    <div class="header">
                        <h1>Pesan Baru untuk Pengurus</h1>
                        <p>Warga meninggalkan pesan melalui formulir kontak di {{ config('app.name') }}.</p>
                    </div>
                    <div class="body-section">
                        <table role="presentation" class="info-block" width="100%" cellspacing="0" cellpadding="0">
                            <tr>
                                <td class="info-item">
                                    <span class="info-label">Nama Pengirim</span>
                                    <span class="info-value">{{ $contactMessage->name }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td height="12"></td>
                            </tr>
                            <tr>
                                <td class="info-item">
                                    <span class="info-label">Email Pengirim</span>
                                    <span class="info-value">{{ $contactMessage->email }}</span>
                                </td>
                            </tr>
                            @if ($contactMessage->phone)
                                <tr>
                                    <td height="12"></td>
                                </tr>
                                <tr>
                                    <td class="info-item">
                                        <span class="info-label">Kontak Telepon</span>
                                        <span class="info-value">{{ $contactMessage->phone }}</span>
                                    </td>
                                </tr>
                            @endif
                        </table>

                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top: 20px;">
                            <tr>
                                <td class="message-box">
                                    <h2>Isi Pesan</h2>
                                    <p class="message-body">{!! nl2br(e($contactMessage->message)) !!}</p>
                                </td>
                            </tr>
                        </table>

                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top: 24px;">
                            <tr>
                                <td class="cta">
                                    <a href="mailto:{{ $contactMessage->email }}?subject=Re: Pesan dari {{ rawurlencode($contactMessage->name) }}">
                                        <span>Balas Pesan Ini</span>
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="meta">
                        <span>Diterima pada {{ $submittedAt }}</span>
                        <span>ID Pesan: {{ $contactMessage->id ?? '-' }}</span>
                    </div>
                    <div class="footer">
                        Email ini dikirim secara otomatis oleh {{ config('app.name') }}. Mohon balas langsung ke warga melalui tombol di atas jika diperlukan.
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
