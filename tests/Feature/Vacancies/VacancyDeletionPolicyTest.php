<?php

use App\Enums\ApplicationStatus;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\User;
use App\Models\Vacancy;
use App\Policies\VacancyPolicy;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('vacancy with no applications can be deleted by authorised user', function () {
    $user = User::factory()->create(['status' => 'active']);
    $user->givePermissionTo('vacancies.delete');

    $vacancy = Vacancy::factory()->draft()->create();

    $policy = new VacancyPolicy;
    expect($policy->delete($user, $vacancy))->toBeTrue();
});

test('vacancy with applications cannot be deleted', function () {
    $user = User::factory()->create(['status' => 'active']);
    $user->givePermissionTo('vacancies.delete');

    $vacancy = Vacancy::factory()->open()->create();
    $applicant = Applicant::factory()->create();

    Application::create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancy->id,
        'reference_number' => 'APP-2026-DEL01',
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
    ]);

    $policy = new VacancyPolicy;
    expect($policy->delete($user, $vacancy))->toBeFalse();
});

test('user without delete permission cannot delete vacancy', function () {
    $user = User::factory()->create(['status' => 'active']);

    $vacancy = Vacancy::factory()->draft()->create();

    $policy = new VacancyPolicy;
    expect($policy->delete($user, $vacancy))->toBeFalse();
});
