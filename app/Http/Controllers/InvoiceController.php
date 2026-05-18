<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Quote;
use App\Services\InvoiceService;
use App\Services\Ksef\KsefService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends Controller
{
    public function index()
    {
        return view('invoices.index', [
            'invoices' => Invoice::with('quote:id,number,client_name')
                ->orderByDesc('id')->paginate(25),
        ]);
    }

    public function show(Invoice $invoice)
    {
        $invoice->load('items', 'quote');
        return view('invoices.show', compact('invoice'));
    }

    /** Wystaw fakturę z oferty (musi być w statusie accepted). */
    public function storeFromQuote(Request $request, Quote $quote)
    {
        if ($quote->status !== 'accepted') {
            return back()->with('error', 'Faktury można wystawić tylko z zaakceptowanej oferty.');
        }

        if ($quote->invoice()->exists()) {
            return redirect()->route('invoices.show', $quote->invoice);
        }

        $invoice = InvoiceService::fromQuote($quote);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', "Faktura {$invoice->number} utworzona.");
    }

    /** Wysłanie wystawionej faktury do KSeF. */
    public function sendToKsef(Invoice $invoice)
    {
        try {
            $ok = KsefService::send($invoice);
            return back()->with($ok ? 'success' : 'error',
                $ok ? "Faktura wysłana do KSeF (status: {$invoice->fresh()->ksef_status})."
                    : "KSeF odrzucił fakturę: " . json_encode($invoice->fresh()->ksef_response));
        } catch (\Throwable $e) {
            return back()->with('error', 'Błąd KSeF: ' . $e->getMessage());
        }
    }

    public function correct(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'reason'       => ['required', 'string', 'max:255'],
            'subtotal_net' => ['nullable', 'numeric'],
            'vat_amount'   => ['nullable', 'numeric'],
            'total_gross'  => ['nullable', 'numeric'],
            'notes'        => ['nullable', 'string', 'max:2000'],
        ]);

        $correction = InvoiceService::correction($invoice, $data);

        return redirect()->route('invoices.show', $correction)
            ->with('success', "Faktura korygująca {$correction->number} wystawiona.");
    }
}
