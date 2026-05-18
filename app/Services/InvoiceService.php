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

    /**
     * Wystawia fakturę zaliczkową na podaną kwotę (brutto).
     * Z brutto wyliczamy netto i VAT na podstawie vat_percent oferty.
     */
    public static function advance(Quote $quote, float $advanceGross, ?string $note = null): Invoice
    {
        if ($quote->status !== 'accepted') {
            throw new \RuntimeException('Fakturę zaliczkową można wystawić tylko z zaakceptowanej oferty.');
        }
        $advanceGross = round(max(0, $advanceGross), 2);
        if ($advanceGross <= 0 || $advanceGross > (float) $quote->total_gross) {
            throw new \RuntimeException('Kwota zaliczki musi być > 0 i ≤ wartości brutto oferty.');
        }

        $org = $quote->organization;
        $numbering = InvoiceNumberGenerator::next($org);
        $vatPercent = (float) $quote->vat_percent;
        $net = $vatPercent > 0 ? $advanceGross / (1 + $vatPercent / 100) : $advanceGross;
        $vat = $advanceGross - $net;

        return DB::transaction(function () use ($quote, $org, $numbering, $advanceGross, $net, $vat, $vatPercent, $note) {
            $invoice = Invoice::create([
                'organization_id' => $quote->organization_id,
                'quote_id'        => $quote->id,
                'number'          => $numbering['number'],
                'year'  => $numbering['year'], 'month' => $numbering['month'], 'sequence' => $numbering['sequence'],
                'type'            => 'invoice',
                'invoice_subtype' => 'advance',

                'client_name'    => $quote->client_name,
                'client_company' => $quote->client_company,
                'client_nip'     => $quote->client_nip,
                'client_address' => $quote->client_address,
                'client_email'   => $quote->client_email,

                'subtotal_net'   => round($net, 2),
                'vat_amount'     => round($vat, 2),
                'total_gross'    => $advanceGross,
                'vat_percent'    => $vatPercent,
                'currency'       => $quote->currency,

                'issued_at'      => now()->toDateString(),
                'sold_at'        => now()->toDateString(),
                'payment_due_at' => now()->addDays((int) $org->invoice_payment_due_days)->toDateString(),

                'ksef_status'    => $org->ksef_mode === 'disabled' ? 'manual' : 'draft',
                'notes'          => $note,
                'created_by'     => auth()->id(),
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => "Zaliczka na transport ({$quote->number})",
                'qty' => 1, 'unit' => null,
                'unit_price_net' => round($net, 2),
                'total_net'      => round($net, 2),
                'vat_percent'    => $vatPercent,
                'sort_order'     => 0,
            ]);

            return $invoice;
        });
    }

    /**
     * Wystawia fakturę końcową rozliczeniową — pomniejszoną o sumę zaliczek
     * powiązanych z tą samą ofertą.
     */
    public static function finalSettlement(Quote $quote): Invoice
    {
        if ($quote->status !== 'accepted') {
            throw new \RuntimeException('Fakturę końcową można wystawić tylko z zaakceptowanej oferty.');
        }

        $advances = Invoice::query()
            ->where('quote_id', $quote->id)
            ->where('invoice_subtype', 'advance')
            ->get();

        if ($advances->isEmpty()) {
            throw new \RuntimeException('Brak faktur zaliczkowych do rozliczenia.');
        }

        $settledGross = (float) $advances->sum('total_gross');
        $remainingGross = round((float) $quote->total_gross - $settledGross, 2);
        if ($remainingGross <= 0) {
            throw new \RuntimeException('Zaliczki w pełni pokrywają wartość oferty — faktura końcowa zbędna.');
        }

        $org = $quote->organization;
        $numbering = InvoiceNumberGenerator::next($org);
        $vatPercent = (float) $quote->vat_percent;
        $net = $vatPercent > 0 ? $remainingGross / (1 + $vatPercent / 100) : $remainingGross;
        $vat = $remainingGross - $net;

        return DB::transaction(function () use ($quote, $org, $numbering, $remainingGross, $net, $vat, $vatPercent, $advances, $settledGross) {
            $invoice = Invoice::create([
                'organization_id' => $quote->organization_id,
                'quote_id'        => $quote->id,
                'number'          => $numbering['number'],
                'year' => $numbering['year'], 'month' => $numbering['month'], 'sequence' => $numbering['sequence'],
                'type'            => 'invoice',
                'invoice_subtype' => 'final',
                'advance_invoice_ids'  => $advances->pluck('id')->toArray(),
                'settled_from_advances'=> $settledGross,

                'client_name'    => $quote->client_name,
                'client_company' => $quote->client_company,
                'client_nip'     => $quote->client_nip,
                'client_address' => $quote->client_address,
                'client_email'   => $quote->client_email,

                'subtotal_net'   => round($net, 2),
                'vat_amount'     => round($vat, 2),
                'total_gross'    => $remainingGross,
                'vat_percent'    => $vatPercent,
                'currency'       => $quote->currency,

                'issued_at'      => now()->toDateString(),
                'sold_at'        => $quote->transport_date ?? now()->toDateString(),
                'payment_due_at' => now()->addDays((int) $org->invoice_payment_due_days)->toDateString(),

                'ksef_status'    => $org->ksef_mode === 'disabled' ? 'manual' : 'draft',
                'created_by'     => auth()->id(),
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => sprintf(
                    'Rozliczenie końcowe transportu (%s) po zaliczkach: %d × ZAL na łącznie %s zł',
                    $quote->number, $advances->count(), number_format($settledGross, 2, ',', ' ')
                ),
                'qty' => 1,
                'unit_price_net' => round($net, 2),
                'total_net'      => round($net, 2),
                'vat_percent'    => $vatPercent,
                'sort_order'     => 0,
            ]);

            return $invoice;
        });
    }

    /**
     * Wystawia fakturę korygującą do istniejącej faktury.
     * Kwoty można w korekcie dostosować (delta lub całkowita zmiana).
     *
     * @param array{reason:string,subtotal_net?:float,vat_amount?:float,total_gross?:float} $payload
     */
    public static function correction(Invoice $original, array $payload): Invoice
    {
        $org = $original->organization;
        $numbering = InvoiceNumberGenerator::next($org);

        return DB::transaction(function () use ($original, $org, $numbering, $payload) {
            $correction = Invoice::create([
                'organization_id'      => $original->organization_id,
                'quote_id'             => $original->quote_id,
                'number'               => $numbering['number'],
                'year'                 => $numbering['year'],
                'month'                => $numbering['month'],
                'sequence'             => $numbering['sequence'],
                'type'                 => 'correction',
                'corrects_invoice_id'  => $original->id,
                'correction_reason'    => $payload['reason'],

                'client_name'    => $original->client_name,
                'client_company' => $original->client_company,
                'client_nip'     => $original->client_nip,
                'client_address' => $original->client_address,
                'client_email'   => $original->client_email,

                'subtotal_net'   => $payload['subtotal_net'] ?? $original->subtotal_net,
                'vat_amount'     => $payload['vat_amount']   ?? $original->vat_amount,
                'total_gross'    => $payload['total_gross']  ?? $original->total_gross,
                'vat_percent'    => $original->vat_percent,
                'currency'       => $original->currency,

                'issued_at'      => now()->toDateString(),
                'sold_at'        => $original->sold_at,
                'payment_due_at' => now()->addDays((int) $org->invoice_payment_due_days)->toDateString(),

                'ksef_status'    => $org->ksef_mode === 'disabled' ? 'manual' : 'draft',
                'notes'          => $payload['notes'] ?? null,
                'created_by'     => auth()->id(),
            ]);

            // Pozycje: jeśli nie nadpisane — kopiujemy z oryginału, jeśli są w payloadzie - bierzemy je.
            $items = $payload['items'] ?? null;
            if ($items === null) {
                foreach ($original->items as $orig) {
                    InvoiceItem::create([
                        'invoice_id' => $correction->id,
                        'description' => $orig->description,
                        'qty' => $orig->qty, 'unit' => $orig->unit,
                        'unit_price_net' => $orig->unit_price_net, 'total_net' => $orig->total_net,
                        'vat_percent' => $orig->vat_percent, 'sort_order' => $orig->sort_order,
                    ]);
                }
            }

            return $correction;
        });
    }
}
