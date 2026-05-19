<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('+2519########'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'status' => 'active',
            'preferred_locale' => 'en',
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }

    public function superAdmin(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('super_admin');
        });
    }

    public function admin(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('admin');
        });
    }

    public function screeningOfficer(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('screening_officer');
        });
    }

    public function asApplicant(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('applicant');
        });
    }

    public function reportViewer(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('report_viewer');
        });
    }
}
