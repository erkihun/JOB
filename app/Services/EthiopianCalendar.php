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

    /** JDN of Ethiopian 1-1-1 (Amete Mihret era). */
    private const ETHIOPIC_EPOCH = 1724221;

    /**
     * Convert Gregorian y/m/d to Ethiopian date components.
     *
     * Uses exact integer Julian Day Number conversion (no floating point, no
     * timezone/DST dependency), so it is correct on every date including
     * month/year boundaries and leap years.
     *
     * @return array{year: int, month: int, day: int}
     */
    public static function fromGregorian(int $gy, int $gm, int $gd): array
    {
        return self::jdnToEthiopic(self::gregorianToJdn($gy, $gm, $gd));
    }

    /** Gregorian date → Julian Day Number (standard integer formula). */
    private static function gregorianToJdn(int $y, int $m, int $d): int
    {
        return intdiv(1461 * ($y + 4800 + intdiv($m - 14, 12)), 4)
            + intdiv(367 * ($m - 2 - 12 * intdiv($m - 14, 12)), 12)
            - intdiv(3 * intdiv($y + 4900 + intdiv($m - 14, 12), 100), 4)
            + $d - 32075;
    }

    /**
     * Julian Day Number → Ethiopian date components.
     *
     * @return array{year: int, month: int, day: int}
     */
    private static function jdnToEthiopic(int $jdn): array
    {
        $r = $jdn - self::ETHIOPIC_EPOCH;
        $year = intdiv(4 * $r + 1463, 1461);
        $dayOfYear = $r - (365 * ($year - 1) + intdiv($year, 4));

        return [
            'year' => $year,
            'month' => intdiv($dayOfYear, 30) + 1,
            'day' => ($dayOfYear % 30) + 1,
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
        $et = self::fromGregorian($date->year, $date->month, $date->day);
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
            return $base.' '.$date->format('H:i');
        }

        return $base;
    }

    /** Shorthand: full "day month year" string. */
    public static function format(Carbon $date): string
    {
        return self::formatGc($date, 'd M Y');
    }
}
