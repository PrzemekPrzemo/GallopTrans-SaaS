<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Quote;
use Illuminate\Support\Collection;

/**
 * Generator iCal feed dla kierowców: zwraca wszystkie zaplanowane trasy
 * przypisane do danego kierowcy (driver_id = user.id).
 */
final class CalendarService
{
    public static function buildIcs(int $userId, int $organizationId): string
    {
        $quotes = Quote::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->where(function ($q) use ($userId) {
                $q->where('driver_id', $userId)->orWhere('created_by', $userId);
            })
            ->whereNotNull('transport_date')
            ->whereIn('status', ['accepted', 'sent'])
            ->orderBy('transport_date')
            ->get();

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//GallopTrans//pl',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:GallopTrans — trasy',
        ];

        foreach ($quotes as $q) {
            $lines = array_merge($lines, self::event($q));
        }

        $lines[] = 'END:VCALENDAR';
        return implode("\r\n", $lines) . "\r\n";
    }

    /** @return string[] */
    private static function event(Quote $q): array
    {
        $date = $q->transport_date->format('Ymd');
        $dtstamp = $q->updated_at->utc()->format('Ymd\THis\Z');

        $summary = sprintf('Trasa %s: %s → %s', $q->number, $q->from_address, $q->to_address);
        $desc = sprintf(
            'Klient: %s\nKoni: %d\nDystans: %s km\nKwota: %s %s',
            $q->client_name,
            $q->horses_count,
            number_format((float) $q->distance_km, 0),
            number_format((float) $q->total_gross, 2, ',', ' '),
            $q->currency,
        );

        return [
            'BEGIN:VEVENT',
            'UID:quote-' . $q->id . '@galloptrans',
            'DTSTAMP:' . $dtstamp,
            'DTSTART;VALUE=DATE:' . $date,
            'DTEND;VALUE=DATE:' . $date,
            'SUMMARY:' . self::esc($summary),
            'DESCRIPTION:' . self::esc($desc),
            'LOCATION:' . self::esc($q->from_address . ' → ' . $q->to_address),
            'END:VEVENT',
        ];
    }

    private static function esc(string $s): string
    {
        return preg_replace('/[\r\n,;\\\\]/', fn ($m) => match ($m[0]) {
            "\r" => '',
            "\n" => '\\n',
            ',' => '\\,',
            ';' => '\\;',
            '\\' => '\\\\',
        }, $s);
    }
}
