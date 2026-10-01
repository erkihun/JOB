<?php

declare(strict_types=1);

use App\Actions\Screening\ReviewApplicationAction;
use App\Enums\ApplicationStatus;
use App\Enums\ScreeningDecision;
use App\Exceptions\RecruitmentRuleException;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Queue::fake();
    $this->travelTo('2026-10-01 12:00:00');
    $this->officer = User::factory()->screeningOfficer()->create();
});

test('a pass or fail decision before the application period closes is blocked', function (ScreeningDecision $decision): void {
    $vacancy = lifecycleVacancy('2026-09-20', '2026-10-10');
    $application = lifecycleApplication($vacancy, ApplicationStatus::Submitted);

    $this->actingAs($this->officer)
        ->post(route('admin.screening.submit', $application), ['decision' => $decision->value, 'remark' => 'Does not meet the GPA requirement.'])
        ->assertSessionHasErrors(['decision' => __('recruitment.errors.screening_before_close')]);

    expect(fn () => app(ReviewApplicationAction::class)->handle($application, $this->officer, $decision, 'Remark'))
        ->toThrow(RecruitmentRuleException::class, __('recruitment.errors.screening_before_close'));
    expect($application->refresh()->status)->toBe(ApplicationStatus::Submitted)
        ->and($vacancy->announcement->refresh()->status)->toBe('published');
})->with([ScreeningDecision::Passed, ScreeningDecision::Failed]);

test('asking for a correction while the window is open stays possible (documented exception)', function (): void {
    $vacancy = lifecycleVacancy('2026-09-20', '2026-10-10');
    $application = lifecycleApplication($vacancy, ApplicationStatus::Submitted);

    app(ReviewApplicationAction::class)->handle($application, $this->officer, ScreeningDecision::CorrectionRequired, 'Upload a clearer degree.');

    expect($application->refresh()->status)->toBe(ApplicationStatus::CorrectionRequired)
        ->and($application->isEditable())->toBeTrue()
        ->and($vacancy->announcement->refresh()->status)->toBe('published');
});

test('screening after the closing date is allowed and moves the recruitment into the Screening stage', function (): void {
    $vacancy = lifecycleVacancy('2026-09-01', '2026-09-30');
    $application = lifecycleApplication($vacancy, ApplicationStatus::Submitted);

    $this->actingAs($this->officer)
        ->post(route('admin.screening.submit', $application), ['decision' => 'passed'])
        ->assertSessionHasNoErrors();

    expect($application->refresh()->status)->toBe(ApplicationStatus::PassedScreening)
        ->and($vacancy->announcement->refresh()->status)->toBe('screening')
        ->and(AuditLog::where('action', 'announcement_closed')->where('record_id', (string) $vacancy->announcement_id)->exists())->toBeTrue()
        ->and(AuditLog::where('action', 'screening_started')->where('record_id', (string) $vacancy->announcement_id)->exists())->toBeTrue();

    // A second decision does not create a duplicate stage transition.
    $second = lifecycleApplication($vacancy, ApplicationStatus::Submitted);
    app(ReviewApplicationAction::class)->handle($second, $this->officer, ScreeningDecision::Failed, 'Missing degree');
    expect(AuditLog::where('action', 'screening_started')->count())->toBe(1);
});

test('the explicit Start screening action waits for the closing date and needs screening.start', function (): void {
    $vacancy = lifecycleVacancy('2026-09-20', '2026-10-10');
    $announcement = $vacancy->announcement;
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.announcements.transition', $announcement), ['target' => 'screening'])
        ->assertSessionHasErrors('status');

    $this->travelTo('2026-10-11 09:00:00');
    $this->flushSession(); // a 10-day jump would otherwise trip the idle-session timeout
    $this->actingAs($this->officer)->post(route('admin.announcements.transition', $announcement), ['target' => 'screening'])
        ->assertForbidden();

    $this->actingAs($admin)->post(route('admin.announcements.transition', $announcement), ['target' => 'screening'])
        ->assertSessionHasNoErrors();
    expect($announcement->refresh()->status)->toBe('screening');
});

test('screening decisions are frozen once the recruitment is finalized', function (): void {
    $vacancy = lifecycleVacancy('2026-09-01', '2026-09-30', ['status' => 'finalized']);
    $application = lifecycleApplication($vacancy, ApplicationStatus::Submitted);

    expect(fn () => app(ReviewApplicationAction::class)->handle($application, $this->officer, ScreeningDecision::Passed))
        ->toThrow(RecruitmentRuleException::class, __('recruitment.errors.stage_not_allowed'));
});
