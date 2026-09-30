<?php

declare(strict_types=1);

use App\Actions\Vacancies\SyncVacancyRequirementsAction;
use App\Enums\EducationLevel;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\Eligibility\EligibilityProfile;
use App\Services\Eligibility\VacancyEligibilityChecker;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * Vacancy with the two alternative options from the business rule:
 *   Option 1: Degree + 0 years   OR   Option 2: Diploma + 2 years
 */
function degreeOrDiplomaVacancy(): Vacancy
{
    $vacancy = Vacancy::factory()->open()->create();

    app(SyncVacancyRequirementsAction::class)->handle($vacancy, [
        ['title' => null, 'is_active' => true, 'requirements' => [
            ['education_level' => 'degree', 'min_experience_years' => 0],
        ]],
        ['title' => null, 'is_active' => true, 'requirements' => [
            ['education_level' => 'diploma', 'min_experience_years' => 2],
        ]],
    ]);

    return $vacancy->fresh();
}

function checkEligibility(Vacancy $vacancy, ?EducationLevel $level, float $years = 0, ?string $field = null)
{
    return app(VacancyEligibilityChecker::class)->check(
        $vacancy,
        new EligibilityProfile(educationLevel: $level, fieldOfStudy: $field, experienceYears: $years),
    );
}

// ── Checker: alternative (OR) requirement options ────────────────────────────

test('degree with 0 years experience passes', function (): void {
    $result = checkEligibility(degreeOrDiplomaVacancy(), EducationLevel::Degree, 0);

    expect($result->eligible)->toBeTrue()
        ->and($result->matchedOption)->toBe(1);
});

test('diploma with 2 years experience passes', function (): void {
    $result = checkEligibility(degreeOrDiplomaVacancy(), EducationLevel::Diploma, 2);

    expect($result->eligible)->toBeTrue()
        ->and($result->matchedOption)->toBe(2);
});

test('diploma with 0 years experience fails with a clear reason per option', function (): void {
    $result = checkEligibility(degreeOrDiplomaVacancy(), EducationLevel::Diploma, 0);

    expect($result->eligible)->toBeFalse()
        ->and($result->failedOptions)->toHaveCount(2)
        ->and($result->message())->toBe(__('vacancies.eligibility_meets_no_option'))
        ->and($result->reasonText())->toContain(__('vacancies.requirement_option', ['number' => 1]))
        ->and($result->reasonText())->toContain(__('vacancies.requirement_option', ['number' => 2]));

    expect($result->failedOptions[1]['reasons'][0])->toContain('2'); // needs 2 years
});

test('applicant passes if one requirement group matches', function (): void {
    // Fails option 1 (not a degree) but meets option 2 (diploma + 3 years).
    $result = checkEligibility(degreeOrDiplomaVacancy(), EducationLevel::Diploma, 3);

    expect($result->eligible)->toBeTrue()
        ->and($result->message())->toBe(__('vacancies.eligibility_meets_option'));
});

test('applicant does not need to match all groups', function (): void {
    // Degree holder with no experience: meets option 1, would fail option 2's experience rule.
    $result = checkEligibility(degreeOrDiplomaVacancy(), EducationLevel::Degree, 0);

    expect($result->eligible)->toBeTrue()
        ->and($result->failedOptions)->toBe([]);
});

test('a higher education level satisfies a lower requirement', function (): void {
    expect(checkEligibility(degreeOrDiplomaVacancy(), EducationLevel::Masters, 0)->eligible)->toBeTrue();
});

test('all conditions inside one option must be met together', function (): void {
    $vacancy = Vacancy::factory()->open()->create();
    app(SyncVacancyRequirementsAction::class)->handle($vacancy, [
        ['is_active' => true, 'requirements' => [
            ['education_level' => 'degree', 'field_of_study' => 'Computer Science, Information Technology', 'min_experience_years' => 1],
        ]],
    ]);

    expect(checkEligibility($vacancy, EducationLevel::Degree, 2, 'Information Technology')->eligible)->toBeTrue()
        ->and(checkEligibility($vacancy, EducationLevel::Degree, 2, 'Accounting')->eligible)->toBeFalse()
        ->and(checkEligibility($vacancy, EducationLevel::Degree, 0, 'Computer Science')->eligible)->toBeFalse();
});

test('inactive options are ignored', function (): void {
    $vacancy = Vacancy::factory()->open()->create();
    app(SyncVacancyRequirementsAction::class)->handle($vacancy, [
        ['is_active' => false, 'requirements' => [['education_level' => 'certificate']]],
        ['is_active' => true, 'requirements' => [['education_level' => 'phd']]],
    ]);

    expect(checkEligibility($vacancy, EducationLevel::Certificate)->eligible)->toBeFalse();
});

test('a vacancy without requirement options accepts everyone', function (): void {
    $result = checkEligibility(Vacancy::factory()->open()->create(), null);

    expect($result->eligible)->toBeTrue()
        ->and($result->hasRules)->toBeFalse();
});

// ── Admin: saving options ────────────────────────────────────────────────────

test('vacancy can save multiple requirement groups', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.vacancies.store'), [
        'code' => 'VAC-ELIG-001',
        'title' => ['en' => 'Junior Developer'],
        // department / employment type / description deliberately omitted: they are optional
        'location' => ['en' => 'Addis Ababa'],
        'status' => 'open',
        'number_of_positions' => 2,
        'announcement_id' => \App\Models\RecruitmentAnnouncement::factory()->create()->id,
        'requirement_groups_submitted' => 1,
        'requirement_groups' => [
            ['title' => 'Degree holders', 'is_active' => 1, 'requirements' => [
                ['education_level' => 'degree', 'min_experience_years' => 0],
            ]],
            ['title' => '', 'is_active' => 1, 'requirements' => [
                ['education_level' => 'diploma', 'min_experience_years' => 2],
            ]],
            // A completely blank option is dropped.
            ['title' => '', 'is_active' => 1, 'requirements' => [
                ['education_level' => '', 'min_experience_years' => 0],
            ]],
        ],
    ])->assertRedirect(route('admin.vacancies.index'))->assertSessionHasNoErrors();

    $vacancy = Vacancy::where('title->en', 'Junior Developer')->firstOrFail();
    $groups = $vacancy->requirementGroups()->with('requirements')->get();

    expect($groups)->toHaveCount(2)
        ->and($groups[0]->title)->toBe('Degree holders')
        ->and($groups[0]->requirements->first()->education_level)->toBe(EducationLevel::Degree)
        ->and($groups[1]->requirements->first()->education_level)->toBe(EducationLevel::Diploma)
        ->and($groups[1]->requirements->first()->min_experience_years)->toBe(2);
});

test('updating a vacancy replaces its options, and leaves them alone when the form did not send them', function (): void {
    $admin = User::factory()->admin()->create();
    $vacancy = degreeOrDiplomaVacancy();

    $payload = [
        'code' => $vacancy->code,
        'title' => ['en' => 'Updated'],
        'department' => 'IT',
        'location' => ['en' => 'Adama'],
        'status' => 'open',
        'number_of_positions' => 1,
        'announcement_id' => $vacancy->announcement_id,
    ];

    // No options section submitted → existing options kept.
    $this->actingAs($admin)->put(route('admin.vacancies.update', $vacancy), $payload)->assertSessionHasNoErrors();
    expect($vacancy->requirementGroups()->count())->toBe(2);

    // Options submitted → replaced.
    $this->actingAs($admin)->put(route('admin.vacancies.update', $vacancy), $payload + [
        'requirement_groups_submitted' => 1,
        'requirement_groups' => [
            ['is_active' => 1, 'requirements' => [['education_level' => 'masters', 'min_experience_years' => 5]]],
        ],
    ])->assertSessionHasNoErrors();

    $groups = $vacancy->requirementGroups()->with('requirements')->get();
    expect($groups)->toHaveCount(1)
        ->and($groups[0]->requirements->first()->education_level)->toBe(EducationLevel::Masters);
});

test('admin vacancy edit form lists the saved options', function (): void {
    $vacancy = degreeOrDiplomaVacancy();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.vacancies.edit', $vacancy))
        ->assertOk()
        ->assertSee(__('vacancies.eligibility_requirements'))
        ->assertSee('requirement_groups_submitted', false);
});

// ── Applicant: applying ──────────────────────────────────────────────────────

test('ineligible applicant cannot apply and sees the reason', function (): void {
    $user = User::factory()->asApplicant()->create();
    Applicant::factory()->create([
        'user_id' => $user->id,
        'education_level' => 'diploma',
        'work_experience_years' => 0,
        'work_experience_months' => 0,
        'field_of_study' => 'Accounting',
        'graduation_year' => 2020,
        'gpa' => 3.0,
    ]);
    $vacancy = degreeOrDiplomaVacancy();

    $this->actingAs($user)->get(route('applicant.applications.create', $vacancy))
        ->assertOk()
        ->assertSee(__('vacancies.eligibility_meets_no_option'));

    $this->actingAs($user)->post(route('applicant.applications.store', $vacancy), [])
        ->assertSessionHasErrors('eligibility');

    expect(Application::where('vacancy_id', $vacancy->id)->exists())->toBeFalse();
});

test('eligible applicant can apply', function (): void {
    $user = User::factory()->asApplicant()->create();
    Applicant::factory()->create([
        'user_id' => $user->id,
        'education_level' => 'diploma',
        'work_experience_years' => 2,
        'field_of_study' => 'Accounting',
        'graduation_year' => 2018,
        'gpa' => 3.0,
    ]);
    $vacancy = degreeOrDiplomaVacancy();

    $this->actingAs($user)->post(route('applicant.applications.store', $vacancy), [])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect(Application::where('vacancy_id', $vacancy->id)->exists())->toBeTrue();
});

// ── Public vacancy page ──────────────────────────────────────────────────────

test('public vacancy page lists requirement options as alternatives', function (): void {
    $vacancy = degreeOrDiplomaVacancy();

    $this->get(route('vacancies.show', $vacancy))
        ->assertOk()
        ->assertSee(__('vacancies.eligibility_requirements'))
        ->assertSee(__('vacancies.requirement_option', ['number' => 1]))
        ->assertSee(__('vacancies.requirement_option', ['number' => 2]))
        ->assertSee(EducationLevel::Degree->getLabel())
        ->assertSee(EducationLevel::Diploma->getLabel());
});
