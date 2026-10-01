<?php

declare(strict_types=1);

use App\Actions\Exams\AssignApplicantsToScheduleAction;
use App\Actions\Exams\CreateExamInterviewScheduleAction;
use App\Enums\ApplicationStatus;
use App\Enums\ExamInterviewType;
use App\Exceptions\RecruitmentRuleException;
use App\Models\Applicant;
use App\Models\AuditLog;
use App\Models\ExamInterviewApplicant;
use App\Models\ExamInterviewSchedule;
use App\Models\User;
use App\Models\Vacancy;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Queue::fake();
    $this->travelTo('2026-10-01 12:00:00');
    $this->admin = User::factory()->admin()->create();
});

/** Schedule through the action (all timeline rules apply). */
function scheduleFor(Vacancy $vacancy, ExamInterviewType $type, string $date, string $start = '09:00', ?string $end = '11:00'): ExamInterviewSchedule
{
    return app(CreateExamInterviewScheduleAction::class)->handle(
        vacancy: $vacancy, title: $type->value.' session', type: $type, date: $date,
        startTime: $start, endTime: $end, venue: 'Hall A', instruction: null,
        createdBy: User::factory()->admin()->create(),
    );
}

function assignTo(ExamInterviewSchedule $schedule, ...$applications): void
{
    app(AssignApplicantsToScheduleAction::class)->handle($schedule, $applications);
}

// ── Exam scheduling ─────────────────────────────────────────────────────────

test('an exam cannot be scheduled during the application period', function (): void {
    $vacancy = lifecycleVacancy('2026-09-20', '2026-10-10');

    $this->actingAs($this->admin)->post(route('admin.schedules.store'), [
        'vacancy_id' => $vacancy->id, 'title' => 'Written exam', 'type' => 'exam',
        'date' => '2026-10-20', 'start_time' => '09:00', 'end_time' => '11:00', 'venue' => 'Hall A',
    ])->assertSessionHasErrors(['date' => __('recruitment.errors.exam_before_close')]);

    expect(fn () => scheduleFor($vacancy, ExamInterviewType::Exam, '2026-10-05'))
        ->toThrow(RecruitmentRuleException::class, __('recruitment.errors.exam_before_close'));
    expect(ExamInterviewSchedule::count())->toBe(0);
});

test('an exam can be scheduled after closing and moves the recruitment into the Exam stage', function (): void {
    $vacancy = lifecycleVacancy('2026-09-01', '2026-09-28');
    lifecycleApplication($vacancy, ApplicationStatus::PassedScreening);

    $this->actingAs($this->admin)->post(route('admin.schedules.store'), [
        'vacancy_id' => $vacancy->id, 'title' => 'Written exam', 'type' => 'exam',
        'date' => '2026-10-06', 'start_time' => '09:00', 'end_time' => '11:00', 'venue' => 'Hall A',
    ])->assertSessionHasNoErrors();

    $schedule = ExamInterviewSchedule::sole();
    expect($schedule->starts_at->toDateTimeString())->toBe('2026-10-06 09:00:00')
        ->and($schedule->ends_at->toDateTimeString())->toBe('2026-10-06 11:00:00')
        ->and($vacancy->announcement->refresh()->status)->toBe('exam')
        ->and(AuditLog::where('action', 'exam_created')->where('record_id', $schedule->id)->exists())->toBeTrue();
});

test('an exam in the past is blocked', function (): void {
    $vacancy = lifecycleVacancy('2026-09-01', '2026-09-28');

    // Today, but 09:00 has already passed (it is 12:00).
    expect(fn () => scheduleFor($vacancy, ExamInterviewType::Exam, '2026-10-01', '09:00', '10:00'))
        ->toThrow(RecruitmentRuleException::class, __('recruitment.errors.schedule_in_past'));
});

test('the end time must be after the start time', function (): void {
    $vacancy = lifecycleVacancy('2026-09-01', '2026-09-28');

    expect(fn () => scheduleFor($vacancy, ExamInterviewType::Exam, '2026-10-06', '11:00', '09:00'))
        ->toThrow(RecruitmentRuleException::class, __('recruitment.errors.schedule_end_before_start'));
});

test('an exam cannot be scheduled while applications still await screening', function (): void {
    $vacancy = lifecycleVacancy('2026-09-01', '2026-09-28');
    lifecycleApplication($vacancy, ApplicationStatus::Submitted);

    expect(fn () => scheduleFor($vacancy, ExamInterviewType::Exam, '2026-10-06'))
        ->toThrow(RecruitmentRuleException::class, __('recruitment.errors.screening_incomplete'));
});

test('draft and cancelled recruitments cannot have exams', function (string $status): void {
    $vacancy = lifecycleVacancy('2026-09-01', '2026-09-28', ['status' => $status]);

    expect(fn () => scheduleFor($vacancy, ExamInterviewType::Exam, '2026-10-06'))
        ->toThrow(RecruitmentRuleException::class, __('recruitment.errors.stage_not_allowed'));
})->with(['draft', 'cancelled', 'finalized']);

// ── Exam assignment ─────────────────────────────────────────────────────────

test('an applicant who failed screening cannot be assigned to an exam', function (): void {
    $vacancy = lifecycleVacancy('2026-09-01', '2026-09-28');
    $failed = lifecycleApplication($vacancy, ApplicationStatus::FailedScreening);
    $exam = scheduleFor($vacancy, ExamInterviewType::Exam, '2026-10-06');

    expect(fn () => assignTo($exam, $failed))
        ->toThrow(RecruitmentRuleException::class, __('recruitment.errors.applicant_not_eligible'));
    expect(ExamInterviewApplicant::count())->toBe(0);
});

test('the same applicant cannot be assigned twice to an exam', function (): void {
    $vacancy = lifecycleVacancy('2026-09-01', '2026-09-28');
    $application = lifecycleApplication($vacancy, ApplicationStatus::PassedScreening);
    $exam = scheduleFor($vacancy, ExamInterviewType::Exam, '2026-10-06');
    $other = scheduleFor($vacancy, ExamInterviewType::Exam, '2026-10-07');

    assignTo($exam, $application);

    expect(fn () => assignTo($exam, $application->refresh()))
        ->toThrow(RecruitmentRuleException::class, __('recruitment.errors.duplicate_assignment'))
        ->and(fn () => assignTo($other, $application->refresh()))
        ->toThrow(RecruitmentRuleException::class, __('recruitment.errors.duplicate_assignment'));
    expect(ExamInterviewApplicant::count())->toBe(1)
        ->and(AuditLog::where('action', 'exam_applicant_assigned')->count())->toBe(1);
});

test('an applicant cannot sit two overlapping exams', function (): void {
    $applicant = Applicant::factory()->create();
    $first = lifecycleVacancy('2026-09-01', '2026-09-28');
    $second = lifecycleVacancy('2026-09-01', '2026-09-28');
    $a = lifecycleApplication($first, ApplicationStatus::PassedScreening, $applicant);
    $b = lifecycleApplication($second, ApplicationStatus::PassedScreening, $applicant);

    assignTo(scheduleFor($first, ExamInterviewType::Exam, '2026-10-06', '09:00', '11:00'), $a);

    expect(fn () => assignTo(scheduleFor($second, ExamInterviewType::Exam, '2026-10-06', '10:30', '12:00'), $b))
        ->toThrow(RecruitmentRuleException::class, __('recruitment.errors.schedule_conflict'));

    // Back-to-back sessions do not overlap.
    assignTo(scheduleFor($second, ExamInterviewType::Exam, '2026-10-06', '11:00', '12:00'), $b->refresh());
    expect(ExamInterviewApplicant::count())->toBe(2);
});

test('moving a session onto another session of an invited applicant is blocked', function (): void {
    $applicant = Applicant::factory()->create();
    $first = lifecycleVacancy('2026-09-01', '2026-09-28');
    $second = lifecycleVacancy('2026-09-01', '2026-09-28');
    $examA = scheduleFor($first, ExamInterviewType::Exam, '2026-10-06', '09:00', '11:00');
    $examB = scheduleFor($second, ExamInterviewType::Exam, '2026-10-07', '09:00', '11:00');
    assignTo($examA, lifecycleApplication($first, ApplicationStatus::PassedScreening, $applicant));
    assignTo($examB, lifecycleApplication($second, ApplicationStatus::PassedScreening, $applicant));

    $this->actingAs($this->admin)->put(route('admin.schedules.update', $examB), [
        'vacancy_id' => $second->id, 'title' => 'Moved', 'type' => 'exam',
        'date' => '2026-10-06', 'start_time' => '10:00', 'end_time' => '12:00', 'venue' => 'Hall A',
    ])->assertSessionHasErrors(['date' => __('recruitment.errors.schedule_conflict')]);

    expect($examB->refresh()->date->toDateString())->toBe('2026-10-07');
});

// ── Interviews ──────────────────────────────────────────────────────────────

test('an interview cannot be scheduled during the application period', function (): void {
    $vacancy = lifecycleVacancy('2026-09-20', '2026-10-10');

    expect(fn () => scheduleFor($vacancy, ExamInterviewType::Interview, '2026-10-20'))
        ->toThrow(RecruitmentRuleException::class, __('recruitment.errors.interview_before_previous_stage'));
});

test('an interview cannot be scheduled before the required exam is complete', function (): void {
    $vacancy = lifecycleVacancy('2026-09-01', '2026-09-28');

    // No exam yet.
    expect(fn () => scheduleFor($vacancy, ExamInterviewType::Interview, '2026-10-08'))
        ->toThrow(RecruitmentRuleException::class, __('recruitment.errors.interview_before_previous_stage'));

    // An exam exists, but the interview would start before it ends.
    scheduleFor($vacancy, ExamInterviewType::Exam, '2026-10-08', '09:00', '11:00');
    expect(fn () => scheduleFor($vacancy, ExamInterviewType::Interview, '2026-10-08', '10:00', '12:00'))
        ->toThrow(RecruitmentRuleException::class, __('recruitment.errors.interview_before_previous_stage'));

    $interview = scheduleFor($vacancy, ExamInterviewType::Interview, '2026-10-09');
    expect($interview->exists)->toBeTrue()
        ->and($vacancy->announcement->refresh()->status)->toBe('interview');
});

test('applicants without a completed exam cannot be assigned to the interview', function (): void {
    $vacancy = lifecycleVacancy('2026-09-01', '2026-09-28');
    scheduleFor($vacancy, ExamInterviewType::Exam, '2026-10-05');
    $interview = scheduleFor($vacancy, ExamInterviewType::Interview, '2026-10-09');

    foreach ([ApplicationStatus::PassedScreening, ApplicationStatus::ShortlistedExam, ApplicationStatus::FailedScreening, ApplicationStatus::NotSelected] as $status) {
        expect(fn () => assignTo($interview, lifecycleApplication($vacancy, $status)))
            ->toThrow(RecruitmentRuleException::class, __('recruitment.errors.applicant_not_eligible'));
    }
});

test('an eligible applicant can be assigned to the interview, but only once', function (): void {
    $vacancy = lifecycleVacancy('2026-09-01', '2026-09-28');
    scheduleFor($vacancy, ExamInterviewType::Exam, '2026-10-05');
    $interview = scheduleFor($vacancy, ExamInterviewType::Interview, '2026-10-09');
    $application = lifecycleApplication($vacancy, ApplicationStatus::ExamCompleted);

    assignTo($interview, $application);
    expect($application->refresh()->status)->toBe(ApplicationStatus::ShortlistedInterview)
        ->and(AuditLog::where('action', 'interview_applicant_assigned')->exists())->toBeTrue();

    expect(fn () => assignTo($interview, $application->refresh()))
        ->toThrow(RecruitmentRuleException::class, __('recruitment.errors.duplicate_assignment'));
});

test('an interview may not overlap an exam of the same applicant', function (): void {
    $applicant = Applicant::factory()->create();
    $examVacancy = lifecycleVacancy('2026-09-01', '2026-09-28');
    // A second recruitment without an exam stage, so its interview is possible right away.
    $interviewVacancy = lifecycleVacancy('2026-09-01', '2026-09-28', ['exam_required' => false]);

    assignTo(scheduleFor($examVacancy, ExamInterviewType::Exam, '2026-10-06', '09:00', '11:00'),
        lifecycleApplication($examVacancy, ApplicationStatus::PassedScreening, $applicant));
    $interview = scheduleFor($interviewVacancy, ExamInterviewType::Interview, '2026-10-06', '10:00', '10:30');

    expect(fn () => assignTo($interview, lifecycleApplication($interviewVacancy, ApplicationStatus::PassedScreening, $applicant)))
        ->toThrow(RecruitmentRuleException::class, __('recruitment.errors.schedule_conflict'));
});

test('the assignment screen reports rule violations instead of failing', function (): void {
    $vacancy = lifecycleVacancy('2026-09-01', '2026-09-28');
    $exam = scheduleFor($vacancy, ExamInterviewType::Exam, '2026-10-06');
    $application = lifecycleApplication($vacancy, ApplicationStatus::PassedScreening);
    assignTo($exam, $application);
    $second = scheduleFor($vacancy, ExamInterviewType::Exam, '2026-10-07');

    $this->actingAs($this->admin)
        ->post(route('admin.schedules.applicants.assign', $second), ['application_ids' => [$application->id]])
        ->assertRedirect(route('admin.schedules.results', $second))
        ->assertSessionHas('error');

    expect(ExamInterviewApplicant::where('schedule_id', $second->id)->count())->toBe(0);
});

test('cancelling a session is audited', function (): void {
    $vacancy = lifecycleVacancy('2026-09-01', '2026-09-28');
    $exam = scheduleFor($vacancy, ExamInterviewType::Exam, '2026-10-06');

    $this->actingAs($this->admin)->delete(route('admin.schedules.destroy', $exam))->assertSessionHasNoErrors();

    expect(ExamInterviewSchedule::count())->toBe(0)
        ->and(AuditLog::where('action', 'exam_cancelled')->where('record_id', $exam->id)->exists())->toBeTrue();
});
