<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Raport {{ $start->format('Y-m') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #111; }
        h1 { font-size: 16pt; margin: 0 0 12pt 0; }
        h2 { font-size: 12pt; margin: 16pt 0 6pt 0; border-bottom: 1pt solid #ccc; padding-bottom: 4pt; }
        table { width: 100%; border-collapse: collapse; margin: 4pt 0; }
        th, td { padding: 4pt 6pt; border-bottom: 1px solid #eee; text-align: left; font-size: 8.5pt; }
        th { background: #f5f5f5; }
        .r { text-align: right; }
        .summary { background: #eef2ff; padding: 8pt; margin: 8pt 0 16pt 0; }
    </style>
</head>
<body>
    <h1>Raport miesięczny: {{ $start->translatedFormat('LLLL Y') }}</h1>
    <div class="muted" style="color:#666;">Okres: {{ $start->format('Y-m-d') }} — {{ $end->format('Y-m-d') }}</div>

    <div class="summary">
        <strong>Podsumowanie:</strong><br>
        Wartość ofert (brutto): {{ number_format($summary['quotes_gross'], 2, ',', ' ') }} zł ·
        Wystawione faktury (brutto): {{ number_format($summary['invoices_gross'], 2, ',', ' ') }} zł ·
        Wpłaty (brutto): {{ number_format($summary['payments_gross'], 2, ',', ' ') }} zł
    </div>

    <h2>Oferty ({{ $quotes->count() }})</h2>
    <table>
        <thead><tr><th>Numer</th><th>Data</th><th>Klient</th><th class="r">Brutto</th><th>Status</th></tr></thead>
        <tbody>
            @foreach ($quotes as $q)
                <tr><td>{{ $q->number }}</td><td>{{ $q->created_at->format('Y-m-d') }}</td><td>{{ $q->client_name }}</td>
                    <td class="r">{{ number_format((float) $q->total_gross, 2, ',', ' ') }} {{ $q->currency }}</td>
                    <td>{{ $q->status }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <h2>Faktury ({{ $invoices->count() }})</h2>
    <table>
        <thead><tr><th>Numer</th><th>Typ</th><th>Data</th><th>Klient</th><th class="r">Brutto</th><th>KSeF</th></tr></thead>
        <tbody>
            @foreach ($invoices as $i)
                <tr><td>{{ $i->number }}</td><td>{{ $i->type ?? 'invoice' }}</td><td>{{ $i->issued_at->format('Y-m-d') }}</td>
                    <td>{{ $i->client_company ?: $i->client_name }}</td>
                    <td class="r">{{ number_format((float) $i->total_gross, 2, ',', ' ') }} {{ $i->currency }}</td>
                    <td>{{ $i->ksef_status }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <h2>Wpłaty ({{ $payments->count() }})</h2>
    <table>
        <thead><tr><th>Data</th><th>Oferta</th><th>Klient</th><th>Typ</th><th class="r">Brutto</th></tr></thead>
        <tbody>
            @foreach ($payments as $p)
                <tr><td>{{ $p->paid_at->format('Y-m-d') }}</td><td>{{ $p->quote?->number }}</td><td>{{ $p->quote?->client_name }}</td>
                    <td>{{ $p->payment_type }}</td>
                    <td class="r">{{ number_format((float) $p->amount_gross, 2, ',', ' ') }} {{ $p->currency }}</td></tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
