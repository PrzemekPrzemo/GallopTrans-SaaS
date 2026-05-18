<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Payment;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;

/**
 * Rejestrowanie wpłat do ofert. Z kwoty brutto wylicza netto i VAT
 * na podstawie vat_percent z oferty.
 */
final class PaymentService
{
    /** @param array{amount_gross:float,payment_type?:string,payment_method?:string,paid_at:string,reference?:?string,note?:?string} $data */
    public static function record(Quote $quote, array $data): Payment
    {
        $gross = (float) $data['amount_gross'];
        $vatPercent = (float) $quote->vat_percent;
        $net = $vatPercent > 0 ? $gross / (1 + $vatPercent / 100) : $gross;
        $vat = $gross - $net;

        return DB::transaction(function () use ($quote, $data, $gross, $net, $vat) {
            $payment = Payment::create([
                'organization_id' => $quote->organization_id,
                'quote_id'        => $quote->id,
                'amount_gross'    => round($gross, 2),
                'amount_net'      => round($net, 2),
                'vat_amount'      => round($vat, 2),
                'currency'        => $quote->currency,
                'payment_type'    => $data['payment_type']   ?? 'full',
                'payment_method'  => $data['payment_method'] ?? 'transfer',
                'paid_at'         => $data['paid_at'],
                'reference'       => $data['reference'] ?? null,
                'note'            => $data['note'] ?? null,
            ]);

            // Auto-status: pełna zapłata → status 'accepted' (jeśli był sent/draft).
            $totalPaid = (float) Payment::where('quote_id', $quote->id)->sum('amount_gross');
            if ($totalPaid + 0.01 >= (float) $quote->total_gross && in_array($quote->status, ['draft', 'sent'], true)) {
                $quote->update(['status' => 'accepted', 'accepted_at' => $quote->accepted_at ?? now()]);
            }

            return $payment;
        });
    }
}
