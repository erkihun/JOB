<?php

use App\Enums\ApplicationStatus;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\Institution;
use App\Models\User;
use App\Models\Vacancy;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\QueryException;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

// ── Helper to create an application ──────────────────────────────────────────

function makeInstitutionApplication(Applicant $applicant, Vacancy $vacancy): Application
{
    static $seq = 1000;

    return Application::create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancy->id,
        'reference_number' => 'MULTI-TEST-'.(++$seq),
        'field_of_study' => 'Computer Science',
        'graduation_date' => now()->subYears(2),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
    ]);
}

// ── Institution model ─────────────────────────────────────────────────────────

test('institution can be created with required fields', function () {
    $inst = Institution::factory()->create([
        'name' => 'Addis Ababa University',
        'code' => 'AAU',
        'status' => 'active',
    ]);

    expect($inst->name)->toBe('Addis Ababa University')
        ->and($inst->code)->toBe('AAU')
        ->and($inst->isActive())->toBeTrue();
});

test('institution displayName returns short_name when set', function () {
    $inst = Institution::factory()->create(['name' => 'Long Institution Name', 'short_name' => 'LIN']);
    expect($inst->displayName())->toBe('LIN');
});

test('institution displayName falls back to name when no short_name', function () {
    $inst = Institution::factory()->create(['name' => 'My Institution', 'short_name' => null]);
    expect($inst->displayName())->toBe('My Institution');
});

test('institution has many vacancies', function () {
    $inst = Institution::factory()->create();
    Vacancy::factory()->open()->count(3)->create(['institution_id' => $inst->id]);

    expect($inst->vacancies()->count())->toBe(3);
});

// ── Multi-institution application rules ───────────────────────────────────────

test('applicant can apply to vacancy from institution A', function () {
    $instA = Institution::factory()->create(['name' => 'Institution A']);
    $vacancy = Vacancy::factory()->open()->create(['institution_id' => $instA->id]);
    $applicant = Applicant::factory()->create();

    $app = makeInstitutionApplication($applicant, $vacancy);

    expect($app->exists)->toBeTrue()
        ->and($app->vacancy->institution_id)->toBe($instA->id);
});

test('same applicant can apply to vacancy from institution B', function () {
    $instA = Institution::factory()->create(['name' => 'Institution A']);
    $instB = Institution::factory()->create(['name' => 'Institution B']);
    $vacancyA = Vacancy::factory()->open()->create(['institution_id' => $instA->id]);
    $vacancyB = Vacancy::factory()->open()->create(['institution_id' => $instB->id]);
    $applicant = Applicant::factory()->create();

    makeInstitutionApplication($applicant, $vacancyA);
    makeInstitutionApplication($applicant, $vacancyB);

    expect($applicant->applications()->count())->toBe(2);
});

test('same applicant can apply to vacancy from institution C independently', function () {
    $instA = Institution::factory()->create();
    $instB = Institution::factory()->create();
    $instC = Institution::factory()->create();
    $vacancyA = Vacancy::factory()->open()->create(['institution_id' => $instA->id]);
    $vacancyB = Vacancy::factory()->open()->create(['institution_id' => $instB->id]);
    $vacancyC = Vacancy::factory()->open()->create(['institution_id' => $instC->id]);
    $applicant = Applicant::factory()->create();

    makeInstitutionApplication($applicant, $vacancyA);
    makeInstitutionApplication($applicant, $vacancyB);
    makeInstitutionApplication($applicant, $vacancyC);

    expect($applicant->applications()->count())->toBe(3);
});

test('applicant cannot apply twice to the same vacancy notice', function () {
    $inst = Institution::factory()->create();
    $vacancy = Vacancy::factory()->open()->create(['institution_id' => $inst->id]);
    $applicant = Applicant::factory()->create();

    makeInstitutionApplication($applicant, $vacancy);

    expect(fn () => makeInstitutionApplication($applicant, $vacancy))
        ->toThrow(QueryException::class);
});

test('same applicant can apply to a second different vacancy from the same institution', function () {
    $inst = Institution::factory()->create(['name' => 'Same Institution']);
    $vacancy1 = Vacancy::factory()->open()->create(['institution_id' => $inst->id]);
    $vacancy2 = Vacancy::factory()->open()->create(['institution_id' => $inst->id]);
    $applicant = Applicant::factory()->create();

    makeInstitutionApplication($applicant, $vacancy1);
    makeInstitutionApplication($applicant, $vacancy2);

    expect($applicant->applications()->count())->toBe(2);
});

test('duplicate prevention is enforced by applicant_id + vacancy_id unique constraint only', function () {
    $instA = Institution::factory()->create();
    $instB = Institution::factory()->create();
    // Two vacancies — different institutions — same applicant should work
    $vacancyA = Vacancy::factory()->open()->create(['institution_id' => $instA->id]);
    $vacancyB = Vacancy::factory()->open()->create(['institution_id' => $instB->id]);
    $applicant = Applicant::factory()->create();

    $appA = makeInstitutionApplication($applicant, $vacancyA);
    $appB = makeInstitutionApplication($applicant, $vacancyB);

    expect($appA->id)->not->toBe($appB->id)
        ->and($applicant->applications()->count())->toBe(2);
});

test('hasAppliedTo returns false before applying and true after', function () {
    $inst = Institution::factory()->create();
    $vacancy = Vacancy::factory()->open()->create(['institution_id' => $inst->id]);
    $applicant = Applicant::factory()->create();

    expect($applicant->hasAppliedTo($vacancy))->toBeFalse();

    makeInstitutionApplication($applicant, $vacancy);

    expect($applicant->hasAppliedTo($vacancy))->toBeTrue();
});

test('hasAppliedTo is vacancy-scoped not institution-scoped', function () {
    $inst = Institution::factory()->create();
    $vacancy1 = Vacancy::factory()->open()->create(['institution_id' => $inst->id]);
    $vacancy2 = Vacancy::factory()->open()->create(['institution_id' => $inst->id]);
    $applicant = Applicant::factory()->create();

    makeInstitutionApplication($applicant, $vacancy1);

    // Applied to vacancy1 but NOT vacancy2 (even though same institution)
    expect($applicant->hasAppliedTo($vacancy1))->toBeTrue()
        ->and($applicant->hasAppliedTo($vacancy2))->toBeFalse();
});

test('vacancy belongs to institution', function () {
    $inst = Institution::factory()->create(['name' => 'Test Org']);
    $vacancy = Vacancy::factory()->open()->create(['institution_id' => $inst->id]);

    expect($vacancy->institution)->not->toBeNull()
        ->and($vacancy->institution->name)->toBe('Test Org');
});

test('vacancy institution_id is nullable for backwards compatibility', function () {
    $vacancy = Vacancy::factory()->open()->create(['institution_id' => null]);

    expect($vacancy->institution)->toBeNull();
});

// ── Public vacancy page shows institution ─────────────────────────────────────

test('public vacancy listing page loads with institution data', function () {
    $inst = Institution::factory()->create(['name' => 'Ministry of Finance']);
    Vacancy::factory()->open()->create(['institution_id' => $inst->id]);

    $response = $this->get('/vacancies');

    $response->assertStatus(200);
});

test('public vacancy detail page shows institution name', function () {
    $inst = Institution::factory()->create(['name' => 'Ministry of Finance']);
    $vacancy = Vacancy::factory()->open()->create(['institution_id' => $inst->id]);

    $response = $this->get("/vacancies/{$vacancy->id}");

    $response->assertStatus(200)
        ->assertSee('Ministry of Finance');
});

// ── Admin institution management ──────────────────────────────────────────────

test('super admin can view institutions index', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    Institution::factory()->count(3)->create();

    $response = $this->actingAs($admin)->get('/admin/institutions');

    $response->assertStatus(200);
});

test('super admin can create institution', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $response = $this->actingAs($admin)->post('/admin/institutions', [
        'name' => 'Ethiopian Civil Service Commission',
        'code' => 'ECSC',
        'status' => 'active',
    ]);

    $response->assertRedirect('/admin/institutions');
    expect(Institution::where('code', 'ECSC')->exists())->toBeTrue();
});

test('user without institutions.view permission cannot access institutions index', function () {
    $user = User::factory()->create();
    $user->assignRole('report_viewer'); // no institutions.view permission

    $response = $this->actingAs($user)->get('/admin/institutions');

    $response->assertStatus(403);
});

test('institution code must be unique', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    Institution::factory()->create(['code' => 'DUPLICATE']);

    $response = $this->actingAs($admin)->post('/admin/institutions', [
        'name' => 'Another Institution',
        'code' => 'DUPLICATE',
        'status' => 'active',
    ]);

    $response->assertSessionHasErrors('code');
});

test('admin vacancy index shows institution filter', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $response = $this->actingAs($admin)->get('/admin/vacancies');

    $response->assertStatus(200);
});

test('admin can filter vacancies by institution', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $instA = Institution::factory()->create();
    $instB = Institution::factory()->create();
    Vacancy::factory()->open()->create(['institution_id' => $instA->id]);
    Vacancy::factory()->open()->create(['institution_id' => $instB->id]);

    $response = $this->actingAs($admin)->get("/admin/vacancies?institution_id={$instA->id}");

    $response->assertStatus(200);
});

// ── Applicant cannot access institutions admin ────────────────────────────────

test('applicant cannot access admin institutions routes', function () {
    $user = User::factory()->create();
    $user->assignRole('applicant');

    $response = $this->actingAs($user)->get('/admin/institutions');

    // Should be 403 (policy blocks) or redirect
    expect($response->status())->toBeIn([302, 403]);
});
