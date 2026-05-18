<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\QuoteSentMail;
use App\Models\Quote;
use App\Services\PdfService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class QuoteController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $query = Quote::query()->orderByDesc('id');

        // Driver widzi tylko swoje trasy.
        if ($request->user()->role === 'driver') {
            $query->where(function ($q) use ($request) {
                $q->where('driver_id', $request->user()->id)
                  ->orWhere('created_by', $request->user()->id);
            });
        }

        return view('quotes.index', ['quotes' => $query->paginate(20)]);
    }

    public function show(Quote $quote)
    {
        $this->authorize('view', $quote);
        $quote->load('items', 'payments');
        return view('quotes.show', compact('quote'));
    }

    public function destroy(Quote $quote)
    {
        $this->authorize('delete', $quote);
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

    /** Publiczna akceptacja oferty przez klienta. Zmienia status na 'accepted'. */
    public function publicAccept(Request $request, string $token)
    {
        $quote = Quote::withoutGlobalScopes()
            ->where('public_token', $token)
            ->firstOrFail();

        if ($quote->status === 'accepted') {
            return back()->with('warning', 'Oferta była już zaakceptowana.');
        }
        if (in_array($quote->status, ['rejected', 'expired', 'cancelled'], true)) {
            return back()->with('error', 'Oferta nie jest już aktywna.');
        }

        $quote->update([
            'status'      => 'accepted',
            'accepted_at' => now(),
        ]);

        \App\Services\NotificationService::notifyOrg(
            $quote->organization_id, ['owner', 'admin', 'operator'],
            'quote.accepted',
            "Klient zaakceptował ofertę {$quote->number}",
            sprintf('%s · %s %s brutto', $quote->client_name, number_format((float) $quote->total_gross, 2, ',', ' '), $quote->currency),
            route('quotes.show', $quote->id),
        );

        return back()->with('success', 'Dziękujemy! Oferta zaakceptowana. Skontaktujemy się w sprawie szczegółów transportu.');
    }
}
