<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Services\CalculatorService;
use App\Services\FuelPriceService;
use App\Services\NbpService;
use App\Services\OrsService;
use App\Services\QuoteService;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CalculatorController extends Controller
{
    public function index(Request $request)
    {
        $vehicles = Vehicle::query()
            ->where('is_active', true)
            ->where('is_trailer', false)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $defaultVehicle = $vehicles->firstWhere('is_default', true) ?? $vehicles->first();

        $latestDiesel = FuelPriceService::latest('diesel');
        $latestEur = NbpService::latestEur() ?? (float) SettingsService::get('eur_exchange_rate', 4.30);

        $defaults = [
            'base_rate_per_km'  => (float) SettingsService::get('base_rate_per_km', 4.50),
            'surcharge_percent' => (float) SettingsService::get('surcharge_percent', 15.0),
            'extra_horse_fee'   => (float) SettingsService::get('extra_horse_fee', 150.0),
            'fixed_fees'        => (float) SettingsService::get('fixed_fees', 0.0),
            'min_quote_amount'  => (float) SettingsService::get('min_quote_amount', 500.0),
            'stay_24h_cost'     => (float) SettingsService::get('stay_24h_cost', 200.0),
            'vat_percent'       => (float) SettingsService::get('vat_percent', 23.0),
            'round_trip'        => (bool)  SettingsService::get('round_trip_default', true),
            'currency'          => (string) SettingsService::get('default_currency', 'PLN'),
            'exchange_rate'     => $latestEur,
            'fuel_price'        => $latestDiesel['price_per_liter'] ?? 6.50,
            'fuel_consumption'  => $defaultVehicle?->fuel_consumption ?? 25.0,
            'horses_count'      => 1,
            'vehicle_id'        => $defaultVehicle?->id,
            'horse_capacity'    => $defaultVehicle?->horse_capacity ?? 4,
        ];

        $prefill = [
            'inquiry_id'     => $request->integer('inquiry_id') ?: null,
            'client_name'    => $request->string('client_name')->toString(),
            'client_email'   => $request->string('client_email')->toString(),
            'client_phone'   => $request->string('client_phone')->toString(),
            'from_address'   => $request->string('from_address')->toString(),
            'to_address'     => $request->string('to_address')->toString(),
            'transport_date' => $request->string('transport_date')->toString(),
            'horses_count'   => $request->integer('horses_count') ?: null,
            'notes'          => $request->string('notes')->toString(),
        ];

        return view('calculator.index', [
            'title'          => 'Kalkulator tras',
            'vehicles'       => $vehicles,
            'defaults'       => $defaults,
            'prefill'        => $prefill,
            'ors_configured' => OrsService::isConfigured(),
        ]);
    }

    public function geocode(Request $request): JsonResponse
    {
        try {
            $results = OrsService::geocode(
                (string) $request->input('q'),
                6,
                $request->string('country')->toString() ?: null,
            );
            return response()->json(['ok' => true, 'results' => $results]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 502);
        }
    }

    public function reverseGeocode(Request $request): JsonResponse
    {
        $lat = (float) $request->input('lat');
        $lng = (float) $request->input('lng');
        if ($lat === 0.0 && $lng === 0.0) {
            return response()->json(['ok' => false, 'error' => 'invalid coords'], 422);
        }
        try {
            return response()->json(['ok' => true, 'result' => OrsService::reverseGeocode($lat, $lng)]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 502);
        }
    }

    public function route(Request $request): JsonResponse
    {
        $body = $request->all();
        $points = $body['points'] ?? [['lat' => $body['from']['lat'] ?? null, 'lng' => $body['from']['lng'] ?? null], ['lat' => $body['to']['lat'] ?? null, 'lng' => $body['to']['lng'] ?? null]];
        $clean = [];
        foreach ($points as $i => $p) {
            if (! is_array($p) || empty($p['lat']) || empty($p['lng'])) {
                return response()->json(['ok' => false, 'error' => 'Punkt #' . ($i + 1) . ' nie ma współrzędnych.'], 422);
            }
            $clean[] = ['lat' => (float) $p['lat'], 'lng' => (float) $p['lng']];
        }
        if (count($clean) < 2) {
            return response()->json(['ok' => false, 'error' => 'Trasa wymaga co najmniej 2 punktów.'], 422);
        }

        $vehicle = $this->vehicleRestrictions((int) ($body['vehicle_id'] ?? 0));

        try {
            $r = OrsService::routeMulti($clean, $vehicle);
            return response()->json([
                'ok'           => true,
                'distance_km'  => $r['distance_km'],
                'duration_min' => $r['duration_min'],
                'geometry'     => $r['geometry'],
                'point_count'  => count($clean),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 502);
        }
    }

    public function estimateTolls(Request $request): JsonResponse
    {
        $body = $request->all();
        $points = $body['points'] ?? [];
        $clean = [];
        foreach ($points as $i => $p) {
            if (! is_array($p) || empty($p['lat']) || empty($p['lng'])) {
                return response()->json(['ok' => false, 'error' => 'Punkt #' . ($i + 1) . ' nie ma współrzędnych.'], 422);
            }
            $clean[] = ['lat' => (float) $p['lat'], 'lng' => (float) $p['lng']];
        }
        if (count($clean) < 2) {
            return response()->json(['ok' => false, 'error' => 'Trasa wymaga co najmniej 2 punktów.'], 422);
        }

        $vehicleId = (int) ($body['vehicle_id'] ?? 0);
        $vehicleKg = (float) SettingsService::get('ors_vehicle_weight', 7500);
        $vehicle = $this->vehicleRestrictions($vehicleId, $vehicleKg);

        $threshold = (float) SettingsService::get('toll_hgv_threshold_kg', 3500);
        $rateLight = (float) SettingsService::get('toll_rate_light', 0.20);
        $rateHgv   = (float) SettingsService::get('toll_rate_hgv', 0.55);
        $rate      = $vehicleKg > $threshold ? $rateHgv : $rateLight;
        $category  = $vehicleKg > $threshold ? sprintf('HGV (>%dkg)', (int) $threshold) : sprintf('lekki (≤%dkg)', (int) $threshold);
        $isHgv     = $vehicleKg > $threshold;

        try {
            $r = OrsService::routeMulti($clean, $vehicle, null, true);
            $tollKm = (float) ($r['tollways_km'] ?? 0);
            $highKm = (float) ($r['highway_km'] ?? 0);
            $oneKm  = $isHgv ? max($tollKm, $highKm) : $tollKm;
            $source = $isHgv ? ($highKm >= $tollKm ? 'autostrady (waycategory)' : 'tollways') : 'tollways';

            $tripMode = (string) ($body['trip_mode'] ?? 'one_way');
            $returnKm = 0.0;
            if ($tripMode === 'return_home' && count($clean) >= 2) {
                $returnPoints = [end($clean), reset($clean)];
                $r2 = OrsService::routeMulti($returnPoints, $vehicle, null, true);
                $rt2 = (float) ($r2['tollways_km'] ?? 0);
                $rh2 = (float) ($r2['highway_km'] ?? 0);
                $returnKm = $isHgv ? max($rt2, $rh2) : $rt2;
            }

            $totalTollKm = match ($tripMode) {
                'round_trip'  => $oneKm * 2,
                'return_home' => $oneKm + $returnKm,
                default       => $oneKm,
            };
            $tollCost = round($totalTollKm * $rate, 2);

            return response()->json([
                'ok'          => true,
                'tollways_km' => round($totalTollKm, 2),
                'source'      => $source,
                'rate_per_km' => $rate,
                'category'    => $category,
                'toll_cost'   => $tollCost,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 502);
        }
    }

    public function calculate(Request $request): JsonResponse
    {
        try {
            $result = CalculatorService::calculate($request->all());
            return response()->json(['ok' => true, 'result' => $result]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 400);
        }
    }

    public function fetchEurRate(): JsonResponse
    {
        try {
            return response()->json(['ok' => true, 'rate' => NbpService::fetchEur()]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 502);
        }
    }

    public function saveAsQuote(Request $request): JsonResponse
    {
        $body = $request->all();
        try {
            $result = CalculatorService::calculate($body);
            $payload = array_merge($body, [
                'subtotal_net' => $result['subtotal_net'],
                'vat_amount'   => $result['vat_amount'],
                'total_gross'  => $result['total_gross'],
                'items'        => $result['items'],
            ]);
            $quote = QuoteService::createDraft($payload);
            return response()->json([
                'ok'       => true,
                'quote_id' => $quote->id,
                'redirect' => route('quotes.show', $quote),
            ]);
        } catch (ValidationException $e) {
            return response()->json(['ok' => false, 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 400);
        }
    }

    /**
     * Wyciąga ograniczenia pojazdu (HGV restrictions) z DB + fallback do settings.
     *
     * @param int|null $vehicleKgOut przekazany przez referencję jako waga w kg dla logiki HGV/light
     * @return array{weight?:float,height?:float,length?:float,width?:float,axleload?:float}
     */
    private function vehicleRestrictions(int $vehicleId, ?float &$vehicleKgOut = null): array
    {
        $vehicle = [
            'weight' => (float) SettingsService::get('ors_vehicle_weight', 7500) / 1000,
            'height' => (float) SettingsService::get('ors_vehicle_height', 3.5),
        ];
        if ($vehicleId > 0) {
            $v = Vehicle::find($vehicleId);
            if ($v) {
                if ($v->max_weight_kg) {
                    $vehicle['weight'] = $v->max_weight_kg / 1000;
                    if ($vehicleKgOut !== null) {
                        $vehicleKgOut = (float) $v->max_weight_kg;
                    }
                }
                if ($v->height_m) $vehicle['height'] = (float) $v->height_m;
                if ($v->length_m) $vehicle['length'] = (float) $v->length_m;
                if ($v->width_m)  $vehicle['width']  = (float) $v->width_m;
                if ($v->axles)    $vehicle['axleload'] = ($v->max_weight_kg / 1000) / max(2, (int) $v->axles);
            }
        }
        return $vehicle;
    }
}
