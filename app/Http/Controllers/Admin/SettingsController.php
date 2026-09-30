<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class SettingsController extends Controller
{
    private array $keys = [
        'org.name', 'org.logo', 'org.favicon', 'org.address', 'org.phone', 'org.email', 'org.website', 'org.footer_text',
        'org.facebook', 'org.twitter', 'org.linkedin', 'org.youtube',
        'app.available_locales', 'app.fallback_locale', 'app.date_format',
        'recruitment.max_file_size_mb', 'recruitment.allowed_file_types', 'recruitment.allow_registration',
        'recruitment.show_archived_vacancies', 'recruitment.reference_format',
        'localization.default_locale', 'localization.show_language_switcher',
        'mail.from_name', 'mail.from_address',
        'security.session_timeout', 'security.login_attempts',
        'security.mfa_enabled',
        'security.mfa_required_for_admins',
        'security.mfa_required_for_applicants',
        'security.mfa_required_roles',
        'security.mfa_methods_allowed',
        'security.mfa_remember_device_days',
        'security.mfa_issuer_name',
        'security.admin_password_min_length',
        'security.admin_password_require_uppercase',
        'security.admin_password_require_lowercase',
        'security.admin_password_require_number',
        'security.admin_password_require_symbol',
        'security.admin_password_prevent_common_passwords',
        'security.admin_password_expiry_days',
        'security.admin_password_history_count',
        'security.applicant_password_min_length',
        'security.applicant_password_require_uppercase',
        'security.applicant_password_require_lowercase',
        'security.applicant_password_require_number',
        'security.applicant_password_require_symbol',
        'security.applicant_password_prevent_common_passwords',
        'security.applicant_password_expiry_days',
        'security.applicant_password_history_count',
        // Code generation
        'codes.application.prefix', 'codes.application.format', 'codes.application.padding',
        'codes.vacancy.prefix', 'codes.vacancy.format', 'codes.vacancy.padding', 'codes.vacancy.auto',
        'codes.applicant.prefix', 'codes.applicant.format', 'codes.applicant.padding',
        'results.exam_weight', 'results.interview_weight', 'results.practical_weight',
        'appearance.primary_color', 'appearance.sidebar_color', 'appearance.accent_color', 'appearance.logo_size',
    ];

    public function index(): View
    {
        $settings = collect($this->keys)->mapWithKeys(
            fn ($key) => [$key => Setting::get($key, $this->defaultFor($key))]
        );

        // Roles selectable for per-role MFA enforcement (newest last for stable order).
        $assignableRoles = Role::orderBy('name')->pluck('name')->all();

        return view('admin.settings.index', compact('settings', 'assignableRoles'));
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('_section');

        // Handle logo upload separately to avoid overwriting its path in the loop.
        if ($request->hasFile('org.logo')) {
            $path = $request->file('org.logo')->store('org', 'public');
            Setting::set('org.logo', $path);
        }
        if ($request->hasFile('org.favicon')) {
            $path = $request->file('org.favicon')->store('org', 'public');
            Setting::set('org.favicon', $path);
        }
        Arr::forget($data, 'org.logo');
        Arr::forget($data, 'org.favicon');

        // A tab save updates only the supplied settings. Preserve all other tabs.
        if (Arr::has($data, 'app.available_locales')) {
            $available = array_values($data['app']['available_locales']);
            foreach (['app.fallback_locale', 'localization.default_locale'] as $key) {
                $locale = Arr::get($data, $key, Setting::get($key, 'en'));
                Arr::set($data, $key, in_array($locale, $available, true) ? $locale : $available[0]);
            }
        }

        DB::transaction(function () use ($data): void {
            foreach (['app.available_locales', 'recruitment.allowed_file_types', 'security.mfa_methods_allowed', 'security.mfa_required_roles'] as $key) {
                if (Arr::has($data, $key)) {
                    $this->persist($key, array_values((array) Arr::get($data, $key)));
                    Arr::forget($data, $key);
                }
            }
            foreach (Arr::dot($data) as $key => $value) {
                if (in_array($key, $this->keys, true)) {
                    $this->persist($key, $value ?? '');
                }
            }
        });

        $this->applyRuntimeConfiguration();

        if ($request->has('security')) {
            AuditLog::record(
                'security_settings_updated',
                'settings',
                newValues: Arr::dot((array) $request->input('security', [])),
            );
        }

        $settingsAuditPayload = Arr::except($request->except(['_token', '_method', '_section', '_present']), ['security']);
        if (isset($settingsAuditPayload['org']['logo'])) {
            $settingsAuditPayload['org']['logo'] = '[uploaded]';
        }
        if (isset($settingsAuditPayload['org']['favicon'])) {
            $settingsAuditPayload['org']['favicon'] = '[uploaded]';
        }

        if ($settingsAuditPayload !== []) {
            AuditLog::record(
                'settings_updated',
                'settings',
                newValues: Arr::dot($settingsAuditPayload),
            );
        }

        return back()->with('success', __('messages.settings_saved'))
            ->with('settings_section', $request->input('_section', 'org'));
    }

    private function persist(string $key, mixed $value): void
    {
        $types = [
            'app.available_locales' => 'json',
            'recruitment.allowed_file_types' => 'json',
            'recruitment.max_file_size_mb' => 'integer',
            'recruitment.allow_registration' => 'boolean',
            'recruitment.show_archived_vacancies' => 'boolean',
            'localization.show_language_switcher' => 'boolean',
            'security.session_timeout' => 'integer',
            'security.login_attempts' => 'integer',
            'security.mfa_enabled' => 'boolean',
            'security.mfa_required_for_admins' => 'boolean',
            'security.mfa_required_for_applicants' => 'boolean',
            'security.mfa_methods_allowed' => 'json',
            'security.mfa_required_roles' => 'json',
            'security.mfa_remember_device_days' => 'integer',
            'security.admin_password_min_length' => 'integer',
            'security.admin_password_require_uppercase' => 'boolean',
            'security.admin_password_require_lowercase' => 'boolean',
            'security.admin_password_require_number' => 'boolean',
            'security.admin_password_require_symbol' => 'boolean',
            'security.admin_password_prevent_common_passwords' => 'boolean',
            'security.applicant_password_min_length' => 'integer',
            'security.applicant_password_require_uppercase' => 'boolean',
            'security.applicant_password_require_lowercase' => 'boolean',
            'security.applicant_password_require_number' => 'boolean',
            'security.applicant_password_require_symbol' => 'boolean',
            'security.applicant_password_prevent_common_passwords' => 'boolean',
            'codes.application.padding' => 'integer',
            'codes.vacancy.padding' => 'integer',
            'codes.vacancy.auto' => 'boolean',
            'codes.applicant.padding' => 'integer',
            'results.exam_weight' => 'float',
            'results.interview_weight' => 'float',
            'results.practical_weight' => 'float',
            'appearance.logo_size' => 'integer',
        ];

        $groups = [
            'app.' => 'general',
            'org.' => 'general',
            'recruitment.' => 'recruitment',
            'localization.' => 'localization',
            'mail.' => 'notifications',
            'security.' => 'security',
            'codes.' => 'codes',
            'results.' => 'results',
            'appearance.' => 'appearance',
        ];

        $group = collect($groups)->first(
            fn (string $candidate, string $prefix): bool => str_starts_with($key, $prefix),
            'general'
        );

        Setting::set($key, $value, $types[$key] ?? 'string', $group);
    }

    private function defaultFor(string $key): mixed
    {
        return match ($key) {
            'org.name' => config('app.name'),
            'app.available_locales' => ['en', 'am'],
            'app.fallback_locale' => 'en',
            'app.date_format' => 'Y-m-d',
            'recruitment.max_file_size_mb' => 2,
            'recruitment.allowed_file_types' => ['pdf', 'jpg', 'jpeg', 'png'],
            'recruitment.allow_registration',
            'recruitment.show_archived_vacancies',
            'codes.vacancy.auto',
            'localization.show_language_switcher' => true,
            'localization.default_locale' => 'en',
            'mail.from_name' => config('mail.from.name'),
            'mail.from_address' => config('mail.from.address'),
            'security.session_timeout' => 120,
            'security.login_attempts' => 5,
            'security.mfa_enabled' => filter_var(env('MFA_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
            'security.mfa_required_for_admins' => filter_var(env('MFA_REQUIRED_FOR_ADMINS', true), FILTER_VALIDATE_BOOLEAN),
            'security.mfa_required_for_applicants' => filter_var(env('MFA_REQUIRED_FOR_APPLICANTS', false), FILTER_VALIDATE_BOOLEAN),
            'security.mfa_methods_allowed' => ['totp'],
            'security.mfa_required_roles' => [],
            'security.mfa_remember_device_days' => 0,
            'security.mfa_issuer_name' => env('MFA_ISSUER_NAME', config('app.name')),
            'security.admin_password_min_length' => 12,
            'security.admin_password_require_uppercase',
            'security.admin_password_require_lowercase',
            'security.admin_password_require_number',
            'security.admin_password_require_symbol',
            'security.admin_password_prevent_common_passwords',
            'security.applicant_password_require_uppercase',
            'security.applicant_password_require_lowercase',
            'security.applicant_password_require_number',
            'security.applicant_password_prevent_common_passwords' => true,
            'security.applicant_password_min_length' => 8,
            'security.applicant_password_require_symbol' => false,
            'security.admin_password_expiry_days',
            'security.admin_password_history_count',
            'security.applicant_password_expiry_days',
            'security.applicant_password_history_count' => '',
            'codes.application.prefix' => 'APP',
            'codes.application.format' => '{PREFIX}-{YEAR}-{SEQ}',
            'codes.application.padding' => 6,
            'codes.vacancy.prefix' => 'VAC',
            'codes.vacancy.format' => '{PREFIX}-{YEAR}-{SEQ}',
            'codes.vacancy.padding' => 4,
            'codes.applicant.prefix' => 'APL',
            'codes.applicant.format' => '{PREFIX}-{YEAR}-{SEQ}',
            'codes.applicant.padding' => 5,
            'results.exam_weight' => 60,
            'results.interview_weight' => 40,
            'results.practical_weight' => 0,
            'appearance.primary_color' => '#1A56DB',
            'appearance.sidebar_color' => '#1E3A8A',
            'appearance.accent_color' => '#FF6B2B',
            'appearance.logo_size' => 36,
            default => '',
        };
    }

    private function applyRuntimeConfiguration(): void
    {
        Config::set('mail.from.name', Setting::get('mail.from_name', config('mail.from.name')));
        Config::set('mail.from.address', Setting::get('mail.from_address', config('mail.from.address')));
        Config::set('app.fallback_locale', Setting::get('app.fallback_locale', config('app.fallback_locale', 'en')));
    }
}
