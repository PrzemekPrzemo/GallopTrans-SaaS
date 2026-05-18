<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FuelPrice;

final class FuelPriceService
{
    /** @return array<string,mixed>|null */
    public static function latest(string $fuelType = 'diesel'): ?array
    {
        return FuelPrice::query()
            ->where('fuel_type', $fuelType)
            ->orderByDesc('valid_for_date')
            ->orderByDesc('id')
            ->first()
            ?->toArray();
    }
}
