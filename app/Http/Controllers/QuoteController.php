<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Quote;
use Illuminate\Http\Request;

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
}
