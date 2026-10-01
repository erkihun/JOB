<?php

declare(strict_types=1);

use App\Enums\ApplicationStatus;
use App\Enums\RecruitmentStage;
use App\Enums\RecruitmentStatus;
use App\Exceptions\RecruitmentRuleException;
use App\Models\AuditLog;
use App\Models\RecruitmentAnnouncement;
use App\Models\User;
use App\Services\Recruitment\RecruitmentStateMachine;
use App\Services\Recruitment\RecruitmentTimelineService;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Queue::fake();
    $this->travelTo('2026-10-01 12:00:00');
    $this->admin = User::factory()->admin()->create();
});

// ── Announcement lifecycle ──────────────────────────────────────────────────

test('effective stage follows the dates of a published announcement', function (string $opening, string $closing, RecruitmentStage $expected): void {
    $announcement = RecruitmentAnnouncement::factory()->create(['opening_date' => $opening, 'closing_date' => $closing]);

    expect(app(RecruitmentTimelineService::class)->stage($announcement))->toBe($expected);
})->with([
    'upcoming' => ['2026-10-05', '2026-10-20', RecruitmentStage::Upcoming],
    'open' => ['2026-09-20', '2026-10-10', RecruitmentStage::Open],
    'closes today' => ['2026-09-20', '2026-10-01', RecruitmentStage::Open],
    'effectively closed' => ['2026-09-01', '2026-09-30', RecruitmentStage::Closed],
]);

test('invalid announcement transitions are rejected', function (string $from, RecruitmentStatus $to): void {
    $announcement = RecruitmentAnnouncement::factory()->create([
        'status' => $from, 'opening_date' => '2026-09-01', 'closing_date' => '2026-09-30',
    ]);

    expect(fn () => app(RecruitmentStateMachine::class)->transitionAnnouncement($announcement, $to))
        ->toThrow(RecruitmentRuleException::class);
    expect($announcement->refresh()->status)->toBe($from);
})->with([
    'draft straight to interview' => ['draft', RecruitmentStatus::Interview],
    'draft straight to screening' => ['draft', RecruitmentStatus::Screening],
    'published to exam' => ['published', RecruitmentStatus::Exam],
    'closed back to published without an extension' => ['closed', RecruitmentStatus::Published],
    'screening to interview when the exam is required' => ['screening', RecruitmentStatus::Interview],
    'exam back to screening' => ['exam', RecruitmentStatus::Screening],
    'finalized to anything' => ['finalized', RecruitmentStatus::Cancelled],
    'cancelled to published' => ['cancelled', RecruitmentStatus::Published],
]);

test('an open announcement cannot start the exam stage', function (): void {
    $vacancy = lifecycleVacancy('2026-09-20', '2026-10-10');

    $this->actingAs($this->admin)->post(route('admin.announcements.transition', $vacancy->announcement), ['target' => 'exam'])
        ->assertSessionHasErrors('status');

    expect($vacancy->announcement->refresh()->status)->toBe('published');
});

test('the browser cannot submit an arbitrary status', function (): void {
    $vacancy = lifecycleVacancy('2026-09-01', '2026-09-28', ['status' => 'closed']);

    $this->actingAs($this->admin)->post(route('admin.announcements.transition', $vacancy->announcement), ['target' => 'published'])
        ->assertSessionHasErrors('target');
    $this->put(route('admin.announcements.update', $vacancy->announcement), [
        'subject' => 'Still closed', 'code' => $vacancy->announcement->code, 'status' => 'draft',
        'institution_ids' => $vacancy->announcement->institutions->modelKeys(),
    ]);

    expect($vacancy->announcement->refresh()->status)->toBe('closed');
});

test('a user without the finalize permission cannot finalize', function (): void {
    $vacancy = lifecycleVacancy('2026-09-01', '2026-09-28', ['status' => 'interview']);
    $officer = User::factory()->create();
    $officer->assignRole('hr_officer');

    $this->actingAs($officer)->post(route('admin.announcements.transition', $vacancy->announcement), ['target' => 'finalized'])
        ->assertForbidden();
});

test('finalization requires every application to have an outcome', function (): void {
    $vacancy = lifecycleVacancy('2026-09-01', '2026-09-28', ['status' => 'interview']);
    $pending = lifecycleApplication($vacancy, ApplicationStatus::InterviewCompleted);
    lifecycleApplication($vacancy, ApplicationStatus::FailedScreening);

    $this->actingAs($this->admin)->post(route('admin.announcements.transition', $vacancy->announcement), ['target' => 'finalized'])
        ->assertSessionHasErrors(['status' => __('recruitment.errors.finalize_incomplete')]);

    $pending->forceFill(['status' => ApplicationStatus::Selected])->save();
    $this->post(route('admin.announcements.transition', $vacancy->announcement), ['target' => 'finalized'])
        ->assertSessionHasNoErrors();

    expect($vacancy->announcement->refresh()->status)->toBe('finalized')
        ->and($vacancy->announcement->finalized_at)->not->toBeNull()
        ->and(AuditLog::where('action', 'recruitment_finalized')->exists())->toBeTrue();
});

test('cancelling needs a reason and is final', function (): void {
    $vacancy = lifecycleVacancy('2026-09-20', '2026-10-10');

    $this->actingAs($this->admin)->post(route('admin.announcements.transition', $vacancy->announcement), ['target' => 'cancelled'])
        ->assertSessionHasErrors('reason');
    $this->post(route('admin.announcements.transition', $vacancy->announcement), ['target' => 'cancelled', 'reason' => 'Budget withdrawn'])
        ->assertSessionHasNoErrors();

    expect($vacancy->announcement->refresh()->status)->toBe('cancelled')
        ->and($vacancy->refresh()->canAcceptApplications())->toBeFalse()
        ->and(AuditLog::where('action', 'announcement_cancelled')->sole()->new_values['reason'])->toBe('Budget withdrawn');
});

test('publishing through the form is audited and needs the publish permission', function (): void {
    $draft = RecruitmentAnnouncement::factory()->create(['status' => 'draft', 'published_at' => null]);
    $payload = [
        'subject' => $draft->subject, 'code' => $draft->code, 'status' => 'published', '_publish_mode' => 'now',
        'opening_date' => $draft->opening_date->toDateString(), 'closing_date' => $draft->closing_date->toDateString(),
    ];

    // Staff who may edit announcements still need the separate publish permission.
    $editor = User::factory()->screeningOfficer()->create();
    $editor->givePermissionTo('vacancies.update');
    $this->actingAs($editor)->put(route('admin.announcements.update', $draft), $payload)->assertForbidden();
    expect($draft->refresh()->status)->toBe('draft');

    $this->actingAs($this->admin)->put(route('admin.announcements.update', $draft), $payload)->assertSessionHasNoErrors();
    expect($draft->refresh()->status)->toBe('published')
        ->and(AuditLog::where('action', 'announcement_published')->where('record_id', (string) $draft->id)->exists())->toBeTrue();
});

// ── Application stages ──────────────────────────────────────────────────────

test('invalid applicant stage transitions are rejected', function (ApplicationStatus $from, ApplicationStatus $to): void {
    $vacancy = lifecycleVacancy('2026-09-01', '2026-09-28');
    $application = lifecycleApplication($vacancy, $from);

    expect(app(RecruitmentStateMachine::class)->canTransitionApplication($application, $to))->toBeFalse()
        ->and(fn () => app(RecruitmentStateMachine::class)->assertApplicationTransition($application, $to))
        ->toThrow(RecruitmentRuleException::class);
})->with([
    'submitted straight to selected' => [ApplicationStatus::Submitted, ApplicationStatus::Selected],
    'failed screening to exam' => [ApplicationStatus::FailedScreening, ApplicationStatus::ShortlistedExam],
    'passed screening to interview while the exam is required' => [ApplicationStatus::PassedScreening, ApplicationStatus::ShortlistedInterview],
    'passed screening straight to selected' => [ApplicationStatus::PassedScreening, ApplicationStatus::Selected],
    'invited to exam straight to selected' => [ApplicationStatus::ShortlistedExam, ApplicationStatus::Selected],
    'withdrawn back to review' => [ApplicationStatus::Withdrawn, ApplicationStatus::UnderReview],
]);

test('a final decision cannot skip the exam', function (): void {
    $vacancy = lifecycleVacancy('2026-09-01', '2026-09-28');
    $application = lifecycleApplication($vacancy, ApplicationStatus::ShortlistedExam);

    $this->actingAs($this->admin)->post(route('admin.final-results.store', $application), [
        'exam_score' => 80, 'interview_score' => 80, 'exam_weight' => 60, 'interview_weight' => 40, 'decision' => 'selected',
    ])->assertSessionHasErrors('decision');

    expect($application->refresh()->status)->toBe(ApplicationStatus::ShortlistedExam);
});

test('recruitments without an exam stage may interview straight after screening', function (): void {
    $vacancy = lifecycleVacancy('2026-09-01', '2026-09-28', ['exam_required' => false, 'status' => 'screening']);
    $application = lifecycleApplication($vacancy, ApplicationStatus::PassedScreening);

    expect(app(RecruitmentStateMachine::class)->canTransitionApplication($application, ApplicationStatus::ShortlistedInterview))->toBeTrue()
        ->and(app(RecruitmentStateMachine::class)->canTransitionAnnouncement($vacancy->announcement, RecruitmentStatus::Interview))->toBeTrue();
});

// ── Automatic synchronisation & timezone ────────────────────────────────────

test('the sync command records opening and closing once', function (): void {
    $open = RecruitmentAnnouncement::factory()->create(['opening_date' => '2026-09-25', 'closing_date' => '2026-10-10']);
    $ended = RecruitmentAnnouncement::factory()->create(['opening_date' => '2026-09-01', 'closing_date' => '2026-09-30']);

    $this->artisan('recruitment:sync-statuses')->assertSuccessful();
    $this->artisan('recruitment:sync-statuses')->assertSuccessful();

    expect($ended->refresh()->status)->toBe('closed')
        ->and($ended->closed_at)->not->toBeNull()
        ->and($open->refresh()->status)->toBe('published')
        ->and($open->opened_at)->not->toBeNull()
        ->and(AuditLog::where('action', 'announcement_closed')->count())->toBe(1)
        ->and(AuditLog::where('action', 'announcement_opened')->count())->toBe(1);
});

test('closing is evaluated at the end of the closing day in the configured timezone', function (): void {
    config(['app.timezone' => 'Africa/Addis_Ababa']);
    $vacancy = lifecycleVacancy('2026-09-20', '2026-10-10');

    // 23:30 in Addis Ababa on the closing day (20:30 UTC): still open.
    $this->travelTo(Carbon::parse('2026-10-10 20:30:00', 'UTC'));
    expect($vacancy->refresh()->canAcceptApplications())->toBeTrue();

    // 00:30 the next day in Addis Ababa (21:30 UTC, still Oct 10 in UTC): closed.
    $this->travelTo(Carbon::parse('2026-10-10 21:30:00', 'UTC'));
    expect($vacancy->refresh()->canAcceptApplications())->toBeFalse();
});

// ── Admin UI ────────────────────────────────────────────────────────────────

test('the announcement page shows the stage, dates, counts and the next allowed action', function (): void {
    $vacancy = lifecycleVacancy('2026-09-20', '2026-10-10');
    lifecycleApplication($vacancy, ApplicationStatus::Submitted);

    $this->actingAs($this->admin)->get(route('admin.announcements.show', $vacancy->announcement))
        ->assertOk()
        ->assertSee(__('recruitment.lifecycle'))
        ->assertSee(RecruitmentStage::Open->label())
        ->assertSee(trans_choice('recruitment.days_remaining', 9, ['count' => 9]))
        ->assertSee(__('recruitment.action.start_screening'))
        ->assertSee(__('recruitment.blocked.open', ['date' => et_date($vacancy->announcement->closing_date, 'M d, Y')]))
        ->assertSee(__('recruitment.extension.title'))
        ->assertSee(route('admin.announcements.extend-deadline', $vacancy->announcement), false);

    $this->get(route('admin.announcements.index'))->assertOk()
        ->assertSee(RecruitmentStage::Open->label())
        ->assertSee(trans_choice('recruitment.days_remaining', 9, ['count' => 9]));
    $this->get(route('admin.announcements.index', ['state' => 'open']))->assertOk()->assertSee($vacancy->announcement->subject);
    $this->get(route('admin.announcements.index', ['state' => 'closed']))->assertOk()->assertDontSee($vacancy->announcement->subject);
});

test('lifecycle wording is translated to Amharic', function (): void {
    app()->setLocale('am');

    expect(__('recruitment.errors.registration_closed'))->toMatch('/\p{Ethiopic}/u')
        ->and(__('recruitment.errors.schedule_conflict'))->toMatch('/\p{Ethiopic}/u')
        ->and(RecruitmentStage::Upcoming->label())->toMatch('/\p{Ethiopic}/u');

    $en = require lang_path('en/recruitment.php');
    $am = require lang_path('am/recruitment.php');
    expect(array_keys(Arr::dot($am)))->toEqualCanonicalizing(array_keys(Arr::dot($en)));
});
