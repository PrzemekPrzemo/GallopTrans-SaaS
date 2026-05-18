<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\TeamInvitationMail;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TeamInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_invite_user_to_team(): void
    {
        Mail::fake();
        $org = Organization::factory()->create();
        $owner = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);

        $this->actingAs($owner)
            ->post(route('team.invite'), [
                'email' => 'new@example.com',
                'role'  => 'operator',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('user_invitations', [
            'organization_id' => $org->id,
            'email' => 'new@example.com',
            'role'  => 'operator',
        ]);
        Mail::assertSent(TeamInvitationMail::class);
    }

    public function test_operator_cannot_invite(): void
    {
        $org = Organization::factory()->create();
        $operator = User::factory()->create(['organization_id' => $org->id, 'role' => 'operator']);

        $this->actingAs($operator)
            ->post(route('team.invite'), ['email' => 'x@x.pl', 'role' => 'driver'])
            ->assertForbidden();
    }

    public function test_invitation_acceptance_creates_user_in_correct_org(): void
    {
        $org = Organization::factory()->create();
        $invitation = UserInvitation::withoutGlobalScopes()->create([
            'organization_id' => $org->id,
            'email' => 'invited@example.com',
            'role' => 'driver',
        ]);

        $this->post(route('invitations.process', $invitation->token), [
            'name' => 'Nowy Kierowca',
            'password' => 'secret-123',
            'password_confirmation' => 'secret-123',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'invited@example.com',
            'organization_id' => $org->id,
            'role' => 'driver',
        ]);
        $this->assertNotNull($invitation->fresh()->accepted_at);
    }

    public function test_expired_invitation_cannot_be_accepted(): void
    {
        $org = Organization::factory()->create();
        $invitation = UserInvitation::withoutGlobalScopes()->create([
            'organization_id' => $org->id,
            'email' => 'old@example.com',
            'role' => 'operator',
            'expires_at' => now()->subDay(),
        ]);

        $this->get(route('invitations.accept', $invitation->token))
            ->assertSee('Zaproszenie nieaktualne');
    }
}
