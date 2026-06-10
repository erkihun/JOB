<?php

declare(strict_types=1);

namespace App\Http\Requests\Applicant;

use App\Enums\EducationLevel;
use App\Models\Setting;
use App\Rules\SafeUploadRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicantProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->hasRole('applicant'));
    }

    public function rules(): array
    {
        $applicantId = $this->user()->applicant->id;
        $currentYear = (int) now()->format('Y');
        $maxKb = ((int) Setting::get('recruitment.max_file_size_mb', 2)) * 1024;
        $documentMimes = implode(',', (array) Setting::get('recruitment.allowed_file_types', ['pdf', 'jpg', 'jpeg', 'png']));

        return [
            // Name
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],

            // Personal
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20',
                Rule::unique('applicants', 'phone')->ignore($applicantId)],
            'alternative_phone' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255',
                Rule::unique('applicants', 'email')->ignore($applicantId)],
            'national_id' => ['required', 'string', 'min:3', 'max:50',
                Rule::unique('applicants', 'national_id')->ignore($applicantId)],

            // Disability
            'disability_status' => ['required', 'boolean'],
            'disability_type' => [
                Rule::requiredIf(fn () => (bool) $this->input('disability_status')),
                'nullable', 'string', 'max:255',
            ],

            // Education
            'university_name' => ['nullable', 'string', 'max:255'],
            'field_of_study' => ['nullable', 'string', 'max:255'],
            'graduation_year' => ['nullable', 'integer', 'min:1950', 'max:'.$currentYear],
            'gpa' => ['nullable', 'numeric', 'min:0', 'max:4'],
            'education_level' => ['nullable', Rule::in(array_column(EducationLevel::cases(), 'value'))],

            // Work experience
            'work_experience_years' => ['nullable', 'integer', 'min:0'],
            'work_experience_months' => ['nullable', 'integer', 'min:0', 'max:11'],
            'current_employer' => ['nullable', 'string', 'max:255'],
            'current_position' => ['nullable', 'string', 'max:255'],
            'work_experience_summary' => ['nullable', 'string', 'max:2000'],

            // Address
            'address' => ['nullable', 'string', 'max:1000'],

            // Preferences
            'preferred_locale' => ['required', Rule::in(['en', 'am'])],
            'ethnicity' => ['nullable', 'string', 'max:100'],

            // Profile photo (optional update)
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', "max:{$maxKb}", new SafeUploadRule],

            // Combined documents PDF (optional — replaces existing when uploaded)
            'documents' => ['nullable', 'file', "mimes:{$documentMimes}", "max:{$maxKb}", new SafeUploadRule],
        ];
    }
}
