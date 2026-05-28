<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;

class EthiopianCalendar
{
    private static array $months = [
        'መስከረም', 'ጥቅምት', 'ህዳር',  'ታህሳስ',
        'ጥር',     'የካቲት', 'መጋቢት', 'ሚያዚያ',
        'ግንቦት',   'ሰኔ',   'ሐምሌ',  'ነሐሴ',  'ጳጉሜ',
    ];

    /** Convert Gregorian y/m/d to Ethiopian date components. */
    public static function fromGregorian(int $gy, int $gm, int $gd): array
    {
        $prev   = $gy - 1;
        $pLeap  = $prev % 400 === 0 || ($prev % 4 === 0 && $prev % 100 !== 0);
        $nyDay  = $pLeap ? 12 : 11;
        $etY    = ($gm > 9 || ($gm === 9 && $gd >= $nyDay)) ? $gy - 7 : $gy - 8;
        $nyGcY  = $etY + 7;
        $p2     = $nyGcY - 1;
        $p2Leap = $p2 % 400 === 0 || ($p2 % 4 === 0 && $p2 % 100 !== 0);
        $ny     = $p2Leap ? 12 : 11;
        $diff   = (int) Carbon::create($nyGcY, 9, $ny)
                              ->diffInDays(Carbon::create($gy, $gm, $gd), false);

        return [
            'year'  => $etY,
            'month' => intdiv($diff, 30) + 1,
            'day'   => ($diff % 30) + 1,
        ];
    }

    /**
     * Format a Carbon date in Ethiopian calendar, respecting the GC format hint.
     *
     * Supported GC format hints → ET output:
     *   'Y'                       → "2018"
     *   'M Y' / 'F Y' / 'M, Y'   → "ግንቦት 2018"
     *   'M d' / 'M d, Y'          → "19 ግንቦት" / "19 ግንቦት 2018"
     *   anything with 'H:i'       → "19 ግንቦት 2018 14:05"
     *   everything else           → "19 ግንቦት 2018"
     */
    public static function formatGc(Carbon $date, string $gcFormat = 'd M Y'): string
    {
        $et   = self::fromGregorian($date->year, $date->month, $date->day);
        $name = self::$months[$et['month'] - 1];

        if ($gcFormat === 'Y') {
            return (string) $et['year'];
        }

        if (in_array($gcFormat, ['M Y', 'F Y', 'M, Y'], true)) {
            return "{$name} {$et['year']}";
        }

        // Month + day only, no year (e.g. timeline short display)
        if (in_array($gcFormat, ['M d', 'd M'], true)) {
            return "{$et['day']} {$name}";
        }

        $base = "{$et['day']} {$name} {$et['year']}";

        // Preserve the time part when the GC format includes hours/minutes
        if (str_contains($gcFormat, 'H:i')) {
            return $base . ' ' . $date->format('H:i');
        }

        return $base;
    }

    /** Shorthand: full "day month year" string. */
    public static function format(Carbon $date): string
    {
        return self::formatGc($date, 'd M Y');
    }
}
