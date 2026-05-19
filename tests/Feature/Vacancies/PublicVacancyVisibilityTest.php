<?php

use App\Enums\ApplicationStatus;
use App\Enums\VacancyStatus;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\Vacancy;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('public index only shows open vacancies', function () {
    Vacancy::factory()->open()->create(['title' => ['en' => 'Open Position', 'am' => 'ክፍት ቦታ']]);
    Vacancy::factory()->draft()->create(['title' => ['en' => 'Draft Position', 'am' => '']]);
    Vacancy::factory()->closed()->create(['title' => ['en' => 'Closed Position', 'am' => '']]);

    $response = $this->get(route('vacancies.index'));

    $response->assertStatus(200);
    $response->assertSee('Open Position');
    $response->assertDontSee('Draft Position');
    $response->assertDontSee('Closed Position');
});

test('draft vacancy is not accessible on the public show page', function () {
    $vacancy = Vacancy::factory()->draft()->create();

    $this->get(route('vacancies.show', $vacancy))
        ->assertNotFound();
});

test('cancelled vacancy is not accessible on the public show page', function () {
    $vacancy = Vacancy::factory()->create(['status' => VacancyStatus::Cancelled]);

    $this->get(route('vacancies.show', $vacancy))
        ->assertNotFound();
});

test('finalized vacancy is not accessible on the public show page', function () {
    $vacancy = Vacancy::factory()->create(['status' => VacancyStatus::Finalized]);

    $this->get(route('vacancies.show', $vacancy))
        ->assertNotFound();
});

test('open vacancy is accessible on the public show page', function () {
    $vacancy = Vacancy::factory()->open()->create();

    $this->get(route('vacancies.show', $vacancy))
        ->assertOk();
});

test('past-deadline vacancy is not shown in public index', function () {
    Vacancy::factory()->pastDeadline()->create(['title' => ['en' => 'Expired Job', 'am' => '']]);

    $response = $this->get(route('vacancies.index'));

    $response->assertStatus(200);
    $response->assertDontSee('Expired Job');
});

test('apply now button shown to authenticated applicant for open vacancy', function () {
    $vacancy = Vacancy::factory()->open()->create();
    $applicant = Applicant::factory()->create();

    $this->actingAs($applicant->user)
        ->get(route('vacancies.show', $vacancy))
        ->assertOk()
        ->assertSee(__('vacancies.apply_now'));
});

test('already applied message shown when applicant has applied', function () {
    $vacancy = Vacancy::factory()->open()->create();
    $applicant = Applicant::factory()->create();

    Application::create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancy->id,
        'reference_number' => 'APP-2026-VIS01',
        'field_of_study' => 'Engineering',
        'graduation_date' => now()->subYears(3),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
    ]);

    $this->actingAs($applicant->user)
        ->get(route('vacancies.show', $vacancy))
        ->assertOk()
        ->assertSee(__('vacancies.already_applied'));
});

test('deadline passed message shown for past-deadline open vacancy on show page', function () {
    $vacancy = Vacancy::factory()->pastDeadline()->create();

    $this->get(route('vacancies.show', $vacancy))
        ->assertOk()
        ->assertSee(__('vacancies.deadline_passed'));
});

test('public index filters by search term', function () {
    Vacancy::factory()->open()->create(['title' => ['en' => 'Software Engineer', 'am' => '']]);
    Vacancy::factory()->open()->create(['title' => ['en' => 'Finance Analyst', 'am' => '']]);

    $response = $this->get(route('vacancies.index', ['search' => 'Software']));

    $response->assertOk();
    $response->assertSee('Software Engineer');
    $response->assertDontSee('Finance Analyst');
});

test('public index filters by department', function () {
    Vacancy::factory()->open()->create(['title' => ['en' => 'IT Role', 'am' => ''], 'department' => 'IT']);
    Vacancy::factory()->open()->create(['title' => ['en' => 'HR Role', 'am' => ''], 'department' => 'HR']);

    $response = $this->get(route('vacancies.index', ['department' => 'IT']));

    $response->assertOk();
    $response->assertSee('IT Role');
    $response->assertDontSee('HR Role');
});

test('public index filters by employment type', function () {
    Vacancy::factory()->open()->create([
        'title' => ['en' => 'Perm Job', 'am' => ''],
        'employment_type' => 'permanent',
    ]);
    Vacancy::factory()->open()->create([
        'title' => ['en' => 'Contract Job', 'am' => ''],
        'employment_type' => 'contract',
    ]);

    $response = $this->get(route('vacancies.index', ['employment_type' => 'permanent']));

    $response->assertOk();
    $response->assertSee('Perm Job');
    $response->assertDontSee('Contract Job');
});
