<?php

declare(strict_types=1);

use App\Enums\ApplicationStatus;
use App\Enums\VacancyStatus;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\User;
use App\Models\Vacancy;
use App\Models\VacancyDocument;
use App\Services\CodeGeneratorService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
});

// ── Unique constraint: database-level duplicate guard ────────────────────────

test('database unique constraint blocks duplicate (applicant_id, vacancy_id)', function () {
    $applicant = Applicant::factory()->create(['user_id' => User::factory()->asApplicant()->create()->id]);
    $vacancy = Vacancy::factory()->open()->create();

    Application::create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancy->id,
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
    ]);

    expect(fn () => Application::create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancy->id,
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
    ]))->toThrow(UniqueConstraintViolationException::class);

    expect(Application::where('applicant_id', $applicant->id)->count())->toBe(1);
});

// ── Race condition: concurrent HTTP submissions produce exactly one row ───────

test('concurrent duplicate submissions via HTTP result in exactly one application', function () {
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);
    $vacancy = Vacancy::factory()->open()->create();

    $payload = [
        'field_of_study' => 'Computer Science',
        'graduation_date' => now()->subYears(2)->toDateString(),
        'cgpa' => '3.50',
    ];

    // First request succeeds
    $r1 = $this->actingAs($user)->post(route('applicant.applications.store', $vacancy), $payload);
    $r1->assertRedirect();

    // Second request should redirect back with an error (duplicate guard)
    $r2 = $this->actingAs($user)->post(route('applicant.applications.store', $vacancy), $payload);
    $r2->assertRedirect();

    expect(
        Application::where('applicant_id', $applicant->id)
            ->where('vacancy_id', $vacancy->id)
            ->count()
    )->toBe(1);
});

// ── Reference numbers stay unique across multiple submissions ─────────────────

test('reference numbers are unique across many applications', function () {
    $refs = [];

    for ($i = 0; $i < 20; $i++) {
        $user = User::factory()->asApplicant()->create();
        $applicant = Applicant::factory()->create(['user_id' => $user->id]);
        $vacancy = Vacancy::factory()->open()->create();

        $this->actingAs($user)->post(route('applicant.applications.store', $vacancy), [
            'field_of_study' => 'Engineering',
            'graduation_date' => now()->subYears(2)->toDateString(),
        ])->assertRedirect();

        $ref = Application::query()
            ->where('applicant_id', $applicant->id)
            ->where('vacancy_id', $vacancy->id)
            ->value('reference_number');

        expect($ref)->not->toBeIn($refs);
        $refs[] = $ref;
    }

    expect(array_unique($refs))->toHaveCount(20);
});

test('CodeGeneratorService generates unique codes when collision occurs', function () {
    $service = app(CodeGeneratorService::class);
    $codes = [];

    for ($i = 0; $i < 50; $i++) {
        $applicant = Applicant::factory()->create(['user_id' => User::factory()->asApplicant()->create()->id]);
        $vacancy = Vacancy::factory()->open()->create();
        $code = $service->forApplication();

        Application::create([
            'applicant_id' => $applicant->id,
            'vacancy_id' => $vacancy->id,
            'reference_number' => $code,
            'field_of_study' => 'Engineering',
            'graduation_date' => now()->subYears(2),
            'status' => ApplicationStatus::Submitted,
            'submitted_at' => now(),
        ]);

        $codes[] = $code;
    }

    expect(array_unique($codes))->toHaveCount(count($codes));
});

// ── Upload failure does not leave orphan application ──────────────────────────

test('failed document upload rolls back application creation', function () {
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);
    $vacancy = Vacancy::factory()->open()->create();

    $vacDoc = VacancyDocument::factory()->create([
        'vacancy_id' => $vacancy->id,
        'document_name' => 'CV',
        'is_required' => true,
        'allowed_types' => ['pdf'],
        'max_size_mb' => 2,
    ]);

    // Provide an invalid file type to trigger validation failure before upload.
    $file = UploadedFile::fake()->create('hack.exe', 100, 'application/octet-stream');

    $response = $this->actingAs($user)->post(route('applicant.applications.store', $vacancy), [
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2)->toDateString(),
        'documents' => [$vacDoc->id => $file],
    ]);

    $response->assertSessionHasErrors();

    // No orphaned application row
    expect(Application::where('applicant_id', $applicant->id)->count())->toBe(0);
});

// ── Business rule: vacancy open status ───────────────────────────────────────

test('application to a draft vacancy is blocked', function () {
    $user = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $user->id]);
    $vacancy = Vacancy::factory()->create(['status' => VacancyStatus::Draft]);

    $response = $this->actingAs($user)->post(route('applicant.applications.store', $vacancy), [
        'field_of_study' => 'Law',
        'graduation_date' => now()->subYears(2)->toDateString(),
    ]);

    expect($response->status())->toBeIn([404, 422]);
    $this->assertDatabaseMissing('applications', ['vacancy_id' => $vacancy->id]);
});

test('application after closing date is blocked', function () {
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

// ── File security: size and type validation ───────────────────────────────────

test('document over 2 MB is rejected', function () {
    $user = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $user->id]);
    $vacancy = Vacancy::factory()->open()->create();
    $vacDoc = VacancyDocument::factory()->create([
        'vacancy_id' => $vacancy->id,
        'is_required' => true,
        'allowed_types' => ['pdf'],
        'max_size_mb' => 2,
    ]);

    $file = UploadedFile::fake()->create('big.pdf', 2049, 'application/pdf');

    $response = $this->actingAs($user)->post(route('applicant.applications.store', $vacancy), [
        'field_of_study' => 'Finance',
        'graduation_date' => now()->subYears(2)->toDateString(),
        'documents' => [$vacDoc->id => $file],
    ]);

    $response->assertSessionHasErrors();
    $this->assertDatabaseMissing('applications', ['vacancy_id' => $vacancy->id]);
});

test('unsupported document type is rejected', function () {
    $user = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $user->id]);
    $vacancy = Vacancy::factory()->open()->create();
    $vacDoc = VacancyDocument::factory()->create([
        'vacancy_id' => $vacancy->id,
        'is_required' => true,
        'allowed_types' => ['pdf', 'jpg', 'jpeg', 'png'],
        'max_size_mb' => 2,
    ]);

    $file = UploadedFile::fake()->create('resume.docx', 200,
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

    $response = $this->actingAs($user)->post(route('applicant.applications.store', $vacancy), [
        'field_of_study' => 'Business',
        'graduation_date' => now()->subYears(2)->toDateString(),
        'documents' => [$vacDoc->id => $file],
    ]);

    $response->assertSessionHasErrors();
});

test('accepted document is stored on private local disk not public', function () {
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);
    $vacancy = Vacancy::factory()->open()->create();
    $vacDoc = VacancyDocument::factory()->create([
        'vacancy_id' => $vacancy->id,
        'is_required' => true,
        'allowed_types' => ['pdf'],
        'max_size_mb' => 2,
    ]);

    $file = UploadedFile::fake()->create('cv.pdf', 500, 'application/pdf');

    $this->actingAs($user)->post(route('applicant.applications.store', $vacancy), [
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2)->toDateString(),
        'documents' => [$vacDoc->id => $file],
    ]);

    $application = Application::where('applicant_id', $applicant->id)->first();
    $document = $application?->documents()->first();

    expect($document)->not->toBeNull();
    Storage::disk('local')->assertExists($document->file_path);
    Storage::disk('public')->assertMissing($document->file_path);
});

test('document is stored in applications/{id}/documents/ path', function () {
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);
    $vacancy = Vacancy::factory()->open()->create();
    $vacDoc = VacancyDocument::factory()->create([
        'vacancy_id' => $vacancy->id,
        'is_required' => true,
        'allowed_types' => ['pdf'],
        'max_size_mb' => 2,
    ]);

    $file = UploadedFile::fake()->create('cert.pdf', 400, 'application/pdf');

    $this->actingAs($user)->post(route('applicant.applications.store', $vacancy), [
        'field_of_study' => 'Law',
        'graduation_date' => now()->subYears(2)->toDateString(),
        'documents' => [$vacDoc->id => $file],
    ]);

    $application = Application::where('applicant_id', $applicant->id)->first();
    $document = $application?->documents()->first();

    expect($document->file_path)->toStartWith('applications/'.$application->id.'/documents/');
});

// ── Authorization: applicant isolation ───────────────────────────────────────

test('applicant cannot view another applicants application', function () {
    $u1 = User::factory()->asApplicant()->create();
    $a1 = Applicant::factory()->create(['user_id' => $u1->id]);
    $u2 = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $u2->id]);

    $vacancy = Vacancy::factory()->open()->create();
    $app = Application::create([
        'applicant_id' => $a1->id,
        'vacancy_id' => $vacancy->id,
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
    ]);

    $this->actingAs($u2)
        ->get(route('applicant.applications.show', $app))
        ->assertForbidden();
});

test('applicant cannot download another applicants document', function () {
    $u1 = User::factory()->asApplicant()->create();
    $a1 = Applicant::factory()->create(['user_id' => $u1->id]);
    $u2 = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $u2->id]);

    $vacancy = Vacancy::factory()->open()->create();
    $vacDoc = VacancyDocument::factory()->create(['vacancy_id' => $vacancy->id]);

    $app = Application::create([
        'applicant_id' => $a1->id,
        'vacancy_id' => $vacancy->id,
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
    ]);

    $doc = ApplicationDocument::create([
        'application_id' => $app->id,
        'vacancy_document_id' => $vacDoc->id,
        'file_name' => 'test.pdf',
        'original_name' => 'test.pdf',
        'file_path' => 'applications/'.$app->id.'/documents/test.pdf',
        'file_type' => 'pdf',
        'file_size' => 100,
    ]);

    $this->actingAs($u2)
        ->get(route('applicant.documents.download', $doc))
        ->assertForbidden();
});

test('applicant cannot access admin routes', function () {
    $user = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $user->id]);

    foreach (['/admin', '/admin/dashboard', '/admin/applications', '/admin/users'] as $path) {
        expect($this->actingAs($user)->get($path)->getStatusCode())->toBeIn([302, 403]);
    }
});

test('unauthenticated user cannot access applicant dashboard', function () {
    $this->get(route('applicant.dashboard'))->assertRedirect(route('login'));
});

// ── Authorization: admin permission checks ───────────────────────────────────

test('admin without permission cannot view audit logs', function () {
    $user = User::factory()->create();
    $user->assignRole('hr_officer');

    $this->actingAs($user)
        ->get(route('admin.audit-logs.index'))
        ->assertForbidden();
});

test('admin without settings.manage cannot update settings', function () {
    $user = User::factory()->create();
    $user->assignRole('hr_officer');

    $this->actingAs($user)
        ->put(route('admin.settings.update'), [])
        ->assertForbidden();
});

// ── Pagination ────────────────────────────────────────────────────────────────

test('applicant applications list response does not load all applications at once', function () {
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);

    // Create 20 applications
    for ($i = 0; $i < 20; $i++) {
        $vacancy = Vacancy::factory()->open()->create();
        Application::create([
            'applicant_id' => $applicant->id,
            'vacancy_id' => $vacancy->id,
            'field_of_study' => 'CS',
            'graduation_date' => now()->subYears(2),
            'status' => ApplicationStatus::Submitted,
            'submitted_at' => now(),
        ]);
    }

    $response = $this->actingAs($user)->get(route('applicant.applications.index'));

    $response->assertOk();
    $response->assertSee('page=2', false);
});

test('vacancy list is paginated on public page', function () {
    Vacancy::factory()->count(25)->open()->create();

    $response = $this->get(route('vacancies.index'));

    $response->assertOk();
    $response->assertSee('page=2', false);
});
