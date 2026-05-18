<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CalendarService;
use Illuminate\Http\Response;

class CalendarController extends Controller
{
    public function feed(string $token): Response
    {
        $user = User::withoutGlobalScopes()
            ->where('calendar_token', $token)
            ->whereNotNull('organization_id')
            ->firstOrFail();

        $ics = CalendarService::buildIcs($user->id, $user->organization_id);

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="galloptrans.ics"',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
