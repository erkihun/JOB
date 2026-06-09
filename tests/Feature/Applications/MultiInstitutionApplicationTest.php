<?php

declare(strict_types=1);

use App\Enums\ApplicationStatus;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\Institution;
use App\Models\User;
use App\Models\Vacancy;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

/**
 * Business rule: an applicant may apply once per institution within the same
 * recruitment announcement. In this system a Vacancy is the institution-specific
 * notice under an announcement, so the rule is enforced by the existing
 * unique(applicant_id, vacancy_id) constraint:
 *
 *   - one application per (applicant, vacancy)
 *   - a vacancy belongs to exactly one institution
 *   - a later notice from the same institution is a different vacancy → allowed again
 *
 * The applicant may apply to other institutions (other vacancies) freely, and to
 * the same institution again under a different announcement (a different vacancy).
 */
beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
});

function applyAs(User $user, Vacancy $vacancy): TestResponse
{
    return test()->actingAs($user)->post(route('applicant.applications.store', $vacancy), [
        'field_of_study' => 'Computer Science',
        'graduation_date' => now()->subYears(2)->toDateString(),
        'cgpa' => '3.50',
    ]);
}

test('applicant can apply to institution A in announcement 1', function (): void {
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);

    $institutionA = Institution::factory()->create(['name' => 'Institution A']);
    $vacancyA = Vacancy::factory()->open()->create(['institution_id' => $institutionA->id]);

    applyAs($user, $vacancyA)->assertRedirect()->assertSessionHasNoErrors();

    expect(Application::where('applicant_id', $applicant->id)->where('vacancy_id', $vacancyA->id)->count())->toBe(1);
});

test('same applicant cannot apply twice to institution A in announcement 1', function (): void {
    $user = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $user->id]);

    $institutionA = Institution::factory()->create(['name' => 'Institution A']);
    $vacancyA = Vacancy::factory()->open()->create(['institution_id' => $institutionA->id]);

    applyAs($user, $vacancyA)->assertRedirect()->assertSessionHasNoErrors();

    // Second attempt to the same institution's notice is blocked at the controller
    // pre-check and redirected with the duplicate message.
    applyAs($user, $vacancyA)
        ->assertRedirect(route('applicant.applications.index'))
        ->assertSessionHas('error', __('applications.duplicate_application'));

    expect(Application::where('vacancy_id', $vacancyA->id)->count())->toBe(1);
});

test('same applicant can apply to institution B and C in announcement 1', function (): void {
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);

    $institutionA = Institution::factory()->create(['name' => 'Institution A']);
    $institutionB = Institution::factory()->create(['name' => 'Institution B']);
    $institutionC = Institution::factory()->create(['name' => 'Institution C']);

    $vacancyA = Vacancy::factory()->open()->create(['institution_id' => $institutionA->id]);
    $vacancyB = Vacancy::factory()->open()->create(['institution_id' => $institutionB->id]);
    $vacancyC = Vacancy::factory()->open()->create(['institution_id' => $institutionC->id]);

    applyAs($user, $vacancyA)->assertRedirect()->assertSessionHasNoErrors();
    applyAs($user, $vacancyB)->assertRedirect()->assertSessionHasNoErrors();
    applyAs($user, $vacancyC)->assertRedirect()->assertSessionHasNoErrors();

    expect($applicant->applications()->count())->toBe(3);
});

test('same applicant can apply to institution A again in a later announcement', function (): void {
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);

    $institutionA = Institution::factory()->create(['name' => 'Institution A']);

    // Announcement 1: institution A's first notice.
    $vacancyA1 = Vacancy::factory()->open()->create(['institution_id' => $institutionA->id]);
    // Announcement 2: institution A's later notice (a different vacancy).
    $vacancyA2 = Vacancy::factory()->open()->create(['institution_id' => $institutionA->id]);

    applyAs($user, $vacancyA1)->assertRedirect()->assertSessionHasNoErrors();
    applyAs($user, $vacancyA2)->assertRedirect()->assertSessionHasNoErrors();

    expect($applicant->applications()->count())->toBe(2);
});

test('duplicate prevention is enforced by the database unique constraint', function (): void {
    $applicant = Applicant::factory()->create();
    $vacancy = Vacancy::factory()->open()->create();

    Application::create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancy->id,
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
    ]);

    // Bypassing the application-layer guard, the DB still rejects the duplicate.
    expect(fn () => Application::create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancy->id,
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
    ]))->toThrow(QueryException::class);
});

test('duplicate race condition creates only one application', function (): void {
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);
    $vacancy = Vacancy::factory()->open()->create();

    // Pre-seed an application to simulate a concurrent insert that won the race.
    Application::create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancy->id,
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
    ]);

    // The second submit must be handled gracefully (no 500, only one row survives).
    applyAs($user, $vacancy);

    expect(Application::where('applicant_id', $applicant->id)->where('vacancy_id', $vacancy->id)->count())->toBe(1);
});

test('duplicate error message names the institution and announcement', function (): void {
    expect(__('applications.duplicate_application'))
        ->toBe('You have already applied to this institution for this recruitment announcement.');
});

test('apply form shows the institution name and the other-institutions note', function (): void {
    $user = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $user->id]);

    $institution = Institution::factory()->create(['name' => 'Ministry of Health']);
    $vacancy = Vacancy::factory()->open()->create(['institution_id' => $institution->id]);

    $this->actingAs($user)
        ->get(route('applicant.applications.create', $vacancy))
        ->assertOk()
        ->assertSee('Ministry of Health', false)
        ->assertSee(__('applications.other_institutions_note'), false);
});
