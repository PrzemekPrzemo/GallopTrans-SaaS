<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Oferta {{ $quote->number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; color: #111; }
        .header { display: table; width: 100%; margin-bottom: 24pt; }
        .header .left, .header .right { display: table-cell; vertical-align: top; }
        .header .right { text-align: right; }
        h1 { font-size: 18pt; margin: 0; }
        .muted { color: #666; font-size: 9pt; }
        .box { border: 1px solid #ddd; padding: 8pt 10pt; margin-bottom: 10pt; }
        table { width: 100%; border-collapse: collapse; margin-top: 8pt; }
        th, td { padding: 5pt 6pt; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #f5f5f5; font-size: 9pt; text-transform: uppercase; letter-spacing: 0.5pt; }
        .right { text-align: right; }
        .totals td { border: none; padding: 4pt 6pt; }
        .totals tr.total td { border-top: 2pt solid #333; font-weight: bold; font-size: 13pt; }
        .footer { margin-top: 30pt; font-size: 9pt; color: #666; }
        .pill { display: inline-block; padding: 2pt 6pt; background: #eef2ff; color: #3730a3; border-radius: 3pt; font-size: 9pt; }
    </style>
</head>
<body>

<div class="header">
    <div class="left">
        <h1>Oferta {{ $quote->number }}</h1>
        <div class="muted">Wystawiona: {{ $quote->created_at->format('Y-m-d') }} · Ważna do: {{ $quote->valid_until?->format('Y-m-d') ?? '—' }}</div>
    </div>
    <div class="right">
        <strong>{{ $quote->organization->name }}</strong><br>
        @if ($quote->organization->company_address)<span class="muted">{{ $quote->organization->company_address }}</span><br>@endif
        @if ($quote->organization->company_nip)<span class="muted">NIP: {{ $quote->organization->company_nip }}</span><br>@endif
        @if ($quote->organization->company_phone)<span class="muted">{{ $quote->organization->company_phone }}</span><br>@endif
        @if ($quote->organization->company_email)<span class="muted">{{ $quote->organization->company_email }}</span>@endif
    </div>
</div>

<div class="box">
    <strong>Klient:</strong> {{ $quote->client_name }}
    @if ($quote->client_company)<br>{{ $quote->client_company }}@endif
    @if ($quote->client_nip)<br>NIP: {{ $quote->client_nip }}@endif
    @if ($quote->client_address)<br>{{ $quote->client_address }}@endif
    @if ($quote->client_email)<br>E-mail: {{ $quote->client_email }}@endif
    @if ($quote->client_phone)<br>Tel.: {{ $quote->client_phone }}@endif
</div>

<div class="box">
    <strong>Trasa:</strong> {{ $quote->from_address }} → {{ $quote->to_address }}<br>
    <span class="muted">
        Dystans: {{ number_format((float) $quote->distance_km, 2, ',', ' ') }} km ·
        Czas jazdy: ~{{ intdiv($quote->duration_min, 60) }} h {{ $quote->duration_min % 60 }} min ·
        Tryb: <span class="pill">{{ $quote->trip_mode }}</span> ·
        Konie: {{ $quote->horses_count }} ·
        Data: {{ $quote->transport_date?->format('Y-m-d') ?? '—' }}
    </span>
</div>

<table>
    <thead>
        <tr>
            <th>Opis</th>
            <th class="right">Ilość</th>
            <th class="right">Cena netto</th>
            <th class="right">Wartość netto</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($quote->items as $it)
            <tr>
                <td>{{ $it->description }}</td>
                <td class="right">{{ rtrim(rtrim(number_format($it->qty, 2, ',', ' '), '0'), ',') }} {{ $it->unit }}</td>
                <td class="right">{{ number_format((float) $it->unit_price_net, 2, ',', ' ') }}</td>
                <td class="right">{{ number_format((float) $it->total_net, 2, ',', ' ') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table class="totals">
    <tr><td class="right" colspan="3">Razem netto</td><td class="right">{{ number_format((float) $quote->subtotal_net, 2, ',', ' ') }} {{ $quote->currency }}</td></tr>
    <tr><td class="right" colspan="3">VAT ({{ number_format((float) $quote->vat_percent, 0) }}%)</td><td class="right">{{ number_format((float) $quote->vat_amount, 2, ',', ' ') }} {{ $quote->currency }}</td></tr>
    <tr class="total"><td class="right" colspan="3">Do zapłaty</td><td class="right">{{ number_format((float) $quote->total_gross, 2, ',', ' ') }} {{ $quote->currency }}</td></tr>
</table>

@if ($quote->notes)
    <div class="box" style="margin-top: 14pt;">
        <strong>Uwagi:</strong> {{ $quote->notes }}
    </div>
@endif

<div class="footer">
    Numer oferty: {{ $quote->number }} · Wygenerowano: {{ now()->format('Y-m-d H:i') }}<br>
    @if ($quote->organization->company_bank)
        Numer konta do wpłat: {{ $quote->organization->company_bank }}
    @endif
</div>

</body>
</html>
