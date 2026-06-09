<?php

declare(strict_types=1);

namespace App\Http\Requests\Application;

use App\Enums\VacancyStatus;
use App\Models\Application;
use App\Models\Vacancy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Application|null $application */
        $application = $this->route('application');

        return $this->user()->can('update', $application);
    }

    public function rules(): array
    {
        return [
            'vacancy_id' => ['nullable', 'uuid', 'exists:vacancies,id'],
            'field_of_study' => ['required', 'string', 'max:255'],
            'graduation_date' => ['required', 'date', 'before_or_equal:today'],
            'cgpa' => ['nullable', 'numeric', 'min:0', 'max:4'],
        ];
    }

    /**
     * Validate a position switch: the target vacancy must be open and the
     * applicant must not already have an application on it.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $vacancyId = $this->input('vacancy_id');

            /** @var Application|null $application */
            $application = $this->route('application');

            // No switch requested, or switching to the same vacancy → nothing to check.
            if (! $vacancyId || ! $application || $vacancyId === $application->vacancy_id) {
                return;
            }

            $vacancy = Vacancy::find($vacancyId);

            if (! $vacancy || ! $vacancy->canAcceptApplications() || $vacancy->status !== VacancyStatus::Open) {
                $validator->errors()->add('vacancy_id', __('vacancies.deadline_passed'));

                return;
            }

            $alreadyApplied = Application::where('applicant_id', $application->applicant_id)
                ->where('vacancy_id', $vacancyId)
                ->whereKeyNot($application->id)
                ->exists();

            if ($alreadyApplied) {
                $validator->errors()->add('vacancy_id', __('applications.duplicate_application'));
            }
        });
    }
}
