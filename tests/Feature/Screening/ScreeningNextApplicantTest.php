<?php

declare(strict_types=1);

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\User;
use App\Models\Vacancy;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->admin()->create();
    Queue::fake();
});

function queuedApplication(array $attributes, int $minutesAgo): Application
{
    $application = Application::factory()->afterDeadline()->create($attributes + ['status' => ApplicationStatus::Submitted]);
    $application->forceFill(['created_at' => now()->subMinutes($minutesAgo)])->saveQuietly();

    return $application;
}

test('saving a decision opens the next applicant in the queue', function (): void {
    $vacancy = Vacancy::factory()->pastDeadline()->create();
    $first = queuedApplication(['vacancy_id' => $vacancy->id], 1);
    $second = queuedApplication(['vacancy_id' => $vacancy->id], 2);
    queuedApplication(['status' => ApplicationStatus::PassedScreening], 3); // already screened

    $this->actingAs($this->admin)
        ->post(route('admin.screening.submit', $first), ['decision' => 'passed'])
        ->assertRedirect(route('admin.screening.review', $second))
        ->assertSessionHas('success');

    expect($first->fresh()->status)->toBe(ApplicationStatus::PassedScreening);

    // Last one: back to the queue with a "done" message
    $this->actingAs($this->admin)
        ->post(route('admin.screening.submit', $second), ['decision' => 'failed', 'remark' => 'Missing required degree certificate.'])
        ->assertRedirect(route('admin.screening.index'))
        ->assertSessionHas('success', __('messages.screening_queue_done'));
});

test('the vacancy filter from the queue is kept when moving to the next applicant', function (): void {
    [$a, $b] = Vacancy::factory()->pastDeadline()->count(2)->create();
    $current = queuedApplication(['vacancy_id' => $a->id], 1);
    queuedApplication(['vacancy_id' => $b->id], 2); // other vacancy, skipped
    $nextSame = queuedApplication(['vacancy_id' => $a->id], 3);

    $this->actingAs($this->admin)
        ->get(route('admin.screening.review', ['application' => $current->id, 'vacancy_id' => $a->id]))
        ->assertOk()
        ->assertSee('name="queue_vacancy_id" value="'.$a->id.'"', false)
        ->assertSee(__('messages.save_and_next'));

    $this->actingAs($this->admin)
        ->post(route('admin.screening.submit', $current), ['decision' => 'passed', 'queue_vacancy_id' => $a->id])
        ->assertRedirect(route('admin.screening.review', ['application' => $nextSame->id, 'vacancy_id' => $a->id]));
});

test('a screening officer is only sent to applications they may screen', function (): void {
    $officer = User::factory()->screeningOfficer()->create();
    $other = User::factory()->screeningOfficer()->create();
    $current = queuedApplication(['assigned_reviewer_id' => $officer->id], 1);
    queuedApplication(['assigned_reviewer_id' => $other->id], 2);
    $mine = queuedApplication(['assigned_reviewer_id' => $officer->id], 3);

    $this->actingAs($officer)
        ->post(route('admin.screening.submit', $current), ['decision' => 'passed'])
        ->assertRedirect(route('admin.screening.review', $mine));
});
