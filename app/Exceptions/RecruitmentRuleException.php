<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Validation\ValidationException;

/**
 * A recruitment timeline / lifecycle rule was violated.
 *
 * Extends ValidationException so web requests redirect back with the localized
 * message under the given error key, while Actions and jobs can still catch it
 * specifically.
 */
class RecruitmentRuleException extends ValidationException
{
    /**
     * @param  string  $messageKey  translation key, e.g. "recruitment.errors.application_closed"
     * @param  array<string, mixed>  $replace
     */
    public static function because(string $messageKey, string $field = 'recruitment', array $replace = []): static
    {
        return static::withMessages([$field => [__($messageKey, $replace)]]);
    }
}
