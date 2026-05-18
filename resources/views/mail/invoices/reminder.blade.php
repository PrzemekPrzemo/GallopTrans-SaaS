<!DOCTYPE html>
<html lang="pl"><head><meta charset="utf-8"></head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 24px auto; color: #222; line-height: 1.55;">

    <h2 style="color:#b45309;">Przypomnienie o płatności</h2>

    <p>Dzień dobry, {{ $invoice->client_name }}!</p>

    @if ($daysOverdue > 0)
        <p>Faktura nr <strong>{{ $invoice->number }}</strong> jest <strong style="color:#dc2626">zaległa od {{ $daysOverdue }} dni</strong>
        (termin płatności minął {{ $invoice->payment_due_at?->format('Y-m-d') }}).</p>
    @else
        <p>Uprzejmie przypominamy o zbliżającym się terminie płatności faktury nr <strong>{{ $invoice->number }}</strong>.</p>
    @endif

    <table style="width: 100%; border-collapse: collapse; margin: 16px 0; background: #fef3c7; border-radius: 6px;">
        <tr><td style="padding: 8px 12px;">Do zapłaty:</td>
            <td style="padding: 8px 12px; text-align: right;"><strong style="font-size: 18px;">{{ number_format($balance, 2, ',', ' ') }} {{ $invoice->currency }}</strong></td></tr>
        <tr><td style="padding: 4px 12px;">Termin płatności:</td>
            <td style="padding: 4px 12px; text-align: right;">{{ $invoice->payment_due_at?->format('Y-m-d') ?? '—' }}</td></tr>
        @if ($invoice->organization->company_bank)
            <tr><td style="padding: 4px 12px;">Nr konta:</td>
                <td style="padding: 4px 12px; text-align: right; font-family: monospace;">{{ $invoice->organization->company_bank }}</td></tr>
        @endif
        <tr><td style="padding: 4px 12px;">Tytuł przelewu:</td>
            <td style="padding: 4px 12px; text-align: right; font-family: monospace;">{{ $invoice->number }}</td></tr>
    </table>

    <p>Jeśli płatność została już dokonana — prosimy zignorować tę wiadomość.</p>

    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 24px 0;">
    <p style="font-size: 12px; color: #888;">
        {{ $invoice->organization->name }}
        @if ($invoice->organization->company_phone) · tel.: {{ $invoice->organization->company_phone }} @endif
        @if ($invoice->organization->company_email) · {{ $invoice->organization->company_email }} @endif
    </p>
</body></html>
