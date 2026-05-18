<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        // Lista ostatnich 12 miesięcy z agregatami.
        $months = [];
        $now = now()->startOfMonth();
        for ($i = 0; $i < 12; $i++) {
            $start = (clone $now)->subMonths($i);
            $end = (clone $start)->endOfMonth();

            $months[] = [
                'year'   => (int) $start->format('Y'),
                'month'  => (int) $start->format('n'),
                'label'  => $start->translatedFormat('LLLL Y'),
                'quotes_count' => Quote::whereBetween('created_at', [$start, $end])->count(),
                'quotes_gross' => (float) Quote::whereBetween('created_at', [$start, $end])->sum('total_gross'),
                'paid_gross'   => (float) Payment::whereBetween('paid_at', [$start->toDateString(), $end->toDateString()])->sum('amount_gross'),
            ];
        }

        return view('reports.index', compact('months'));
    }

    public function month(int $year, int $month)
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $quotes = Quote::whereBetween('created_at', [$start, $end])->orderByDesc('id')->get();
        $payments = Payment::whereBetween('paid_at', [$start->toDateString(), $end->toDateString()])
            ->with('quote:id,number,client_name')
            ->orderByDesc('paid_at')->get();

        $summary = [
            'quotes_count'    => $quotes->count(),
            'quotes_net'      => (float) $quotes->sum('subtotal_net'),
            'quotes_vat'      => (float) $quotes->sum('vat_amount'),
            'quotes_gross'    => (float) $quotes->sum('total_gross'),
            'paid_gross'      => (float) $payments->sum('amount_gross'),
            'paid_net'        => (float) $payments->sum('amount_net'),
            'outstanding'     => (float) $quotes->sum('total_gross') - (float) $payments->sum('amount_gross'),
        ];

        return view('reports.month', compact('start', 'quotes', 'payments', 'summary'));
    }
}
