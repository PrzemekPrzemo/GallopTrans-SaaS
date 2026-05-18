<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvoiceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_cannot_be_created_from_draft_quote(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $quote = $this->makeQuote($org->id, 'draft');

        $this->actingAs($user)
            ->post(route('invoices.from-quote', $quote))
            ->assertRedirect();

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_invoice_created_from_accepted_quote_snapshot_amounts(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $quote = $this->makeQuote($org->id, 'accepted');

        $this->actingAs($user)
            ->post(route('invoices.from-quote', $quote))
            ->assertRedirect();

        $invoice = Invoice::first();
        $this->assertNotNull($invoice);
        $this->assertEquals($quote->total_gross, $invoice->total_gross);
        $this->assertEquals($quote->client_name, $invoice->client_name);
        $this->assertEquals('manual', $invoice->ksef_status);  // org ma ksef_mode=disabled
        $this->assertStringStartsWith('FV/', $invoice->number);
    }

    public function test_invoice_publicly_accept_quote_then_issue(): void
    {
        $org = Organization::factory()->create();
        $quote = $this->makeQuote($org->id, 'sent');

        // Klient akceptuje przez publiczny URL.
        $this->post(route('quotes.public.accept', $quote->public_token))
            ->assertRedirect();

        $this->assertEquals('accepted', $quote->fresh()->status);
    }

    private function makeQuote(int $orgId, string $status): Quote
    {
        $quote = Quote::withoutGlobalScopes()->create([
            'organization_id' => $orgId,
            'number'          => 'OF/2026/05/0001',
            'year' => 2026, 'month' => 5, 'sequence' => 1,
            'client_name'     => 'Firma Klient sp. z o.o.',
            'client_nip'      => '1234567890',
            'from_address'    => 'Warszawa',
            'to_address'      => 'Berlin',
            'fuel_consumption'=> 25, 'fuel_price' => 6.5, 'base_rate_per_km' => 4.5,
            'subtotal_net'    => 1000, 'vat_amount' => 230, 'total_gross' => 1230,
            'vat_percent'     => 23, 'currency' => 'PLN',
            'public_token'    => Str::random(40),
            'status'          => $status,
        ]);
        QuoteItem::create([
            'quote_id' => $quote->id, 'description' => 'Transport', 'qty' => 1,
            'unit_price_net' => 1000, 'total_net' => 1000,
        ]);
        return $quote;
    }
}
