<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Enums\EducationLevel;
use App\Models\Setting;
use App\Rules\SafeUploadRule;
use App\Services\Security\PasswordPolicyService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ApplicantRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $nationalId = $this->input('national_id');
        $phone = $this->input('phone');
        $alternativePhone = $this->input('alternative_phone');

        $this->merge([
            'national_id' => is_string($nationalId) ? preg_replace('/\D/', '', $nationalId) : $nationalId,
            'phone' => $this->normalizeEthiopianPhone(is_string($phone) ? $phone : null),
            'alternative_phone' => $this->normalizeEthiopianPhone(is_string($alternativePhone) ? $alternativePhone : null),
        ]);
    }

    public function rules(): array
    {
        $currentYear = (int) now()->format('Y');
        $maxKb = ((int) Setting::get('recruitment.max_file_size_mb', 2)) * 1024;
        $documentMimes = implode(',', (array) Setting::get('recruitment.allowed_file_types', ['pdf', 'jpg', 'jpeg', 'png']));

        return [
            // ── Personal ────────────────────────────────────────────────
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['required', 'date'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'national_id' => ['required', 'digits:16', 'unique:applicants,national_id'],

            // ── Disability ───────────────────────────────────────────────
            'disability_status' => ['required', 'boolean'],
            'disability_type' => [
                Rule::requiredIf(fn () => (bool) $this->input('disability_status')),
                'nullable', 'string', 'max:255',
            ],

            // ── Education ────────────────────────────────────────────────
            'university_name' => ['nullable', 'string', 'max:255'],
            'field_of_study' => ['nullable', 'string', 'max:255'],
            'graduation_year' => ['nullable', 'integer', 'min:1950', 'max:'.$currentYear],
            // GPA on a 4.0 scale (documented convention for this system)
            'gpa' => ['nullable', 'numeric', 'min:0', 'max:4'],
            'education_level' => ['nullable', Rule::in(array_column(EducationLevel::cases(), 'value'))],

            // ── Work experience ──────────────────────────────────────────
            'work_experience_years' => ['nullable', 'integer', 'min:0'],
            'work_experience_months' => ['nullable', 'integer', 'min:0', 'max:11'],
            'current_employer' => ['nullable', 'string', 'max:255'],
            'current_position' => ['nullable', 'string', 'max:255'],
            'work_experience_summary' => ['nullable', 'string', 'max:2000'],

            // ── Contact ──────────────────────────────────────────────────
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => [
                'required',
                'string',
                'regex:/^\+2519\d{8}$/',
                'unique:users,phone',
                'unique:applicants,phone',
            ],
            'alternative_phone' => ['nullable', 'string', 'regex:/^\+2519\d{8}$/', 'different:phone'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', 'unique:applicants,email'],

            // ── Account ──────────────────────────────────────────────────
            'password' => ['required', 'confirmed', ...app(PasswordPolicyService::class)->applicantRules()],
            'preferred_locale' => ['required', Rule::in(['en', 'am'])],

            // ── Documents ────────────────────────────────────────────────
            'profile_photo' => ['prohibited'],
            'documents' => [
                'required',
                'file',
                "mimes:{$documentMimes}",
                "max:{$maxKb}",
                // Hard denylist of script-capable types, independent of the
                // configurable allow-list, so SVG/HTML can never be stored even
                // if an admin mistakenly adds them to allowed_file_types.
                new SafeUploadRule,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.unique' => __('validation.phone_taken'),
            'phone.regex' => app()->getLocale() === 'am'
                ? 'እባክዎ ትክክለኛ የኢትዮጵያ ሞባይል ቁጥር ያስገቡ።'
                : 'Enter a valid Ethiopian mobile number.',
            'alternative_phone.regex' => app()->getLocale() === 'am'
                ? 'እባክዎ ትክክለኛ የኢትዮጵያ ሞባይል ቁጥር ያስገቡ።'
                : 'Enter a valid Ethiopian mobile number.',
            'middle_name.required' => __('validation.required', ['attribute' => __('fields.middle_name')]),
            'national_id.unique' => __('validation.national_id_taken'),
            'national_id.digits' => __('validation.digits', ['attribute' => __('fields.national_id'), 'digits' => 16]),
            'email.unique' => __('validation.email_taken'),
            'documents.required' => __('validation.required', ['attribute' => __('documents.type_documents')]),
            'profile_photo.prohibited' => __('validation.prohibited', ['attribute' => __('fields.profile_photo')]),
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        // Save valid uploaded files to temp storage so they survive the redirect
        if ($this->hasFile('documents') && ! $validator->errors()->has('documents')) {
            $file = $this->file('documents');
            $ext = $file->getClientOriginalExtension();
            $path = $file->storeAs('temp/reg-docs', Str::random(32).'.'.$ext, 'local');
            session([
                'reg_temp_docs' => $path,
                'reg_temp_docs_name' => $file->getClientOriginalName(),
            ]);
        }

        parent::failedValidation($validator);
    }

    private function normalizeEthiopianPhone(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return $value;
        }

        $digits = preg_replace('/\D/', '', $value);

        if (preg_match('/^09\d{8}$/', (string) $digits) === 1) {
            return '+251'.substr((string) $digits, 1);
        }

        if (preg_match('/^2519\d{8}$/', (string) $digits) === 1) {
            return '+'.$digits;
        }

        if (preg_match('/^9\d{8}$/', (string) $digits) === 1) {
            return '+251'.$digits;
        }

        return trim($value);
    }
}
