<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Czysta logika wyceny transportu koni - bez I/O, łatwa do testowania.
 *
 * Model:
 *   distance_total = wg trip_mode (one_way | round_trip | return_home)
 *   fuel_cost      = distance_total * (consumption / 100) * fuel_price
 *   distance_cost  = distance_total * base_rate_per_km
 *   extra_horses   = max(0, horses_count - 1) * extra_horse_fee
 *   subtotal_raw   = fuel + distance + horses + difficult + fixed + tolls + stay
 *   subtotal_marg  = subtotal_raw * (1 + surcharge_percent/100)
 *   subtotal_net   = max(subtotal_marg, min_quote_amount)
 *   total_gross    = subtotal_net * (1 + vat_percent/100)
 *   → zaokrąglenie BRUTTO w dół do pełnych 10 (jednostek waluty)
 *
 * Waluta: wszystkie wartości wejściowe w PLN. Jeśli currency=EUR → wynik / exchange_rate.
 */
final class CalculatorService
{
    /**
     * @param array<string,mixed> $in
     * @return array<string,mixed>
     */
    public static function calculate(array $in): array
    {
        $distanceKm    = max(0.0, (float) ($in['distance_km'] ?? 0));
        $consumption   = max(0.0, (float) ($in['fuel_consumption'] ?? 25.0));
        $fuelPrice     = max(0.0, (float) ($in['fuel_price'] ?? 0));
        $baseRate      = max(0.0, (float) ($in['base_rate_per_km'] ?? 0));
        $surcharge     = max(0.0, (float) ($in['surcharge_percent'] ?? 0));
        $extraHorseFee = max(0.0, (float) ($in['extra_horse_fee'] ?? 0));
        $difficultFee  = max(0.0, (float) ($in['difficult_horse_fee'] ?? 0));
        $fixedFees     = max(0.0, (float) ($in['fixed_fees'] ?? 0));
        $tollCost      = max(0.0, (float) ($in['toll_cost'] ?? 0));
        $minAmount     = max(0.0, (float) ($in['min_quote_amount'] ?? 0));
        $stayDays      = max(0,   (int)   ($in['stay_days'] ?? 0));
        $stay24hCost   = max(0.0, (float) ($in['stay_24h_cost'] ?? 0));
        $vatPercent    = max(0.0, (float) ($in['vat_percent'] ?? 23));
        $horses        = max(0, (int) ($in['horses_count'] ?? 1));

        $tripMode = $in['trip_mode'] ?? null;
        if (! $tripMode) {
            $tripMode = ! empty($in['round_trip']) ? 'round_trip' : 'one_way';
        }
        $returnKm  = max(0.0, (float) ($in['return_distance_km'] ?? 0));
        $roundTrip = $tripMode !== 'one_way';

        $currency     = strtoupper((string) ($in['currency'] ?? 'PLN'));
        $exchangeRate = max(0.0001, (float) ($in['exchange_rate'] ?? 1.0));

        $distanceTotal = match ($tripMode) {
            'one_way'     => $distanceKm,
            'round_trip'  => $distanceKm * 2,
            'return_home' => $distanceKm + $returnKm,
            default       => $distanceKm * 2,
        };

        $fuelCost     = $distanceTotal * ($consumption / 100) * $fuelPrice;
        $distanceCost = $distanceTotal * $baseRate;
        $extraHorses  = max(0, $horses - 1) * $extraHorseFee;
        $stayCost     = ($roundTrip && $stayDays > 0) ? $stayDays * $stay24hCost : 0;
        $subtotalRaw  = $fuelCost + $distanceCost + $extraHorses + $difficultFee + $fixedFees + $tollCost + $stayCost;
        $subtotalMarg = $subtotalRaw * (1 + $surcharge / 100);
        $appliedMin   = $subtotalMarg < $minAmount;
        $subtotalNet  = max($subtotalMarg, $minAmount);
        $minCorrection = $subtotalNet - $subtotalMarg;
        $vatAmount    = $subtotalNet * $vatPercent / 100;
        $totalGross   = $subtotalNet + $vatAmount;

        // Zaokrąglenie końcowej kwoty BRUTTO w DÓŁ do pełnych 10 jednostek waluty.
        $netBeforeRound = $subtotalNet;
        $rounded = floor($totalGross / 10) * 10;
        if ($rounded > 0) {
            $totalGross  = $rounded;
            $subtotalNet = $totalGross / (1 + $vatPercent / 100);
            $vatAmount   = $totalGross - $subtotalNet;
        }
        $roundingDelta = $subtotalNet - $netBeforeRound;

        $modeDesc = match ($tripMode) {
            'one_way'     => 'jednorazowo',
            'return_home' => sprintf('w obie strony: %s km tam + %s km powrót bezpośredni', self::fmt($distanceKm, 2), self::fmt($returnKm, 2)),
            default       => 'w obie strony',
        };

        $items = [
            [
                'item_type'      => 'distance',
                'description'    => sprintf('Stawka km × %s km (%s)', self::fmt($distanceTotal, 2), $modeDesc),
                'qty'            => round($distanceTotal, 2),
                'unit'           => 'km',
                'unit_price_net' => round($baseRate, 2),
                'total_net'      => round($distanceCost, 2),
            ],
            [
                'item_type'      => 'fuel',
                'description'    => sprintf(
                    'Paliwo: %s km × %s l/100 × %s zł/l',
                    self::fmt($distanceTotal, 2),
                    self::fmt($consumption, 2),
                    self::fmt($fuelPrice, 3)
                ),
                'qty'            => 1,
                'unit'           => null,
                'unit_price_net' => round($fuelCost, 2),
                'total_net'      => round($fuelCost, 2),
            ],
        ];

        if ($extraHorses > 0) {
            $items[] = [
                'item_type'      => 'horse',
                'description'    => sprintf('Dopłata za %d dodatkowego/ych konia/i', max(0, $horses - 1)),
                'qty'            => max(0, $horses - 1),
                'unit'           => 'kon',
                'unit_price_net' => round($extraHorseFee, 2),
                'total_net'      => round($extraHorses, 2),
            ];
        }
        if ($difficultFee > 0) {
            $items[] = [
                'item_type'      => 'horse',
                'description'    => 'Dopłata za trudnego konia',
                'qty'            => 1,
                'unit'           => null,
                'unit_price_net' => round($difficultFee, 2),
                'total_net'      => round($difficultFee, 2),
            ];
        }
        if ($tollCost > 0) {
            $items[] = [
                'item_type'      => 'fixed',
                'description'    => 'Opłaty drogowe (autostrady / prom / e-Toll)',
                'qty'            => 1,
                'unit'           => null,
                'unit_price_net' => round($tollCost, 2),
                'total_net'      => round($tollCost, 2),
            ];
        }
        if ($fixedFees > 0) {
            $items[] = [
                'item_type'      => 'fixed',
                'description'    => 'Inne opłaty stałe',
                'qty'            => 1,
                'unit'           => null,
                'unit_price_net' => round($fixedFees, 2),
                'total_net'      => round($fixedFees, 2),
            ];
        }
        if ($stayCost > 0) {
            $items[] = [
                'item_type'      => 'fixed',
                'description'    => sprintf('Postój u celu: %d dni × %s zł/24h', $stayDays, self::fmt($stay24hCost, 2)),
                'qty'            => $stayDays,
                'unit'           => 'dni',
                'unit_price_net' => round($stay24hCost, 2),
                'total_net'      => round($stayCost, 2),
            ];
        }
        if ($surcharge > 0) {
            $items[] = [
                'item_type'      => 'surcharge',
                'description'    => sprintf('Narzut %s%% (marża)', self::fmt($surcharge, 2)),
                'qty'            => 1,
                'unit'           => null,
                'unit_price_net' => round($subtotalMarg - $subtotalRaw, 2),
                'total_net'      => round($subtotalMarg - $subtotalRaw, 2),
            ];
        }
        if ($appliedMin && $minCorrection > 0.01) {
            $items[] = [
                'item_type'      => 'custom',
                'description'    => 'Korekta do kwoty minimalnej',
                'qty'            => 1,
                'unit'           => null,
                'unit_price_net' => round($minCorrection, 2),
                'total_net'      => round($minCorrection, 2),
            ];
        }
        if (abs($roundingDelta) > 0.01) {
            $items[] = [
                'item_type'      => 'custom',
                'description'    => 'Rabat',
                'qty'            => 1,
                'unit'           => null,
                'unit_price_net' => round($roundingDelta, 2),
                'total_net'      => round($roundingDelta, 2),
            ];
        }

        $result = [
            'currency'       => $currency,
            'exchange_rate'  => $exchangeRate,
            'distance_total' => round($distanceTotal, 2),
            'subtotal_net'   => round($subtotalNet, 2),
            'vat_amount'     => round($vatAmount, 2),
            'total_gross'    => round($totalGross, 2),
            'min_applied'    => $appliedMin,
            'items'          => $items,
        ];

        if ($currency === 'EUR') {
            $result['subtotal_net'] = round($subtotalNet / $exchangeRate, 2);
            $result['vat_amount']   = round($vatAmount   / $exchangeRate, 2);
            $result['total_gross']  = round($totalGross  / $exchangeRate, 2);
            foreach ($result['items'] as &$it) {
                $it['unit_price_net'] = round($it['unit_price_net'] / $exchangeRate, 2);
                $it['total_net']      = round($it['total_net']      / $exchangeRate, 2);
            }
            unset($it);
        }

        return $result;
    }

    private static function fmt(float $v, int $decimals): string
    {
        return number_format($v, $decimals, ',', ' ');
    }
}
