<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Vacancy;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

test('admin dashboard requires authentication', function () {
    $response = $this->get('/admin');
    $response->assertRedirect();
});

test('applicant cannot access admin panel', function () {
    $user = User::factory()->asApplicant()->create();
    $response = $this->actingAs($user)->get('/admin');
    expect($response->status())->not->toBe(200);
});

test('admin can access admin panel', function () {
    $user = User::factory()->admin()->create();
    $response = $this->actingAs($user)->get('/admin');
    expect($response->status())->not->toBe(403);
});

test('super admin can access admin panel', function () {
    $user = User::factory()->superAdmin()->create();
    $response = $this->actingAs($user)->get('/admin');
    expect($response->status())->not->toBe(403);
});

test('screening officer can access admin panel', function () {
    $user = User::factory()->screeningOfficer()->create();
    $response = $this->actingAs($user)->get('/admin');
    expect($response->status())->not->toBe(403);
});

test('report viewer cannot create vacancies', function () {
    $user = User::factory()->reportViewer()->create();
    expect($user->can('create', Vacancy::class))->toBeFalse();
});

test('report viewer cannot update vacancies', function () {
    $user = User::factory()->reportViewer()->create();
    $vacancy = Vacancy::factory()->create();
    expect($user->can('update', $vacancy))->toBeFalse();
});

test('report viewer cannot create or update users', function () {
    $user = User::factory()->reportViewer()->create();
    $other = User::factory()->create();
    expect($user->hasPermissionTo('users.create'))->toBeFalse();
    expect($user->hasPermissionTo('users.update'))->toBeFalse();
    expect($user->can('update', $other))->toBeFalse();
});
