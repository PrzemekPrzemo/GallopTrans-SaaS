<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Organization;
use App\Models\User;
use App\Services\QuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_quote_save_auto_creates_client(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $this->actingAs($user);

        QuoteService::createDraft([
            'client_name'  => 'Test Klient',
            'client_email' => 'klient@example.com',
            'client_nip'   => '1234567890',
            'from_address' => 'Warszawa',
            'to_address'   => 'Berlin',
            'fuel_consumption' => 25, 'fuel_price' => 6.5, 'base_rate_per_km' => 4.5,
            'distance_km'  => 100, 'trip_mode' => 'one_way',
            'subtotal_net' => 1000, 'vat_amount' => 230, 'total_gross' => 1230,
        ]);

        $this->assertDatabaseHas('clients', [
            'organization_id' => $org->id,
            'nip' => '1234567890',
            'name' => 'Test Klient',
        ]);
    }

    public function test_quote_with_existing_client_nip_reuses_record(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $this->actingAs($user);

        Client::create(['organization_id' => $org->id, 'name' => 'Stary', 'nip' => '9876543210']);

        QuoteService::createDraft([
            'client_name'  => 'Inny Email Klient',  // celowo różna nazwa
            'client_nip'   => '9876543210',          // ten sam NIP
            'from_address' => 'A', 'to_address' => 'B',
            'fuel_consumption' => 25, 'fuel_price' => 6.5, 'base_rate_per_km' => 4.5,
            'distance_km' => 50, 'trip_mode' => 'one_way',
            'subtotal_net' => 500, 'vat_amount' => 115, 'total_gross' => 615,
        ]);

        // Mimo różnej nazwy NIE tworzymy nowego klienta, bo NIP jest taki sam.
        $this->assertEquals(1, Client::count());
    }

    public function test_client_search_returns_only_own_organization(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $userA = User::factory()->create(['organization_id' => $orgA->id]);

        Client::create(['organization_id' => $orgA->id, 'name' => 'Kowalski A']);
        Client::create(['organization_id' => $orgB->id, 'name' => 'Kowalski B']);

        $this->actingAs($userA);
        $r = $this->getJson(route('clients.search', ['q' => 'Kowalski']))->json('results');

        $this->assertCount(1, $r);
        $this->assertEquals('Kowalski A', $r[0]['name']);
    }
}
