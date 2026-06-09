<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\Setting;
use App\Models\User;

final class MfaSettings
{
    public function enabled(): bool
    {
        return (bool) Setting::get('security.mfa_enabled', $this->envBoolean('MFA_ENABLED', false));
    }

    public function requiredForAdmins(): bool
    {
        return (bool) Setting::get('security.mfa_required_for_admins', $this->envBoolean('MFA_REQUIRED_FOR_ADMINS', true));
    }

    public function requiredForApplicants(): bool
    {
        return (bool) Setting::get('security.mfa_required_for_applicants', $this->envBoolean('MFA_REQUIRED_FOR_APPLICANTS', false));
    }

    /**
     * Roles for which MFA is mandatory. When configured, this takes precedence
     * over the legacy admin/applicant toggles, allowing per-role granularity.
     *
     * @return list<string>
     */
    public function requiredRoles(): array
    {
        $roles = Setting::get('security.mfa_required_roles', null);

        if ($roles === null || $roles === '') {
            return [];
        }

        if (is_string($roles)) {
            $roles = array_filter(explode(',', $roles));
        }

        return array_values(array_filter(array_map(
            static fn ($role): string => trim((string) $role),
            (array) $roles,
        ), static fn (string $role): bool => $role !== ''));
    }

    public function rememberDeviceDays(): int
    {
        return max(0, (int) Setting::get('security.mfa_remember_device_days', 0));
    }

    public function issuerName(): string
    {
        return (string) Setting::get('security.mfa_issuer_name', env('MFA_ISSUER_NAME', config('app.name')));
    }

    /**
     * @return list<string>
     */
    public function methodsAllowed(): array
    {
        $methods = Setting::get('security.mfa_methods_allowed', ['totp']);

        if (is_string($methods)) {
            $methods = array_filter(explode(',', $methods));
        }

        return array_values(array_intersect((array) $methods, ['totp']));
    }

    public function allowsTotp(): bool
    {
        return in_array('totp', $this->methodsAllowed(), true);
    }

    public function requiresFor(User $user): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        // Per-role configuration takes precedence when any role is selected.
        $requiredRoles = $this->requiredRoles();

        if ($requiredRoles !== []) {
            return $user->hasAnyRole($requiredRoles);
        }

        // Backward-compatible fallback to the legacy admin/applicant toggles.
        if ($user->canAccessAdminArea()) {
            return $this->requiredForAdmins();
        }

        if ($user->hasRole('applicant')) {
            return $this->requiredForApplicants();
        }

        return false;
    }

    public function shouldChallenge(User $user): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        return $user->hasTwoFactorEnabled() || $this->requiresFor($user);
    }

    private function envBoolean(string $key, bool $default): bool
    {
        $value = env($key);

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
