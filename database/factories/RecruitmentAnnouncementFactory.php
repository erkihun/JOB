<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\RecruitmentAnnouncement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecruitmentAnnouncementFactory extends Factory
{
    protected $model = RecruitmentAnnouncement::class;

    public function definition(): array
    {
        return [
            'subject' => fake()->sentence(),
            'code' => 'ANN-'.fake()->unique()->uuid(),
            'content' => '',
            'opening_date' => today()->subDay(),
            'closing_date' => today()->addDays(30),
            'status' => 'published',
            'published_at' => now()->subDay(),
            'created_by' => User::factory()->admin(),
        ];
    }
}
