<?php

declare(strict_types=1);

use App\Enums\ApplicationStatus;
use App\Enums\ExamInterviewType;
use App\Enums\NotificationType;
use App\Models\Applicant;
use App\Models\ApplicantNotification;
use App\Models\Application;
use App\Models\ExamInterviewApplicant;
use App\Models\ExamInterviewSchedule;
use App\Models\User;
use App\Models\Vacancy;
use App\Support\ApplicationProgress;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->user = User::factory()->asApplicant()->create();
    $this->applicant = Applicant::factory()->create(['user_id' => $this->user->id]);
});

function portalApplication(Applicant $applicant, ApplicationStatus $status, array $attrs = []): Application
{
    return Application::factory()->create(['applicant_id' => $applicant->id, 'status' => $status] + $attrs);
}

function inviteTo(Application $application, int $inDays): ExamInterviewSchedule
{
    $schedule = ExamInterviewSchedule::create([
        'vacancy_id' => $application->vacancy_id, 'title' => 'Written exam', 'type' => ExamInterviewType::Exam,
        'date' => now()->addDays($inDays)->toDateString(), 'start_time' => '09:00', 'venue' => 'Main Hall B',
        'created_by' => User::factory()->create()->id,
    ]);
    ExamInterviewApplicant::create(['schedule_id' => $schedule->id, 'application_id' => $application->id, 'status' => 'invited']);

    return $schedule;
}

test('dashboard next step: a correction request comes before an upcoming exam', function (): void {
    $exam = portalApplication($this->applicant, ApplicationStatus::ShortlistedExam);
    inviteTo($exam, 5);

    $this->actingAs($this->user)->get(route('applicant.dashboard'))
        ->assertOk()
        ->assertSee(__('applicant.next_step'))
        ->assertSee('Main Hall B');

    portalApplication($this->applicant, ApplicationStatus::CorrectionRequired);

    $this->actingAs($this->user)->get(route('applicant.dashboard'))
        ->assertOk()
        ->assertSee(__('applicant.next_action_needed'))
        ->assertSee(__('applicant.next_correct_cta'));
});

test('dashboard suggests completing the profile or finding a job when nothing else is pending', function (): void {
    $this->actingAs($this->user)->get(route('applicant.dashboard'))
        ->assertOk()
        ->assertSeeInOrder([__('applicant.next_step'), $this->applicant->profileCompletionPercentage() < 100 ? __('applicant.next_profile_title') : __('applicant.next_apply_title')]);
});

test('tracker marks done, current and failed steps', function (): void {
    $steps = fn (ApplicationStatus $s) => collect(ApplicationProgress::steps(portalApplication($this->applicant, $s)))->pluck('state')->all();

    expect($steps(ApplicationStatus::Submitted))->toBe(['current', 'pending', 'pending', 'pending', 'pending'])
        ->and($steps(ApplicationStatus::ShortlistedExam))->toBe(['done', 'done', 'current', 'pending', 'pending'])
        ->and($steps(ApplicationStatus::FailedScreening))->toBe(['done', 'failed', 'pending', 'pending', 'pending'])
        ->and($steps(ApplicationStatus::Selected))->toBe(['done', 'done', 'done', 'done', 'done']);

    foreach (ApplicationStatus::cases() as $status) {
        expect(__('applicant.next_'.$status->value))->not->toBe('applicant.next_'.$status->value);
    }
});

test('application detail shows the tracker, the exam and messages about it', function (): void {
    $application = portalApplication($this->applicant, ApplicationStatus::ShortlistedExam);
    inviteTo($application, 3);
    ApplicantNotification::create([
        'applicant_id' => $this->applicant->id, 'application_id' => $application->id, 'type' => NotificationType::ExamInvitation,
        'channel' => 'in_system', 'subject' => 'Invitation to the written exam', 'message' => 'Please attend.', 'status' => 'sent',
    ]);

    $this->actingAs($this->user)->get(route('applicant.applications.show', $application))
        ->assertOk()
        ->assertSee(__('applicant.what_happens_next'))
        ->assertSee(__('applicant.next_shortlisted_exam'))
        ->assertSee('Main Hall B')
        ->assertSee('Invitation to the written exam');
});

test('vacancy list marks vacancies already applied to', function (): void {
    $vacancy = Vacancy::factory()->open()->create();
    portalApplication($this->applicant, ApplicationStatus::Submitted, ['vacancy_id' => $vacancy->id]);

    $this->actingAs($this->user)->get(route('applicant.vacancies.index'))
        ->assertOk()
        ->assertSee(__('applicant.applied'));
});
