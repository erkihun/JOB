<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.manage') && $this->user()->can('settings.security');
    }

    protected function prepareForValidation(): void
    {
        // The per-role MFA field ships a blank hidden value so unchecking every
        // role still submits the key; strip it before validation/persistence.
        if ($this->has('security.mfa_required_roles')) {
            $this->merge([
                'security' => array_merge((array) $this->input('security', []), [
                    'mfa_required_roles' => array_values(array_filter(
                        (array) $this->input('security.mfa_required_roles', []),
                        static fn ($role): bool => is_string($role) && $role !== '',
                    )),
                ]),
            ]);
        }

        foreach (['app.available_locales', 'recruitment.allowed_file_types', 'security.mfa_methods_allowed'] as $key) {
            if ($this->input('_section') && $this->boolean('_present.'.str_replace('.', '_', $key)) && ! $this->has($key)) {
                [$group, $field] = explode('.', $key, 2);
                $this->merge([$group => array_merge((array) $this->input($group, []), [$field => []])]);
            }
        }
    }

    public function rules(): array
    {
        return [
            '_section' => ['sometimes', Rule::in(['org', 'social', 'recruitment', 'codes', 'results', 'localization', 'notifications', 'security', 'appearance'])],
            'org.name' => ['nullable', 'string', 'max:255'],
            'org.address' => ['nullable', 'string', 'max:500'],
            'org.phone' => ['nullable', 'string', 'max:50'],
            'org.email' => ['nullable', 'email'],
            'org.website' => ['nullable', 'url'],
            'org.footer_text' => ['nullable', 'string', 'max:255'],
            'org.facebook' => ['nullable', 'url'],
            'org.twitter' => ['nullable', 'url'],
            'org.linkedin' => ['nullable', 'url'],
            'org.youtube' => ['nullable', 'url'],
            'org.logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
            'org.favicon' => ['nullable', 'file', 'mimes:ico,png,jpg,jpeg,webp', 'max:512'],
            'app.available_locales' => ['sometimes', 'required', 'array', 'min:1'],
            'app.available_locales.*' => ['string', 'in:en,am'],
            'app.fallback_locale' => ['nullable', 'string', 'in:en,am'],
            'app.date_format' => ['sometimes', 'required', 'string', Rule::in(['Y-m-d', 'd/m/Y', 'm/d/Y', 'd M Y', 'M d, Y'])],
            'recruitment.max_file_size_mb' => ['sometimes', 'required', 'integer', 'min:1', 'max:10'],
            'recruitment.allowed_file_types' => ['sometimes', 'required', 'array', 'min:1'],
            'recruitment.allowed_file_types.*' => ['string', 'in:pdf,jpg,jpeg,png'],
            'recruitment.allow_registration' => ['nullable', 'boolean'],
            'recruitment.show_archived_vacancies' => ['nullable', 'boolean'],
            'recruitment.reference_format' => ['nullable', 'string', 'max:100'],
            'localization.default_locale' => ['nullable', 'string', 'in:en,am'],
            'localization.show_language_switcher' => ['nullable', 'boolean'],
            'mail.from_name' => ['nullable', 'string', 'max:255'],
            'mail.from_address' => ['nullable', 'email'],
            'security.session_timeout' => ['sometimes', 'required', 'integer', 'min:5', 'max:1440'],
            'security.login_attempts' => ['sometimes', 'required', 'integer', 'min:3', 'max:20'],
            'security.mfa_enabled' => ['nullable', 'boolean'],
            'security.mfa_required_for_admins' => ['nullable', 'boolean'],
            'security.mfa_required_for_applicants' => ['nullable', 'boolean'],
            'security.mfa_required_roles' => ['nullable', 'array'],
            'security.mfa_required_roles.*' => ['string', 'exists:roles,name'],
            'security.mfa_methods_allowed' => ['sometimes', 'required', 'array', 'min:1'],
            'security.mfa_methods_allowed.*' => ['string', 'in:totp'],
            'security.mfa_remember_device_days' => ['sometimes', 'required', 'integer', 'min:0', 'max:365'],
            'security.mfa_issuer_name' => ['nullable', 'string', 'max:100'],
            'security.admin_password_min_length' => ['sometimes', 'required', 'integer', 'min:8', 'max:128'],
            'security.admin_password_require_uppercase' => ['nullable', 'boolean'],
            'security.admin_password_require_lowercase' => ['nullable', 'boolean'],
            'security.admin_password_require_number' => ['nullable', 'boolean'],
            'security.admin_password_require_symbol' => ['nullable', 'boolean'],
            'security.admin_password_prevent_common_passwords' => ['nullable', 'boolean'],
            'security.admin_password_expiry_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'security.admin_password_history_count' => ['nullable', 'integer', 'min:1', 'max:24'],
            'security.applicant_password_min_length' => ['sometimes', 'required', 'integer', 'min:8', 'max:128'],
            'security.applicant_password_require_uppercase' => ['nullable', 'boolean'],
            'security.applicant_password_require_lowercase' => ['nullable', 'boolean'],
            'security.applicant_password_require_number' => ['nullable', 'boolean'],
            'security.applicant_password_require_symbol' => ['nullable', 'boolean'],
            'security.applicant_password_prevent_common_passwords' => ['nullable', 'boolean'],
            'security.applicant_password_expiry_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'security.applicant_password_history_count' => ['nullable', 'integer', 'min:1', 'max:24'],
            // Code generation
            'codes.application.prefix' => ['nullable', 'string', 'max:20', 'alpha_num'],
            'codes.application.format' => ['sometimes', 'required', 'string', 'max:50'],
            'codes.application.padding' => ['sometimes', 'required', 'integer', 'min:1', 'max:10'],
            'codes.vacancy.prefix' => ['nullable', 'string', 'max:20', 'alpha_num'],
            'codes.vacancy.format' => ['sometimes', 'required', 'string', 'max:50'],
            'codes.vacancy.padding' => ['sometimes', 'required', 'integer', 'min:1', 'max:10'],
            'codes.vacancy.auto' => ['nullable', 'boolean'],
            'codes.applicant.prefix' => ['nullable', 'string', 'max:20', 'alpha_num'],
            'codes.applicant.format' => ['sometimes', 'required', 'string', 'max:50'],
            'codes.applicant.padding' => ['sometimes', 'required', 'integer', 'min:1', 'max:10'],
            'results.exam_weight' => ['sometimes', 'required', 'numeric', 'min:0', 'max:100'],
            'results.interview_weight' => ['sometimes', 'required', 'numeric', 'min:0', 'max:100'],
            'results.practical_weight' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'appearance.primary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'appearance.sidebar_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'appearance.accent_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'appearance.logo_size' => ['sometimes', 'required', 'integer', 'min:24', 'max:72'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            if ($this->has('results.exam_weight') || $this->has('results.interview_weight') || $this->has('results.practical_weight')) {
                $exam = (float) $this->input('results.exam_weight', Setting::get('results.exam_weight', 60));
                $interview = (float) $this->input('results.interview_weight', Setting::get('results.interview_weight', 40));
                $practical = (float) $this->input('results.practical_weight', Setting::get('results.practical_weight', 0));
                if (abs($exam + $interview + $practical - 100) > 0.00001) {
                    $validator->errors()->add('results.exam_weight', __('settings.weights_total_error'));
                }
            }
            foreach (['application', 'vacancy', 'applicant'] as $entity) {
                $key = 'codes.'.$entity.'.format';
                if (! $this->has($key)) {
                    continue;
                }
                $format = (string) $this->input($key);
                $literal = str_replace(['{PREFIX}', '{YEAR}', '{YY}', '{MONTH}', '{DAY}', '{SEQ}'], '', $format);
                if (! str_contains($format, '{SEQ}') || str_contains($literal, '{') || str_contains($literal, '}')) {
                    $validator->errors()->add($key, __('settings.code_format_error'));
                }
            }
        }];
    }
}
