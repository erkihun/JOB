<?php

declare(strict_types=1);

use App\Actions\Exams\AssignApplicantsToScheduleAction;
use App\Actions\Exams\RecordExamInterviewResultAction;
use App\Enums\ApplicationStatus;
use App\Enums\ExamInterviewType;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\ExamInterviewSchedule;
use App\Models\User;
use App\Models\Vacancy;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->admin()->create();
    Queue::fake();
});

test('one schedule is created per selected vacancy', function (): void {
    $vacancies = Vacancy::factory()->open()->count(3)->create();

    $this->actingAs($this->admin)->post(route('admin.schedules.store'), [
        'vacancy_ids' => $vacancies->pluck('id')->all(),
        'title' => 'Written exam',
        'type' => 'practical',
        'date' => now()->addDays(5)->toDateString(),
        'start_time' => '09:00',
        'end_time' => '11:00',
        'venue' => 'Hall 2',
    ])->assertSessionHasNoErrors()
        ->assertSessionHas('success', __('messages.schedules_created', ['count' => 3]));

    expect(ExamInterviewSchedule::where('type', 'practical')->count())->toBe(3)
        ->and(ExamInterviewSchedule::pluck('vacancy_id')->sort()->values()->all())->toBe($vacancies->pluck('id')->sort()->values()->all());
});

test('create form lists vacancies with select all', function (): void {
    Vacancy::factory()->open()->count(2)->create();

    $this->actingAs($this->admin)->get(route('admin.schedules.create'))
        ->assertOk()
        ->assertSee('name="vacancy_ids[]"', false)
        ->assertSee(__('messages.select_all'))
        ->assertSee(ExamInterviewType::Practical->getLabel());
});

test('practical test: failing ends candidacy, passing keeps the stage, score feeds final result', function (): void {
    $application = Application::factory()->create(['status' => ApplicationStatus::ShortlistedInterview]);
    $schedule = ExamInterviewSchedule::create([
        'vacancy_id' => $application->vacancy_id, 'title' => 'Practical', 'type' => ExamInterviewType::Practical,
        'date' => now()->addDay()->toDateString(), 'start_time' => '09:00', 'venue' => 'Lab', 'created_by' => $this->admin->id,
    ]);

    $record = app(AssignApplicantsToScheduleAction::class)->handle($schedule, [$application])->first();
    expect($application->refresh()->status)->toBe(ApplicationStatus::ShortlistedInterview);

    app(RecordExamInterviewResultAction::class)->handle($record, 'passed', 88.0);
    expect($application->refresh()->status)->toBe(ApplicationStatus::ShortlistedInterview);

    $this->actingAs($this->admin)->get(route('admin.final-results.create', $application))
        ->assertSee("practicalScore: '88", false);

    app(RecordExamInterviewResultAction::class)->handle($record, 'failed', 20.0);
    expect($application->refresh()->status)->toBe(ApplicationStatus::NotSelected);
});

test('schedules page shows a month calendar with scheduled sessions', function (): void {
    $vacancy = Vacancy::factory()->open()->create();
    $date = now()->startOfMonth()->addDays(14);
    ExamInterviewSchedule::create([
        'vacancy_id' => $vacancy->id, 'title' => 'Calendar exam', 'type' => ExamInterviewType::Exam,
        'date' => $date->toDateString(), 'start_time' => '10:30', 'venue' => 'Hall', 'created_by' => $this->admin->id,
    ]);

    $this->actingAs($this->admin)->get(route('admin.schedules.index', ['month' => $date->format('Y-m')]))
        ->assertOk()
        ->assertSee(__('messages.calendar'))
        ->assertSee('Calendar exam')
        ->assertSee('10:30');

    // List view still works
    $this->actingAs($this->admin)->get(route('admin.schedules.index', ['view' => 'list']))
        ->assertOk()->assertSee('Calendar exam');

    // A bad month value falls back to the current month instead of erroring
    $this->actingAs($this->admin)->get(route('admin.schedules.index', ['month' => 'not-a-month']))->assertOk();
});

test('screening review shows documents inline', function (): void {
    $application = Application::factory()->create(['status' => ApplicationStatus::Submitted]);
    $required = \App\Models\VacancyDocument::create([
        'vacancy_id' => $application->vacancy_id, 'document_name' => 'Degree certificate', 'is_required' => true,
    ]);
    $doc = ApplicationDocument::create([
        'application_id' => $application->id, 'vacancy_document_id' => $required->id,
        'file_name' => 'degree.pdf', 'original_name' => 'degree.pdf',
        'file_path' => 'documents/degree.pdf', 'file_type' => 'application/pdf', 'file_size' => 2048,
    ]);

    $this->actingAs($this->admin)->get(route('admin.screening.review', $application))
        ->assertOk()
        ->assertSee('<iframe src="'.route('admin.documents.preview', $doc), false);
});
