<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\PaymentReminderMail;
use App\Models\Invoice;
use Illuminate\Support\Facades\Mail;

/**
 * Wysyła przypomnienia o płatnościach dla faktur:
 *  - których termin płatności minął
 *  - które nie są w pełni opłacone (saldo > 0 na powiązanej ofercie)
 *  - których ostatnie przypomnienie było wysłane > 3 dni temu (żeby nie spamować)
 */
final class PaymentReminderService
{
    private const COOLDOWN_DAYS = 3;
    private const MAX_REMINDERS = 5;

    /** @return array{sent:int,skipped:int,errors:int} */
    public static function sendDue(): array
    {
        $stats = ['sent' => 0, 'skipped' => 0, 'errors' => 0];

        $invoices = Invoice::query()
            ->withoutGlobalScopes()
            ->with(['quote.payments', 'organization'])
            ->whereNotNull('payment_due_at')
            ->where('payment_due_at', '<', now()->toDateString())
            ->where('reminders_sent', '<', self::MAX_REMINDERS)
            ->whereNotNull('client_email')
            ->where('client_email', '!=', '')
            ->get();

        foreach ($invoices as $invoice) {
            // Sprawdzamy saldo z powiązanej oferty (jeśli istnieje).
            $balance = self::balanceFor($invoice);
            if ($balance <= 0) {
                $stats['skipped']++;
                continue;
            }

            // Cooldown — nie wysyłaj częściej niż co N dni.
            if ($invoice->last_reminder_at && $invoice->last_reminder_at->diffInDays(now()) < self::COOLDOWN_DAYS) {
                $stats['skipped']++;
                continue;
            }

            try {
                Mail::to($invoice->client_email)->send(new PaymentReminderMail($invoice, $balance));
                $invoice->update([
                    'last_reminder_at' => now(),
                    'reminders_sent'   => $invoice->reminders_sent + 1,
                ]);
                $stats['sent']++;
            } catch (\Throwable $e) {
                $stats['errors']++;
            }
        }

        return $stats;
    }

    private static function balanceFor(Invoice $invoice): float
    {
        if (! $invoice->quote) {
            return (float) $invoice->total_gross;
        }
        $paid = (float) $invoice->quote->payments->sum('amount_gross');
        return round((float) $invoice->total_gross - $paid, 2);
    }
}
