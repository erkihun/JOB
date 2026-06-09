<?php

use App\Enums\ApplicationStatus;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\Vacancy;
use App\Policies\ApplicationPolicy;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('application is editable before vacancy closing date', function () {
    $vacancy = Vacancy::factory()->open()->create([
        'closing_date' => now()->addDays(30),
    ]);

    $applicant = Applicant::factory()->create();

    $application = Application::create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancy->id,
        'reference_number' => 'APP-2026-LOCK01',
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
    ]);

    expect($application->isEditable())->toBeTrue();
    expect($application->isLocked())->toBeFalse();
});

test('application is locked after vacancy closing date', function () {
    $vacancy = Vacancy::factory()->pastDeadline()->create();

    $applicant = Applicant::factory()->create();

    $application = Application::create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancy->id,
        'reference_number' => 'APP-2026-LOCK02',
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now()->subDays(5),
    ]);

    expect($application->isEditable())->toBeFalse();
    expect($application->isLocked())->toBeTrue();
});

test('manually locked application is not editable even before deadline', function () {
    $vacancy = Vacancy::factory()->open()->create([
        'closing_date' => now()->addDays(30),
    ]);

    $applicant = Applicant::factory()->create();

    $application = Application::create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancy->id,
        'reference_number' => 'APP-2026-LOCK03',
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
        'locked_at' => now(),
    ]);

    expect($application->isEditable())->toBeFalse();
    expect($application->isLocked())->toBeTrue();
});

test('application remains editable after a screening decision while the vacancy is open', function (ApplicationStatus $status) {
    // Business rule: applications are editable until the closing date, regardless
    // of screening status. A pass/fail decision does NOT lock editing on its own.
    $vacancy = Vacancy::factory()->open()->create([
        'closing_date' => now()->addDays(30),
    ]);

    $applicant = Applicant::factory()->create();

    $application = Application::create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancy->id,
        'reference_number' => 'APP-2026-SCREEN-'.$status->value,
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2),
        'status' => $status,
        'submitted_at' => now(),
    ]);

    expect($application->isEditable())->toBeTrue();
    expect($application->isLocked())->toBeFalse();
})->with([
    'passed screening' => [ApplicationStatus::PassedScreening],
    'failed screening' => [ApplicationStatus::FailedScreening],
]);

test('policy denies applicant update after deadline', function () {
    $vacancy = Vacancy::factory()->pastDeadline()->create();

    $applicant = Applicant::factory()->create();

    $application = Application::create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancy->id,
        'reference_number' => 'APP-2026-LOCK04',
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now()->subDays(5),
    ]);

    $policy = new ApplicationPolicy;
    $user = $applicant->user;

    expect($policy->update($user, $application))->toBeFalse();
});

test('policy allows applicant update before deadline', function () {
    $vacancy = Vacancy::factory()->open()->create();
    $applicant = Applicant::factory()->create();

    $application = Application::create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancy->id,
        'reference_number' => 'APP-2026-LOCK05',
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
    ]);

    $policy = new ApplicationPolicy;
    $user = $applicant->user;

    expect($policy->update($user, $application))->toBeTrue();
});

test('policy allows applicant update after a screening decision while the vacancy is open', function (ApplicationStatus $status) {
    // Editing stays open until the closing date even after a screening decision.
    $vacancy = Vacancy::factory()->open()->create([
        'closing_date' => now()->addDays(30),
    ]);

    $applicant = Applicant::factory()->create();

    $application = Application::create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancy->id,
        'reference_number' => 'APP-2026-POLICY-'.$status->value,
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2),
        'status' => $status,
        'submitted_at' => now(),
    ]);

    $policy = new ApplicationPolicy;
    $user = $applicant->user;

    expect($policy->update($user, $application))->toBeTrue();
})->with([
    'passed screening' => [ApplicationStatus::PassedScreening],
    'failed screening' => [ApplicationStatus::FailedScreening],
]);

test('vacancy past deadline cannot accept applications', function () {
    $vacancy = Vacancy::factory()->pastDeadline()->create();

    expect($vacancy->canAcceptApplications())->toBeFalse();
    expect($vacancy->isPastDeadline())->toBeTrue();
});

test('open vacancy within deadline can accept applications', function () {
    $vacancy = Vacancy::factory()->open()->create();

    expect($vacancy->canAcceptApplications())->toBeTrue();
    expect($vacancy->isPastDeadline())->toBeFalse();
});

test('closed vacancy cannot accept applications', function () {
    $vacancy = Vacancy::factory()->closed()->create();

    expect($vacancy->canAcceptApplications())->toBeFalse();
    expect($vacancy->isOpen())->toBeFalse();
});
