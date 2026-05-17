<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Inquiry;
use App\Models\Payment;
use App\Models\Quote;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $monthStart = now()->startOfMonth();

        $stats = [
            'quotes_total'    => Quote::count(),
            'quotes_month'    => Quote::where('created_at', '>=', $monthStart)->count(),
            'inquiries_new'   => Inquiry::where('status', 'new')->count(),
            'revenue_month'   => (float) Payment::where('paid_at', '>=', $monthStart->toDateString())->sum('amount_gross'),
        ];

        $recentQuotes = Quote::orderByDesc('id')->limit(8)->get();
        $recentInquiries = Inquiry::orderByDesc('id')->limit(5)->get();

        return view('dashboard', compact('stats', 'recentQuotes', 'recentInquiries'));
    }
}
