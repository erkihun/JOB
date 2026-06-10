<?php

declare(strict_types=1);

namespace App\Http\Requests\Application;

use App\Models\Setting;
use App\Models\Vacancy;
use App\Rules\SafeUploadRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->hasPermissionTo('applicant.applications.create'));
    }

    /**
     * Back-fill academic fields from the applicant profile when the apply form
     * omitted them (because the profile already supplies the value). The form
     * hides fields the profile has, so they arrive empty/absent here.
     */
    protected function prepareForValidation(): void
    {
        $applicant = $this->user()?->applicant;

        if (! $applicant) {
            return;
        }

        $defaults = $applicant->applicationDefaults();
        $merge = [];

        foreach (['field_of_study', 'graduation_date', 'cgpa'] as $field) {
            $submitted = $this->input($field);

            if (($submitted === null || $submitted === '') && ! empty($defaults[$field])) {
                $merge[$field] = $defaults[$field];
            }
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        $rules = [
            'field_of_study' => ['required', 'string', 'max:255'],
            'graduation_date' => ['required', 'date', 'before_or_equal:today'],
            'cgpa' => ['nullable', 'numeric', 'min:0', 'max:4'],
            'documents' => ['sometimes', 'array'],
        ];

        /** @var Vacancy|null $vacancy */
        $vacancy = $this->route('vacancy');

        // When the profile is fully complete the applicant applies in one click;
        // vacancy document uploads are not requested or required.
        $profileComplete = $this->user()?->applicant?->profileCompletionPercentage() === 100;

        if ($vacancy && ! $profileComplete) {
            foreach ($vacancy->requiredDocuments as $doc) {
                $maxKb = ($doc->max_size_mb ?? Setting::get('recruitment.max_file_size_mb', 2)) * 1024;
                $mimes = implode(',', $doc->allowed_types ?? (array) Setting::get('recruitment.allowed_file_types', ['pdf', 'jpg', 'jpeg', 'png']));
                $presence = $doc->is_required ? 'required' : 'nullable';

                $rules["documents.{$doc->id}"] = [
                    $presence,
                    'file',
                    "mimes:{$mimes}",
                    "max:{$maxKb}",
                    new SafeUploadRule,
                ];
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'documents.*.mimes' => __('applications.invalid_file_type'),
            'documents.*.max' => __('applications.file_too_large'),
            'documents.*.required' => __('applications.required_document_missing'),
        ];
    }
}
