<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicInquiryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_inquiry_creates_record_for_correct_tenant(): void
    {
        $orgA = Organization::factory()->create(['name' => 'Firma A']);
        $orgB = Organization::factory()->create(['name' => 'Firma B']);

        $response = $this->postJson("/api/o/{$orgA->slug}/inquiry", [
            'client_name'  => 'Jan Kowalski',
            'client_email' => 'jan@example.com',
            'from_address' => 'Warszawa',
            'to_address'   => 'Kraków',
            'horses_count' => 2,
        ]);

        $response->assertOk()->assertJson(['ok' => true]);

        $inq = Inquiry::withoutGlobalScopes()->first();
        $this->assertEquals($orgA->id, $inq->organization_id);
        $this->assertEquals(0, Inquiry::withoutGlobalScopes()->where('organization_id', $orgB->id)->count());
    }

    public function test_inquiry_returns_404_for_unknown_tenant(): void
    {
        $this->postJson('/api/o/no-such-firma/inquiry', [
            'client_name' => 'X', 'client_email' => 'x@x.pl',
            'from_address' => 'A', 'to_address' => 'B',
        ])->assertNotFound();
    }

    public function test_inquiry_validates_required_fields(): void
    {
        $org = Organization::factory()->create();
        $this->postJson("/api/o/{$org->slug}/inquiry", [])
            ->assertStatus(422);
    }

    public function test_inquiry_honeypot_drops_silently(): void
    {
        $org = Organization::factory()->create();
        $this->postJson("/api/o/{$org->slug}/inquiry", [
            'hp_field'    => 'bot was here',
            'client_name' => 'Bot',
            // brakuje walidacji - ale honeypot zwraca ok bez tworzenia rekordu
        ])->assertOk();

        $this->assertEquals(0, Inquiry::withoutGlobalScopes()->count());
    }
}
