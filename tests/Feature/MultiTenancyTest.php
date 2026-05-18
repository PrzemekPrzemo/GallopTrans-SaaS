<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Quote;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MultiTenancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_only_sees_own_organization_data(): void
    {
        $orgA = Organization::factory()->create(['name' => 'Firma A']);
        $orgB = Organization::factory()->create(['name' => 'Firma B']);

        $userA = User::factory()->create(['organization_id' => $orgA->id, 'role' => 'owner']);
        $userB = User::factory()->create(['organization_id' => $orgB->id, 'role' => 'owner']);

        Vehicle::create(['organization_id' => $orgA->id, 'name' => 'Vehicle A', 'fuel_consumption' => 25, 'horse_capacity' => 4]);
        Vehicle::create(['organization_id' => $orgB->id, 'name' => 'Vehicle B', 'fuel_consumption' => 25, 'horse_capacity' => 4]);

        // Po zalogowaniu jako userA – widzimy tylko Vehicle A.
        $this->actingAs($userA);
        $visible = Vehicle::pluck('name');
        $this->assertCount(1, $visible);
        $this->assertEquals('Vehicle A', $visible[0]);

        $this->actingAs($userB);
        $visible = Vehicle::pluck('name');
        $this->assertCount(1, $visible);
        $this->assertEquals('Vehicle B', $visible[0]);
    }

    public function test_quote_creation_auto_fills_organization_id(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $this->actingAs($user);

        $quote = Quote::create([
            'number'              => 'OF/2026/05/0001',
            'year'                => 2026, 'month' => 5, 'sequence' => 1,
            'client_name'         => 'Klient',
            'from_address'        => 'Warszawa',
            'to_address'          => 'Berlin',
            'fuel_consumption'    => 25,
            'fuel_price'          => 6.5,
            'base_rate_per_km'    => 4.5,
            'public_token'        => Str::random(40),
        ]);

        $this->assertEquals($org->id, $quote->organization_id);
    }

    public function test_dashboard_redirects_user_without_organization_to_onboarding(): void
    {
        $user = User::factory()->withoutOrganization()->create();
        $this->actingAs($user);

        $this->get('/dashboard')->assertRedirect(route('onboarding.create', absolute: false));
    }
}
