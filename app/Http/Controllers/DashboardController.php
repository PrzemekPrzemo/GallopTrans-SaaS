<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Inquiry;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Quote;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $monthStart = now()->startOfMonth();
        $today = now()->toDateString();

        // Zaległe faktury: termin minął, nie w pełni zapłacone.
        $overdueInvoices = Invoice::query()
            ->with('quote.payments')
            ->whereNotNull('payment_due_at')
            ->where('payment_due_at', '<', $today)
            ->get()
            ->filter(function (Invoice $inv) {
                if (! $inv->quote) {
                    return true;  // brak ofery → zakładamy nieopłaconą
                }
                $paid = (float) $inv->quote->payments->sum('amount_gross');
                return $paid < (float) $inv->total_gross - 0.01;
            });

        $stats = [
            'quotes_total'    => Quote::count(),
            'quotes_month'    => Quote::where('created_at', '>=', $monthStart)->count(),
            'inquiries_new'   => Inquiry::where('status', 'new')->count(),
            'revenue_month'   => (float) Payment::where('paid_at', '>=', $monthStart->toDateString())->sum('amount_gross'),
            'overdue_count'   => $overdueInvoices->count(),
            'overdue_amount'  => $overdueInvoices->sum(fn ($inv) => max(0, $inv->total_gross - ($inv->quote?->payments->sum('amount_gross') ?? 0))),
        ];

        $recentQuotes = Quote::orderByDesc('id')->limit(8)->get();
        $recentInquiries = Inquiry::orderByDesc('id')->limit(5)->get();

        return view('dashboard', compact('stats', 'recentQuotes', 'recentInquiries'));
    }
}
