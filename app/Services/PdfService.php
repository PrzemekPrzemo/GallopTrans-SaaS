<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Generowanie i przechowywanie PDF ofert.
 * Każdy tenant ma własny katalog (storage/app/quotes/{org_id}/).
 */
final class PdfService
{
    public static function stream(Quote $quote): \Symfony\Component\HttpFoundation\Response
    {
        return self::makePdf($quote)->stream($quote->number . '.pdf');
    }

    public static function download(Quote $quote): \Symfony\Component\HttpFoundation\Response
    {
        return self::makePdf($quote)->download($quote->number . '.pdf');
    }

    /** Zapisuje PDF do storage i zwraca względną ścieżkę zapisaną w quotes.pdf_path. */
    public static function save(Quote $quote): string
    {
        $pdf = self::makePdf($quote);
        $relative = sprintf('quotes/%d/%s.pdf', $quote->organization_id, $quote->number);
        Storage::disk('local')->put($relative, $pdf->output());
        $quote->update(['pdf_path' => $relative]);
        return $relative;
    }

    /** Surowy bajtowy output PDF — używane np. jako attachment do maila. */
    public static function binary(Quote $quote): string
    {
        return self::makePdf($quote)->output();
    }

    private static function makePdf(Quote $quote)
    {
        $quote->loadMissing('items', 'organization');
        return Pdf::loadView('quotes.pdf', ['quote' => $quote])
            ->setPaper('a4', 'portrait')
            ->setOption('defaultFont', 'DejaVu Sans');
    }
}
