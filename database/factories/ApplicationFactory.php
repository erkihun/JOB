<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Services\Recruitment\RecruitmentTimelineService;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition(): array
    {
        return [
            'applicant_id' => ApplicantFactory::new(),
            // Realistic timeline by default: an application still awaiting screening
            // sits under an open announcement; one that has been screened or moved on
            // belongs to a recruitment whose application period has closed.
            'vacancy_id' => function (array $attributes) {
                $status = $attributes['status'] instanceof ApplicationStatus
                    ? $attributes['status'] : ApplicationStatus::tryFrom((string) $attributes['status']);

                return in_array($status, RecruitmentTimelineService::AWAITING_SCREENING_STATUSES, true)
                    ? VacancyFactory::new()->open()
                    : VacancyFactory::new()->pastDeadline();
            },
            'reference_number' => 'APP-'.now()->year.'-'.str_pad(fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'field_of_study' => 'Computer Science',
            'graduation_date' => now()->subYears(2),
            'status' => ApplicationStatus::Submitted,
            'submitted_at' => now(),
        ];
    }

    /** Submitted to a recruitment whose application period has already closed. */
    public function afterDeadline(): static
    {
        return $this->state(['vacancy_id' => VacancyFactory::new()->pastDeadline()]);
    }
}
