<?php

use App\Models\User;
use App\Models\Vacancy;
use App\Policies\VacancyPolicy;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('user with vacancies.create permission can create vacancies', function () {
    $user = User::factory()->create(['status' => 'active']);
    $user->givePermissionTo('vacancies.create');

    $policy = new VacancyPolicy;
    expect($policy->create($user))->toBeTrue();
});

test('user without vacancies.create permission cannot create vacancies', function () {
    $user = User::factory()->create(['status' => 'active']);

    $policy = new VacancyPolicy;
    expect($policy->create($user))->toBeFalse();
});

test('user with vacancies.update permission can update vacancy', function () {
    $user = User::factory()->create(['status' => 'active']);
    $user->givePermissionTo('vacancies.update');

    $vacancy = Vacancy::factory()->draft()->create();
    $policy = new VacancyPolicy;

    expect($policy->update($user, $vacancy))->toBeTrue();
});

test('user with vacancies.publish permission can publish vacancy', function () {
    $user = User::factory()->create(['status' => 'active']);
    $user->givePermissionTo('vacancies.publish');

    $vacancy = Vacancy::factory()->draft()->create();
    $policy = new VacancyPolicy;

    expect($policy->publish($user, $vacancy))->toBeTrue();
});

test('user without vacancies.publish permission cannot publish vacancy', function () {
    $user = User::factory()->create(['status' => 'active']);

    $vacancy = Vacancy::factory()->draft()->create();
    $policy = new VacancyPolicy;

    expect($policy->publish($user, $vacancy))->toBeFalse();
});

test('user with vacancies.close permission can close vacancy', function () {
    $user = User::factory()->create(['status' => 'active']);
    $user->givePermissionTo('vacancies.close');

    $vacancy = Vacancy::factory()->open()->create();
    $policy = new VacancyPolicy;

    expect($policy->close($user, $vacancy))->toBeTrue();
});

test('user with vacancies.view permission can view vacancy list', function () {
    $user = User::factory()->create(['status' => 'active']);
    $user->givePermissionTo('vacancies.view');

    $vacancy = Vacancy::factory()->open()->create();
    $policy = new VacancyPolicy;

    expect($policy->viewAny($user))->toBeTrue();
    expect($policy->view($user, $vacancy))->toBeTrue();
});

test('hr_manager role has all vacancy permissions', function () {
    $user = User::factory()->create(['status' => 'active']);
    $user->assignRole('hr_manager');

    $policy = new VacancyPolicy;
    $vacancy = Vacancy::factory()->draft()->create();

    expect($policy->create($user))->toBeTrue();
    expect($policy->update($user, $vacancy))->toBeTrue();
    expect($policy->publish($user, $vacancy))->toBeTrue();
    expect($policy->close($user, $vacancy))->toBeTrue();
});

test('applicant role cannot manage vacancies', function () {
    $user = User::factory()->create(['status' => 'active']);
    $user->assignRole('applicant');

    $policy = new VacancyPolicy;
    $vacancy = Vacancy::factory()->open()->create();

    expect($policy->create($user))->toBeFalse();
    expect($policy->update($user, $vacancy))->toBeFalse();
    expect($policy->delete($user, $vacancy))->toBeFalse();
    expect($policy->publish($user, $vacancy))->toBeFalse();
});
