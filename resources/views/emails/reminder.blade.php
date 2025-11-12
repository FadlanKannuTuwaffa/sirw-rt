@php
    use App\Models\Bill;
    use App\Models\Event;
    use Illuminate\Support\Str;

    /** @var Bill|Event|null $model */
    /** @var \App\Models\User|null $recipient */

    $brand = config('app.name');
    $recipientName = $recipient?->name ?? 'Warga';
    $brandInitial = Str::upper(Str::substr($brand ?? 'S', 0, 1) ?: 'S');

    $isBill = $model instanceof Bill;
    $isEvent = $model instanceof Event;

    $amount = $isBill && $model?->amount
        ? 'Rp ' . number_format($model->amount, 0, ',', '.')
        : null;

    $dueDate = $isBill
        ? optional($model->due_date)->translatedFormat('d F Y')
        : null;

    $eventStart = $isEvent
        ? optional($model->start_at)->translatedFormat('d F Y H:i')
        : null;

    $eventEnd = $isEvent
        ? optional($model->end_at)->translatedFormat('d F Y H:i')
        : null;

    $statusRaw = $model->status ?? null;
    $status = $statusRaw ? ucfirst(str_replace('_', ' ', $statusRaw)) : null;

    $ctaUrl = $isBill
        ? url(route('resident.bills'))
        : ($isEvent ? url(route('landing.agenda')) : url('/'));

    $ctaLabel = $isBill
        ? 'Buka Detail Tagihan'
        : ($isEvent ? 'Lihat Agenda' : 'Kunjungi Portal');

    $typeLabel = $isBill
        ? 'Reminder Tagihan'
        : ($isEvent ? 'Reminder Agenda' : 'Reminder Komunitas');

    $title = trim($model->title ?? '');
    $title = $title !== '' ? $title : $subject;

    $summary = $model?->description
        ? Str::limit(strip_tags($model->description), 220)
        : null;

    $accentPrimary = $isBill ? '#2563eb' : ($isEvent ? '#0f766e' : '#4338ca');
    $accentSecondary = $isBill ? '#10b981' : ($isEvent ? '#0ea5e9' : '#6366f1');
    $surfaceSoft = $isBill ? '#f0f6ff' : ($isEvent ? '#ecfeff' : '#eef2ff');

    $statusColor = $accentPrimary;
    if ($status) {
        $statusNormalized = strtolower($statusRaw ?? $status);
        if (in_array($statusNormalized, ['paid', 'lunas', 'dibayar', 'selesai'])) {
            $statusColor = '#16a34a';
        } elseif (in_array($statusNormalized, ['overdue', 'unpaid', 'menunggak', 'tertunda', 'pending'])) {
            $statusColor = '#dc2626';
        } elseif (in_array($statusNormalized, ['menunggu', 'diproses', 'berjalan'])) {
            $statusColor = '#d97706';
        }
    }

    $statBlocks = [];
    if ($isBill && $amount) {
        $statBlocks[] = ['label' => 'Jumlah Tagihan', 'value' => $amount];
    }
    if ($isBill && $dueDate) {
        $statBlocks[] = ['label' => 'Jatuh Tempo', 'value' => $dueDate];
    }
    if ($isBill && $status) {
        $statBlocks[] = ['label' => 'Status Pembayaran', 'value' => $status, 'color' => $statusColor];
    }
    if ($isEvent && $eventStart) {
        $statBlocks[] = ['label' => 'Mulai', 'value' => $eventStart];
    }
    if ($isEvent && $eventEnd) {
        $statBlocks[] = ['label' => 'Selesai', 'value' => $eventEnd];
    }
    if ($isEvent && $status) {
        $statBlocks[] = ['label' => 'Status Agenda', 'value' => $status, 'color' => $statusColor];
    }

    $tips = $isBill
        ? [
            'Cek detail tagihan dan lakukan pembayaran melalui portal warga.',
            'Tambahkan pengingat di kalender pribadi agar jatuh tempo tidak terlewat.',
        ]
        : ($isEvent ? [
            'Catat agenda ini di kalender dan kabari pengurus bila tidak bisa hadir.',
            'Bagikan informasi agenda ke warga lain agar semua mendapat kabar yang sama.',
        ] : [
            'Kunjungi portal warga untuk melihat pembaruan dan informasi penting lainnya.',
            'Hubungi pengurus melalui kanal resmi apabila membutuhkan bantuan tambahan.',
        ]);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        @media only screen and (max-width: 620px) {
            body {
                padding: 16px !important;
            }

            .email-container {
                border-radius: 16px !important;
            }

            .email-header {
                padding: 28px 24px !important;
            }

            .email-section {
                padding: 28px 24px !important;
            }

            .email-card {
                padding: 20px !important;
            }

            .info-table td {
                display: block !important;
                width: 100% !important;
                box-sizing: border-box;
                padding: 8px 0 !important;
            }

            .info-table td.label {
                font-size: 12px !important;
                color: #64748b !important;
                text-transform: uppercase;
                letter-spacing: 0.02em;
                padding-bottom: 0 !important;
            }
        }
    </style>
</head>
<body style="margin:0;font-family:'Segoe UI',Arial,sans-serif;background-color:#f4f6fb;padding:30px;color:#0f172a;">
<div class="email-container" style="max-width:620px;margin:0 auto;background-color:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 18px 44px rgba(15,23,42,0.16);">
    <div class="email-header" style="padding:32px 36px;background:linear-gradient(135deg, {{ $accentPrimary }}, {{ $accentSecondary }});color:#ffffff;">
        <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td style="vertical-align:middle;">
                    <table role="presentation" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="width:44px;height:44px;border-radius:50%;background-color:rgba(255,255,255,0.24);text-align:center;font-weight:700;font-size:18px;line-height:44px;color:#ffffff;">
                                {{ $brandInitial }}
                            </td>
                            <td style="padding-left:14px;font-size:16px;font-weight:600;letter-spacing:0.06em;text-transform:uppercase;">
                                {{ $brand }}
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="text-align:right;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.08em;color:rgba(255,255,255,0.85);">
                    {{ $typeLabel }}
                </td>
            </tr>
        </table>
        <h1 style="margin:22px 0 8px;font-size:26px;font-weight:700;letter-spacing:-0.01em;">{{ $subject }}</h1>
        <p style="margin:0;font-size:15px;line-height:1.6;max-width:440px;color:rgba(255,255,255,0.92);">
            {{ $isBill ? 'Pastikan kewajiban iuran terpenuhi tepat waktu.' : ($isEvent ? 'Berikut informasi agenda penting untuk Anda.' : 'Kabar terbaru dari aplikasi digital RT kita untuk Anda.') }}
        </p>
    </div>

    <div class="email-section" style="padding:32px 36px;">
        <p style="margin:0 0 18px;font-size:15px;color:#0f172a;">Halo {{ $recipientName }},</p>
        <div style="font-size:15px;line-height:1.75;color:#1f2933;margin-bottom:28px;">
            {!! nl2br(e($body)) !!}
        </div>

        @if (($title && $title !== $subject) || $summary || ($isEvent && $model?->location))
            <div class="email-card" style="padding:24px 26px;border-radius:16px;background-color:{{ $surfaceSoft }};border:1px solid rgba(148,163,184,0.25);margin-bottom:28px;">
                @if ($title && $title !== $subject)
                    <p style="margin:0 0 8px;font-size:18px;font-weight:600;color:#0f172a;">{{ $title }}</p>
                @endif
                @if ($summary)
                    <p style="margin:0;font-size:14px;line-height:1.7;color:#475569;">{{ $summary }}</p>
                @endif
                @if ($isEvent && $model?->location)
                    <p style="margin:14px 0 0;font-size:13px;font-weight:600;letter-spacing:0.06em;text-transform:uppercase;color:#0f172a;">
                        Lokasi: <span style="font-weight:500;text-transform:none;letter-spacing:normal;">{{ $model->location }}</span>
                    </p>
                @endif
            </div>
        @endif

        @if (!empty($statBlocks))
            <div style="margin-bottom:28px;">
                <p style="margin:0 0 10px;font-size:12px;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;font-weight:600;">Highlight</p>
                @foreach ($statBlocks as $block)
                    <div class="email-card" style="padding:20px 22px;border-radius:14px;border:1px solid rgba(148,163,184,0.22);margin-bottom:12px;">
                        <p style="margin:0;font-size:12px;letter-spacing:0.06em;text-transform:uppercase;color:#64748b;font-weight:600;">{{ $block['label'] }}</p>
                        <p style="margin:8px 0 0;font-size:18px;font-weight:700;color:{{ $block['color'] ?? $accentPrimary }};">{{ $block['value'] }}</p>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($isEvent && ($eventStart || $eventEnd))
            <div class="email-card" style="padding:22px 24px;border-radius:16px;border:1px solid rgba(148,163,184,0.22);margin-bottom:28px;">
                <p style="margin:0 0 12px;font-size:12px;letter-spacing:0.1em;text-transform:uppercase;color:#64748b;font-weight:600;">Jadwal Agenda</p>
                <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">
                    @if ($eventStart)
                        <tr>
                            <td style="width:40%;padding:10px 0;font-size:13px;font-weight:600;color:#475569;">Mulai</td>
                            <td style="padding:10px 0;font-size:15px;font-weight:600;color:#0f172a;">{{ $eventStart }}</td>
                        </tr>
                    @endif
                    @if ($eventEnd)
                        <tr>
                            <td style="width:40%;padding:10px 0;font-size:13px;font-weight:600;color:#475569;">Selesai</td>
                            <td style="padding:10px 0;font-size:15px;font-weight:600;color:#0f172a;">{{ $eventEnd }}</td>
                        </tr>
                    @endif
                </table>
            </div>
        @endif

        @if (!empty($metadata))
            <div style="margin-bottom:30px;">
                <p style="margin:0 0 12px;font-size:12px;letter-spacing:0.12em;text-transform:uppercase;color:#64748b;font-weight:600;">Detail Tambahan</p>
                <table class="info-table" role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;border:1px solid rgba(148,163,184,0.22);border-radius:14px;overflow:hidden;">
                    @foreach ($metadata as $label => $value)
                        <tr>
                            <td class="label" style="padding:14px 18px;font-size:13px;font-weight:600;color:#475569;background-color:#f8fafc;width:40%;">{{ $label }}</td>
                            <td style="padding:14px 18px;font-size:14px;color:#1f2933;">{{ $value }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif

        @if (!empty($tips))
            <div class="email-card" style="padding:22px 24px;border-radius:16px;background-color:#f8fafc;border:1px solid rgba(148,163,184,0.2);margin-bottom:32px;">
                <p style="margin:0 0 12px;font-size:12px;letter-spacing:0.1em;text-transform:uppercase;color:#0f172a;font-weight:600;">Langkah Selanjutnya</p>
                <ul style="margin:0;padding-left:18px;">
                    @foreach ($tips as $tip)
                        <li style="margin:8px 0;font-size:13px;color:#475569;line-height:1.65;">{{ $tip }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($status)
            <div style="margin-bottom:28px;padding:18px 22px;border-radius:14px;background-color:#f1f5f9;border:1px solid rgba(148,163,184,0.2);font-size:13px;color:#475569;">
                <strong style="color:#0f172a;">Status saat ini:</strong> {{ $status }}.
                @if ($isBill && $dueDate)
                    Mohon selesaikan sebelum {{ $dueDate }} agar data tetap terbarui.
                @endif
            </div>
        @endif

        <div style="text-align:center;padding-top:8px;">
            <a href="{{ $ctaUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:15px 28px;border-radius:30px;background-color:{{ $accentPrimary }};color:#ffffff;font-size:15px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;text-decoration:none;box-shadow:0 18px 38px rgba(37,99,235,0.28);">
                {{ $ctaLabel }}
            </a>
            <p style="margin:16px 0 0;font-size:13px;line-height:1.6;color:#475569;">
                {{ $isBill ? 'Jika telah melakukan pembayaran, cek status pembayaran di akun protal warga milikmu atau konfirmasi ke pengurus RT.' : ($isEvent ? 'Catat agenda ini dan kabari pengurus bila memerlukan bantuan.' : 'Laporkan ke pengurus bila Anda membutuhkan klarifikasi lebih lanjut.') }}
            </p>
        </div>
    </div>

    <div style="background-color:#0f172a;padding:22px 32px;text-align:center;">
        <p style="margin:0;font-size:13px;line-height:1.7;color:#cbd5f5;">
            {{ $isBill ? 'Terima kasih atas komitmen Anda mendukung operasional lingkungan RT.' : ($isEvent ? 'Sampai jumpa di agenda RT berikutnya.' : 'Terima kasih sudah menjadi bagian penting RT kita.') }}
        </p>
        <p style="margin:14px 0 0;font-size:12px;color:#94a3b8;">
            Email ini dikirim otomatis oleh {{ $brand }}. Tetap aktifkan notifikasi agar tidak melewatkan kabar penting.
        </p>
    </div>
</div>
</body>
</html>
