<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdvanceInvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_advance_invoice_has_correct_amount_and_subtype(): void
    {
        $quote = $this->makeAcceptedQuote(1230); // brutto 1230, VAT 23%

        $advance = InvoiceService::advance($quote, 500);

        $this->assertEquals('advance', $advance->invoice_subtype);
        $this->assertEquals(500.00, $advance->total_gross);
        $this->assertEqualsWithDelta(406.50, $advance->subtotal_net, 0.01);
        $this->assertEqualsWithDelta(93.50, $advance->vat_amount, 0.01);
    }

    public function test_advance_cannot_exceed_quote_gross(): void
    {
        $quote = $this->makeAcceptedQuote(1230);

        $this->expectExceptionMessage('Kwota zaliczki');
        InvoiceService::advance($quote, 1500);
    }

    public function test_final_settles_advances_and_pays_remainder(): void
    {
        $quote = $this->makeAcceptedQuote(1230);

        InvoiceService::advance($quote, 500);
        InvoiceService::advance($quote, 300);

        $final = InvoiceService::finalSettlement($quote);

        $this->assertEquals('final', $final->invoice_subtype);
        $this->assertEquals(430.00, $final->total_gross);  // 1230 - 800
        $this->assertEquals(800.00, $final->settled_from_advances);
        $this->assertCount(2, $final->advance_invoice_ids);
    }

    public function test_final_throws_when_no_advances(): void
    {
        $quote = $this->makeAcceptedQuote(1230);
        $this->expectExceptionMessage('Brak faktur zaliczkowych');
        InvoiceService::finalSettlement($quote);
    }

    public function test_advance_requires_accepted_quote(): void
    {
        $quote = $this->makeAcceptedQuote(1230);
        $quote->update(['status' => 'sent']);
        $this->expectExceptionMessage('zaakceptowanej');
        InvoiceService::advance($quote->fresh(), 500);
    }

    private function makeAcceptedQuote(float $gross): Quote
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $this->actingAs($user);

        $vatRate = 23;
        $net = round($gross / (1 + $vatRate / 100), 2);

        $quote = Quote::create([
            'organization_id' => $org->id,
            'number'      => 'OF/2026/05/0001', 'year' => 2026, 'month' => 5, 'sequence' => 1,
            'client_name' => 'X', 'from_address' => 'A', 'to_address' => 'B',
            'fuel_consumption' => 25, 'fuel_price' => 6.5, 'base_rate_per_km' => 4.5,
            'subtotal_net' => $net, 'vat_amount' => $gross - $net, 'total_gross' => $gross,
            'vat_percent' => $vatRate, 'currency' => 'PLN',
            'public_token' => Str::random(40),
            'status' => 'accepted',
        ]);
        QuoteItem::create(['quote_id' => $quote->id, 'description' => 'X', 'qty' => 1, 'unit_price_net' => $net, 'total_net' => $net]);
        return $quote;
    }
}
