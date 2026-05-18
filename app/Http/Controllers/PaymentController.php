<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Quote;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function store(Request $request, Quote $quote)
    {
        $data = $request->validate([
            'amount_gross'   => ['required', 'numeric', 'min:0.01'],
            'payment_type'   => ['nullable', 'in:advance,final,full,other'],
            'payment_method' => ['nullable', 'in:transfer,cash,card,other'],
            'paid_at'        => ['required', 'date'],
            'reference'      => ['nullable', 'string', 'max:190'],
            'note'           => ['nullable', 'string', 'max:1000'],
        ]);

        PaymentService::record($quote, $data);

        return back()->with('success', 'Wpłata zarejestrowana.');
    }

    public function destroy(Payment $payment)
    {
        $payment->delete();
        return back()->with('success', 'Wpłata usunięta.');
    }
}
