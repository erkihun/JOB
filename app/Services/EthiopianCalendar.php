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

    /** Convert a Gregorian date to Ethiopian date components. */
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

    /** Return a formatted Ethiopian date string, e.g. "19 ግንቦት 2018". */
    public static function format(Carbon $date): string
    {
        $et = self::fromGregorian($date->year, $date->month, $date->day);

        return "{$et['day']} " . self::$months[$et['month'] - 1] . " {$et['year']}";
    }
}
