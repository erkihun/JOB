<?php

declare(strict_types=1);

namespace App\Services\Eligibility;

use App\Enums\EducationLevel;
use App\Models\Vacancy;
use App\Models\VacancyRequirement;
use Illuminate\Support\Str;

/**
 * Checks an applicant against a vacancy's alternative requirement groups.
 *
 * - Requirements within one group are ANDed.
 * - Groups are ORed: the applicant is eligible when ANY active group is fully met.
 * - A vacancy with no active groups has no structured rules and everyone is eligible.
 * - An education level satisfies any lower level (e.g. a Master's meets "Degree").
 */
class VacancyEligibilityChecker
{
    public function check(Vacancy $vacancy, EligibilityProfile $profile): EligibilityResult
    {
        $groups = $vacancy->relationLoaded('requirementGroups')
            ? $vacancy->requirementGroups
            : $vacancy->requirementGroups()->with('requirements')->get();

        $groups = $groups->filter(fn ($group) => $group->is_active && $group->requirements->isNotEmpty())->values();

        if ($groups->isEmpty()) {
            return new EligibilityResult(eligible: true, hasRules: false);
        }

        $failedOptions = [];

        foreach ($groups as $index => $group) {
            $option = $index + 1;
            $reasons = [];

            foreach ($group->requirements as $requirement) {
                array_push($reasons, ...$this->unmetReasons($requirement, $profile));
            }

            if ($reasons === []) {
                return new EligibilityResult(eligible: true, hasRules: true, matchedGroup: $group, matchedOption: $option);
            }

            $failedOptions[] = [
                'option' => $option,
                'label' => self::optionLabel($option, $group->title),
                'reasons' => $reasons,
            ];
        }

        return new EligibilityResult(eligible: false, hasRules: true, failedOptions: $failedOptions);
    }

    public static function optionLabel(int $option, ?string $title = null): string
    {
        $label = __('vacancies.requirement_option', ['number' => $option]);

        return filled($title) ? "{$label} ({$title})" : $label;
    }

    /**
     * @return array<int, string> empty when the requirement is fully met
     */
    private function unmetReasons(VacancyRequirement $requirement, EligibilityProfile $profile): array
    {
        $reasons = [];
        $notProvided = __('vacancies.not_provided');

        if ($requirement->education_level !== null
            && ! $this->meetsEducation($profile->educationLevel, $requirement->education_level)) {
            $reasons[] = __('vacancies.reason_education', [
                'level' => $requirement->education_level->getLabel(),
                'actual' => $profile->educationLevel?->getLabel() ?? $notProvided,
            ]);
        }

        if (filled($requirement->field_of_study)
            && ! $this->meetsFieldOfStudy($profile->fieldOfStudy, $requirement->field_of_study)) {
            $reasons[] = __('vacancies.reason_field', [
                'field' => $requirement->field_of_study,
                'actual' => $profile->fieldOfStudy ?? $notProvided,
            ]);
        }

        if ($requirement->min_experience_years > 0
            && $profile->experienceYears < $requirement->min_experience_years) {
            $reasons[] = __('vacancies.reason_experience', [
                'years' => $requirement->min_experience_years,
                'actual' => rtrim(rtrim(number_format($profile->experienceYears, 1), '0'), '.'),
            ]);
        }

        if ($requirement->min_gpa !== null
            && ($profile->gpa === null || $profile->gpa < (float) $requirement->min_gpa)) {
            $reasons[] = __('vacancies.reason_gpa', [
                'gpa' => number_format((float) $requirement->min_gpa, 2),
                'actual' => $profile->gpa !== null ? number_format($profile->gpa, 2) : $notProvided,
            ]);
        }

        $from = $requirement->graduation_year_from;
        $to = $requirement->graduation_year_to;
        if (($from || $to) && (
            $profile->graduationYear === null
            || ($from && $profile->graduationYear < $from)
            || ($to && $profile->graduationYear > $to)
        )) {
            $reasons[] = __('vacancies.reason_graduation', [
                'from' => $from ?? '…',
                'to' => $to ?? '…',
                'actual' => $profile->graduationYear ?? $notProvided,
            ]);
        }

        return $reasons;
    }

    private function meetsEducation(?EducationLevel $actual, EducationLevel $required): bool
    {
        if ($actual === null) {
            return false;
        }

        $rank = array_flip(array_map(fn (EducationLevel $l) => $l->value, EducationLevel::cases()));

        return $rank[$actual->value] >= $rank[$required->value];
    }

    /**
     * The requirement may list several accepted fields ("Computer Science, IT / Software").
     * A field matches when either text contains the other, ignoring case.
     */
    private function meetsFieldOfStudy(?string $actual, string $required): bool
    {
        $actual = Str::lower(trim((string) $actual));
        if ($actual === '') {
            return false;
        }

        $accepted = preg_split('/\s*(?:,|;|\/|\||\bor\b|ወይም)\s*/iu', Str::lower($required)) ?: [];

        foreach ($accepted as $field) {
            $field = trim($field);
            if ($field !== '' && (str_contains($actual, $field) || str_contains($field, $actual))) {
                return true;
            }
        }

        return false;
    }
}
