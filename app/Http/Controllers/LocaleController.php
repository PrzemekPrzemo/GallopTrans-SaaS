<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function switch(Request $request, string $locale)
    {
        if (! in_array($locale, SetLocale::SUPPORTED, true)) {
            abort(404);
        }

        // Zapisz w preferencjach usera jeśli zalogowany.
        if ($user = $request->user()) {
            $user->forceFill(['preferred_locale' => $locale])->save();
        }

        // Cookie na rok — bez tego goście traciliby wybór na refresh.
        return back()->withCookie(cookie('locale', $locale, 60 * 24 * 365));
    }
}
