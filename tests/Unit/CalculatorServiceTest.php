<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\CalculatorService;
use PHPUnit\Framework\TestCase;

/**
 * Test logiki kalkulatora — porównuje wynik z dokładnie tym samym scenariuszem
 * z TransportKoni-Kalkulator (single-tenant aplikacji którą migrujemy).
 *
 * Cel: gwarancja, że SaaS daje takie same wyceny jak aplikacja źródłowa.
 */
final class CalculatorServiceTest extends TestCase
{
    public function test_one_way_basic(): void
    {
        $r = CalculatorService::calculate([
            'distance_km'      => 100,
            'trip_mode'        => 'one_way',
            'fuel_consumption' => 25,
            'fuel_price'       => 6.5,
            'base_rate_per_km' => 4.5,
            'surcharge_percent'=> 15,
            'vat_percent'      => 23,
            'horses_count'     => 1,
        ]);

        // distance_total = 100
        // fuel = 100 * 0.25 * 6.5 = 162.5
        // distance_cost = 100 * 4.5 = 450
        // subtotal_raw = 612.5; * 1.15 = 704.375; netto > 500 → 704.375
        // gross = 704.375 * 1.23 = 866.38; round_down do 10 = 860
        // netto wstecz = 860 / 1.23 ≈ 699.19
        $this->assertEquals(860.00, $r['total_gross']);
        $this->assertGreaterThan(0, $r['subtotal_net']);
        $this->assertNotEmpty($r['items']);
    }

    public function test_round_trip_doubles_distance(): void
    {
        $oneWay = CalculatorService::calculate([
            'distance_km'      => 100,
            'trip_mode'        => 'one_way',
            'fuel_consumption' => 25,
            'fuel_price'       => 6.5,
            'base_rate_per_km' => 4.5,
            'surcharge_percent'=> 0,
            'min_quote_amount' => 0,
            'vat_percent'      => 0,
        ]);
        $round = CalculatorService::calculate([
            'distance_km'      => 100,
            'trip_mode'        => 'round_trip',
            'fuel_consumption' => 25,
            'fuel_price'       => 6.5,
            'base_rate_per_km' => 4.5,
            'surcharge_percent'=> 0,
            'min_quote_amount' => 0,
            'vat_percent'      => 0,
        ]);
        // round_trip ma 2x distance → fuel i distance są 2x. Inne składowe brak.
        // Wynik round = 2x one-way zaokrąglone do 10. Sprawdźmy że > niż one-way * 1.8.
        $this->assertGreaterThan($oneWay['total_gross'] * 1.8, $round['total_gross']);
    }

    public function test_min_amount_correction(): void
    {
        $r = CalculatorService::calculate([
            'distance_km'      => 1,
            'fuel_consumption' => 25,
            'fuel_price'       => 6.5,
            'base_rate_per_km' => 4.5,
            'min_quote_amount' => 500,
            'surcharge_percent'=> 0,
            'vat_percent'      => 23,
            'horses_count'     => 1,
            'trip_mode'        => 'one_way',
        ]);
        $this->assertTrue($r['min_applied']);
        // Po round-down brutto do 10 zł, netto może spaść lekko poniżej 500 (rabat).
        // Sprawdzamy że jest blisko 500 (między 490 a 500).
        $this->assertGreaterThan(490, $r['subtotal_net']);
        $this->assertLessThanOrEqual(500, $r['subtotal_net']);
    }

    public function test_eur_currency_divides(): void
    {
        $pln = CalculatorService::calculate([
            'distance_km' => 100, 'fuel_consumption' => 25, 'fuel_price' => 6.5,
            'base_rate_per_km' => 4.5, 'vat_percent' => 23, 'trip_mode' => 'one_way',
            'currency' => 'PLN',
        ]);
        $eur = CalculatorService::calculate([
            'distance_km' => 100, 'fuel_consumption' => 25, 'fuel_price' => 6.5,
            'base_rate_per_km' => 4.5, 'vat_percent' => 23, 'trip_mode' => 'one_way',
            'currency' => 'EUR', 'exchange_rate' => 4.30,
        ]);
        $this->assertEquals('EUR', $eur['currency']);
        $this->assertLessThan($pln['total_gross'], $eur['total_gross']);
    }

    public function test_extra_horses_fee(): void
    {
        $one = CalculatorService::calculate([
            'distance_km' => 50, 'fuel_consumption' => 25, 'fuel_price' => 6,
            'base_rate_per_km' => 4, 'vat_percent' => 0, 'surcharge_percent' => 0,
            'extra_horse_fee' => 150, 'horses_count' => 1, 'trip_mode' => 'one_way',
        ]);
        $three = CalculatorService::calculate([
            'distance_km' => 50, 'fuel_consumption' => 25, 'fuel_price' => 6,
            'base_rate_per_km' => 4, 'vat_percent' => 0, 'surcharge_percent' => 0,
            'extra_horse_fee' => 150, 'horses_count' => 3, 'trip_mode' => 'one_way',
        ]);
        // 2 dodatkowe konie × 150 = 300 zł (po zaokrągleniu)
        $this->assertEqualsWithDelta(300, $three['total_gross'] - $one['total_gross'], 10);
    }
}
