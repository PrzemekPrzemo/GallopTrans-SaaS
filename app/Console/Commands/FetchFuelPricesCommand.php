<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\FuelPriceScraper;
use Illuminate\Console\Command;

class FetchFuelPricesCommand extends Command
{
    protected $signature = 'saas:fetch-fuel-prices';

    protected $description = 'Pobiera bieżące ceny paliw z e-petrol.pl i zapisuje dla wszystkich organizacji.';

    public function handle(): int
    {
        $prices = FuelPriceScraper::fetchFromEpetrol();

        $count = FuelPriceScraper::storeForAllOrganizations($prices);

        $this->info(sprintf(
            'Diesel: %s, Pb95: %s, LPG: %s — zapisano %d wpisów.',
            $prices['diesel'] ?? '—',
            $prices['petrol'] ?? '—',
            $prices['lpg'] ?? '—',
            $count,
        ));

        return self::SUCCESS;
    }
}
