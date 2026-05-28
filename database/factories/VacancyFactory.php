<?php

namespace Database\Factories;

use App\Enums\VacancyStatus;
use App\Models\Institution;
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
            'opening_date' => now()->subDay(),
            'closing_date' => now()->addDays(30),
            'status' => VacancyStatus::Open,
            'published_at' => now()->subDay(),
            'created_by' => User::factory()->admin(),
        ];
    }

    public function open(): static
    {
        return $this->state([
            'status' => VacancyStatus::Open,
            'opening_date' => now()->subDay(),
            'closing_date' => now()->addDays(30),
        ]);
    }

    public function closed(): static
    {
        return $this->state([
            'status' => VacancyStatus::Closed,
            'opening_date' => now()->subDays(60),
            'closing_date' => now()->subDays(5),
        ]);
    }

    public function pastDeadline(): static
    {
        return $this->state([
            'status' => VacancyStatus::Open,
            'opening_date' => now()->subDays(60),
            'closing_date' => now()->subDay(),
        ]);
    }

    public function draft(): static
    {
        return $this->state([
            'status' => VacancyStatus::Draft,
        ]);
    }
}
