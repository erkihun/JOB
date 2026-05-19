<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition(): array
    {
        return [
            'applicant_id' => ApplicantFactory::new(),
            'vacancy_id' => VacancyFactory::new()->open(),
            'reference_number' => 'APP-'.now()->year.'-'.str_pad(fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'field_of_study' => 'Computer Science',
            'graduation_date' => now()->subYears(2),
            'status' => ApplicationStatus::Submitted,
            'submitted_at' => now(),
        ];
    }
}
