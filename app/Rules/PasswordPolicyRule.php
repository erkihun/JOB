<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final readonly class PasswordPolicyRule implements ValidationRule
{
    /**
     * @param  array{min_length: int, require_uppercase: bool, require_lowercase: bool, require_number: bool, require_symbol: bool, prevent_common_passwords: bool}  $policy
     */
    public function __construct(private array $policy) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $password = (string) $value;

        if ($this->policy['require_uppercase'] && ! preg_match('/[A-Z]/', $password)) {
            $fail(__('validation.password_policy'));
        }

        if ($this->policy['require_lowercase'] && ! preg_match('/[a-z]/', $password)) {
            $fail(__('validation.password_policy'));
        }

        if ($this->policy['require_number'] && ! preg_match('/[0-9]/', $password)) {
            $fail(__('validation.password_policy'));
        }

        if ($this->policy['require_symbol'] && ! preg_match('/[^A-Za-z0-9]/', $password)) {
            $fail(__('validation.password_policy'));
        }

        if ($this->policy['prevent_common_passwords'] && $this->isCommonPassword($password)) {
            $fail(__('validation.password_policy'));
        }
    }

    private function isCommonPassword(string $password): bool
    {
        $normalized = mb_strtolower($password);

        return in_array($normalized, [
            'password',
            'password1',
            'password123',
            'admin',
            'admin123',
            'qwerty',
            'qwerty123',
            '123456',
            '12345678',
            '123456789',
            'letmein',
            'welcome',
            'welcome123',
            'iloveyou',
        ], true);
    }
}
