<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Ethiopian mobile numbers are stored as +251 followed by 9 digits (9XXXXXXXX or
 * 7XXXXXXXX). Accepts what people actually type: 0911 234 567, 911234567,
 * 251911234567 or +251 911 234 567.
 */
final class EthiopianPhone
{
    public const PATTERN = '/^\+251[79]\d{8}$/';

    public static function normalize(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $digits = (string) preg_replace('/\D/', '', $value);

        if (str_starts_with($digits, '251')) {
            $digits = substr($digits, 3);
        }
        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return '+251'.$digits;
    }

    /** The part shown after the fixed "+251" prefix in forms. */
    public static function local(?string $stored): string
    {
        return $stored !== null && str_starts_with($stored, '+251') ? substr($stored, 4) : (string) $stored;
    }
}
