<?php

declare(strict_types=1);

use App\Actions\Applicants\UpdateApplicantProfileAction;
use App\Actions\Applications\ChangeApplicationLockAction;
use App\Actions\Applications\ReplaceApplicationDocumentAction;
use App\Actions\Applications\SubmitApplicationAction;
use App\Actions\Applications\UpdateApplicationAction;
use App\Enums\ApplicationStatus;
use App\Exceptions\RecruitmentRuleException;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Vacancy;
use App\Models\VacancyDocument;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
    $this->travelTo('2026-10-01 12:00:00');
});

function windowApplicationData(): array
{
    return ['field_of_study' => 'Computer Science', 'graduation_date' => '2021-07-01', 'cgpa' => '3.40'];
}

/** Full profile payload for UpdateApplicantProfileAction. */
function windowProfileData(Applicant $applicant, array $overrides = []): array
{
    return array_merge([
        'first_name' => $applicant->first_name ?? 'Sara', 'middle_name' => $applicant->middle_name,
        'last_name' => $applicant->last_name ?? 'Bekele', 'phone' => $applicant->phone, 'email' => $applicant->email,
        'national_id' => $applicant->national_id, 'gender' => $applicant->gender?->value ?? 'female',
        'university_name' => $applicant->university_name, 'field_of_study' => $applicant->field_of_study,
        'gpa' => $applicant->gpa, 'preferred_locale' => 'en',
    ], $overrides);
}

// ── Submission window ───────────────────────────────────────────────────────

test('submission before the opening date is blocked', function (): void {
    $vacancy = lifecycleVacancy('2026-10-05', '2026-10-20');
    $applicant = Applicant::factory()->create();

    $this->actingAs($applicant->user)->post(route('applicant.applications.store', $vacancy), windowApplicationData())
        ->assertRedirect(route('vacancies.show', $vacancy))
        ->assertSessionHas('error', __('recruitment.errors.application_not_open'));

    expect(fn () => app(SubmitApplicationAction::class)->handle($applicant, $vacancy, windowApplicationData()))
        ->toThrow(RecruitmentRuleException::class, __('recruitment.errors.application_not_open'));
    expect(Application::count())->toBe(0);
});

test('submission during the application period is accepted and snapshotted', function (): void {
    $vacancy = lifecycleVacancy('2026-09-20', '2026-10-10');
    $applicant = Applicant::factory()->create(['university_name' => 'Addis Ababa University']);

    $this->actingAs($applicant->user)->post(route('applicant.applications.store', $vacancy), windowApplicationData())
        ->assertSessionHasNoErrors();

    $application = Application::sole();
    expect($application->profile_snapshot['university_name'])->toBe('Addis Ababa University')
        ->and($application->profile_snapshot['national_id_reference'])->toEndWith(substr($applicant->national_id, -4))
        ->and($application->profile_snapshot['national_id_reference'])->not->toBe($applicant->national_id)
        ->and($application->snapshot_taken_at)->not->toBeNull()
        ->and(AuditLog::where('action', 'application_submitted')->where('record_id', $application->id)->exists())->toBeTrue();
});

test('submission after the closing date is blocked', function (): void {
    $vacancy = lifecycleVacancy('2026-09-01', '2026-09-30');
    $applicant = Applicant::factory()->create();

    $this->actingAs($applicant->user)->post(route('applicant.applications.store', $vacancy), windowApplicationData())
        ->assertSessionHas('error', __('recruitment.errors.application_closed'));

    expect(Application::count())->toBe(0);
});

test('cancelled, draft and finalized announcements refuse applications', function (string $status): void {
    $vacancy = lifecycleVacancy('2026-09-20', '2026-10-10', ['status' => $status]);

    expect($vacancy->canAcceptApplications())->toBeFalse()
        ->and(fn () => app(SubmitApplicationAction::class)->handle(Applicant::factory()->create(), $vacancy, windowApplicationData()))
        ->toThrow(RecruitmentRuleException::class);
})->with(['draft', 'cancelled', 'finalized']);

// ── Editing window ──────────────────────────────────────────────────────────

test('editing during the application period is allowed', function (): void {
    $vacancy = lifecycleVacancy('2026-09-20', '2026-10-10');
    $applicant = Applicant::factory()->create();
    $application = app(SubmitApplicationAction::class)->handle($applicant, $vacancy, windowApplicationData());

    $this->actingAs($applicant->user)->put(route('applicant.applications.update', $application), [
        'field_of_study' => 'Software Engineering', 'graduation_date' => '2021-07-01',
    ])->assertSessionHasNoErrors();

    expect($application->refresh()->field_of_study)->toBe('Software Engineering')
        ->and($application->profile_snapshot['field_of_study'])->toBe('Software Engineering')
        ->and(AuditLog::where('action', 'application_edited')->where('record_id', $application->id)->exists())->toBeTrue();
});

test('editing after the closing date is blocked and the application becomes read-only', function (): void {
    $vacancy = lifecycleVacancy('2026-09-20', '2026-10-10');
    $applicant = Applicant::factory()->create();
    $application = app(SubmitApplicationAction::class)->handle($applicant, $vacancy, windowApplicationData());

    $this->travelTo('2026-10-11 00:00:01');

    $this->actingAs($applicant->user)->put(route('applicant.applications.update', $application), [
        'field_of_study' => 'Changed', 'graduation_date' => '2021-07-01',
    ])->assertForbidden();
    $this->get(route('applicant.applications.edit', $application))->assertForbidden();
    $this->get(route('applicant.applications.show', $application))->assertOk()
        ->assertSee(__('recruitment.applicant.read_only'))
        ->assertSee(__('recruitment.applicant.closed'));

    expect(fn () => app(UpdateApplicationAction::class)->handle($application, ['field_of_study' => 'Changed', 'graduation_date' => '2021-07-01']))
        ->toThrow(RecruitmentRuleException::class, __('recruitment.errors.application_read_only'));
    expect($application->refresh()->field_of_study)->toBe('Computer Science');
});

test('document replacement after the closing date is blocked', function (): void {
    $vacancy = lifecycleVacancy('2026-09-20', '2026-10-10');
    $applicant = Applicant::factory()->create();
    $application = app(SubmitApplicationAction::class)->handle($applicant, $vacancy, windowApplicationData());
    $requirement = VacancyDocument::factory()->create(['vacancy_id' => $vacancy->id]);
    $document = ApplicationDocument::create([
        'application_id' => $application->id, 'vacancy_document_id' => $requirement->id,
        'file_name' => 'old.pdf', 'original_name' => 'old.pdf', 'file_path' => 'applications/old.pdf',
        'file_type' => 'pdf', 'file_size' => 1024,
    ]);

    $this->travelTo('2026-10-11 08:00:00');
    $file = UploadedFile::fake()->create('new.pdf', 100, 'application/pdf');

    $this->actingAs($applicant->user)
        ->post(route('applicant.applications.documents.replace', [$application, $document]), ['file' => $file])
        ->assertForbidden();
    expect(fn () => app(ReplaceApplicationDocumentAction::class)->handle($document, $file))
        ->toThrow(RecruitmentRuleException::class);
    expect($document->refresh()->original_name)->toBe('old.pdf');
});

test('an administrative lock blocks editing even while the window is open', function (): void {
    $vacancy = lifecycleVacancy('2026-09-20', '2026-10-10');
    $application = lifecycleApplication($vacancy, ApplicationStatus::Submitted);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.applications.lock', $application), ['lock_reason' => 'Under investigation'])
        ->assertSessionHasNoErrors();

    expect($application->refresh()->isEditable())->toBeFalse()
        ->and(AuditLog::where('action', 'application_locked')->where('record_id', $application->id)->exists())->toBeTrue();
});

test('reopening is an authorised, reasoned, time-boxed action that relocks itself', function (): void {
    $vacancy = lifecycleVacancy('2026-09-01', '2026-09-30');
    $application = lifecycleApplication($vacancy, ApplicationStatus::CorrectionRequired);
    expect($application->isEditable())->toBeFalse();

    // A screening officer has no applications.unlock permission.
    $this->actingAs(User::factory()->screeningOfficer()->create())
        ->post(route('admin.applications.reopen', $application), ['reopened_until' => '2026-10-03', 'reopen_reason' => 'Allow certificate re-upload'])
        ->assertForbidden();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.applications.reopen', $application), ['reopened_until' => '2026-10-03'])
        ->assertSessionHasErrors('reopen_reason');

    $this->post(route('admin.applications.reopen', $application), ['reopened_until' => '2026-10-03', 'reopen_reason' => 'Allow certificate re-upload'])
        ->assertSessionHasNoErrors();

    expect($application->refresh()->isEditable())->toBeTrue()
        ->and(AuditLog::where('action', 'application_reopened')->where('record_id', $application->id)->exists())->toBeTrue();

    $this->travelTo('2026-10-04 00:00:01');
    expect($application->refresh()->isEditable())->toBeFalse();
});

test('a decided application cannot be reopened', function (): void {
    $vacancy = lifecycleVacancy('2026-09-01', '2026-09-30');
    $application = lifecycleApplication($vacancy, ApplicationStatus::PassedScreening);

    expect(fn () => app(ChangeApplicationLockAction::class)->reopen($application, User::factory()->admin()->create(), '2026-10-03', 'Too late now'))
        ->toThrow(RecruitmentRuleException::class, __('recruitment.errors.reopen_not_allowed'));
});

// ── Profile vs application snapshot ─────────────────────────────────────────

test('a closed application snapshot does not change when the global profile changes later', function (): void {
    $closedVacancy = lifecycleVacancy('2026-09-20', '2026-10-05');
    $applicant = Applicant::factory()->create(['university_name' => 'Jimma University', 'field_of_study' => 'Accounting']);
    $closedApplication = app(SubmitApplicationAction::class)->handle($applicant, $closedVacancy, windowApplicationData());

    // A later recruitment opens; the first one has closed.
    $this->travelTo('2026-10-08 10:00:00');
    $openVacancy = lifecycleVacancy('2026-10-06', '2026-10-30');
    $openApplication = app(SubmitApplicationAction::class)->handle($applicant->refresh(), $openVacancy, windowApplicationData());

    app(UpdateApplicantProfileAction::class)->handle($applicant->refresh(), windowProfileData($applicant, ['university_name' => 'Bahir Dar University']));

    expect($applicant->refresh()->university_name)->toBe('Bahir Dar University')
        ->and($closedApplication->refresh()->profile_snapshot['university_name'])->toBe('Jimma University')
        ->and($openApplication->refresh()->profile_snapshot['university_name'])->toBe('Bahir Dar University');
});

// ── Concurrency ─────────────────────────────────────────────────────────────

test('the deadline is re-checked inside the transaction against fresh data', function (): void {
    $vacancy = lifecycleVacancy('2026-09-20', '2026-10-10');
    // The request loaded the vacancy while it was open …
    $stale = Vacancy::with('announcement')->find($vacancy->id);
    expect($stale->canAcceptApplications())->toBeTrue();

    // … but before it commits the announcement is no longer accepting applications.
    $vacancy->announcement->forceFill(['status' => 'cancelled'])->save();

    expect(fn () => app(SubmitApplicationAction::class)->handle(Applicant::factory()->create(), $stale, windowApplicationData()))
        ->toThrow(RecruitmentRuleException::class);
    expect(Application::count())->toBe(0);
});

test('a duplicate concurrent application is still rejected by the database guard', function (): void {
    $vacancy = lifecycleVacancy('2026-09-20', '2026-10-10');
    $applicant = Applicant::factory()->create();
    app(SubmitApplicationAction::class)->handle($applicant, $vacancy, windowApplicationData());

    expect(fn () => app(SubmitApplicationAction::class)->handle($applicant, $vacancy, windowApplicationData()))
        ->toThrow(ValidationException::class, __('applications.duplicate_application'));
    expect(Application::where('applicant_id', $applicant->id)->count())->toBe(1);
});

test('one applicant can apply to several institutions in one announcement but once per institution', function (): void {
    $first = lifecycleVacancy('2026-09-20', '2026-10-10');
    $second = Vacancy::factory()->for($first->announcement, 'announcement')->create();
    $applicant = Applicant::factory()->create();

    app(SubmitApplicationAction::class)->handle($applicant, $first, windowApplicationData());
    app(SubmitApplicationAction::class)->handle($applicant, $second, windowApplicationData());

    expect($applicant->applications()->count())->toBe(2)
        ->and(fn () => app(SubmitApplicationAction::class)->handle($applicant, $first, windowApplicationData()))
        ->toThrow(ValidationException::class);
});

// ── Applicant UI ────────────────────────────────────────────────────────────

test('the vacancy page shows upcoming, open and closed states', function (): void {
    $vacancy = lifecycleVacancy('2026-10-05', '2026-10-20');
    $applicant = Applicant::factory()->create();
    $opensOn = __('recruitment.applicant.opens_on', ['date' => et_date($vacancy->announcement->opening_date, 'M d, Y')]);

    $this->actingAs($applicant->user)->get(route('applicant.vacancies.show', $vacancy))
        ->assertOk()->assertSee($opensOn)->assertDontSee(route('applicant.applications.create', $vacancy), false);

    $this->travelTo('2026-10-06 09:00:00');
    $this->flushSession();
    $this->actingAs($applicant->user)->get(route('applicant.vacancies.show', $vacancy))
        ->assertOk()->assertSee(__('vacancies.apply_now'))->assertSee(route('applicant.applications.create', $vacancy), false);

    $this->travelTo('2026-10-21 09:00:00');
    $this->flushSession();
    $this->actingAs($applicant->user)->get(route('vacancies.show', $vacancy))
        ->assertOk()->assertSee(__('recruitment.applicant.closed'))->assertDontSee(__('vacancies.apply_now'));
});
