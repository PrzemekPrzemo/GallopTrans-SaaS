<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FuelPrice;
use App\Models\Organization;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pobieranie aktualnych cen paliw z publicznego źródła (default: e-petrol.pl).
 *
 * Strategia odporności:
 *   - prosty HTTP GET z user-agentem przeglądarki,
 *   - regex na "Diesel" + cena (X.XX zł/l) — odporny na drobne zmiany layoutu,
 *   - jeśli scrap się nie powiódł — command kończy sukcesem ale loguje warning
 *     (manualne wprowadzanie cen wciąż działa).
 *
 * Ceny są dzielone pomiędzy wszystkie tenanty (rynkowa średnia PL jest publiczna).
 */
final class FuelPriceScraper
{
    /** @return array{diesel:?float,petrol:?float,lpg:?float,source:string} */
    public static function fetchFromEpetrol(): array
    {
        $result = ['diesel' => null, 'petrol' => null, 'lpg' => null, 'source' => 'epetrol'];

        try {
            $resp = Http::timeout(20)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; GallopTransBot/1.0; +https://galloptrans.app)',
                    'Accept' => 'text/html,application/xhtml+xml',
                    'Accept-Language' => 'pl,en;q=0.5',
                ])
                ->get('https://e-petrol.pl/');

            if (! $resp->ok()) {
                Log::warning('FuelPriceScraper: e-petrol HTTP ' . $resp->status());
                return $result;
            }

            $html = $resp->body();

            // Wzór: "ON" lub "Diesel" potem cena. Działa nawet jeśli zmienią klasy CSS.
            foreach (['diesel' => ['ON', 'Diesel', 'olej napędowy'], 'petrol' => ['Pb 95', 'Pb95', 'benzyna'], 'lpg' => ['LPG', 'autogaz']] as $fuel => $needles) {
                foreach ($needles as $needle) {
                    if (preg_match('/' . preg_quote($needle, '/') . '.{0,200}?(\d[,.]\d{2,3})\s*z[łl]?\s*\/\s*l/iu', $html, $m)) {
                        $price = (float) str_replace(',', '.', $m[1]);
                        if ($price > 0 && $price < 20) {  // sanity check
                            $result[$fuel] = $price;
                            break;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('FuelPriceScraper exception: ' . $e->getMessage());
        }

        return $result;
    }

    /** Zapisuje pobrane ceny do fuel_prices dla wszystkich organizacji (które mają funkcję włączoną). */
    public static function storeForAllOrganizations(array $prices): int
    {
        $count = 0;
        $today = now()->toDateString();

        foreach (Organization::all() as $org) {
            foreach (['diesel', 'petrol', 'lpg'] as $type) {
                if (empty($prices[$type])) {
                    continue;
                }
                FuelPrice::withoutGlobalScopes()->updateOrCreate(
                    [
                        'organization_id' => $org->id,
                        'fuel_type'       => $type,
                        'valid_for_date'  => $today,
                        'source'          => $prices['source'] ?? 'scraper',
                    ],
                    [
                        'price_per_liter' => $prices[$type],
                        'currency'        => 'PLN',
                        'fetched_at'      => now(),
                    ],
                );
                $count++;
            }
        }
        return $count;
    }
}
