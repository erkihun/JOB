<?php

declare(strict_types=1);

namespace App\Services\Recruitment;

use App\Models\Applicant;
use App\Models\Application;

/**
 * Builds the per-application copy of the recruitment-relevant profile data.
 *
 * The applicant profile is reusable across recruitments; the snapshot records
 * what this particular application was judged on. It is refreshed while the
 * application is still editable and never touched again once it is read-only
 * (see RecruitmentTimelineService::canEditApplication()).
 *
 * Privacy: the national ID is stored only as a masked reference — the full
 * number stays in the applicant profile behind `applications.view-sensitive`.
 */
final class ApplicationSnapshotService
{
    public const VERSION = 1;

    /**
     * @return array<string, mixed>
     */
    public function build(Applicant $applicant, Application $application): array
    {
        return [
            'version' => self::VERSION,
            // Identity
            'first_name' => $applicant->first_name,
            'middle_name' => $applicant->middle_name,
            'last_name' => $applicant->last_name,
            'full_name' => $applicant->full_name,
            'gender' => $applicant->gender?->value,
            'date_of_birth' => $applicant->date_of_birth?->toDateString(),
            'nationality' => $applicant->nationality,
            'national_id_reference' => $this->maskNationalId($applicant->national_id),
            'disability_status' => (bool) $applicant->disability_status,
            'disability_type' => $applicant->disability_type,
            // Education (application-form values win over the profile)
            'education_level' => $applicant->education_level?->value,
            'university_name' => $applicant->university_name,
            'field_of_study' => $application->field_of_study ?: $applicant->field_of_study,
            'graduation_year' => $applicant->graduation_year !== null ? (int) $applicant->graduation_year : null,
            'graduation_date' => $application->graduation_date?->toDateString() ?? $applicant->graduation_date?->toDateString(),
            'gpa' => $application->cgpa ?? $applicant->gpa,
            // Work experience
            'work_experience_years' => (int) $applicant->work_experience_years,
            'work_experience_months' => $applicant->work_experience_months !== null ? (int) $applicant->work_experience_months : null,
            'current_employer' => $applicant->current_employer,
            'current_position' => $applicant->current_position,
            'work_experience_summary' => $applicant->work_experience_summary,
            // Contact & address
            'email' => $applicant->email,
            'phone' => $applicant->phone,
            'alternative_phone' => $applicant->alternative_phone,
            'region' => $applicant->getAttribute('region'),
            'city' => $applicant->getAttribute('city'),
            'woreda' => $applicant->getAttribute('woreda'),
            'address' => $applicant->address,
        ];
    }

    /** Capture (or refresh) the snapshot. Callers must have checked editability. */
    public function capture(Application $application, ?Applicant $applicant = null): void
    {
        $applicant ??= $application->applicant;

        if ($applicant === null) {
            return;
        }

        $application->forceFill([
            'profile_snapshot' => $this->build($applicant, $application),
            'snapshot_taken_at' => now(),
        ])->save();
    }

    private function maskNationalId(?string $nationalId): ?string
    {
        if (blank($nationalId)) {
            return null;
        }

        $digits = (string) $nationalId;

        return str_repeat('•', max(0, strlen($digits) - 4)).substr($digits, -4);
    }
}
