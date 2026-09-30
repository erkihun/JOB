<?php

declare(strict_types=1);

namespace App\Services\Eligibility;

use App\Enums\EducationLevel;
use App\Models\Applicant;
use App\Models\Application;
use Illuminate\Support\Carbon;

/**
 * The applicant facts that eligibility rules are checked against.
 */
final readonly class EligibilityProfile
{
    public function __construct(
        public ?EducationLevel $educationLevel = null,
        public ?string $fieldOfStudy = null,
        public ?float $gpa = null,
        public ?int $graduationYear = null,
        public float $experienceYears = 0.0,
    ) {}

    /**
     * Build from the applicant's profile. Values typed on the application form
     * (field_of_study, graduation_date, cgpa) take precedence over the profile.
     *
     * @param  array<string, mixed>  $applicationData
     */
    public static function fromApplicant(Applicant $applicant, array $applicationData = []): self
    {
        $graduationYear = null;
        $graduationDate = $applicationData['graduation_date'] ?? $applicant->graduation_date;
        if (filled($graduationDate)) {
            $graduationYear = Carbon::parse($graduationDate)->year;
        } elseif (filled($applicant->graduation_year)) {
            $graduationYear = (int) $applicant->graduation_year;
        }

        $gpa = $applicationData['cgpa'] ?? $applicant->gpa;

        return new self(
            educationLevel: $applicant->education_level,
            fieldOfStudy: ($applicationData['field_of_study'] ?? null) ?: ($applicant->field_of_study ?: null),
            gpa: filled($gpa) ? (float) $gpa : null,
            graduationYear: $graduationYear,
            experienceYears: (int) $applicant->work_experience_years + ((int) $applicant->work_experience_months / 12),
        );
    }

    public static function fromApplication(Application $application): self
    {
        return self::fromApplicant($application->applicant, [
            'field_of_study' => $application->field_of_study,
            'graduation_date' => $application->graduation_date,
            'cgpa' => $application->cgpa,
        ]);
    }
}
