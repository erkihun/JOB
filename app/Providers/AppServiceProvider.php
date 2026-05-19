<?php

namespace App\Providers;

use App\Enums\UserStatus;
use App\Models\ApplicantProfileDocument;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vacancy;
use App\Policies\ApplicantProfileDocumentPolicy;
use App\Policies\ApplicationDocumentPolicy;
use App\Policies\ApplicationPolicy;
use App\Policies\AuditLogPolicy;
use App\Policies\SettingPolicy;
use App\Policies\UserPolicy;
use App\Policies\VacancyPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        User::deleting(function (User $user): bool {
            if (! $user->isSuperAdmin()) {
                return true;
            }

            return User::role('super_admin')
                ->where('status', UserStatus::Active->value)
                ->whereKeyNot($user->id)
                ->exists();
        });

        User::updating(function (User $user): bool {
            if (! $user->isSuperAdmin() || ! $user->isDirty('status')) {
                return true;
            }

            if ($user->getOriginal('status') !== UserStatus::Active->value && $user->getOriginal('status') !== UserStatus::Active) {
                return true;
            }

            if ($user->status === UserStatus::Active || $user->status === UserStatus::Active->value) {
                return true;
            }

            return User::role('super_admin')
                ->where('status', UserStatus::Active->value)
                ->whereKeyNot($user->id)
                ->exists();
        });

        // Super admin bypasses all gates (permission-level access)
        Gate::before(function (User $user, string $_ability) {
            if ($user->isSuperAdmin()) {
                return true;
            }
        });

        // Applicants are completely blocked from admin-level gates
        Gate::after(function (User $user, string $ability, ?bool $result) {
            if ($result === null && $user->hasRole('applicant')) {
                // Allow only applicant-prefixed permissions for applicants
                if (! str_starts_with($ability, 'applicant.')) {
                    return false;
                }
            }
        });

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Vacancy::class, VacancyPolicy::class);
        Gate::policy(Application::class, ApplicationPolicy::class);
        Gate::policy(ApplicationDocument::class, ApplicationDocumentPolicy::class);
        Gate::policy(ApplicantProfileDocument::class, ApplicantProfileDocumentPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(Setting::class, SettingPolicy::class);
    }
}
