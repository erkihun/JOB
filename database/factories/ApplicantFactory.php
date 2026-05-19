<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Applicant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApplicantFactory extends Factory
{
    protected $model = Applicant::class;

    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'user_id' => User::factory()->asApplicant(),
            'first_name' => $firstName,
            'middle_name' => null,
            'last_name' => $lastName,
            'full_name' => "$firstName $lastName",
            'phone' => fake()->unique()->numerify('+2519########'),
            'alternative_phone' => null,
            'email' => fake()->unique()->safeEmail(),
            'national_id' => fake()->unique()->numerify('ETH##########'),
            'gender' => fake()->randomElement(['male', 'female']),
            'date_of_birth' => null,
            'nationality' => null,
            'disability_status' => false,
            'disability_type' => null,
            'ethnicity' => null,
            'university_name' => null,
            'field_of_study' => null,
            'graduation_year' => null,
            'graduation_date' => null,
            'gpa' => null,
            'education_level' => null,
            'work_experience_years' => 0,
            'work_experience_months' => null,
            'current_employer' => null,
            'current_position' => null,
            'work_experience_summary' => null,
            'region' => null,
            'city' => null,
            'woreda' => null,
            'address' => null,
            'preferred_locale' => 'en',
            'profile_photo_path' => null,
        ];
    }
}
