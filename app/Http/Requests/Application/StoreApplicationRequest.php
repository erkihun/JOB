<?php

declare(strict_types=1);

namespace App\Http\Requests\Application;

use App\Models\Vacancy;
use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->hasPermissionTo('applicant.applications.create'));
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

        if ($vacancy) {
            foreach ($vacancy->requiredDocuments as $doc) {
                $maxKb = ($doc->max_size_mb ?? 2) * 1024;
                $mimes = implode(',', $doc->allowed_types ?? ['pdf', 'jpg', 'jpeg', 'png']);
                $presence = $doc->is_required ? 'required' : 'nullable';

                $rules["documents.{$doc->id}"] = [
                    $presence,
                    'file',
                    "mimes:{$mimes}",
                    "max:{$maxKb}",
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
