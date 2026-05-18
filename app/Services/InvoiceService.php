<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;

/**
 * Tworzy fakturę z zaakceptowanej oferty. Snapshot kwot i pozycji — żeby
 * późniejsza edycja oferty nie zepsuła już wystawionej faktury.
 *
 * Wysłanie do KSeF jest osobnym krokiem (KsefService::send), tu tylko
 * tworzymy rekord w bazie w stanie `ksef_status=draft`.
 */
final class InvoiceService
{
    public static function fromQuote(Quote $quote): Invoice
    {
        $org = $quote->organization;
        $numbering = InvoiceNumberGenerator::next($org);

        return DB::transaction(function () use ($quote, $org, $numbering) {
            $invoice = Invoice::create([
                'organization_id' => $quote->organization_id,
                'quote_id'        => $quote->id,
                'number'          => $numbering['number'],
                'year'            => $numbering['year'],
                'month'           => $numbering['month'],
                'sequence'        => $numbering['sequence'],

                'client_name'     => $quote->client_name,
                'client_company'  => $quote->client_company,
                'client_nip'      => $quote->client_nip,
                'client_address'  => $quote->client_address,
                'client_email'    => $quote->client_email,

                'subtotal_net'    => $quote->subtotal_net,
                'vat_amount'      => $quote->vat_amount,
                'total_gross'     => $quote->total_gross,
                'vat_percent'     => $quote->vat_percent,
                'currency'        => $quote->currency,

                'issued_at'       => now()->toDateString(),
                'sold_at'         => $quote->transport_date ?? now()->toDateString(),
                'payment_due_at'  => now()->addDays((int) $org->invoice_payment_due_days)->toDateString(),

                'ksef_status'     => $org->ksef_mode === 'disabled' ? 'manual' : 'draft',
                'created_by'      => auth()->id(),
            ]);

            $sort = 0;
            foreach ($quote->items as $item) {
                if ((float) $item->total_net == 0.0) {
                    continue;
                }
                InvoiceItem::create([
                    'invoice_id'     => $invoice->id,
                    'description'    => $item->description,
                    'qty'            => $item->qty,
                    'unit'           => $item->unit,
                    'unit_price_net' => $item->unit_price_net,
                    'total_net'      => $item->total_net,
                    'vat_percent'    => $quote->vat_percent,
                    'sort_order'     => $sort++,
                ]);
            }

            return $invoice;
        });
    }
}
