<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Quote;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class QuoteBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_records_correct_net_and_vat(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $this->actingAs($user);

        $quote = Quote::create($this->quoteAttrs($org->id));

        $payment = PaymentService::record($quote, [
            'amount_gross' => 1230.00,  // VAT 23% → netto 1000, VAT 230
            'paid_at'      => now()->toDateString(),
        ]);

        $this->assertEquals(1000.00, $payment->amount_net);
        $this->assertEquals(230.00, $payment->vat_amount);
    }

    public function test_quote_becomes_accepted_after_full_payment(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $this->actingAs($user);

        $quote = Quote::create(array_merge($this->quoteAttrs($org->id), [
            'total_gross' => 1230,
            'status' => 'sent',
        ]));

        PaymentService::record($quote, ['amount_gross' => 1230, 'paid_at' => now()->toDateString()]);

        $this->assertEquals('accepted', $quote->fresh()->status);
        $this->assertEquals(0, $quote->fresh()->balance());
    }

    private function quoteAttrs(int $orgId): array
    {
        return [
            'organization_id'   => $orgId,
            'number'            => 'OF/2026/05/0001',
            'year' => 2026, 'month' => 5, 'sequence' => 1,
            'client_name'       => 'Test',
            'from_address'      => 'A',
            'to_address'        => 'B',
            'fuel_consumption'  => 25, 'fuel_price' => 6.5, 'base_rate_per_km' => 4.5,
            'subtotal_net'      => 1000, 'vat_amount' => 230, 'total_gross' => 1230,
            'vat_percent'       => 23,
            'currency'          => 'PLN',
            'public_token'      => Str::random(40),
        ];
    }
}
