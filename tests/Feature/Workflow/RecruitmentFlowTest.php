<?php

declare(strict_types=1);

use App\Actions\Exams\AssignApplicantsToScheduleAction;
use App\Actions\Exams\RecordExamInterviewResultAction;
use App\Actions\Screening\ReviewApplicationAction;
use App\Enums\ApplicationStatus;
use App\Enums\ExamInterviewType;
use App\Enums\ScreeningDecision;
use App\Models\Applicant;
use App\Models\ApplicantNotification;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\ExamInterviewSchedule;
use App\Models\User;
use App\Models\Vacancy;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Queue::fake();
});

function flowSchedule(Application $application, ExamInterviewType $type, int $daysAhead = 7): ExamInterviewSchedule
{
    return ExamInterviewSchedule::create([
        'vacancy_id' => $application->vacancy_id,
        'title' => $type->label().' Schedule',
        'type' => $type,
        'date' => now()->addDays($daysAhead)->toDateString(),
        'start_time' => '09:00',
        'end_time' => '11:00',
        'venue' => 'Main Hall',
        'created_by' => User::factory()->admin()->create()->id,
    ]);
}

/** Applicant user + application on an open vacancy, ready for the applicant edit flow. */
function flowApplicantApplication(ApplicationStatus $status): array
{
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);
    $vacancy = Vacancy::factory()->open()->create();

    $application = Application::create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancy->id,
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2),
        'status' => $status,
        'submitted_at' => now(),
    ]);

    return [$user, $application];
}

// ── Screening → applicant is informed ────────────────────────────────────────

test('screening decisions notify the applicant', function (ScreeningDecision $decision, string $type): void {
    $application = Application::factory()->afterDeadline()->create(['status' => ApplicationStatus::Submitted]);

    app(ReviewApplicationAction::class)->handle(
        $application, User::factory()->admin()->create(), $decision, 'Please upload a clearer degree certificate.',
    );

    $notification = ApplicantNotification::where('application_id', $application->id)->sole();
    expect($notification->type->value)->toBe($type);
})->with([
    'passed' => [ScreeningDecision::Passed, 'screening_passed'],
    'failed' => [ScreeningDecision::Failed, 'screening_failed'],
    'correction required' => [ScreeningDecision::CorrectionRequired, 'correction_required'],
]);

test('correction required notification includes the screener remark', function (): void {
    $application = Application::factory()->create(['status' => ApplicationStatus::Submitted]);

    app(ReviewApplicationAction::class)->handle(
        $application, User::factory()->admin()->create(), ScreeningDecision::CorrectionRequired, 'Upload a clearer degree certificate.',
    );

    expect(ApplicantNotification::where('application_id', $application->id)->sole()->message)
        ->toContain('Upload a clearer degree certificate.');
});

test('a pending screening decision does not notify the applicant', function (): void {
    $application = Application::factory()->create(['status' => ApplicationStatus::Submitted]);

    app(ReviewApplicationAction::class)->handle($application, User::factory()->admin()->create(), ScreeningDecision::Pending);

    expect(ApplicantNotification::where('application_id', $application->id)->count())->toBe(0);
});

// ── Applicant edits after a screening decision ───────────────────────────────

test('editing an application after a screening decision sends it back for screening', function (ApplicationStatus $status): void {
    [$user, $application] = flowApplicantApplication($status);

    $this->actingAs($user)->put(route('applicant.applications.update', $application), [
        'field_of_study' => 'Software Engineering',
        'graduation_date' => now()->subYears(3)->toDateString(),
    ])->assertRedirect(route('applicant.applications.show', $application))
        ->assertSessionHas('success', __('applications.resubmitted_for_review'));

    expect($application->refresh()->status)->toBe(ApplicationStatus::UnderReview)
        ->and($application->field_of_study)->toBe('Software Engineering');
    expect(AuditLog::where('action', 'application_resubmitted_for_screening')->where('record_id', $application->id)->exists())->toBeTrue();
})->with([
    'correction required' => [ApplicationStatus::CorrectionRequired],
    'passed screening' => [ApplicationStatus::PassedScreening],
    'failed screening' => [ApplicationStatus::FailedScreening],
]);

test('editing a not-yet-screened application keeps its status', function (): void {
    [$user, $application] = flowApplicantApplication(ApplicationStatus::Submitted);

    $this->actingAs($user)->put(route('applicant.applications.update', $application), [
        'field_of_study' => 'Software Engineering',
        'graduation_date' => now()->subYears(3)->toDateString(),
    ])->assertSessionHas('success', __('applications.application_updated'));

    expect($application->refresh()->status)->toBe(ApplicationStatus::Submitted);
});

test('a shortlisted application cannot be moved to another vacancy', function (): void {
    [$user, $application] = flowApplicantApplication(ApplicationStatus::ShortlistedExam);
    $originalVacancy = $application->vacancy_id;
    $other = Vacancy::factory()->open()->create();

    // Past screening the application is read-only altogether, so the switch is refused.
    $this->actingAs($user)->put(route('applicant.applications.update', $application), [
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2)->toDateString(),
        'vacancy_id' => $other->id,
    ])->assertForbidden();

    expect($application->refresh()->vacancy_id)->toBe($originalVacancy);
});

// ── Exam / interview results ─────────────────────────────────────────────────

test('failing the exam ends the candidacy instead of making the applicant interview-eligible', function (): void {
    $application = Application::factory()->create(['status' => ApplicationStatus::PassedScreening]);
    $schedule = flowSchedule($application, ExamInterviewType::Exam);
    $record = app(AssignApplicantsToScheduleAction::class)->handle($schedule, [$application])->first();

    app(RecordExamInterviewResultAction::class)->handle($record, 'failed', 32.0);

    expect($application->refresh()->status)->toBe(ApplicationStatus::NotSelected);
    expect(AuditLog::where('action', 'exam_result_recorded')->where('record_id', $record->id)->exists())->toBeTrue();
});

test('passing the interview does not mark the applicant selected before the final result', function (): void {
    $application = Application::factory()->create(['status' => ApplicationStatus::ShortlistedInterview]);
    $schedule = flowSchedule($application, ExamInterviewType::Interview);
    $record = app(AssignApplicantsToScheduleAction::class)->handle($schedule, [$application])->first();

    app(RecordExamInterviewResultAction::class)->handle($record, 'passed', 88.0);

    expect($application->refresh()->status)->toBe(ApplicationStatus::InterviewCompleted);
});

test('a late exam result does not overwrite a recorded final decision', function (): void {
    $application = Application::factory()->create(['status' => ApplicationStatus::PassedScreening]);
    $schedule = flowSchedule($application, ExamInterviewType::Exam);
    $record = app(AssignApplicantsToScheduleAction::class)->handle($schedule, [$application])->first();
    // The exam is completed before any final decision can be recorded.
    app(RecordExamInterviewResultAction::class)->handle($record, 'passed', 80.0);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.final-results.store', $application), [
            'exam_score' => 80, 'interview_score' => 90,
            'exam_weight' => 60, 'interview_weight' => 40,
            'decision' => 'selected',
        ])->assertRedirect(route('admin.final-results.index'));

    app(RecordExamInterviewResultAction::class)->handle($record, 'failed', 10.0);

    expect($application->refresh()->status)->toBe(ApplicationStatus::Selected);
});

// ── Final results ────────────────────────────────────────────────────────────

test('recording a final result sets the status and writes an audit entry', function (): void {
    $application = Application::factory()->create(['status' => ApplicationStatus::InterviewCompleted]);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.final-results.store', $application), [
            'exam_score' => 70, 'interview_score' => 80,
            'exam_weight' => 60, 'interview_weight' => 40,
            'decision' => 'waitlisted',
        ])->assertRedirect(route('admin.final-results.index'));

    expect($application->refresh()->status)->toBe(ApplicationStatus::Waitlisted)
        ->and((float) $application->finalResult->final_score)->toBe(74.0);
    expect(AuditLog::where('action', 'final_result_recorded')->where('record_id', $application->id)->exists())->toBeTrue();
});

test('final result form is pre-filled with the recorded exam and interview scores', function (): void {
    $application = Application::factory()->create(['status' => ApplicationStatus::PassedScreening]);
    $exam = flowSchedule($application, ExamInterviewType::Exam);
    $examRecord = app(AssignApplicantsToScheduleAction::class)->handle($exam, [$application])->first();
    app(RecordExamInterviewResultAction::class)->handle($examRecord, 'passed', 81.5);

    $interview = flowSchedule($application, ExamInterviewType::Interview, daysAhead: 8);
    $interviewRecord = app(AssignApplicantsToScheduleAction::class)->handle($interview, [$application->refresh()])->first();
    app(RecordExamInterviewResultAction::class)->handle($interviewRecord, 'passed', 92.0);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.final-results.create', $application))
        ->assertOk()
        ->assertSee("examScore: '81.5", false)
        ->assertSee("interviewScore: '92", false);
});

test('final results page offers an announce form once a decision is recorded', function (): void {
    $application = Application::factory()->create(['status' => ApplicationStatus::InterviewCompleted]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('admin.final-results.index'))
        ->assertOk()
        ->assertDontSee(route('admin.final-results.announce'), false);

    $this->actingAs($admin)->post(route('admin.final-results.store', $application), [
        'exam_score' => 70, 'interview_score' => 80,
        'exam_weight' => 60, 'interview_weight' => 40,
        'decision' => 'selected',
    ]);

    $this->actingAs($admin)->get(route('admin.final-results.index'))
        ->assertOk()
        ->assertSee(route('admin.final-results.announce'), false)
        ->assertSee('name="application_ids[]" value="'.$application->id.'"', false);
});
