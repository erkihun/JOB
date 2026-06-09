<?php

declare(strict_types=1);

use App\Enums\ApplicationStatus;
use App\Models\Applicant;
use App\Models\ApplicantProfileDocument;
use App\Models\Application;
use App\Models\User;
use App\Models\Vacancy;
use App\Models\VacancyDocument;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
});

// ── Create page rendering ─────────────────────────────────────────────────────

test('application create page renders for a vacancy with required documents', function () {
    $user = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $user->id]);
    $vacancy = Vacancy::factory()->open()->create();
    VacancyDocument::factory()->for($vacancy)->create([
        'document_name' => 'Degree Certificate',
    ]);

    $response = $this->actingAs($user)->get(route('applicant.applications.create', $vacancy));

    $response->assertOk();
    // The document label must render (regression: VacancyDocument has no
    // getTranslation() — document_name is a plain string column).
    $response->assertSee('Degree Certificate', false);
});

test('application create page renders in amharic with required documents', function () {
    $user = User::factory()->asApplicant()->create(['preferred_locale' => 'am']);
    Applicant::factory()->create(['user_id' => $user->id]);
    $vacancy = Vacancy::factory()->open()->create();
    VacancyDocument::factory()->for($vacancy)->create([
        'document_name' => 'ID Card',
    ]);

    $this->get(route('lang.switch', 'am'));

    $response = $this->actingAs($user)->get(route('applicant.applications.create', $vacancy));

    $response->assertOk();
    $response->assertSee('ID Card', false);
});

// ── Profile pre-fill on apply ──────────────────────────────────────────────────

test('academic fields are hidden on apply form when the profile already has them', function () {
    $user = User::factory()->asApplicant()->create();
    Applicant::factory()->create([
        'user_id' => $user->id,
        'field_of_study' => 'Computer Science',
        'graduation_year' => 2018,
        'gpa' => 3.75,
    ]);
    $vacancy = Vacancy::factory()->open()->create();

    $response = $this->actingAs($user)->get(route('applicant.applications.create', $vacancy));

    $response->assertOk();
    // The visible text inputs for academic fields must not be rendered ...
    $response->assertDontSee('id="field_of_study"', false);
    $response->assertDontSee('id="graduation_date"', false);
    // ... but the values are submitted via hidden inputs.
    $response->assertSee('name="field_of_study"', false);
    $response->assertSee('name="graduation_date"', false);
});

test('missing academic field is still asked while present ones are hidden', function () {
    $user = User::factory()->asApplicant()->create();
    Applicant::factory()->create([
        'user_id' => $user->id,
        'field_of_study' => 'Accounting',
        'graduation_year' => null,   // missing → must still be asked
        'gpa' => null,
    ]);
    $vacancy = Vacancy::factory()->open()->create();

    $response = $this->actingAs($user)->get(route('applicant.applications.create', $vacancy));

    $response->assertOk();
    // field_of_study present → hidden; graduation_date missing → visible input shown.
    $response->assertDontSee('id="field_of_study"', false);
    $response->assertSee('id="graduation_date"', false);
});

test('applicant can submit without re-entering academic data the profile supplies', function () {
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create([
        'user_id' => $user->id,
        'field_of_study' => 'Computer Science',
        'graduation_year' => 2018,
        'gpa' => 3.75,
    ]);
    $vacancy = Vacancy::factory()->open()->create();

    // POST with NO academic fields — they must be back-filled from the profile.
    $response = $this->actingAs($user)->post(route('applicant.applications.store', $vacancy), []);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $application = Application::where('applicant_id', $applicant->id)->firstOrFail();
    expect($application->field_of_study)->toBe('Computer Science')
        ->and($application->graduation_date->format('Y'))->toBe('2018')
        ->and((float) $application->cgpa)->toBe(3.75);
});

test('explicit graduation_date in the profile is preferred over the derived year', function () {
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create([
        'user_id' => $user->id,
        'field_of_study' => 'Law',
        'graduation_year' => 2019,
        'graduation_date' => '2019-07-15',
        'gpa' => 3.10,
    ]);
    $vacancy = Vacancy::factory()->open()->create();

    $this->actingAs($user)->post(route('applicant.applications.store', $vacancy), [])
        ->assertRedirect();

    $application = Application::where('applicant_id', $applicant->id)->firstOrFail();
    expect($application->graduation_date->toDateString())->toBe('2019-07-15');
});

test('fully complete profile applies in one click skipping required documents', function () {
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create([
        'user_id' => $user->id,
        'first_name' => 'Sara',
        'last_name' => 'Bekele',
        'national_id' => '1234567890123456',
        'gender' => 'female',
        'date_of_birth' => '1995-01-01',
        'phone' => '+251911223344',
        'university_name' => 'AAU',
        'field_of_study' => 'Computer Science',
        'graduation_year' => 2018,
        'gpa' => 3.75,
        'address' => 'Addis Ababa',
        'profile_photo_path' => 'photos/sara.jpg',
    ]);
    // The completion metric also counts an uploaded general document.
    ApplicantProfileDocument::create([
        'applicant_id' => $applicant->id,
        'document_type' => 'documents',
        'file_name' => 'doc.pdf',
        'original_name' => 'doc.pdf',
        'file_path' => 'applicant-documents/'.$applicant->id.'/doc.pdf',
        'file_type' => 'application/pdf',
        'file_size' => 1000,
    ]);

    expect($applicant->fresh()->profileCompletionPercentage())->toBe(100);

    $vacancy = Vacancy::factory()->open()->create();
    VacancyDocument::factory()->for($vacancy)->create(['document_name' => 'CV']);

    // One-click apply: no academic fields, no documents — must still succeed.
    $response = $this->actingAs($user)->get(route('applicant.applications.create', $vacancy));
    $response->assertOk();
    $response->assertDontSee('name="documents[', false);

    $this->actingAs($user)->post(route('applicant.applications.store', $vacancy), [])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Application::where('applicant_id', $applicant->id)->exists())->toBeTrue();
});

// ── Submission ──────────────────────────────────────────────────────────────

test('applicant can submit application to open vacancy', function () {
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);
    $vacancy = Vacancy::factory()->open()->create();

    $response = $this->actingAs($user)->post(route('applicant.applications.store', $vacancy), [
        'field_of_study' => 'Computer Science',
        'graduation_date' => now()->subYears(2)->toDateString(),
        'cgpa' => '3.75',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $this->assertDatabaseHas('applications', [
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancy->id,
        'field_of_study' => 'Computer Science',
    ]);
});

test('submitted application gets reference number', function () {
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);
    $vacancy = Vacancy::factory()->open()->create();

    $this->actingAs($user)->post(route('applicant.applications.store', $vacancy), [
        'field_of_study' => 'Accounting',
        'graduation_date' => now()->subYears(3)->toDateString(),
    ]);

    $application = Application::where('applicant_id', $applicant->id)->first();
    expect($application)->not->toBeNull();
    expect($application->reference_number)->toMatch('/^APP-\d{4}-\d{6}$/');
});

test('applicant cannot apply to closed vacancy', function () {
    $user = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $user->id]);
    $vacancy = Vacancy::factory()->closed()->create();

    $response = $this->actingAs($user)->post(route('applicant.applications.store', $vacancy), [
        'field_of_study' => 'Law',
        'graduation_date' => now()->subYears(2)->toDateString(),
    ]);

    $response->assertStatus(422);
    $this->assertDatabaseMissing('applications', ['vacancy_id' => $vacancy->id]);
});

test('applicant cannot apply to vacancy past deadline', function () {
    $user = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $user->id]);
    $vacancy = Vacancy::factory()->pastDeadline()->create();

    $response = $this->actingAs($user)->post(route('applicant.applications.store', $vacancy), [
        'field_of_study' => 'Engineering',
        'graduation_date' => now()->subYears(2)->toDateString(),
    ]);

    $response->assertRedirect();
    $this->assertDatabaseMissing('applications', ['vacancy_id' => $vacancy->id]);
});

test('applicant cannot apply twice to same vacancy via http', function () {
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);
    $vacancy = Vacancy::factory()->open()->create();

    $this->actingAs($user)->post(route('applicant.applications.store', $vacancy), [
        'field_of_study' => 'Computer Science',
        'graduation_date' => now()->subYears(2)->toDateString(),
    ]);

    $response = $this->actingAs($user)->post(route('applicant.applications.store', $vacancy), [
        'field_of_study' => 'Computer Science',
        'graduation_date' => now()->subYears(2)->toDateString(),
    ]);

    $response->assertRedirect();
    expect(Application::where('applicant_id', $applicant->id)
        ->where('vacancy_id', $vacancy->id)
        ->count()
    )->toBe(1);
});

// ── Document Upload ──────────────────────────────────────────────────────────

test('applicant can submit application with required document', function () {
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);
    $vacancy = Vacancy::factory()->open()->create();

    VacancyDocument::factory()->create([
        'vacancy_id' => $vacancy->id,
        'document_name' => 'CV',
        'is_required' => true,
        'allowed_types' => ['pdf', 'jpg', 'jpeg', 'png'],
        'max_size_mb' => 2,
    ]);

    $vacancy->refresh();
    $docId = $vacancy->requiredDocuments->first()->id;

    $file = UploadedFile::fake()->create('cv.pdf', 500, 'application/pdf');

    $response = $this->actingAs($user)->post(route('applicant.applications.store', $vacancy), [
        'field_of_study' => 'Computer Science',
        'graduation_date' => now()->subYears(2)->toDateString(),
        'documents' => [$docId => $file],
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $application = Application::where('applicant_id', $applicant->id)->first();
    expect($application)->not->toBeNull();
    expect($application->documents()->count())->toBe(1);
});

test('uploaded document is stored privately not in public disk', function () {
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);
    $vacancy = Vacancy::factory()->open()->create();

    VacancyDocument::factory()->create([
        'vacancy_id' => $vacancy->id,
        'document_name' => 'Certificate',
        'is_required' => true,
        'allowed_types' => ['pdf', 'jpg', 'jpeg', 'png'],
        'max_size_mb' => 2,
    ]);

    $vacancy->refresh();
    $docId = $vacancy->requiredDocuments->first()->id;

    $file = UploadedFile::fake()->create('cert.pdf', 500, 'application/pdf');

    $this->actingAs($user)->post(route('applicant.applications.store', $vacancy), [
        'field_of_study' => 'Law',
        'graduation_date' => now()->subYears(2)->toDateString(),
        'documents' => [$docId => $file],
    ]);

    $application = Application::where('applicant_id', $applicant->id)->first();
    $document = $application?->documents()->first();

    expect($document)->not->toBeNull();

    // File must be on local (private) disk
    Storage::disk('local')->assertExists($document->file_path);

    // File must NOT be on public disk
    Storage::disk('public')->assertMissing($document->file_path);
});

test('file over 2mb is rejected during submission', function () {
    $user = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $user->id]);
    $vacancy = Vacancy::factory()->open()->create();

    $vacDoc = VacancyDocument::factory()->create([
        'vacancy_id' => $vacancy->id,
        'document_name' => 'ID',
        'is_required' => true,
        'allowed_types' => ['pdf', 'jpg', 'jpeg', 'png'],
        'max_size_mb' => 2,
    ]);

    $file = UploadedFile::fake()->create('large.pdf', 2049, 'application/pdf'); // > 2 MB

    $response = $this->actingAs($user)->post(route('applicant.applications.store', $vacancy), [
        'field_of_study' => 'Finance',
        'graduation_date' => now()->subYears(2)->toDateString(),
        'documents' => [$vacDoc->id => $file],
    ]);

    $response->assertSessionHasErrors();
    $this->assertDatabaseMissing('applications', ['vacancy_id' => $vacancy->id]);
});

test('unsupported file type is rejected during submission', function () {
    $user = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $user->id]);
    $vacancy = Vacancy::factory()->open()->create();

    $vacDoc = VacancyDocument::factory()->create([
        'vacancy_id' => $vacancy->id,
        'document_name' => 'Resume',
        'is_required' => true,
        'allowed_types' => ['pdf', 'jpg', 'jpeg', 'png'],
        'max_size_mb' => 2,
    ]);

    $file = UploadedFile::fake()->create('resume.docx', 300,
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    );

    $response = $this->actingAs($user)->post(route('applicant.applications.store', $vacancy), [
        'field_of_study' => 'Business',
        'graduation_date' => now()->subYears(2)->toDateString(),
        'documents' => [$vacDoc->id => $file],
    ]);

    $response->assertSessionHasErrors();
    $this->assertDatabaseMissing('applications', ['vacancy_id' => $vacancy->id]);
});

// ── Viewing ──────────────────────────────────────────────────────────────────

test('applicant can view their own application', function () {
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);
    $vacancy = Vacancy::factory()->open()->create();

    $application = Application::create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancy->id,
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('applicant.applications.show', $application));

    $response->assertOk();
    $response->assertSee($application->reference_number);
});

test('applicant cannot view another applicants application', function () {
    $user1 = User::factory()->asApplicant()->create();
    $applicant1 = Applicant::factory()->create(['user_id' => $user1->id]);

    $user2 = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $user2->id]);

    $vacancy = Vacancy::factory()->open()->create();

    $application = Application::create([
        'applicant_id' => $applicant1->id,
        'vacancy_id' => $vacancy->id,
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
    ]);

    // user2 tries to view user1's application
    $response = $this->actingAs($user2)->get(route('applicant.applications.show', $application));

    $response->assertForbidden();
});

// ── Edit ─────────────────────────────────────────────────────────────────────

test('applicant can edit application before deadline', function () {
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);
    $vacancy = Vacancy::factory()->open()->create();

    $application = Application::create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancy->id,
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
    ]);

    $response = $this->actingAs($user)->put(route('applicant.applications.update', $application), [
        'field_of_study' => 'Information Technology',
        'graduation_date' => now()->subYears(3)->toDateString(),
    ]);

    $response->assertRedirect(route('applicant.applications.show', $application));
    $this->assertDatabaseHas('applications', [
        'id' => $application->id,
        'field_of_study' => 'Information Technology',
    ]);
});

test('edit page hides academic information and shows the applied-position selector', function () {
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);
    $vacancy = Vacancy::factory()->open()->create();

    $application = Application::create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancy->id,
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('applicant.applications.edit', $application));

    $response->assertOk();
    // Academic Information section is not displayed ...
    $response->assertDontSee(__('applicant.academic_info'), false);
    // ... but the values are still preserved as hidden inputs.
    $response->assertSee('name="field_of_study"', false);
    $response->assertSee('name="graduation_date"', false);
    // The applied-position selector is shown.
    $response->assertSee('name="vacancy_id"', false);
    $response->assertSee(__('applicant.applied_position'), false);
});

test('applicant can change the applied position to another open vacancy', function () {
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);
    $vacancyA = Vacancy::factory()->open()->create();
    $vacancyB = Vacancy::factory()->open()->create();

    $application = Application::create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancyA->id,
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
    ]);

    $response = $this->actingAs($user)->put(route('applicant.applications.update', $application), [
        'vacancy_id' => $vacancyB->id,
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2)->toDateString(),
    ]);

    $response->assertRedirect()->assertSessionHasNoErrors();
    $this->assertDatabaseHas('applications', [
        'id' => $application->id,
        'vacancy_id' => $vacancyB->id,
    ]);
});

test('changing position to one already applied to is rejected as a duplicate', function () {
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);
    $vacancyA = Vacancy::factory()->open()->create();
    $vacancyB = Vacancy::factory()->open()->create();

    // Existing applications to both A and B.
    $appA = Application::create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancyA->id,
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
    ]);
    Application::create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancyB->id,
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
    ]);

    // Attempt to move application A onto vacancy B (already applied) → rejected.
    $response = $this->actingAs($user)->put(route('applicant.applications.update', $appA), [
        'vacancy_id' => $vacancyB->id,
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2)->toDateString(),
    ]);

    $response->assertSessionHasErrors('vacancy_id');
    // Application A stays on its original vacancy.
    $this->assertDatabaseHas('applications', [
        'id' => $appA->id,
        'vacancy_id' => $vacancyA->id,
    ]);
});

test('cannot change position to a closed vacancy', function () {
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);
    $openVacancy = Vacancy::factory()->open()->create();
    $closedVacancy = Vacancy::factory()->closed()->create();

    $application = Application::create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $openVacancy->id,
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
    ]);

    $response = $this->actingAs($user)->put(route('applicant.applications.update', $application), [
        'vacancy_id' => $closedVacancy->id,
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2)->toDateString(),
    ]);

    $response->assertSessionHasErrors('vacancy_id');
    $this->assertDatabaseHas('applications', [
        'id' => $application->id,
        'vacancy_id' => $openVacancy->id,
    ]);
});

test('applicant cannot edit application after deadline', function () {
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);
    $vacancy = Vacancy::factory()->pastDeadline()->create();

    $application = Application::create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancy->id,
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now()->subDays(5),
    ]);

    $response = $this->actingAs($user)->put(route('applicant.applications.update', $application), [
        'field_of_study' => 'Hacking Attempt',
        'graduation_date' => now()->subYears(3)->toDateString(),
    ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('applications', [
        'id' => $application->id,
        'field_of_study' => 'Hacking Attempt',
    ]);
});

// ── Admin route access ────────────────────────────────────────────────────────

test('applicant can edit application after a screening decision while the vacancy is open', function (ApplicationStatus $status) {
    // Business rule: applications stay editable until the closing date, even after
    // a screening pass/fail decision.
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);
    $vacancy = Vacancy::factory()->open()->create([
        'closing_date' => now()->addDays(30),
    ]);

    $application = Application::create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancy->id,
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2),
        'status' => $status,
        'submitted_at' => now(),
    ]);

    $response = $this->actingAs($user)->put(route('applicant.applications.update', $application), [
        'field_of_study' => 'Updated Field',
        'graduation_date' => now()->subYears(3)->toDateString(),
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('applications', [
        'id' => $application->id,
        'field_of_study' => 'Updated Field',
    ]);
})->with([
    'passed screening' => [ApplicationStatus::PassedScreening],
    'failed screening' => [ApplicationStatus::FailedScreening],
]);

test('applicant cannot access admin panel routes', function () {
    $user = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $user->id]);

    // Filament admin panel is mounted at /admin
    $response = $this->actingAs($user)->get('/admin');

    // Should be forbidden or redirect to login — never 200
    expect($response->status())->not->toBe(200);
});

// ── Profile ───────────────────────────────────────────────────────────────────

test('applicant can view their profile', function () {
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('applicant.profile.show'));

    $response->assertOk();
    $response->assertSee($applicant->full_name);
});

test('applicant can update their profile', function () {
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->put(route('applicant.profile.update'), [
        'first_name' => 'Updated',
        'middle_name' => '',
        'last_name' => 'Name',
        'email' => $applicant->email,
        'phone' => $applicant->phone,
        'national_id' => $applicant->national_id,
        'gender' => $applicant->gender?->value ?? 'male',
        'disability_status' => 0,
        'preferred_locale' => 'en',
    ]);

    $response->assertRedirect(route('applicant.profile.show'));
    $this->assertDatabaseHas('applicants', [
        'id' => $applicant->id,
        'full_name' => 'Updated Name',
    ]);
});
