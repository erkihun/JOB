<?php

use App\Enums\ApplicationStatus;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\Vacancy;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\QueryException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('applicant can apply to a vacancy once', function () {
    $applicant = Applicant::factory()->create();
    $vacancy = Vacancy::factory()->open()->create();

    Application::create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancy->id,
        'reference_number' => 'APP-2026-000001',
        'field_of_study' => 'Computer Science',
        'graduation_date' => now()->subYears(2),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
    ]);

    expect(Application::where('applicant_id', $applicant->id)
        ->where('vacancy_id', $vacancy->id)
        ->count()
    )->toBe(1);
});

test('applicant cannot apply twice to the same vacancy', function () {
    $applicant = Applicant::factory()->create();
    $vacancy = Vacancy::factory()->open()->create();

    Application::create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancy->id,
        'reference_number' => 'APP-2026-000002',
        'field_of_study' => 'Computer Science',
        'graduation_date' => now()->subYears(2),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
    ]);

    // Second application to same vacancy should throw a unique constraint error
    expect(fn () => Application::create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancy->id,
        'reference_number' => 'APP-2026-000003',
        'field_of_study' => 'Computer Science',
        'graduation_date' => now()->subYears(2),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
    ]))->toThrow(QueryException::class);
});

test('applicant model detects existing application', function () {
    $applicant = Applicant::factory()->create();
    $vacancy = Vacancy::factory()->open()->create();

    expect($applicant->hasAppliedTo($vacancy))->toBeFalse();

    Application::create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancy->id,
        'reference_number' => 'APP-2026-000004',
        'field_of_study' => 'Accounting',
        'graduation_date' => now()->subYears(3),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
    ]);

    expect($applicant->hasAppliedTo($vacancy))->toBeTrue();
});

test('applicant can apply to multiple different vacancies', function () {
    $applicant = Applicant::factory()->create();
    $vacancy1 = Vacancy::factory()->open()->create();
    $vacancy2 = Vacancy::factory()->open()->create();

    Application::create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancy1->id,
        'reference_number' => 'APP-2026-000005',
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
    ]);

    Application::create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancy2->id,
        'reference_number' => 'APP-2026-000006',
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
    ]);

    expect($applicant->applications()->count())->toBe(2);
});

test('reference numbers are unique', function () {
    // Let the model's creating-hook generate the reference number via CodeGeneratorService.
    $applicant1 = Applicant::factory()->create();
    $applicant2 = Applicant::factory()->create();
    $vacancy1 = Vacancy::factory()->open()->create();
    $vacancy2 = Vacancy::factory()->open()->create();

    $app1 = Application::create([
        'applicant_id' => $applicant1->id,
        'vacancy_id' => $vacancy1->id,
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
    ]);

    $app2 = Application::create([
        'applicant_id' => $applicant2->id,
        'vacancy_id' => $vacancy2->id,
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
    ]);

    expect($app1->reference_number)->not->toBe($app2->reference_number);
    expect($app1->reference_number)->toStartWith('APP-'.now()->year.'-');
});
