<?php

use App\Services\EthiopianCalendar;
use Carbon\Carbon;

if (! function_exists('et_date')) {
    /**
     * Format a date respecting the current locale.
     * - am → Ethiopian calendar (day month year, or month year, preserves H:i)
     * - en → Gregorian with the given $gcFormat
     */
    function et_date(Carbon|string|null $date, string $gcFormat = 'd M Y'): string
    {
        if ($date === null || $date === '') {
            return '—';
        }

        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        if (app()->getLocale() === 'am') {
            return EthiopianCalendar::formatGc($carbon, $gcFormat);
        }

        return $carbon->format($gcFormat);
    }
}
