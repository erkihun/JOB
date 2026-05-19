<?php

declare(strict_types=1);

use App\Enums\ApplicationStatus;
use App\Enums\DocumentVerificationStatus;
use App\Enums\UserStatus;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Vacancy;
use App\Models\VacancyDocument;
use App\Policies\AuditLogPolicy;
use App\Policies\UserPolicy;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
});

// ── Admin route access ────────────────────────────────────────────────────────

test('applicant cannot access admin dashboard', function (): void {
    $user = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get('/admin');

    expect($response->status())->not->toBe(200);
});

test('unauthenticated user is redirected from applicant dashboard', function (): void {
    $response = $this->get(route('applicant.dashboard'));

    $response->assertRedirect();
});

// ── Document download security ────────────────────────────────────────────────

test('document download requires authentication', function (): void {
    $vacancy = Vacancy::factory()->open()->create();
    $vacDoc = VacancyDocument::factory()->create(['vacancy_id' => $vacancy->id]);
    $applicant = Applicant::factory()->create();
    $app = Application::factory()->create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancy->id,
    ]);

    $doc = ApplicationDocument::create([
        'application_id' => $app->id,
        'vacancy_document_id' => $vacDoc->id,
        'file_name' => 'test.pdf',
        'original_name' => 'cv.pdf',
        'file_path' => 'documents/'.$applicant->id.'/test.pdf',
        'file_type' => 'pdf',
        'file_size' => 1024,
        'verification_status' => DocumentVerificationStatus::Pending,
    ]);

    Storage::disk('local')->put($doc->file_path, 'fake content');

    $response = $this->get(route('applicant.documents.download', $doc));

    $response->assertRedirect(); // redirects to login
});

test('applicant can download their own document', function (): void {
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);
    $vacancy = Vacancy::factory()->open()->create();
    $vacDoc = VacancyDocument::factory()->create(['vacancy_id' => $vacancy->id]);
    $app = Application::factory()->create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancy->id,
    ]);

    $doc = ApplicationDocument::create([
        'application_id' => $app->id,
        'vacancy_document_id' => $vacDoc->id,
        'file_name' => 'myfile.pdf',
        'original_name' => 'cv.pdf',
        'file_path' => 'documents/'.$applicant->id.'/myfile.pdf',
        'file_type' => 'pdf',
        'file_size' => 1024,
        'verification_status' => DocumentVerificationStatus::Pending,
    ]);

    Storage::disk('local')->put($doc->file_path, 'fake content');

    $response = $this->actingAs($user)->get(route('applicant.documents.download', $doc));

    $response->assertOk();
});

test('applicant cannot download another applicants document', function (): void {
    $user1 = User::factory()->asApplicant()->create();
    $applicant1 = Applicant::factory()->create(['user_id' => $user1->id]);

    $user2 = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $user2->id]);

    $vacancy = Vacancy::factory()->open()->create();
    $vacDoc = VacancyDocument::factory()->create(['vacancy_id' => $vacancy->id]);
    $app1 = Application::factory()->create([
        'applicant_id' => $applicant1->id,
        'vacancy_id' => $vacancy->id,
    ]);

    $doc = ApplicationDocument::create([
        'application_id' => $app1->id,
        'vacancy_document_id' => $vacDoc->id,
        'file_name' => 'secret.pdf',
        'original_name' => 'cv.pdf',
        'file_path' => 'documents/'.$applicant1->id.'/secret.pdf',
        'file_type' => 'pdf',
        'file_size' => 1024,
        'verification_status' => DocumentVerificationStatus::Pending,
    ]);

    Storage::disk('local')->put($doc->file_path, 'fake content');

    $response = $this->actingAs($user2)->get(route('applicant.documents.download', $doc));

    $response->assertForbidden();
});

// ── File validation ───────────────────────────────────────────────────────────

test('file larger than 2mb is rejected', function (): void {
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);
    $vacancy = Vacancy::factory()->open()->create();

    $vacDoc = VacancyDocument::factory()->create([
        'vacancy_id' => $vacancy->id,
        'document_name' => 'CV',
        'is_required' => true,
        'allowed_types' => ['pdf', 'jpg', 'jpeg', 'png'],
        'max_size_mb' => 2,
    ]);

    $file = UploadedFile::fake()->create('big.pdf', 2049, 'application/pdf');

    $response = $this->actingAs($user)->post(route('applicant.applications.store', $vacancy), [
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2)->toDateString(),
        'documents' => [$vacDoc->id => $file],
    ]);

    $response->assertSessionHasErrors();
});

test('unsupported file type is rejected', function (): void {
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);
    $vacancy = Vacancy::factory()->open()->create();

    $vacDoc = VacancyDocument::factory()->create([
        'vacancy_id' => $vacancy->id,
        'document_name' => 'CV',
        'is_required' => true,
        'allowed_types' => ['pdf', 'jpg', 'jpeg', 'png'],
        'max_size_mb' => 2,
    ]);

    $file = UploadedFile::fake()->create('resume.docx', 500,
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    );

    $response = $this->actingAs($user)->post(route('applicant.applications.store', $vacancy), [
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2)->toDateString(),
        'documents' => [$vacDoc->id => $file],
    ]);

    $response->assertSessionHasErrors();
});

// ── Super Admin protection ────────────────────────────────────────────────────

test('normal admin cannot delete super admin', function (): void {
    $superAdmin = User::factory()->superAdmin()->create([
        'status' => UserStatus::Active,
    ]);
    $admin = User::factory()->admin()->create();

    $policy = new UserPolicy;

    expect($policy->delete($admin, $superAdmin))->toBeFalse();
});

test('super admin can delete another super admin when more than one exists', function (): void {
    $superAdmin1 = User::factory()->superAdmin()->create(['status' => UserStatus::Active]);
    $superAdmin2 = User::factory()->superAdmin()->create(['status' => UserStatus::Active]);

    $policy = new UserPolicy;

    expect($policy->delete($superAdmin1, $superAdmin2))->toBeTrue();
});

test('super admin cannot be the only active super admin and be deleted', function (): void {
    $superAdmin = User::factory()->superAdmin()->create(['status' => UserStatus::Active]);

    $policy = new UserPolicy;

    // Only one super admin — cannot delete themselves or be deleted
    expect($policy->delete($superAdmin, $superAdmin))->toBeFalse();
});

// ── Settings permission ───────────────────────────────────────────────────────

test('settings require settings.manage permission', function (): void {
    $policy = new UserPolicy;
    $admin = User::factory()->admin()->create();

    // admin has settings.manage per seeder
    expect($admin->hasPermissionTo('settings.manage'))->toBeTrue();
});

test('screening officer does not have settings.manage permission', function (): void {
    $officer = User::factory()->screeningOfficer()->create();

    expect($officer->hasPermissionTo('settings.manage'))->toBeFalse();
});

// ── Audit log permission ──────────────────────────────────────────────────────

test('audit logs require audit.view permission', function (): void {
    $policy = new AuditLogPolicy;
    $officer = User::factory()->screeningOfficer()->create();
    $auditLog = AuditLog::create([
        'action' => 'test', 'module' => 'test',
    ]);

    expect($policy->viewAny($officer))->toBeFalse();
});

test('admin can view audit logs', function (): void {
    $policy = new AuditLogPolicy;
    $admin = User::factory()->admin()->create();
    $auditLog = AuditLog::create([
        'action' => 'test', 'module' => 'test',
    ]);

    expect($policy->viewAny($admin))->toBeTrue();
});

test('normal admin cannot delete audit logs', function (): void {
    $policy = new AuditLogPolicy;
    $admin = User::factory()->admin()->create();
    $auditLog = AuditLog::create([
        'action' => 'test', 'module' => 'test',
    ]);

    expect($policy->delete($admin, $auditLog))->toBeFalse();
});

test('super admin can delete audit logs when they have audit.delete permission', function (): void {
    $policy = new AuditLogPolicy;
    $superAdmin = User::factory()->superAdmin()->create();
    $auditLog = AuditLog::create([
        'action' => 'test', 'module' => 'test',
    ]);

    expect($policy->delete($superAdmin, $auditLog))->toBeTrue();
});

// ── Duplicate application ─────────────────────────────────────────────────────

test('duplicate application is blocked via http', function (): void {
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);
    $vacancy = Vacancy::factory()->open()->create();

    $this->actingAs($user)->post(route('applicant.applications.store', $vacancy), [
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2)->toDateString(),
    ]);

    $second = $this->actingAs($user)->post(route('applicant.applications.store', $vacancy), [
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2)->toDateString(),
    ]);

    $second->assertRedirect();
    expect(Application::where('applicant_id', $applicant->id)
        ->where('vacancy_id', $vacancy->id)->count()
    )->toBe(1);
});

// ── Application edit deadline ─────────────────────────────────────────────────

test('application edit after closing date is blocked', function (): void {
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);
    $vacancy = Vacancy::factory()->pastDeadline()->create();

    $app = Application::create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancy->id,
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now()->subDays(5),
    ]);

    $response = $this->actingAs($user)->put(route('applicant.applications.update', $app), [
        'field_of_study' => 'Changed',
        'graduation_date' => now()->subYears(3)->toDateString(),
    ]);

    $response->assertForbidden();
});
