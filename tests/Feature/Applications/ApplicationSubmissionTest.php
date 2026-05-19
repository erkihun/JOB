<?php

declare(strict_types=1);

use App\Enums\ApplicationStatus;
use App\Models\Applicant;
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

test('applicant cannot edit application after passed or failed screening decision', function (ApplicationStatus $status) {
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
        'field_of_study' => 'Unauthorized Update',
        'graduation_date' => now()->subYears(3)->toDateString(),
    ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('applications', [
        'id' => $application->id,
        'field_of_study' => 'Unauthorized Update',
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
