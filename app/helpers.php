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

if (! function_exists('et_diff_for_humans')) {
    /**
     * Return a locale-aware relative time string.
     * - am → Amharic phrasing (e.g. "ከ3 ቀናት በፊት", "ከ2 ሳምንታት በፊት")
     * - en → Carbon's built-in diffForHumans()
     */
    function et_diff_for_humans(Carbon|string|null $date): string
    {
        if ($date === null || $date === '') {
            return '—';
        }

        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        if (app()->getLocale() !== 'am') {
            return $carbon->diffForHumans();
        }

        $diffSeconds = (int) abs(now()->diffInSeconds($carbon));
        $isFuture    = $carbon->isFuture();

        if ($diffSeconds < 60) {
            return __('applicant.just_now');
        }

        if ($diffSeconds < 3600) {
            $n   = (int) round($diffSeconds / 60);
            $str = $n === 1 ? __('applicant.minute_one') : __('applicant.minutes_many', ['n' => $n]);
        } elseif ($diffSeconds < 86400) {
            $n   = (int) round($diffSeconds / 3600);
            $str = $n === 1 ? __('applicant.hour_one') : __('applicant.hours_many', ['n' => $n]);
        } elseif ($diffSeconds < 7 * 86400) {
            $n   = (int) round($diffSeconds / 86400);
            $str = $n === 1 ? __('applicant.day_one') : __('applicant.days_many', ['n' => $n]);
        } elseif ($diffSeconds < 30 * 86400) {
            $n   = (int) round($diffSeconds / (7 * 86400));
            $str = $n === 1 ? __('applicant.week_one') : __('applicant.weeks_many', ['n' => $n]);
        } elseif ($diffSeconds < 365 * 86400) {
            $n   = (int) round($diffSeconds / (30 * 86400));
            $str = $n === 1 ? __('applicant.month_one') : __('applicant.months_many', ['n' => $n]);
        } else {
            $n   = (int) round($diffSeconds / (365 * 86400));
            $str = $n === 1 ? __('applicant.year_one') : __('applicant.years_many', ['n' => $n]);
        }

        $suffix = $isFuture ? __('applicant.from_now') : __('applicant.ago');

        return "{$str} {$suffix}";
    }
}
