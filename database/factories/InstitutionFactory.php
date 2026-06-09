<?php

namespace Database\Factories;

use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstitutionFactory extends Factory
{
    protected $model = Institution::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'short_name' => strtoupper(fake()->lexify('???')),
            'code' => strtoupper(fake()->unique()->bothify('INST-####')),
            'type' => fake()->randomElement(['Government', 'University', 'NGO', 'Private']),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'status' => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }
}
