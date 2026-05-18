<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class QuotePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_can_only_see_assigned_quotes(): void
    {
        $org = Organization::factory()->create();
        $driver = User::factory()->create(['organization_id' => $org->id, 'role' => 'driver']);
        $owner  = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);

        $assigned = $this->makeQuote($org->id, ['driver_id' => $driver->id]);
        $other    = $this->makeQuote($org->id, ['driver_id' => $owner->id]);

        $this->actingAs($driver);

        $this->get(route('quotes.show', $assigned))->assertOk();
        $this->get(route('quotes.show', $other))->assertForbidden();
    }

    public function test_owner_can_view_all_quotes_in_org(): void
    {
        $org = Organization::factory()->create();
        $owner  = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $other  = User::factory()->create(['organization_id' => $org->id, 'role' => 'driver']);

        $quote = $this->makeQuote($org->id, ['driver_id' => $other->id]);

        $this->actingAs($owner)
            ->get(route('quotes.show', $quote))
            ->assertOk();
    }

    public function test_driver_cannot_delete_quote(): void
    {
        $org = Organization::factory()->create();
        $driver = User::factory()->create(['organization_id' => $org->id, 'role' => 'driver']);
        $quote = $this->makeQuote($org->id, ['driver_id' => $driver->id]);

        $this->actingAs($driver)
            ->delete(route('quotes.destroy', $quote))
            ->assertForbidden();
    }

    private function makeQuote(int $orgId, array $extra = []): Quote
    {
        return Quote::withoutGlobalScopes()->create(array_merge([
            'organization_id'   => $orgId,
            'number'            => 'OF/' . rand(1, 99999),
            'year' => 2026, 'month' => 5, 'sequence' => rand(1, 99999),
            'client_name'       => 'X', 'from_address' => 'A', 'to_address' => 'B',
            'fuel_consumption'  => 25, 'fuel_price' => 6.5, 'base_rate_per_km' => 4.5,
            'currency'          => 'PLN',
            'public_token'      => Str::random(40),
        ], $extra));
    }
}
