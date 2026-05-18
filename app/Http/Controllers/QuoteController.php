<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\QuoteSentMail;
use App\Models\Quote;
use App\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class QuoteController extends Controller
{
    public function index(Request $request)
    {
        $quotes = Quote::orderByDesc('id')->paginate(20);
        return view('quotes.index', compact('quotes'));
    }

    public function show(Quote $quote)
    {
        $quote->load('items', 'payments');
        return view('quotes.show', compact('quote'));
    }

    public function destroy(Quote $quote)
    {
        $quote->delete();
        return redirect()->route('quotes.index')->with('success', 'Oferta usunięta.');
    }

    /** Publiczna strona oferty (token w URL) - bez logowania. */
    public function public(string $token)
    {
        $quote = Quote::withoutGlobalScopes()
            ->where('public_token', $token)
            ->firstOrFail();
        $quote->load('items', 'organization');
        return view('quotes.public', compact('quote'));
    }

    public function pdf(Quote $quote)
    {
        $quote->load('items', 'organization');
        return PdfService::stream($quote);
    }

    public function send(Request $request, Quote $quote)
    {
        $data = $request->validate([
            'to'      => ['required', 'email'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        Mail::to($data['to'])->send(new QuoteSentMail($quote, $data['message'] ?? null));

        $quote->update([
            'status' => $quote->status === 'draft' ? 'sent' : $quote->status,
            'sent_at' => now(),
        ]);

        return back()->with('success', "Oferta wysłana do {$data['to']}.");
    }

    public function publicPdf(string $token)
    {
        $quote = Quote::withoutGlobalScopes()
            ->where('public_token', $token)
            ->firstOrFail();
        $quote->load('items', 'organization');
        return PdfService::download($quote);
    }
}
