<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function plans(Request $request)
    {
        $plans = [
            'starter'  => [
                'name'        => 'Starter',
                'price'       => '99 zł / mies.',
                'price_id'    => config('services.stripe.prices.starter'),
                'features'    => [
                    '50 wycen / miesiąc',
                    '1 użytkownik',
                    'Eksport PDF',
                    'Email wsparcia',
                ],
            ],
            'pro' => [
                'name'        => 'Pro',
                'price'       => '249 zł / mies.',
                'price_id'    => config('services.stripe.prices.pro'),
                'features'    => [
                    'Nieograniczone wyceny',
                    'Do 5 użytkowników',
                    'Publiczne zapytania ofertowe',
                    'Widget na WWW + iCal kierowcy',
                    'Wsparcie priorytetowe',
                ],
                'featured'    => true,
            ],
            'business' => [
                'name'        => 'Business',
                'price'       => '599 zł / mies.',
                'price_id'    => config('services.stripe.prices.business'),
                'features'    => [
                    'Wszystko z Pro',
                    'Nieograniczeni użytkownicy',
                    'Multi-firma',
                    'Custom branding',
                    'Dedykowany opiekun',
                ],
            ],
        ];

        return view('billing.plans', [
            'plans' => $plans,
            'organization' => $request->user()->organization,
        ]);
    }

    public function checkout(Request $request, string $plan)
    {
        $priceId = config('services.stripe.prices.' . $plan);
        abort_if(! $priceId, 404, 'Nieznany plan.');

        $org = $request->user()->organization;
        abort_if(! $org, 403);

        return $org
            ->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => route('billing.success'),
                'cancel_url'  => route('billing.plans'),
            ]);
    }

    public function success(Request $request)
    {
        return redirect()->route('dashboard')->with('success', 'Subskrypcja aktywowana — dziękujemy!');
    }

    public function portal(Request $request)
    {
        $org = $request->user()->organization;
        abort_if(! $org || ! $org->hasStripeId(), 404);

        return $org->redirectToBillingPortal(route('dashboard'));
    }
}
