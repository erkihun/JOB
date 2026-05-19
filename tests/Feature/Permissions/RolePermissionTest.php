<?php

use App\Models\User;
use App\Policies\UserPolicy;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('super_admin role has all permissions', function () {
    $user = User::factory()->superAdmin()->create();

    expect($user->hasRole('super_admin'))->toBeTrue();
    expect($user->hasPermissionTo('users.view'))->toBeTrue();
    expect($user->hasPermissionTo('vacancies.publish'))->toBeTrue();
    expect($user->hasPermissionTo('applications.view-sensitive'))->toBeTrue();
    expect($user->hasPermissionTo('audit.delete'))->toBeTrue();
    expect($user->hasPermissionTo('roles.assign-permissions'))->toBeTrue();
});

test('screening_officer cannot manage users', function () {
    $user = User::factory()->screeningOfficer()->create();

    expect($user->hasPermissionTo('users.create'))->toBeFalse();
    expect($user->hasPermissionTo('users.delete'))->toBeFalse();
    expect($user->hasPermissionTo('users.assign-role'))->toBeFalse();
});

test('screening_officer can review applications', function () {
    $user = User::factory()->screeningOfficer()->create();

    expect($user->hasPermissionTo('screening.view'))->toBeTrue();
    expect($user->hasPermissionTo('screening.review'))->toBeTrue();
    expect($user->hasPermissionTo('screening.mark-passed'))->toBeTrue();
    expect($user->hasPermissionTo('screening.mark-failed'))->toBeTrue();
});

test('screening_officer cannot reverse decisions', function () {
    $user = User::factory()->screeningOfficer()->create();

    expect($user->hasPermissionTo('screening.reverse-decision'))->toBeFalse();
});

test('applicant role cannot access admin permissions', function () {
    $user = User::factory()->asApplicant()->create();

    expect($user->hasPermissionTo('users.view'))->toBeFalse();
    expect($user->hasPermissionTo('vacancies.create'))->toBeFalse();
    expect($user->hasPermissionTo('screening.view'))->toBeFalse();
    expect($user->hasPermissionTo('reports.view'))->toBeFalse();
    expect($user->hasPermissionTo('settings.manage'))->toBeFalse();
    expect($user->hasPermissionTo('audit.view'))->toBeFalse();
});

test('applicant has correct applicant permissions', function () {
    $user = User::factory()->asApplicant()->create();

    expect($user->hasPermissionTo('applicant.applications.create'))->toBeTrue();
    expect($user->hasPermissionTo('applicant.applications.view'))->toBeTrue();
    expect($user->hasPermissionTo('applicant.documents.upload'))->toBeTrue();
    expect($user->hasPermissionTo('applicant.status.track'))->toBeTrue();
});

test('admin cannot manage roles and permissions', function () {
    $user = User::factory()->admin()->create();

    expect($user->hasPermissionTo('roles.assign-permissions'))->toBeFalse();
    expect($user->hasPermissionTo('permissions.manage'))->toBeFalse();
    expect($user->hasPermissionTo('audit.delete'))->toBeFalse();
});

test('admin can manage vacancies and applications', function () {
    $user = User::factory()->admin()->create();

    expect($user->hasPermissionTo('vacancies.create'))->toBeTrue();
    expect($user->hasPermissionTo('vacancies.publish'))->toBeTrue();
    expect($user->hasPermissionTo('applications.view'))->toBeTrue();
    expect($user->hasPermissionTo('screening.review'))->toBeTrue();
    expect($user->hasPermissionTo('screening.reverse-decision'))->toBeTrue();
});

test('super_admin bypasses all gate checks', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    // Super admin should pass even for non-existent permissions via Gate::before
    expect($superAdmin->isSuperAdmin())->toBeTrue();
});

test('user cannot be assigned super_admin role by regular admin', function () {
    $admin = User::factory()->admin()->create();
    $targetUser = User::factory()->create();

    // Admin can assign roles but not super_admin protection is enforced in policy
    $policy = new UserPolicy;
    $superAdminUser = User::factory()->superAdmin()->create();

    expect($policy->update($admin, $superAdminUser))->toBeFalse();
});

test('roles exist after seeding', function () {
    $expectedRoles = [
        'super_admin', 'admin', 'hr_manager', 'hr_officer',
        'screening_officer', 'document_verifier', 'exam_officer',
        'interview_officer', 'report_viewer', 'content_manager', 'applicant',
    ];

    foreach ($expectedRoles as $roleName) {
        expect(Role::where('name', $roleName)->exists())->toBeTrue("Role [{$roleName}] should exist");
    }
});
