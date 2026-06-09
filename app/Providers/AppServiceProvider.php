<?php

namespace App\Providers;

use App\Enums\UserStatus;
use App\Models\ApplicantProfileDocument;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\AuditLog;
use App\Models\Institution;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vacancy;
use App\Policies\ApplicantProfileDocumentPolicy;
use App\Policies\ApplicationDocumentPolicy;
use App\Policies\ApplicationPolicy;
use App\Policies\AuditLogPolicy;
use App\Policies\InstitutionPolicy;
use App\Policies\SettingPolicy;
use App\Policies\UserPolicy;
use App\Policies\VacancyPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->applySystemSettings();
        $this->configureRateLimiting();

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
        Gate::policy(Institution::class, InstitutionPolicy::class);
        Gate::policy(Vacancy::class, VacancyPolicy::class);
        Gate::policy(Application::class, ApplicationPolicy::class);
        Gate::policy(ApplicationDocument::class, ApplicationDocumentPolicy::class);
        Gate::policy(ApplicantProfileDocument::class, ApplicantProfileDocumentPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(Setting::class, SettingPolicy::class);
    }

    private function applySystemSettings(): void
    {
        try {
            config([
                'mail.from.name' => Setting::get('mail.from_name', config('mail.from.name')),
                'mail.from.address' => Setting::get('mail.from_address', config('mail.from.address')),
                'app.fallback_locale' => Setting::get('app.fallback_locale', config('app.fallback_locale', 'en')),
                'app.available_locales' => Setting::get('app.available_locales', config('app.available_locales', ['en', 'am'])),
            ]);
        } catch (Throwable) {
            // Settings table may not exist during install, migrations, or early bootstrap.
        }
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request): Limit {
            try {
                $attempts = (int) Setting::get('security.login_attempts', 5);
            } catch (Throwable) {
                $attempts = 5;
            }

            $attempts = min(max($attempts, 3), 20);
            $email = strtolower((string) $request->input('email'));

            return Limit::perMinute($attempts)->by($email.'|'.$request->ip());
        });
    }
}
