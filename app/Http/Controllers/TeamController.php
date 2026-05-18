<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\TeamInvitationMail;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $orgId = $request->user()->organization_id;

        $members = User::where('organization_id', $orgId)
            ->orderByDesc('role')
            ->orderBy('name')
            ->get();

        $pending = UserInvitation::whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('id')
            ->get();

        return view('team.index', compact('members', 'pending'));
    }

    public function invite(Request $request)
    {
        if (! $request->user()->canManage()) {
            abort(403, 'Tylko właściciel lub admin może zapraszać.');
        }

        $data = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'role'  => ['required', 'in:admin,operator,driver'],
        ]);

        // Jeśli user z tym emailem już jest w tej org - błąd
        if (User::where('organization_id', $request->user()->organization_id)
                ->where('email', $data['email'])->exists()) {
            return back()->with('error', 'Ten email już należy do twojej firmy.');
        }

        $invitation = UserInvitation::updateOrCreate(
            ['organization_id' => $request->user()->organization_id, 'email' => $data['email']],
            ['role' => $data['role'], 'invited_by' => $request->user()->id, 'accepted_at' => null,
             'token' => Str::random(64), 'expires_at' => now()->addDays(7)],
        );

        try {
            Mail::to($invitation->email)->send(new TeamInvitationMail($invitation));
        } catch (\Throwable $e) {
            // Mail może nie być skonfigurowany - link można skopiować z UI.
        }

        return back()->with('success', "Zaproszenie wysłane na {$data['email']}.");
    }

    public function revoke(UserInvitation $invitation)
    {
        $invitation->delete();
        return back()->with('success', 'Zaproszenie cofnięte.');
    }

    public function removeMember(User $user, Request $request)
    {
        if (! $request->user()->canManage()) {
            abort(403);
        }
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Nie można usunąć samego siebie.');
        }
        if ($user->role === 'owner') {
            return back()->with('error', 'Nie można usunąć właściciela.');
        }
        if ($user->organization_id !== $request->user()->organization_id) {
            abort(404);
        }

        $user->delete();
        return back()->with('success', "Użytkownik {$user->email} usunięty.");
    }

    /** Publiczna strona akceptacji zaproszenia. */
    public function showAccept(string $token)
    {
        $invitation = UserInvitation::withoutGlobalScopes()
            ->where('token', $token)
            ->firstOrFail();

        if ($invitation->isExpired() || $invitation->accepted_at) {
            return view('invitations.expired', compact('invitation'));
        }

        return view('invitations.accept', compact('invitation'));
    }

    public function processAccept(Request $request, string $token)
    {
        $invitation = UserInvitation::withoutGlobalScopes()
            ->where('token', $token)
            ->firstOrFail();

        if ($invitation->isExpired() || $invitation->accepted_at) {
            return view('invitations.expired', compact('invitation'));
        }

        $data = $request->validate([
            'name'                  => ['required', 'string', 'max:190'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Jeśli user już istnieje w naszym DB (np. założył konto w innej firmie wcześniej)
        // — nie wspieramy multi-org user'a na razie; wymagamy unikalnego emaila.
        if (User::where('email', $invitation->email)->exists()) {
            return back()->with('error', 'Konto z tym emailem już istnieje. Skontaktuj się z administratorem.');
        }

        $user = User::create([
            'organization_id' => $invitation->organization_id,
            'name'            => $data['name'],
            'email'           => $invitation->email,
            'password'        => Hash::make($data['password']),
            'role'            => $invitation->role,
            'email_verified_at' => now(),
        ]);

        $invitation->update(['accepted_at' => now()]);

        auth()->login($user);

        return redirect()->route('dashboard')->with('success', 'Witaj w GallopTrans!');
    }
}
