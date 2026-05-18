<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/**
 * Hierarchia rozstrzygania locale (od najwyższego):
 *   1. Cookie 'locale' (świadomy wybór użytkownika).
 *   2. user.preferred_locale (jeśli zalogowany).
 *   3. organization.default_locale.
 *   4. Accept-Language header (browser preference).
 *   5. Default 'pl'.
 */
class SetLocale
{
    public const SUPPORTED = ['pl', 'en', 'de'];

    public function handle(Request $request, Closure $next)
    {
        $locale = $this->resolve($request);
        App::setLocale($locale);
        // Carbon-aware formatowanie miesięcy/dni tygodnia w widokach (->translatedFormat()).
        Carbon::setLocale($locale);
        return $next($request);
    }

    private function resolve(Request $request): string
    {
        if ($cookie = $request->cookie('locale')) {
            if (in_array($cookie, self::SUPPORTED, true)) {
                return $cookie;
            }
        }

        if ($user = $request->user()) {
            if (in_array($user->preferred_locale ?? '', self::SUPPORTED, true)) {
                return $user->preferred_locale;
            }
            if ($org = $user->organization ?? null) {
                if (in_array($org->default_locale ?? '', self::SUPPORTED, true)) {
                    return $org->default_locale;
                }
            }
        }

        $preferred = $request->getPreferredLanguage(self::SUPPORTED);
        return $preferred ?: 'pl';
    }
}
