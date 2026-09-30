<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\EducationLevel;
use App\Enums\EmploymentType;
use App\Enums\VacancyStatus;
use App\Models\RecruitmentAnnouncement;
use App\Services\CodeGeneratorService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveVacancyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can($this->route('vacancy') ? 'vacancies.update' : 'vacancies.create');
    }

    public function rules(): array
    {
        $ignoreId = $this->route('vacancy')?->id;
        $autoCode = app(CodeGeneratorService::class)->vacancyAutoGenerate();
        $codeRule = $autoCode
            ? ['nullable', 'string', 'max:50']
            : ['required', 'string', 'max:50', Rule::unique('vacancies', 'code')->ignore($ignoreId)];

        return [
            'institution_id' => ['nullable', 'uuid', 'exists:institutions,id'],
            'code' => $codeRule,
            'title' => ['required', 'array'],
            'title.en' => ['required', 'string', 'max:255'],
            'title.am' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(VacancyStatus::class)],
            'employment_type' => ['nullable', Rule::enum(EmploymentType::class)],
            'number_of_positions' => ['required', 'integer', 'min:1'],
            'salary_grade' => ['nullable', 'string', 'max:100'],
            'location' => ['required', 'array'],
            'location.en' => ['required', 'string', 'max:255'],
            'location.am' => ['nullable', 'string', 'max:255'],
            'announcement_id' => ['required', 'integer', Rule::exists('recruitment_announcements', 'id')->whereNull('deleted_at')->whereNotNull('opening_date')->whereNotNull('closing_date')],
            'description' => ['nullable', 'array'],
            'description.en' => ['nullable', 'string'],
            'description.am' => ['nullable', 'string'],
            'qualification_requirements' => ['nullable', 'array'],
            'qualification_requirements.en' => ['nullable', 'string'],
            'qualification_requirements.am' => ['nullable', 'string'],

            // Eligibility requirement options (OR between options, AND within one)
            'requirement_groups' => ['nullable', 'array', 'max:10'],
            'requirement_groups.*.title' => ['nullable', 'string', 'max:255'],
            'requirement_groups.*.is_active' => ['nullable', 'boolean'],
            'requirement_groups.*.requirements' => ['nullable', 'array', 'max:10'],
            'requirement_groups.*.requirements.*.education_level' => ['nullable', Rule::enum(EducationLevel::class)],
            'requirement_groups.*.requirements.*.field_of_study' => ['nullable', 'string', 'max:255'],
            'requirement_groups.*.requirements.*.min_experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'requirement_groups.*.requirements.*.min_gpa' => ['nullable', 'numeric', 'min:0', 'max:4'],
            'requirement_groups.*.requirements.*.graduation_year_from' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'requirement_groups.*.requirements.*.graduation_year_to' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'requirement_groups.*.requirements.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || ! $this->filled('institution_id')) {
                return;
            }
            $announcement = RecruitmentAnnouncement::find($this->integer('announcement_id'));
            if (! $announcement?->institutions()->whereKey($this->input('institution_id'))->exists()) {
                $validator->errors()->add('institution_id', __('vacancies.announcement_institution_mismatch'));
            }
        }];
    }
}
