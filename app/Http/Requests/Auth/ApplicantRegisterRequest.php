<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Enums\EducationLevel;
use App\Models\Setting;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ApplicantRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $currentYear = (int) now()->format('Y');
        $maxKb = ((int) Setting::get('recruitment.max_file_size_mb', 2)) * 1024;
        $documentMimes = implode(',', (array) Setting::get('recruitment.allowed_file_types', ['pdf', 'jpg', 'jpeg', 'png']));

        return [
            // ── Personal ────────────────────────────────────────────────
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'national_id' => ['required', 'string', 'max:50', 'unique:applicants,national_id'],

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
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone', 'unique:applicants,phone'],
            'alternative_phone' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', 'unique:applicants,email'],

            // ── Account ──────────────────────────────────────────────────
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'preferred_locale' => ['required', Rule::in(['en', 'am'])],
            'terms' => ['required', 'accepted'],

            // ── Documents ────────────────────────────────────────────────
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', "max:{$maxKb}"],
            'documents' => ['nullable', 'file', "mimes:{$documentMimes}", "max:{$maxKb}"],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.unique' => __('validation.phone_taken'),
            'national_id.unique' => __('validation.national_id_taken'),
            'email.unique' => __('validation.email_taken'),
            'terms.accepted' => __('validation.terms_required'),
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        // Save valid uploaded files to temp storage so they survive the redirect
        if ($this->hasFile('profile_photo') && ! $validator->errors()->has('profile_photo')) {
            $file = $this->file('profile_photo');
            $ext = $file->getClientOriginalExtension();
            $path = $file->storeAs('temp/reg-photos', Str::random(32).'.'.$ext, 'local');
            session(['reg_temp_photo' => $path]);
        }

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
}
