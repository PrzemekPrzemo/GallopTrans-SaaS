<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Client;
use App\Models\Quote;
use App\Models\QuoteItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Tworzenie ofert z wyniku kalkulatora.
 */
final class QuoteService
{
    /** @param array<string,mixed> $payload */
    public static function createDraft(array $payload): Quote
    {
        $orgId = (int) (auth()->user()->organization_id ?? app('tenant.id'));
        if (! $orgId) {
            throw new \RuntimeException('Brak kontekstu organizacji.');
        }

        $numbering = QuoteNumberGenerator::next($orgId);
        $items = $payload['items'] ?? [];

        // Powiązanie z Client: jeśli payload przekazał client_id - użyj.
        // W przeciwnym razie spróbuj znaleźć po (email||nip) lub utwórz nowego.
        $clientId = (int) ($payload['client_id'] ?? 0) ?: null;
        if (! $clientId && ! empty($payload['client_name'])) {
            $clientId = self::findOrCreateClientId($orgId, $payload);
        }

        return DB::transaction(function () use ($payload, $numbering, $items, $orgId, $clientId) {
            $quote = Quote::create([
                'organization_id'    => $orgId,
                'number'             => $numbering['number'],
                'year'               => $numbering['year'],
                'month'              => $numbering['month'],
                'sequence'           => $numbering['sequence'],
                'inquiry_id'         => $payload['inquiry_id']   ?? null,
                'client_id'          => $clientId,

                'client_name'        => $payload['client_name']    ?? '',
                'client_email'       => $payload['client_email']   ?? null,
                'client_phone'       => $payload['client_phone']   ?? null,
                'client_company'     => $payload['client_company'] ?? null,
                'client_nip'         => $payload['client_nip']     ?? null,
                'client_address'     => $payload['client_address'] ?? null,

                'from_address'       => $payload['from_address']   ?? '',
                'from_lat'           => $payload['from_lat']       ?? null,
                'from_lng'           => $payload['from_lng']       ?? null,
                'to_address'         => $payload['to_address']     ?? '',
                'to_lat'             => $payload['to_lat']         ?? null,
                'to_lng'             => $payload['to_lng']         ?? null,
                'waypoints'          => $payload['waypoints']      ?? null,

                'distance_km'        => (float) ($payload['distance_km'] ?? 0),
                'return_distance_km' => (float) ($payload['return_distance_km'] ?? 0),
                'duration_min'       => (int)   ($payload['duration_min'] ?? 0),
                'trip_mode'          => $payload['trip_mode'] ?? 'round_trip',
                'round_trip'         => (bool)  ($payload['trip_mode'] ?? 'round_trip') !== 'one_way',

                'transport_date'     => $payload['transport_date'] ?? null,
                'horses_count'       => (int) ($payload['horses_count'] ?? 1),
                'vehicle_id'         => $payload['vehicle_id'] ?? null,
                'trailer_id'         => $payload['trailer_id'] ?? null,
                'driver_id'          => $payload['driver_id']  ?? null,

                'fuel_consumption'   => (float) ($payload['fuel_consumption'] ?? 25),
                'fuel_price'         => (float) ($payload['fuel_price'] ?? 0),
                'base_rate_per_km'   => (float) ($payload['base_rate_per_km'] ?? 0),
                'surcharge_percent'  => (float) ($payload['surcharge_percent'] ?? 0),
                'extra_horse_fee'    => (float) ($payload['extra_horse_fee'] ?? 0),
                'difficult_horse_fee'=> (float) ($payload['difficult_horse_fee'] ?? 0),
                'fixed_fees'         => (float) ($payload['fixed_fees'] ?? 0),
                'toll_cost'          => (float) ($payload['toll_cost'] ?? 0),
                'min_quote_amount'   => (float) ($payload['min_quote_amount'] ?? 0),
                'stay_days'          => (int)   ($payload['stay_days'] ?? 0),
                'stay_24h_cost'      => (float) ($payload['stay_24h_cost'] ?? 0),

                'currency'           => (string) ($payload['currency'] ?? 'PLN'),
                'exchange_rate'      => (float) ($payload['exchange_rate'] ?? 1),
                'vat_percent'        => (float) ($payload['vat_percent'] ?? 23),

                'subtotal_net'       => (float) ($payload['subtotal_net'] ?? 0),
                'vat_amount'         => (float) ($payload['vat_amount'] ?? 0),
                'total_gross'        => (float) ($payload['total_gross'] ?? 0),

                'status'             => 'draft',
                'public_token'       => Str::random(40),
                'valid_until'        => now()->addDays((int) SettingsService::get('quote_validity_days', 14))->toDateString(),
                'notes'              => $payload['notes'] ?? null,
                'created_by'         => auth()->id(),
            ]);

            foreach ($items as $i => $row) {
                QuoteItem::create([
                    'quote_id'       => $quote->id,
                    'item_type'      => $row['item_type'] ?? 'custom',
                    'description'    => $row['description'] ?? '',
                    'qty'            => (float) ($row['qty'] ?? 1),
                    'unit'           => $row['unit'] ?? null,
                    'unit_price_net' => (float) ($row['unit_price_net'] ?? 0),
                    'total_net'      => (float) ($row['total_net'] ?? 0),
                    'sort_order'     => $i,
                ]);
            }

            return $quote;
        });
    }

    /**
     * Znajduje istniejącego klienta po (NIP || email) lub tworzy nowego.
     * Dzięki temu z czasem buduje się historia klientów per-organizacja.
     */
    private static function findOrCreateClientId(int $orgId, array $payload): ?int
    {
        $email = trim((string) ($payload['client_email'] ?? ''));
        $nip   = trim((string) ($payload['client_nip']   ?? ''));
        $name  = trim((string) ($payload['client_name']  ?? ''));

        $query = Client::query()->where('organization_id', $orgId);
        if ($nip !== '') {
            $query->where('nip', $nip);
        } elseif ($email !== '') {
            $query->where('email', $email);
        } else {
            $query->where('name', $name);
        }

        $existing = $query->first();
        if ($existing) {
            return $existing->id;
        }

        $client = Client::create([
            'organization_id' => $orgId,
            'name'    => $name,
            'email'   => $email ?: null,
            'phone'   => $payload['client_phone']   ?? null,
            'company' => $payload['client_company'] ?? null,
            'nip'     => $nip ?: null,
            'address' => $payload['client_address'] ?? null,
        ]);
        return $client->id;
    }
}
