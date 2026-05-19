<?php

declare(strict_types=1);

use App\Actions\Exams\AssignApplicantsToScheduleAction;
use App\Actions\Exams\CreateExamInterviewScheduleAction;
use App\Actions\Notifications\SendApplicantNotificationAction;
use App\Actions\Reports\ExportReportAction;
use App\Actions\Screening\ReviewApplicationAction;
use App\Enums\ApplicationStatus;
use App\Enums\ExamInterviewType;
use App\Enums\NotificationType;
use App\Enums\ScreeningDecision;
use App\Models\ApplicantNotification;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\ExamInterviewSchedule;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Models\Vacancy;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function phase5Application(ApplicationStatus $status = ApplicationStatus::PassedScreening): Application
{
    return Application::factory()->create(['status' => $status]);
}

function phase5Schedule(ExamInterviewType $type = ExamInterviewType::Exam): ExamInterviewSchedule
{
    return ExamInterviewSchedule::create([
        'vacancy_id' => Vacancy::factory()->open()->create()->id,
        'title' => $type->label().' Schedule',
        'type' => $type,
        'date' => now()->addWeek()->toDateString(),
        'start_time' => '09:00',
        'end_time' => '11:00',
        'venue' => 'Main Hall',
        'instruction' => 'Bring identification.',
        'created_by' => User::factory()->admin()->create()->id,
    ]);
}

test('only passed applicants can be assigned to exam schedule', function (): void {
    Queue::fake();
    $application = phase5Application(ApplicationStatus::FailedScreening);
    $schedule = phase5Schedule(ExamInterviewType::Exam);

    app(AssignApplicantsToScheduleAction::class)->handle($schedule, [$application]);
})->throws(InvalidArgumentException::class);

test('only passed applicants can be assigned to interview schedule', function (): void {
    Queue::fake();
    $application = phase5Application(ApplicationStatus::Submitted);
    $schedule = phase5Schedule(ExamInterviewType::Interview);

    app(AssignApplicantsToScheduleAction::class)->handle($schedule, [$application]);
})->throws(InvalidArgumentException::class);

test('unauthorized user cannot create schedule', function (): void {
    $user = User::factory()->create();
    $vacancy = Vacancy::factory()->open()->create();

    app(CreateExamInterviewScheduleAction::class)->handle(
        vacancy: $vacancy,
        title: 'Exam',
        type: ExamInterviewType::Exam,
        date: now()->addDay()->toDateString(),
        startTime: '09:00',
        endTime: null,
        venue: 'Room 1',
        instruction: null,
        createdBy: $user,
    );
})->throws(AuthorizationException::class);

test('authorized user can create exam schedule', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('exams.create');
    $vacancy = Vacancy::factory()->open()->create();

    $schedule = app(CreateExamInterviewScheduleAction::class)->handle(
        vacancy: $vacancy,
        title: 'Exam',
        type: ExamInterviewType::Exam,
        date: now()->addDay()->toDateString(),
        startTime: '09:00',
        endTime: null,
        venue: 'Room 1',
        instruction: null,
        createdBy: $user,
    );

    expect($schedule->exists)->toBeTrue();
    expect($schedule->type)->toBe(ExamInterviewType::Exam);
});

test('admin schedule creation requires venue before database insert', function (): void {
    $user = User::factory()->admin()->create();
    $vacancy = Vacancy::factory()->open()->create();

    $response = $this->actingAs($user)->post(route('admin.schedules.store'), [
        'vacancy_id' => $vacancy->id,
        'title' => 'Interview',
        'type' => ExamInterviewType::Interview->value,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '03:38',
        'end_time' => '04:38',
        'venue' => null,
        'instruction' => null,
    ]);

    $response->assertSessionHasErrors('venue');
    $this->assertDatabaseMissing('exam_interview_schedules', [
        'vacancy_id' => $vacancy->id,
        'title' => 'Interview',
    ]);
});

test('authorized user can assign applicants', function (): void {
    Queue::fake();
    $application = phase5Application();
    $schedule = ExamInterviewSchedule::create([
        'vacancy_id' => $application->vacancy_id,
        'title' => 'Exam',
        'type' => ExamInterviewType::Exam,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '09:00',
        'venue' => 'Room 1',
        'created_by' => User::factory()->admin()->create()->id,
    ]);

    $assigned = app(AssignApplicantsToScheduleAction::class)->handle($schedule, [$application]);

    expect($assigned)->toHaveCount(1);
    expect($application->refresh()->status)->toBe(ApplicationStatus::ShortlistedExam);
});

test('authorized user can record exam score from admin route', function (): void {
    Queue::fake();
    $user = User::factory()->admin()->create();
    $application = phase5Application();
    $schedule = ExamInterviewSchedule::create([
        'vacancy_id' => $application->vacancy_id,
        'title' => 'Exam',
        'type' => ExamInterviewType::Exam,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '09:00',
        'venue' => 'Room 1',
        'created_by' => $user->id,
    ]);

    $record = app(AssignApplicantsToScheduleAction::class)->handle($schedule, [$application])->first();

    $response = $this->actingAs($user)->post(route('admin.schedules.results.store', [$schedule, $record]), [
        'status' => 'passed',
        'score' => '86.50',
        'remark' => 'Strong written result.',
    ]);

    $response->assertRedirect(route('admin.schedules.results', $schedule));
    $this->assertDatabaseHas('exam_interview_applicants', [
        'id' => $record->id,
        'status' => 'passed',
        'score' => '86.50',
        'remark' => 'Strong written result.',
    ]);
    expect($application->refresh()->status)->toBe(ApplicationStatus::ShortlistedInterview);
});

test('authorized user can assign applicants from admin results page', function (): void {
    Queue::fake();
    $user = User::factory()->admin()->create();
    $application = phase5Application();
    $schedule = ExamInterviewSchedule::create([
        'vacancy_id' => $application->vacancy_id,
        'title' => 'Exam',
        'type' => ExamInterviewType::Exam,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '09:00',
        'venue' => 'Room 1',
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user)->post(route('admin.schedules.applicants.assign', $schedule), [
        'application_ids' => [$application->id],
    ]);

    $response->assertRedirect(route('admin.schedules.results', $schedule));
    $this->assertDatabaseHas('exam_interview_applicants', [
        'schedule_id' => $schedule->id,
        'application_id' => $application->id,
        'status' => 'invited',
    ]);
    expect($application->refresh()->status)->toBe(ApplicationStatus::ShortlistedExam);
});

test('admin can announce final result notifications', function (): void {
    Queue::fake();
    $user = User::factory()->admin()->create();
    $application = phase5Application(ApplicationStatus::Selected);

    $response = $this->actingAs($user)->post(route('admin.final-results.announce'), [
        'application_ids' => [$application->id],
        'channel' => 'in_system',
        'message' => 'Please check your dashboard.',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('applicant_notifications', [
        'applicant_id' => $application->applicant_id,
        'application_id' => $application->id,
        'type' => 'selected',
        'channel' => 'in_system',
        'status' => 'pending',
    ]);
});

test('exam invitation notification is created', function (): void {
    Queue::fake();
    $application = phase5Application();
    $schedule = ExamInterviewSchedule::create([
        'vacancy_id' => $application->vacancy_id,
        'title' => 'Exam',
        'type' => ExamInterviewType::Exam,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '09:00',
        'venue' => 'Room 1',
        'created_by' => User::factory()->admin()->create()->id,
    ]);

    app(AssignApplicantsToScheduleAction::class)->handle($schedule, [$application]);

    $this->assertDatabaseHas('applicant_notifications', [
        'application_id' => $application->id,
        'type' => NotificationType::ExamInvitation->value,
    ]);
});

test('interview invitation notification is created', function (): void {
    Queue::fake();
    $application = phase5Application();
    $schedule = ExamInterviewSchedule::create([
        'vacancy_id' => $application->vacancy_id,
        'title' => 'Interview',
        'type' => ExamInterviewType::Interview,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '09:00',
        'venue' => 'Room 1',
        'created_by' => User::factory()->admin()->create()->id,
    ]);

    app(AssignApplicantsToScheduleAction::class)->handle($schedule, [$application]);

    $this->assertDatabaseHas('applicant_notifications', [
        'application_id' => $application->id,
        'type' => NotificationType::InterviewInvitation->value,
    ]);
});

test('notification uses applicant preferred locale', function (): void {
    Queue::fake();
    $application = phase5Application();
    $application->applicant->update(['preferred_locale' => 'am']);

    NotificationTemplate::create([
        'type' => NotificationType::ExamInvitation,
        'locale' => 'am',
        'subject' => 'AM {{ applicant_name }}',
        'body' => 'AM {{ vacancy_title }} {{ venue }}',
        'active' => true,
    ]);

    $notification = app(SendApplicantNotificationAction::class)->handle(
        applicant: $application->applicant,
        type: NotificationType::ExamInvitation,
        placeholders: ['venue' => 'Hall'],
        application: $application,
    );

    expect($notification->subject)->toStartWith('AM ');
    expect($notification->message)->toContain('Hall');
});

test('failed notification can be resent', function (): void {
    Queue::fake();
    $application = phase5Application();
    $notification = ApplicantNotification::create([
        'applicant_id' => $application->applicant_id,
        'application_id' => $application->id,
        'type' => NotificationType::ExamInvitation,
        'channel' => 'email',
        'subject' => 'Failed',
        'message' => 'Retry',
        'status' => 'failed',
    ]);

    $resent = app(SendApplicantNotificationAction::class)->resend($notification);

    expect($resent->status)->toBe('pending');
});

test('report viewer can export permitted report', function (): void {
    Queue::fake();
    $user = User::factory()->create();
    $user->givePermissionTo('reports.view', 'reports.export', 'reports.applicants');

    $path = app(ExportReportAction::class)->handle('vacancy-wise-applicants', $user);

    expect($path)->toEndWith('.xlsx');
});

test('user without reports export cannot export', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('reports.view', 'reports.applicants');

    app(ExportReportAction::class)->handle('applicants', $user);
})->throws(AuthorizationException::class);

test('dashboard requires dashboard view permission', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin');

    expect($response->status())->not->toBe(200);
});

test('audit log is created when screening status changes', function (): void {
    $officer = User::factory()->screeningOfficer()->create();
    $application = phase5Application(ApplicationStatus::Submitted);

    $this->actingAs($officer);

    app(ReviewApplicationAction::class)->handle($application, $officer, ScreeningDecision::Passed);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'screening_status_changed',
        'module' => 'screening',
        'record_id' => $application->id,
    ]);
});

test('audit log is created when notification is sent', function (): void {
    Queue::fake();
    $application = phase5Application();

    app(SendApplicantNotificationAction::class)->handle(
        applicant: $application->applicant,
        type: NotificationType::ApplicationSubmitted,
        application: $application,
    );

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'notification_sent',
        'module' => 'notifications',
    ]);
});

test('normal user cannot delete audit logs', function (): void {
    $user = User::factory()->create();
    $log = AuditLog::create([
        'action' => 'test',
        'module' => 'audit',
        'created_at' => now(),
    ]);

    expect($user->can('delete', $log))->toBeFalse();
});

test('sensitive applicant data access is audited', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('applications.view-sensitive');
    $application = phase5Application();

    $this->actingAs($user);

    expect($user->can('viewSensitive', $application))->toBeTrue();

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'sensitive_applicant_data_viewed',
        'module' => 'applications',
        'record_id' => $application->id,
    ]);
});
