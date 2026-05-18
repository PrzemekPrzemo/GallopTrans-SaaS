<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Quote;
use Illuminate\Http\Request;

class DriverDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $upcoming = Quote::query()
            ->where(function ($q) use ($user) {
                $q->where('driver_id', $user->id)->orWhere('created_by', $user->id);
            })
            ->whereDate('transport_date', '>=', now()->toDateString())
            ->orderBy('transport_date')
            ->get();

        $past = Quote::query()
            ->where(function ($q) use ($user) {
                $q->where('driver_id', $user->id)->orWhere('created_by', $user->id);
            })
            ->whereDate('transport_date', '<', now()->toDateString())
            ->orderByDesc('transport_date')
            ->limit(20)
            ->get();

        return view('driver.dashboard', compact('upcoming', 'past', 'user'));
    }
}
