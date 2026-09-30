<?php

declare(strict_types=1);

use App\Enums\ApplicationStatus;
use App\Enums\ScreeningDecision;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\ScreeningReview;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->admin()->create();
    Queue::fake();
});

function screenedApplication(ApplicationStatus $status = ApplicationStatus::PassedScreening): Application
{
    return Application::factory()->create([
        'status' => $status,
        'screening_status' => $status === ApplicationStatus::FailedScreening ? ScreeningDecision::Failed : ScreeningDecision::Passed,
    ]);
}

test('a recorded decision cannot be overwritten through the normal decision form', function (): void {
    $application = screenedApplication();

    $this->actingAs($this->admin)
        ->post(route('admin.screening.submit', $application), ['decision' => 'failed', 'remark' => 'Trying to overwrite it.'])
        ->assertRedirect(route('admin.screening.review', $application))
        ->assertSessionHas('error', __('messages.decision_already_recorded'));

    expect($application->refresh()->status)->toBe(ApplicationStatus::PassedScreening);
});

test('review page shows the locked decision instead of the decision form', function (): void {
    $application = screenedApplication();

    $this->actingAs($this->admin)->get(route('admin.screening.review', $application))
        ->assertOk()
        ->assertSee(__('messages.decision_locked'))
        ->assertSee(__('messages.change_decision'))
        ->assertDontSee('action="'.route('admin.screening.submit', $application).'"', false);
});

test('a user without permission cannot change a decision', function (): void {
    $officer = User::factory()->screeningOfficer()->create();
    $application = screenedApplication();

    $this->actingAs($officer)->get(route('admin.screening.review', $application))
        ->assertOk()
        ->assertSee(__('messages.decision_change_no_permission'))
        ->assertDontSee(__('messages.change_decision'));

    $this->actingAs($officer)
        ->post(route('admin.screening.change-decision', $application), ['decision' => 'failed', 'reason' => 'Officer wants to change it.'])
        ->assertForbidden();

    expect($application->refresh()->status)->toBe(ApplicationStatus::PassedScreening);
});

test('an authorised user can change a decision with a reason', function (): void {
    $application = screenedApplication();

    // Reason is required
    $this->actingAs($this->admin)
        ->post(route('admin.screening.change-decision', $application), ['decision' => 'failed', 'reason' => ''])
        ->assertSessionHasErrors('reason');
    expect($application->refresh()->status)->toBe(ApplicationStatus::PassedScreening);

    $this->actingAs($this->admin)
        ->post(route('admin.screening.change-decision', $application), ['decision' => 'failed', 'reason' => 'Degree certificate turned out to be invalid.'])
        ->assertRedirect(route('admin.screening.review', $application))
        ->assertSessionHas('success', __('messages.decision_changed'));

    $application->refresh();
    expect($application->status)->toBe(ApplicationStatus::FailedScreening)
        ->and($application->screening_status)->toBe(ScreeningDecision::Failed)
        ->and(ScreeningReview::where('application_id', $application->id)->latest('reviewed_at')->first()->remark)
            ->toBe('Degree certificate turned out to be invalid.')
        ->and(AuditLog::where('action', 'screening_decision_changed')->where('record_id', $application->id)->exists())->toBeTrue();
});

test('changing to the same decision is rejected', function (): void {
    $application = screenedApplication();

    $this->actingAs($this->admin)
        ->post(route('admin.screening.change-decision', $application), ['decision' => 'passed', 'reason' => 'No real change here at all.'])
        ->assertSessionHasErrors('decision');
});

test('decision cannot be changed once the application moved past screening', function (): void {
    $application = Application::factory()->create([
        'status' => ApplicationStatus::ShortlistedExam,
        'screening_status' => ScreeningDecision::Passed,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.screening.change-decision', $application), ['decision' => 'failed', 'reason' => 'Too late to change this one.'])
        ->assertSessionHasErrors('decision');

    expect($application->refresh()->status)->toBe(ApplicationStatus::ShortlistedExam);

    $this->actingAs($this->admin)->get(route('admin.screening.review', $application))
        ->assertOk()
        ->assertSee(__('messages.decision_change_stage_locked'));
});
