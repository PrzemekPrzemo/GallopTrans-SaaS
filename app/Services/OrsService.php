<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Klient Openrouteservice: geokodowanie (Pelias) + routing HGV.
 *
 * Klucz: per-tenant (settings: ors_api_key) z fallbackiem do ENV ORS_API_KEY.
 * Profil: per-tenant (settings: ors_profile) lub ENV ORS_PROFILE (default: driving-hgv).
 */
final class OrsService
{
    private const BASE = 'https://api.openrouteservice.org';

    public static function isConfigured(): bool
    {
        return self::resolveKey() !== '';
    }

    private static function resolveKey(): string
    {
        $k = (string) SettingsService::get('ors_api_key', '');
        if ($k !== '') {
            return $k;
        }
        return (string) config('services.ors.key', env('ORS_API_KEY', ''));
    }

    private static function key(): string
    {
        $k = self::resolveKey();
        if ($k === '') {
            throw new RuntimeException('Brak klucza ORS — wpisz w panelu ustawień lub w .env (ORS_API_KEY).');
        }
        return $k;
    }

    private static function profile(?string $override = null): string
    {
        return $override
            ?: (string) SettingsService::get('ors_profile', config('services.ors.profile', env('ORS_PROFILE', 'driving-hgv')));
    }

    /**
     * Autocomplete adresów (Pelias).
     *
     * @return array<int,array{label:string,lat:?float,lng:?float,country:?string,region:?string,locality:?string}>
     */
    public static function geocode(string $query, int $size = 5, ?string $country = null): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 3) {
            return [];
        }

        $params = [
            'api_key' => self::key(),
            'text'    => $query,
            'size'    => max(1, min(20, $size)),
            'lang'    => 'pl',
            'focus.point.lat' => 54.3520,
            'focus.point.lon' => 18.6466,
        ];
        if ($country) {
            $params['boundary.country'] = $country;
        }

        $resp = Http::timeout(20)->acceptJson()->get(self::BASE . '/geocode/search', $params);
        if (! $resp->ok()) {
            throw new RuntimeException("ORS HTTP {$resp->status()}: " . $resp->body());
        }
        $data = $resp->json();
        if (empty($data['features'])) {
            return [];
        }

        $out = [];
        foreach ($data['features'] as $f) {
            $coords = $f['geometry']['coordinates'] ?? [null, null];
            $p = $f['properties'] ?? [];
            $out[] = [
                'label'    => $p['label'] ?? '',
                'lat'      => isset($coords[1]) ? (float) $coords[1] : null,
                'lng'      => isset($coords[0]) ? (float) $coords[0] : null,
                'country'  => $p['country'] ?? null,
                'region'   => $p['region'] ?? null,
                'locality' => $p['locality'] ?? $p['name'] ?? null,
            ];
        }
        return $out;
    }

    public static function reverseGeocode(float $lat, float $lng): ?array
    {
        $params = [
            'api_key'   => self::key(),
            'point.lat' => $lat,
            'point.lon' => $lng,
            'size'      => 1,
            'lang'      => 'pl',
        ];
        $resp = Http::timeout(20)->acceptJson()->get(self::BASE . '/geocode/reverse', $params);
        if (! $resp->ok()) {
            throw new RuntimeException("ORS HTTP {$resp->status()}");
        }
        $data = $resp->json();
        if (empty($data['features'][0])) {
            return null;
        }
        $f = $data['features'][0];
        $coords = $f['geometry']['coordinates'] ?? [$lng, $lat];
        $p = $f['properties'] ?? [];
        return [
            'label'    => $p['label'] ?? sprintf('%.5f, %.5f', $lat, $lng),
            'lat'      => isset($coords[1]) ? (float) $coords[1] : $lat,
            'lng'      => isset($coords[0]) ? (float) $coords[0] : $lng,
            'country'  => $p['country'] ?? null,
            'locality' => $p['locality'] ?? $p['name'] ?? null,
        ];
    }

    /**
     * Routing przez N punktów (start + waypointy + meta).
     *
     * @param array<int,array{lat:float,lng:float}> $points
     * @param array{weight?:float,height?:float,length?:float,width?:float,axleload?:float} $vehicle
     * @return array<string,mixed>
     */
    public static function routeMulti(array $points, array $vehicle = [], ?string $profile = null, bool $withTollways = false): array
    {
        if (count($points) < 2) {
            throw new RuntimeException('Trasa wymaga co najmniej 2 punktów (start + meta).');
        }
        if (count($points) > 50) {
            throw new RuntimeException('Maksymalnie 50 punktów na trasę.');
        }

        $profile = self::profile($profile);

        $coords = [];
        foreach ($points as $i => $p) {
            if (! isset($p['lat'], $p['lng']) || ! is_numeric($p['lat']) || ! is_numeric($p['lng'])) {
                throw new RuntimeException('Punkt #' . ($i + 1) . ' ma niepoprawne współrzędne.');
            }
            $coords[] = [(float) $p['lng'], (float) $p['lat']];
        }

        $body = [
            'coordinates'  => $coords,
            'instructions' => false,
            'units'        => 'km',
        ];

        if ($withTollways) {
            $body['extra_info'] = ['tollways', 'waycategory'];
        }

        if (! empty($vehicle)) {
            $restrictions = array_filter([
                'weight'   => $vehicle['weight']   ?? null,
                'height'   => $vehicle['height']   ?? null,
                'length'   => $vehicle['length']   ?? null,
                'width'    => $vehicle['width']    ?? null,
                'axleload' => $vehicle['axleload'] ?? null,
            ], static fn ($v) => $v !== null);
            if ($restrictions) {
                $body['options'] = ['profile_params' => ['restrictions' => $restrictions]];
            }
        }

        $resp = Http::timeout(30)
            ->withHeaders([
                'Authorization' => self::key(),
                'Accept'        => 'application/json, application/geo+json',
            ])
            ->post(self::BASE . "/v2/directions/{$profile}/geojson", $body);

        if (! $resp->ok()) {
            $err = $resp->json('error.message') ?? $resp->body();
            if (is_array($err)) {
                $err = json_encode($err);
            }
            throw new RuntimeException("ORS HTTP {$resp->status()}: {$err}");
        }

        $data = $resp->json();
        if (empty($data['features'][0])) {
            throw new RuntimeException('ORS: brak trasy w odpowiedzi.');
        }

        $feature = $data['features'][0];
        $summary = $feature['properties']['summary'] ?? [];
        $routeKm = (float) ($summary['distance'] ?? 0);

        $tollwaysKm = 0.0;
        $highwayKm  = 0.0;
        $debug = ['route_km' => round($routeKm, 2)];

        if ($withTollways) {
            $extras = $feature['properties']['extras'] ?? [];

            $tollSummary = $extras['tollways']['summary'] ?? [];
            $tollSumDist = array_sum(array_column($tollSummary, 'distance'));
            $tollDivisor = ($tollSumDist > 5 * max($routeKm, 1)) ? 1000.0 : 1.0;
            foreach ($tollSummary as $row) {
                if ((int) ($row['value'] ?? 0) > 0) {
                    $tollwaysKm += (float) ($row['distance'] ?? 0) / $tollDivisor;
                }
            }

            $catSummary = $extras['waycategory']['summary'] ?? [];
            $catSumDist = array_sum(array_column($catSummary, 'distance'));
            $catDivisor = ($catSumDist > 5 * max($routeKm, 1)) ? 1000.0 : 1.0;
            foreach ($catSummary as $row) {
                $val = (int) ($row['value'] ?? 0);
                if (($val & 1) || ($val & 2)) {
                    $highwayKm += (float) ($row['distance'] ?? 0) / $catDivisor;
                }
            }
            $debug['tollways_raw']        = $tollSummary;
            $debug['tollways_divisor']    = $tollDivisor;
            $debug['waycategory_raw']     = $catSummary;
            $debug['waycategory_divisor'] = $catDivisor;
        }

        return [
            'distance_km'  => round($routeKm, 2),
            'duration_min' => (int) round((float) ($summary['duration'] ?? 0) / 60),
            'tollways_km'  => round($tollwaysKm, 2),
            'highway_km'   => round($highwayKm, 2),
            'tolls_debug'  => $debug,
            'geometry'     => $feature['geometry'] ?? null,
            'raw'          => $data,
        ];
    }
}
