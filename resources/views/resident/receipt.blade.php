<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Invoice Pembayaran - {{ $bill->invoice_number }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @php
        $exporting = $exporting ?? false;
    @endphp
    <style>
        :root {
            --primary: #0f172a;
            --secondary: #0ea5e9;
            --accent: #22c55e;
            --muted: #64748b;
            --bg-start: #e0f2ff;
            --bg-mid: #f6fbff;
            --bg-end: #edf2ff;
            --card: #ffffff;
            --shadow-dark: rgba(15, 23, 42, 0.25);
            --shadow-light: rgba(14, 165, 233, 0.22);
            --border: rgba(15, 23, 42, 0.08);
            --radius-lg: 28px;
            --radius-md: 18px;
            --radius-sm: 12px;
        }
        @if ($exporting)
        @page {
            size: A4 portrait;
            margin: 12mm 12mm 16mm 12mm;
        }
        @endif
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 48px 24px;
            font-family: 'Inter', 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(140deg, var(--bg-start) 0%, var(--bg-mid) 50%, var(--bg-end) 100%);
            color: var(--primary);
        }
        .wrapper {
            max-width: 860px;
            margin: 0 auto;
        }
        .card {
            position: relative;
            background: var(--card);
            border-radius: var(--radius-lg);
            border: 1px solid rgba(15, 23, 42, 0.06);
            padding: 48px;
            overflow: hidden;
            box-shadow:
                0 60px 90px -65px var(--shadow-dark),
                0 40px 70px -55px var(--shadow-light);
        }
        .card::before,
        .card::after {
            content: '';
            position: absolute;
            pointer-events: none;
            border-radius: 50%;
        }
        .card::before {
            width: 360px;
            height: 360px;
            top: -220px;
            left: -140px;
            background: radial-gradient(circle, rgba(14,165,233,0.22), rgba(14,165,233,0));
        }
        .card::after {
            width: 480px;
            height: 480px;
            bottom: -320px;
            right: -200px;
            background: radial-gradient(circle, rgba(14,165,233,0.18), rgba(14,165,233,0));
        }
        header {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: space-between;
            gap: 32px;
            align-items: flex-start;
        }
        .brand {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .brand h1 {
            margin: 0;
            font-size: 28px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .brand span {
            font-size: 13px;
            letter-spacing: 0.18em;
            color: var(--muted);
        }
        .meta {
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 13px;
            color: var(--muted);
            text-align: right;
        }
        .status-chip {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            border-radius: 999px;
            letter-spacing: 0.18em;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            position: relative;
            overflow: hidden;
        }
        .status-chip::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle, rgba(255,255,255,0.4), rgba(255,255,255,0));
        }
        .info-grid {
            position: relative;
            z-index: 2;
            display: grid;
            gap: 26px;
            margin: 40px 0;
        }
        @media (min-width: 720px) {
            .info-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
        .info-card {
            background: rgba(255,255,255,0.9);
            border-radius: var(--radius-md);
            border: 1px solid rgba(15,23,42,0.06);
            padding: 24px;
            backdrop-filter: blur(8px);
            box-shadow: 0 24px 36px -30px rgba(15, 23, 42, 0.25);
        }
        .info-card h2 {
            margin: 0 0 12px;
            font-size: 13px;
            letter-spacing: 0.2em;
            color: var(--muted);
            text-transform: uppercase;
        }
        .info-card p {
            margin: 4px 0;
            font-size: 14px;
            color: var(--primary);
        }
        .reference-block {
            margin-top: 10px;
            display: grid;
            gap: 6px;
            font-size: 13px;
        }
        .reference-block span.label {
            color: var(--muted);
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-size: 11px;
        }
        .reference-block span.value {
            padding: 10px 14px;
            border-radius: var(--radius-sm);
            background: rgba(15,23,42,0.05);
            border: 1px solid rgba(15,23,42,0.08);
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            letter-spacing: 0.08em;
            word-break: break-word;
        }
        section.summary {
            position: relative;
            z-index: 2;
        }
        section.summary h3 {
            margin: 0;
            font-size: 13px;
            letter-spacing: 0.18em;
            color: var(--muted);
            text-transform: uppercase;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
            background: rgba(255,255,255,0.92);
            border-radius: var(--radius-md);
            overflow: hidden;
            border: 1px solid rgba(15,23,42,0.05);
            box-shadow: 0 24px 36px -32px rgba(15, 23, 42, 0.2);
        }
        thead {
            background: rgba(14,165,233,0.08);
        }
        th, td {
            padding: 16px 20px;
            text-align: left;
            font-size: 14px;
        }
        th {
            font-size: 12px;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--muted);
        }
        tbody tr + tr {
            border-top: 1px solid rgba(15,23,42,0.05);
        }
        .gateway-note {
            margin: 12px 0 0;
            font-size: 12px;
            color: var(--muted);
        }
        .totals {
            margin-top: 26px;
            margin-left: auto;
            width: min(360px, 100%);
            background: linear-gradient(135deg, rgba(14,165,233,0.2), rgba(14,165,233,0.06));
            border-radius: var(--radius-md);
            padding: 26px 30px;
            border: 1px solid rgba(14,165,233,0.14);
            box-shadow: 0 28px 48px -35px rgba(14, 165, 233, 0.35);
        }
        .totals dl {
            margin: 0;
            display: grid;
            gap: 12px;
        }
        .totals dd,
        .totals dt {
            display: flex;
            justify-content: space-between;
            margin: 0;
            font-size: 13px;
            color: var(--muted);
        }
        .totals dd.total {
            font-size: 19px;
            font-weight: 700;
            color: var(--primary);
            border-top: 1px dashed rgba(15,23,42,0.12);
            padding-top: 12px;
        }
        .totals dd.total span:last-child {
            color: var(--secondary);
            font-size: 21px;
        }
        .note {
            margin-top: 16px;
            font-size: 12px;
            color: var(--muted);
            text-align: right;
        }
        .action-bar {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: center;
            gap: 14px;
            margin: 36px 0 0;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 22px;
            border-radius: 999px;
            font-weight: 600;
            font-size: 13px;
            text-decoration: none;
            transition: all 0.25s ease;
            box-shadow: 0 20px 35px -24px rgba(15,23,42,0.22);
        }
        .btn-primary {
            background: var(--secondary);
            color: #fff;
            border: none;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 26px 45px -28px rgba(14,165,233,0.5);
        }
        .btn-soft {
            background: rgba(15,23,42,0.06);
            color: var(--primary);
            border: 1px solid rgba(15,23,42,0.08);
        }
        .btn-soft:hover {
            background: rgba(15,23,42,0.09);
        }
        footer {
            margin-top: 48px;
            position: relative;
            z-index: 2;
            text-align: center;
            font-size: 12px;
            color: var(--muted);
        }
        body.is-exporting {
            padding: 0;
            background: #ffffff;
        }
        body.is-exporting .wrapper {
            max-width: none;
            margin: 0;
            padding: 0;
        }
        body.is-exporting .card {
            border-radius: 12px;
            border: 1px solid rgba(15, 23, 42, 0.12);
            box-shadow: none;
            padding: 24px 26px;
        }
        body.is-exporting .card::before,
        body.is-exporting .card::after {
            display: none;
        }
        body.is-exporting header {
            align-items: flex-start;
            gap: 16px;
        }
        body.is-exporting header > .brand,
        body.is-exporting header > .meta {
            flex: 1;
        }
        body.is-exporting .brand h1 {
            font-size: 20px;
            letter-spacing: 0.06em;
        }
        body.is-exporting .brand span {
            font-size: 11px;
            letter-spacing: 0.14em;
        }
        body.is-exporting .meta {
            font-size: 11px;
            gap: 3px;
        }
        body.is-exporting .status-chip {
            padding: 8px 14px;
            font-size: 10px;
            letter-spacing: 0.12em;
            gap: 8px;
        }
        body.is-exporting .info-grid {
            margin: 22px 0 20px;
            gap: 14px;
        }
        body.is-exporting .info-card {
            background: #ffffff;
            border: 1px solid rgba(15, 23, 42, 0.12);
            box-shadow: none;
            backdrop-filter: none;
            page-break-inside: avoid;
            padding: 18px 20px;
        }
        body.is-exporting .info-card h2 {
            margin-bottom: 10px;
            font-size: 11px;
        }
        body.is-exporting .info-card p {
            font-size: 12px;
            margin: 2px 0;
        }
        body.is-exporting .info-card h2 {
            letter-spacing: 0.16em;
        }
        body.is-exporting .reference-block span.value {
            background: rgba(15,23,42,0.04);
        }
        body.is-exporting section.summary,
        body.is-exporting .totals,
        body.is-exporting table,
        body.is-exporting footer,
        body.is-exporting .note {
            page-break-inside: avoid;
        }
        body.is-exporting table {
            border: 1px solid rgba(15, 23, 42, 0.16);
            box-shadow: none;
            margin-top: 14px;
        }
        body.is-exporting table th,
        body.is-exporting table td {
            padding: 10px 14px;
            font-size: 12px;
        }
        body.is-exporting table th {
            font-size: 11px;
            letter-spacing: 0.12em;
        }
        body.is-exporting .totals {
            margin-top: 18px;
            margin-left: auto;
            width: 100%;
            max-width: 280px;
            padding: 18px 20px;
            background: linear-gradient(135deg, rgba(14,165,233,0.12), rgba(14,165,233,0.04));
            box-shadow: none;
        }
        body.is-exporting .totals dl {
            gap: 10px;
        }
        body.is-exporting .totals dd {
            font-size: 11px;
        }
        body.is-exporting .totals dd.total {
            font-size: 16px;
            padding-top: 10px;
        }
        body.is-exporting .totals dd.total span:last-child {
            font-size: 17px;
        }
        body.is-exporting footer {
            margin-top: 24px;
            font-size: 10px;
        }
        body.is-exporting .note {
            font-size: 10px;
            margin-top: 10px;
        }
        body.is-exporting .gateway-note {
            margin-top: 8px;
            font-size: 11px;
        }
        body.is-exporting .action-bar {
            display: none;
        }
        @media (max-width: 680px) {
            body { padding: 32px 16px; }
            .card { padding: 32px 24px; }
            header { flex-direction: column; align-items: flex-start; }
            .meta { text-align: left; }
            .totals { width: 100%; }
            .action-bar { flex-direction: column; gap: 10px; }
        }
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .wrapper {
                max-width: none;
                margin: 0;
            }
            .card {
                border-radius: 12px;
                border: 1px solid rgba(15, 23, 42, 0.12);
                box-shadow: none;
                padding: 24px 26px;
            }
            header { gap: 16px; }
            header > .brand,
            header > .meta { flex: 1; }
            .card::before,
            .card::after {
                display: none !important;
            }
            .info-card {
                background: #ffffff;
                border: 1px solid rgba(15, 23, 42, 0.12);
                box-shadow: none;
                backdrop-filter: none;
                page-break-inside: avoid;
                padding: 18px 20px;
            }
            table {
                border: 1px solid rgba(15, 23, 42, 0.16);
                box-shadow: none;
                margin-top: 14px;
            }
            .status-chip {
                padding: 8px 14px;
                font-size: 10px;
                letter-spacing: 0.12em;
            }
            table th,
            table td {
                padding: 10px 14px;
                font-size: 12px;
            }
            table th {
                font-size: 11px;
            }
            .totals {
                margin-top: 18px;
                padding: 18px 20px;
                box-shadow: none;
            }
            .totals dd {
                font-size: 11px;
            }
            .totals dd.total {
                font-size: 16px;
                padding-top: 10px;
            }
            .note,
            footer {
                page-break-inside: avoid;
            }
            footer {
                font-size: 10px;
                margin-top: 24px;
            }
            .action-bar { display: none !important; }
        }
    </style>
</head>
<body class="{{ $exporting ? 'is-exporting' : '' }}">
    @php
        $siteName = config('app.name', 'Sistem RT');
        $timezone = config('app.timezone', 'Asia/Jakarta');
        $issuedAt = optional($bill->issued_at)->setTimezone($timezone);
        $createdAt = optional($bill->created_at)->setTimezone($timezone);
        $dueDate = optional($bill->due_date)->setTimezone($timezone);
        $transactionId = data_get($payment->raw_payload, 'response.transaction_id')
            ?? data_get($payment->raw_payload, 'response.id')
            ?? data_get($payment->raw_payload, 'response.order_id');
        $gatewayLabel = strtoupper($payment->gateway);
        $baseAmount = (int) $bill->amount;
        $feeAmount = max((int) $payment->fee_amount, 0);
        $customerTotal = (int) ($payment->customer_total ?? ($baseAmount + $feeAmount));
        if ($customerTotal <= 0) {
            $customerTotal = $baseAmount + $feeAmount;
        }
        $statusIsPaid = $payment->status === 'paid';
        $statusLabel = $statusIsPaid ? 'Lunas / Paid' : strtoupper($payment->status);
        $statusStyle = $statusIsPaid
            ? 'background: rgba(34,197,94,0.14); color: var(--accent);'
            : 'background: rgba(239,68,68,0.18); color: #ef4444;';

        $gatewayPaidAtRaw = data_get($payment->raw_payload, 'webhook.paid_at')
            ?? data_get($payment->raw_payload, 'response.paid_at')
            ?? data_get($payment->raw_payload, 'response.data.paid_at')
            ?? data_get($payment->raw_payload, 'response.data.paid_time');
        if ($gatewayPaidAtRaw) {
            try {
                $paidAtDisplay = \Illuminate\Support\Carbon::parse($gatewayPaidAtRaw, $timezone)->setTimezone($timezone);
            } catch (\Throwable $e) {
                $paidAtDisplay = optional($payment->paid_at)->copy();
            }
        } else {
            $paidAtDisplay = optional($payment->paid_at)->copy();
        }
        if ($paidAtDisplay) {
            $paidAtDisplay = $paidAtDisplay->setTimezone($timezone);
        }
    @endphp

    <div class="wrapper">
        <div class="card">
            <header>
                <div class="brand">
                    <h1>{{ $siteName }}</h1>
                    <span>INVOICE</span>
                </div>
                <div class="meta">
                    <div>Invoice: {{ $bill->invoice_number }}</div>
                    <div>Diterbitkan: {{ ($createdAt ?? now($timezone))->translatedFormat('d M Y H:i') }}</div>
                    <div>Jatuh Tempo: {{ $dueDate?->translatedFormat('d M Y H:i') ?? '-' }}</div>
                </div>
                <span class="status-chip" style="{{ $statusStyle }}">{{ $statusLabel }}</span>
            </header>

            <div class="info-grid">
                <div class="info-card">
                    <h2>Informasi Warga</h2>
                    <p>{{ $bill->user?->name ?? '-' }}</p>
                    <p>{{ $bill->user?->alamat ?? 'Alamat belum tersedia' }}</p>
                    <p>{{ $bill->user?->masked_phone ?? '-' }}</p>
                </div>
                <div class="info-card">
                    <h2>Rincian Tagihan</h2>
                    <p>{{ $bill->title }}</p>
                    <p>Jenis: {{ \Illuminate\Support\Str::headline($bill->type) }}</p>
                    <p>Periode: {{ $issuedAt?->translatedFormat('F Y') ?? '-' }}</p>
                </div>
                <div class="info-card">
                    <h2>Status Pembayaran</h2>
                    <p>Metode: {{ $gatewayLabel }}</p>
                    <div class="reference-block">
                        <span class="label">Referensi</span>
                        <span class="value">{{ $payment->reference ?? '-' }}</span>
                    </div>
                    <p>Tanggal: {{ $paidAtDisplay?->translatedFormat('d M Y H:i') ?? '-' }}</p>
                    <p>Transaksi: {{ $transactionId ?? '-' }}</p>
                </div>
            </div>

            <section class="summary">
                <h3>Ringkasan Pembayaran</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Deskripsi</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                            <th>Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $bill->title }}</td>
                            <td>1</td>
                            <td>{{ \Illuminate\Support\Str::headline($payment->status) }}</td>
                            <td>Rp {{ number_format($payment->amount) }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            @if ($feeAmount > 0)
                <p class="gateway-note">
                    * Biaya administrasi gateway dibebankan ke warga sebesar Rp {{ number_format($feeAmount) }}.
                </p>
            @endif

            <div class="totals">
                <dl>
                    <dd><span>Subtotal</span><span>Rp {{ number_format($payment->amount) }}</span></dd>
                    <dd><span>Biaya Admin</span><span>Rp {{ number_format($feeAmount) }}</span></dd>
                    <dd class="total"><span>Total Dibayar</span><span>Rp {{ number_format($customerTotal) }}</span></dd>
                </dl>
            </div>
            <p class="note">Total diterima pengurus: Rp {{ number_format($payment->amount) }} (biaya admin dipotong oleh gateway).</p>

            @unless($exporting)
                <div class="action-bar">
                    <a href="{{ route('resident.bills.receipt.download', $bill) }}" class="btn btn-primary">Download Bukti (PDF)</a>
                    <button type="button" class="btn btn-soft" data-close-receipt>Kembali</button>
                </div>
                <script>
                    (function () {
                        const backButton = document.querySelector('[data-close-receipt]');
                        if (!backButton) {
                            return;
                        }

                        backButton.addEventListener('click', function (event) {
                            event.preventDefault();

                            const hasOpener =
                                typeof window !== 'undefined' &&
                                !!window.opener &&
                                !window.opener.closed;

                            if (hasOpener) {
                                window.opener.focus();
                                window.close();
                                return;
                            }

                            if (window.history.length > 1) {
                                window.history.back();
                                return;
                            }

                            window.location.href = @json(route('resident.bills'));
                        });
                    })();
                </script>
            @endunless

            <footer>
                <p>Dokumen ini dihasilkan otomatis oleh sistem {{ $siteName }} dan sah tanpa tanda tangan.</p>
            </footer>
        </div>
    </div>
</body>
</html>
