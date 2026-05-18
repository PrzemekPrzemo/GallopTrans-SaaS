<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Jeżeli zalogowany user nie ma organization_id — odsyłamy go do onboardingu.
 */
final class EnsureOrganization
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && ! $user->organization_id && ! $user->is_super_admin) {
            if (! $request->is('onboarding*') && ! $request->is('logout')) {
                return redirect()->route('onboarding.create');
            }
        }

        return $next($request);
    }
}
