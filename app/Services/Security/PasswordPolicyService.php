<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\Setting;
use App\Rules\PasswordPolicyRule;
use Illuminate\Validation\Rules\Password;

final class PasswordPolicyService
{
    /**
     * @return array<int, mixed>
     */
    public function adminRules(): array
    {
        return $this->rulesFor($this->adminPolicy());
    }

    /**
     * @return array<int, mixed>
     */
    public function applicantRules(): array
    {
        return $this->rulesFor($this->applicantPolicy());
    }

    public function adminPasswordRule(): Password
    {
        return $this->laravelRuleFor($this->adminPolicy());
    }

    public function applicantPasswordRule(): Password
    {
        return $this->laravelRuleFor($this->applicantPolicy());
    }

    /**
     * @return array{min_length: int, require_uppercase: bool, require_lowercase: bool, require_number: bool, require_symbol: bool, prevent_common_passwords: bool, expiry_days: int|null, history_count: int|null}
     */
    public function adminPolicy(): array
    {
        return $this->policy('admin', 12, requireSymbol: true);
    }

    /**
     * @return array{min_length: int, require_uppercase: bool, require_lowercase: bool, require_number: bool, require_symbol: bool, prevent_common_passwords: bool, expiry_days: int|null, history_count: int|null}
     */
    public function applicantPolicy(): array
    {
        return $this->policy('applicant', 8, requireSymbol: false);
    }

    /**
     * @param  array{min_length: int, require_uppercase: bool, require_lowercase: bool, require_number: bool, require_symbol: bool, prevent_common_passwords: bool, expiry_days: int|null, history_count: int|null}  $policy
     * @return array<int, mixed>
     */
    private function rulesFor(array $policy): array
    {
        return [
            $this->laravelRuleFor($policy),
            new PasswordPolicyRule($policy),
        ];
    }

    /**
     * @param  array{min_length: int, require_uppercase: bool, require_lowercase: bool, require_number: bool, require_symbol: bool, prevent_common_passwords: bool, expiry_days: int|null, history_count: int|null}  $policy
     */
    private function laravelRuleFor(array $policy): Password
    {
        $rule = Password::min($policy['min_length']);

        if ($policy['require_uppercase'] && $policy['require_lowercase']) {
            $rule->mixedCase();
        }

        if ($policy['require_number']) {
            $rule->numbers();
        }

        if ($policy['require_symbol']) {
            $rule->symbols();
        }

        return $rule;
    }

    /**
     * @return array{min_length: int, require_uppercase: bool, require_lowercase: bool, require_number: bool, require_symbol: bool, prevent_common_passwords: bool, expiry_days: int|null, history_count: int|null}
     */
    private function policy(string $scope, int $defaultMinLength, bool $requireSymbol): array
    {
        $prefix = "security.{$scope}_password";

        return [
            'min_length' => max(1, (int) Setting::get("{$prefix}_min_length", $defaultMinLength)),
            'require_uppercase' => (bool) Setting::get("{$prefix}_require_uppercase", true),
            'require_lowercase' => (bool) Setting::get("{$prefix}_require_lowercase", true),
            'require_number' => (bool) Setting::get("{$prefix}_require_number", true),
            'require_symbol' => (bool) Setting::get("{$prefix}_require_symbol", $requireSymbol),
            'prevent_common_passwords' => (bool) Setting::get("{$prefix}_prevent_common_passwords", true),
            'expiry_days' => $this->nullableInteger(Setting::get("{$prefix}_expiry_days")),
            'history_count' => $this->nullableInteger(Setting::get("{$prefix}_history_count")),
        ];
    }

    private function nullableInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return max(0, (int) $value);
    }
}
