<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\VacancyStatus;
use App\Models\Institution;
use App\Models\RecruitmentAnnouncement;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Factories\Factory;

class VacancyFactory extends Factory
{
    protected $model = Vacancy::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'title' => ['en' => fake()->jobTitle(), 'am' => fake()->jobTitle()],
            'code' => strtoupper(fake()->unique()->bothify('VAC-####')),
            'department' => fake()->randomElement(['IT', 'Finance', 'HR', 'Legal', 'Operations']),
            'employment_type' => 'permanent',
            'location' => ['en' => 'Addis Ababa', 'am' => 'አዲስ አበባ'],
            'number_of_positions' => fake()->numberBetween(1, 10),
            'salary_grade' => null,
            'description' => ['en' => fake()->paragraphs(2, true), 'am' => fake()->paragraphs(2, true)],
            'qualification_requirements' => ['en' => fake()->paragraph(), 'am' => fake()->paragraph()],
            'field_of_study' => fake()->randomElement(['Computer Science', 'Accounting', 'Law', 'Engineering']),
            'minimum_experience' => 24,
            'announcement_id' => RecruitmentAnnouncement::factory(),
            'status' => VacancyStatus::Open,
            'published_at' => now()->subDay(),
            'created_by' => User::factory()->admin(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Vacancy $vacancy): void {
            if ($vacancy->institution_id) {
                $vacancy->announcement->institutions()->syncWithoutDetaching([$vacancy->institution_id]);
            }
        });
    }

    public function open(): static
    {
        return $this->state([
            'status' => VacancyStatus::Open,
        ]);
    }

    public function closed(): static
    {
        return $this->state([
            'status' => VacancyStatus::Closed,
            'announcement_id' => RecruitmentAnnouncement::factory()->state(['opening_date' => now()->subDays(60), 'closing_date' => now()->subDays(5)]),
        ]);
    }

    public function pastDeadline(): static
    {
        return $this->state([
            'status' => VacancyStatus::Open,
            'announcement_id' => RecruitmentAnnouncement::factory()->state(['opening_date' => now()->subDays(60), 'closing_date' => now()->subDay()]),
        ]);
    }

    public function draft(): static
    {
        return $this->state([
            'status' => VacancyStatus::Draft,
        ]);
    }
}
