<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Wymaga aktywnej subskrypcji lub trwającego trialu na organizacji.
 * Inaczej redirect do /billing/plans.
 */
final class EnsureSubscribed
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $org = $user?->organization;

        if ($org && ! $org->onActivePlan()) {
            if (! $request->is('billing*')) {
                return redirect()->route('billing.plans')
                    ->with('warning', 'Aby korzystać z kalkulatora, wybierz plan subskrypcji.');
            }
        }

        return $next($request);
    }
}
