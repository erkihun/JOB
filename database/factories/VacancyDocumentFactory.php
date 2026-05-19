<?php

namespace Database\Factories;

use App\Models\Vacancy;
use App\Models\VacancyDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

class VacancyDocumentFactory extends Factory
{
    protected $model = VacancyDocument::class;

    public function definition(): array
    {
        return [
            'vacancy_id' => Vacancy::factory(),
            'document_name' => fake()->randomElement(['CV', 'Degree Certificate', 'ID Card', 'Transcript']),
            'is_required' => true,
            'allowed_types' => ['pdf', 'jpg', 'jpeg', 'png'],
            'max_size_mb' => 2,
        ];
    }

    public function optional(): static
    {
        return $this->state(['is_required' => false]);
    }
}
