<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Numeracja faktur per-organizacja z reset mesięcznym.
 * Format pochodzi z `organizations.invoice_number_format` (np. FV/{Y}/{M}/{####}).
 */
final class InvoiceNumberGenerator
{
    /** @return array{number:string,year:int,month:int,sequence:int} */
    public static function next(Organization $org, ?Carbon $when = null): array
    {
        $when ??= now();
        $year = (int) $when->format('Y');
        $month = (int) $when->format('n');

        return DB::transaction(function () use ($org, $year, $month) {
            $last = Invoice::withoutGlobalScopes()
                ->where('organization_id', $org->id)
                ->where('year', $year)
                ->where('month', $month)
                ->lockForUpdate()
                ->max('sequence');

            $sequence = (int) ($last ?? 0) + 1;
            $format = $org->invoice_number_format ?: 'FV/{Y}/{M}/{####}';

            $number = strtr($format, [
                '{Y}'    => sprintf('%04d', $year),
                '{YY}'   => sprintf('%02d', $year % 100),
                '{M}'    => sprintf('%02d', $month),
                '{####}' => sprintf('%04d', $sequence),
                '{###}'  => sprintf('%03d', $sequence),
                '{##}'   => sprintf('%02d', $sequence),
                '{#}'    => (string) $sequence,
            ]);

            return compact('number', 'year', 'month', 'sequence');
        });
    }
}
