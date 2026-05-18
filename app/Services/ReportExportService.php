<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Eksport raportów miesięcznych w 2 formatach:
 *  - CSV (UTF-8 BOM dla Excela)
 *  - PDF (DomPDF + osobny szablon raportu)
 */
final class ReportExportService
{
    public static function csv(Carbon $start, Carbon $end): StreamedResponse
    {
        $filename = sprintf('raport-%s.csv', $start->format('Y-m'));

        return response()->streamDownload(function () use ($start, $end) {
            $out = fopen('php://output', 'w');
            // BOM dla Excela żeby PL znaki nie były krzaczkowe.
            fwrite($out, "\xEF\xBB\xBF");

            // === Oferty ===
            fputcsv($out, ['=== OFERTY ==='], ';');
            fputcsv($out, ['Numer', 'Data', 'Klient', 'Trasa', 'Status', 'Netto', 'VAT', 'Brutto', 'Waluta'], ';');
            foreach (Quote::query()->whereBetween('created_at', [$start, $end])->orderBy('id')->get() as $q) {
                fputcsv($out, [
                    $q->number,
                    $q->created_at->format('Y-m-d'),
                    $q->client_name,
                    $q->from_address . ' → ' . $q->to_address,
                    $q->status,
                    number_format((float) $q->subtotal_net, 2, ',', ''),
                    number_format((float) $q->vat_amount, 2, ',', ''),
                    number_format((float) $q->total_gross, 2, ',', ''),
                    $q->currency,
                ], ';');
            }

            // === Faktury ===
            fputcsv($out, [], ';');
            fputcsv($out, ['=== FAKTURY ==='], ';');
            fputcsv($out, ['Numer', 'Typ', 'Wystawiona', 'Termin', 'Klient', 'NIP', 'Netto', 'VAT', 'Brutto', 'Waluta', 'KSeF status'], ';');
            foreach (Invoice::query()->whereBetween('issued_at', [$start->toDateString(), $end->toDateString()])->orderBy('id')->get() as $i) {
                fputcsv($out, [
                    $i->number,
                    $i->type ?? 'invoice',
                    $i->issued_at->format('Y-m-d'),
                    $i->payment_due_at?->format('Y-m-d') ?? '',
                    $i->client_company ?: $i->client_name,
                    $i->client_nip ?? '',
                    number_format((float) $i->subtotal_net, 2, ',', ''),
                    number_format((float) $i->vat_amount, 2, ',', ''),
                    number_format((float) $i->total_gross, 2, ',', ''),
                    $i->currency,
                    $i->ksef_status,
                ], ';');
            }

            // === Wpłaty ===
            fputcsv($out, [], ';');
            fputcsv($out, ['=== WPŁATY ==='], ';');
            fputcsv($out, ['Data', 'Oferta', 'Klient', 'Typ', 'Metoda', 'Referencja', 'Brutto', 'Waluta'], ';');
            foreach (Payment::query()->with('quote:id,number,client_name')->whereBetween('paid_at', [$start->toDateString(), $end->toDateString()])->orderBy('paid_at')->get() as $p) {
                fputcsv($out, [
                    $p->paid_at->format('Y-m-d'),
                    $p->quote?->number ?? '',
                    $p->quote?->client_name ?? '',
                    $p->payment_type,
                    $p->payment_method,
                    $p->reference ?? '',
                    number_format((float) $p->amount_gross, 2, ',', ''),
                    $p->currency,
                ], ';');
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public static function pdf(Carbon $start, Carbon $end)
    {
        $quotes   = Quote::query()->whereBetween('created_at', [$start, $end])->orderBy('id')->get();
        $invoices = Invoice::query()->whereBetween('issued_at', [$start->toDateString(), $end->toDateString()])->orderBy('id')->get();
        $payments = Payment::query()->with('quote:id,number,client_name')
            ->whereBetween('paid_at', [$start->toDateString(), $end->toDateString()])->orderBy('paid_at')->get();

        $summary = [
            'quotes_gross'  => (float) $quotes->sum('total_gross'),
            'invoices_gross'=> (float) $invoices->sum('total_gross'),
            'payments_gross'=> (float) $payments->sum('amount_gross'),
        ];

        $pdf = Pdf::loadView('reports.pdf', compact('start', 'end', 'quotes', 'invoices', 'payments', 'summary'))
            ->setPaper('a4', 'portrait')
            ->setOption('defaultFont', 'DejaVu Sans');

        return $pdf->download(sprintf('raport-%s.pdf', $start->format('Y-m')));
    }
}
