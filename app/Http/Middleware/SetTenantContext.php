<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ustawia `tenant.id` w kontenerze aplikacji na podstawie zalogowanego usera.
 * Globalny scope BelongsToOrganization + SettingsService czytają z tego klucza.
 */
final class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && $user->organization_id) {
            app()->instance('tenant.id', (int) $user->organization_id);
        }

        return $next($request);
    }
}
