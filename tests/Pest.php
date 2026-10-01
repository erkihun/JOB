<?php

use App\Enums\ApplicationStatus;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\RecruitmentAnnouncement;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)
    ->beforeEach(function () {
        $this->withoutVite();
    })
    ->in('Feature');

/**
 * Applicant registration is only possible while at least one recruitment
 * announcement is accepting applications (RecruitmentTimelineService).
 */
function openRecruitmentWindow(array $attributes = []): RecruitmentAnnouncement
{
    return RecruitmentAnnouncement::factory()->create($attributes);
}

/**
 * A position under an announcement with the given period (date strings or
 * Carbon). Defaults to a published announcement.
 */
function lifecycleVacancy(string $opening, string $closing, array $announcement = [], array $vacancy = []): Vacancy
{
    $parent = RecruitmentAnnouncement::factory()->create($announcement + [
        'opening_date' => $opening,
        'closing_date' => $closing,
        'published_at' => now()->subDays(30),
    ]);

    return Vacancy::factory()->for($parent, 'announcement')->create($vacancy);
}

/** Application in a given stage for a vacancy (bypasses the submission window). */
function lifecycleApplication(Vacancy $vacancy, ApplicationStatus $status, ?Applicant $applicant = null): Application
{
    return Application::factory()->create([
        'vacancy_id' => $vacancy->id,
        'applicant_id' => ($applicant ?? Applicant::factory()->create())->id,
        'status' => $status,
    ]);
}
