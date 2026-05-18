<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Kurs EUR/PLN z publicznego API NBP (tabela A). Cache w exchange_rates.
 * Współdzielony między tenantami (kurs jest publiczny).
 */
final class NbpService
{
    public static function fetchEur(): float
    {
        $url = (string) config('services.nbp.url', env('NBP_API_URL', 'https://api.nbp.pl/api/exchangerates/rates/A/EUR'));

        $resp = Http::timeout(15)->acceptJson()->get($url, ['format' => 'json']);
        if (! $resp->ok()) {
            throw new RuntimeException("NBP HTTP {$resp->status()}");
        }
        $data = $resp->json();
        $rate = $data['rates'][0]['mid'] ?? null;
        $date = $data['rates'][0]['effectiveDate'] ?? date('Y-m-d');
        if (! is_numeric($rate)) {
            throw new RuntimeException('NBP: brak kursu w odpowiedzi.');
        }

        ExchangeRate::updateOrCreate(
            ['code' => 'EUR', 'valid_for_date' => $date, 'source' => 'nbp'],
            ['rate' => (float) $rate, 'fetched_at' => now()],
        );

        return (float) $rate;
    }

    public static function latestEur(): ?float
    {
        $r = ExchangeRate::query()
            ->where('code', 'EUR')
            ->orderByDesc('valid_for_date')
            ->orderByDesc('id')
            ->value('rate');

        return $r !== null ? (float) $r : null;
    }
}
