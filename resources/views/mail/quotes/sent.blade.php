<!DOCTYPE html>
<html lang="pl">
<head><meta charset="utf-8"><title>Oferta {{ $quote->number }}</title></head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 24px auto; color: #222; line-height: 1.55;">

    <h2 style="color: #4338ca; margin-bottom: 8px;">Oferta transportu — {{ $quote->organization->name }}</h2>

    <p>Dzień dobry, {{ $quote->client_name }}!</p>

    @if ($messageBody)
        <div style="background: #f9fafb; padding: 12px 16px; border-radius: 6px; margin: 16px 0; white-space: pre-line;">{{ $messageBody }}</div>
    @else
        <p>W załączniku znajdą Państwo ofertę cenową na transport koni wg poniższych warunków.</p>
    @endif

    <table style="width: 100%; border-collapse: collapse; margin: 16px 0;">
        <tr><td style="padding: 4px 0; color: #666;">Numer oferty:</td><td><strong>{{ $quote->number }}</strong></td></tr>
        <tr><td style="padding: 4px 0; color: #666;">Trasa:</td><td>{{ $quote->from_address }} → {{ $quote->to_address }}</td></tr>
        <tr><td style="padding: 4px 0; color: #666;">Dystans:</td><td>{{ number_format((float) $quote->distance_km, 2, ',', ' ') }} km</td></tr>
        <tr><td style="padding: 4px 0; color: #666;">Konie:</td><td>{{ $quote->horses_count }}</td></tr>
        <tr><td style="padding: 4px 0; color: #666;">Data transportu:</td><td>{{ $quote->transport_date?->format('Y-m-d') ?? 'do uzgodnienia' }}</td></tr>
        <tr><td style="padding: 4px 0; color: #666;">Cena brutto:</td>
            <td><strong style="font-size: 18px; color: #4338ca;">{{ number_format((float) $quote->total_gross, 2, ',', ' ') }} {{ $quote->currency }}</strong></td></tr>
        <tr><td style="padding: 4px 0; color: #666;">Ważność oferty:</td><td>{{ $quote->valid_until?->format('Y-m-d') ?? '—' }}</td></tr>
    </table>

    <p style="margin-top: 24px;">
        <a href="{{ $publicUrl }}"
           style="display: inline-block; background: #4338ca; color: #fff; padding: 10px 22px; text-decoration: none; border-radius: 6px; font-weight: bold;">
            Zobacz ofertę online →
        </a>
    </p>

    <p style="margin-top: 24px; color: #555;">
        Pełny szczegółowy dokument znajduje się w załączniku PDF.
    </p>

    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 28px 0;">
    <p style="font-size: 12px; color: #888;">
        {{ $quote->organization->name }}<br>
        @if ($quote->organization->company_phone) tel.: {{ $quote->organization->company_phone }}<br>@endif
        @if ($quote->organization->company_email) {{ $quote->organization->company_email }}@endif
    </p>
</body>
</html>
