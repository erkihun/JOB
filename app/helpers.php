<?php

use App\Services\EthiopianCalendar;
use Carbon\Carbon;

if (! function_exists('et_date')) {
    /**
     * Format a date according to the current locale.
     * Returns an Ethiopian date string when locale is 'am', Gregorian otherwise.
     */
    function et_date(Carbon|string|null $date, string $gcFormat = 'd M Y'): string
    {
        if ($date === null || $date === '') {
            return '—';
        }

        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        if (app()->getLocale() === 'am') {
            return EthiopianCalendar::format($carbon);
        }

        return $carbon->format($gcFormat);
    }
}
