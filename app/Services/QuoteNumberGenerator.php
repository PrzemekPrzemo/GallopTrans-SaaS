<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Quote;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Generator numeracji ofert: OF/RRRR/MM/NNNN z resetem miesięcznym.
 * Numeracja jest per-organizacja (każdy tenant ma swoją sekwencję).
 */
final class QuoteNumberGenerator
{
    /** @return array{number:string,year:int,month:int,sequence:int} */
    public static function next(int $organizationId, ?Carbon $when = null): array
    {
        $when ??= now();
        $year = (int) $when->format('Y');
        $month = (int) $when->format('n');

        return DB::transaction(function () use ($organizationId, $year, $month) {
            $last = Quote::withoutGlobalScopes()
                ->where('organization_id', $organizationId)
                ->where('year', $year)
                ->where('month', $month)
                ->lockForUpdate()
                ->max('sequence');

            $sequence = (int) ($last ?? 0) + 1;
            $number = sprintf('OF/%04d/%02d/%04d', $year, $month, $sequence);

            return compact('number', 'year', 'month', 'sequence');
        });
    }
}
