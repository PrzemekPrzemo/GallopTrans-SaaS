<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Panel super-admina SaaS-a (dla właściciela GallopTrans).
 * Pomija OrganizationScope - widzi wszystko cross-tenant.
 */
class AdminController extends Controller
{
    public function dashboard()
    {
        $now = now();
        $monthStart = $now->copy()->startOfMonth();

        $orgs = Organization::query()->withoutGlobalScopes()->get();
        $usersCount = User::query()->withoutGlobalScopes()->count();
        $quotesMonth = Quote::query()->withoutGlobalScopes()
            ->where('created_at', '>=', $monthStart)->count();

        $trial = $orgs->filter(fn ($o) => $o->trial_ends_at && $o->trial_ends_at->isFuture());
        $paying = $orgs->filter(fn ($o) => $o->subscribed('default'));
        $cancelled = $orgs->filter(fn ($o) => $o->subscriptions()->whereNotNull('ends_at')->where('ends_at', '<', $now)->exists());

        $mrr = $this->estimateMrr($orgs);

        return view('admin.dashboard', [
            'orgs_total'    => $orgs->count(),
            'orgs_trial'    => $trial->count(),
            'orgs_paying'   => $paying->count(),
            'orgs_cancelled'=> $cancelled->count(),
            'users_total'   => $usersCount,
            'quotes_month'  => $quotesMonth,
            'mrr_pln'       => $mrr,
            'recent'        => $orgs->sortByDesc('created_at')->take(10)->values(),
        ]);
    }

    public function organizations()
    {
        $orgs = Organization::query()->withoutGlobalScopes()
            ->withCount(['users', 'quotes'])
            ->orderByDesc('id')->paginate(50);

        return view('admin.organizations', compact('orgs'));
    }

    public function showOrganization(int $id)
    {
        $org = Organization::query()->withoutGlobalScopes()
            ->with(['users', 'subscriptions'])
            ->findOrFail($id);
        $quotesCount = Quote::query()->withoutGlobalScopes()
            ->where('organization_id', $id)->count();

        return view('admin.organization', compact('org', 'quotesCount'));
    }

    /** Sumuje znane ceny planów ze STRIPE_PRICE_* — uproszczone, w prawdziwym SaaS-ie czytaj z Stripe API. */
    private function estimateMrr($orgs): float
    {
        $prices = [
            config('services.stripe.prices.starter')  => 99,
            config('services.stripe.prices.pro')      => 249,
            config('services.stripe.prices.business') => 599,
        ];

        $mrr = 0.0;
        foreach ($orgs as $org) {
            $subscription = $org->subscriptions()->where('stripe_status', 'active')->first();
            if ($subscription && isset($prices[$subscription->stripe_price])) {
                $mrr += $prices[$subscription->stripe_price] * (int) ($subscription->quantity ?? 1);
            }
        }
        return $mrr;
    }
}
