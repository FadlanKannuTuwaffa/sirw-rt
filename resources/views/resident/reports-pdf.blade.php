<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Keuangan</title>
    <style>
        body {
            font-family: 'Inter', 'Poppins', Arial, sans-serif;
            margin: 32px;
            color: #0f172a;
        }
        h1 {
            margin: 0 0 8px;
            font-size: 24px;
            color: #0284C7;
        }
        p {
            margin: 0 0 16px;
            font-size: 12px;
            color: #475569;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #e2e8f0;
            padding: 10px 12px;
            font-size: 12px;
            text-align: left;
        }
        th {
            background: #f1f5f9;
            text-transform: uppercase;
            letter-spacing: .08em;
            font-size: 11px;
            color: #475569;
        }
        tfoot td {
            font-weight: 600;
            background: #eff6ff;
        }
        .totals {
            margin-top: 24px;
            width: 260px;
            float: right;
            border: 1px solid #cbd5f5;
            border-radius: 12px;
            overflow: hidden;
        }
        .totals div {
            display: flex;
            justify-content: space-between;
            padding: 10px 14px;
            font-size: 13px;
        }
        .totals div:nth-child(odd) {
            background: #f8fafc;
        }
        .totals div:last-child {
            background: #0284C7;
            color: white;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <h1>Rekap Keuangan Warga</h1>
    <p>Periode {{ \Carbon\Carbon::parse($period[0])->translatedFormat('d M Y') }} - {{ \Carbon\Carbon::parse($period[1])->translatedFormat('d M Y') }} &middot; {{ $site['name'] ?? 'Sistem RT' }}</p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Tanggal</th>
                <th>Kategori</th>
                <th>Deskripsi</th>
                <th>Aliran Dana</th>
                <th>Status</th>
                <th>Metode</th>
                <th>Nominal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($entries as $index => $entry)
                @php
                    $isIncome = $entry->amount >= 0;
                    $flowRaw = $isIncome ? ($entry->fund_destination ?? null) : ($entry->fund_source ?? null);
                    $flowValue = $flowRaw ?: 'kas';
                    $flowLabel = \Illuminate\Support\Str::headline($flowValue);
                    $statusValue = $entry->payment?->status ?? ($entry->status ?? 'pending');
                    $methodValue = $entry->payment?->gateway ?? ($entry->method ?? '-');
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $entry->occurred_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                    <td>{{ \Illuminate\Support\Str::headline($entry->category) }}</td>
                    <td>
                        <strong>{{ $entry->bill?->title ?? '-' }}</strong><br>
                        @if ($entry->fund_reference)
                            <span style="color:#64748b;">Ref: {{ $entry->fund_reference }}</span><br>
                        @endif
                        <span style="color:#64748b;">{{ $entry->notes ?? '-' }}</span>
                    </td>
                    <td>
                        {{ $flowLabel }}
                    </td>
                    <td>{{ \Illuminate\Support\Str::headline($statusValue) }}</td>
                    <td>{{ strtoupper($methodValue) }}</td>
                    <td style="text-align:right;">{{ ($entry->amount >= 0 ? '+' : '-') }} Rp {{ number_format(abs($entry->amount)) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div><span>Total Pemasukan</span><span>Rp {{ number_format($totals['income']) }}</span></div>
        <div><span>Total Pengeluaran</span><span>Rp {{ number_format($totals['expense']) }}</span></div>
        <div><span>Saldo Bersih</span><span>Rp {{ number_format($totals['net']) }}</span></div>
    </div>
</body>
</html>
