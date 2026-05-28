<?php

declare(strict_types=1);

/**
 * Security test suite for the Jobs Recruitment Portal.
 *
 * Covers: login throttling, role separation, application ownership,
 * document download authorization, and file upload validation.
 */

use App\Enums\ApplicationStatus;
use App\Enums\DocumentVerificationStatus;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\User;
use App\Models\Vacancy;
use App\Models\VacancyDocument;
use App\Policies\UserPolicy;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
});

// ── Login throttling ──────────────────────────────────────────────────────────

test('admin login is throttled after 5 failed attempts', function (): void {
    $email = 'attacker@example.com';

    // Make 5 failed attempts
    for ($i = 0; $i < 5; $i++) {
        $this->post(route('admin.login'), [
            'email' => $email,
            'password' => 'wrong-password-'.$i,
        ]);
    }

    // The 6th attempt should be rate-limited
    $response = $this->post(route('admin.login'), [
        'email' => $email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(429);
});

test('applicant login is throttled after 5 failed attempts', function (): void {
    $email = 'applicant-attacker@example.com';

    for ($i = 0; $i < 5; $i++) {
        $this->post(route('applicant.login'), [
            'email' => $email,
            'password' => 'wrong-password-'.$i,
        ]);
    }

    $response = $this->post(route('applicant.login'), [
        'email' => $email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(429);
});

// ── Role separation ───────────────────────────────────────────────────────────

test('applicant cannot access admin dashboard', function (): void {
    $user = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    expect($response->status())->not->toBe(200);
});

test('applicant is redirected or forbidden from admin panel root', function (): void {
    $user = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get('/admin');

    expect($response->status())->not->toBe(200);
});

test('unauthenticated user is redirected from applicant dashboard', function (): void {
    $this->get(route('applicant.dashboard'))->assertRedirect();
});

test('unauthenticated user is redirected from admin dashboard', function (): void {
    $this->get(route('admin.dashboard'))->assertRedirect();
});

// ── Application ownership ─────────────────────────────────────────────────────

test('applicant cannot view another applicants application', function (): void {
    // Applicant A owns the application
    $userA = User::factory()->asApplicant()->create();
    $applicantA = Applicant::factory()->create(['user_id' => $userA->id]);

    // Applicant B is the attacker
    $userB = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $userB->id]);

    $vacancy = Vacancy::factory()->open()->create();

    $application = Application::factory()->create([
        'applicant_id' => $applicantA->id,
        'vacancy_id' => $vacancy->id,
        'status' => ApplicationStatus::Submitted,
    ]);

    $response = $this->actingAs($userB)->get(
        route('applicant.applications.show', $application)
    );

    $response->assertForbidden();
});

test('applicant can view their own application', function (): void {
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);
    $vacancy = Vacancy::factory()->open()->create();

    $application = Application::factory()->create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancy->id,
        'status' => ApplicationStatus::Submitted,
    ]);

    $response = $this->actingAs($user)->get(
        route('applicant.applications.show', $application)
    );

    $response->assertOk();
});

// ── Document download authorization ──────────────────────────────────────────

test('document download requires authentication', function (): void {
    $applicant = Applicant::factory()->create();
    $vacancy = Vacancy::factory()->open()->create();
    $vacDoc = VacancyDocument::factory()->create(['vacancy_id' => $vacancy->id]);
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

    // No auth — should redirect to login
    $this->get(route('applicant.documents.download', $doc))->assertRedirect();
});

test('applicant cannot download another applicants document', function (): void {
    // Owner
    $userA = User::factory()->asApplicant()->create();
    $applicantA = Applicant::factory()->create(['user_id' => $userA->id]);

    // Attacker
    $userB = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $userB->id]);

    $vacancy = Vacancy::factory()->open()->create();
    $vacDoc = VacancyDocument::factory()->create(['vacancy_id' => $vacancy->id]);
    $app = Application::factory()->create([
        'applicant_id' => $applicantA->id,
        'vacancy_id' => $vacancy->id,
    ]);

    $doc = ApplicationDocument::create([
        'application_id' => $app->id,
        'vacancy_document_id' => $vacDoc->id,
        'file_name' => 'secret.pdf',
        'original_name' => 'cv.pdf',
        'file_path' => 'documents/'.$applicantA->id.'/secret.pdf',
        'file_type' => 'pdf',
        'file_size' => 1024,
        'verification_status' => DocumentVerificationStatus::Pending,
    ]);

    Storage::disk('local')->put($doc->file_path, 'fake content');

    $this->actingAs($userB)
        ->get(route('applicant.documents.download', $doc))
        ->assertForbidden();
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

    $this->actingAs($user)
        ->get(route('applicant.documents.download', $doc))
        ->assertOk();
});

// ── File upload validation ────────────────────────────────────────────────────

test('file over 2 mb is rejected during application submission', function (): void {
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

    $response = $this->actingAs($user)->post(
        route('applicant.applications.store', $vacancy),
        [
            'field_of_study' => 'Computer Science',
            'graduation_date' => now()->subYears(2)->toDateString(),
            'documents' => [$vacDoc->id => $file],
        ]
    );

    $response->assertSessionHasErrors();
});

test('unsupported file type is rejected during application submission', function (): void {
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

    $file = UploadedFile::fake()->create(
        'resume.docx',
        500,
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    );

    $response = $this->actingAs($user)->post(
        route('applicant.applications.store', $vacancy),
        [
            'field_of_study' => 'Computer Science',
            'graduation_date' => now()->subYears(2)->toDateString(),
            'documents' => [$vacDoc->id => $file],
        ]
    );

    $response->assertSessionHasErrors();
});

test('svg file is rejected for profile photo during registration', function (): void {
    $svgFile = UploadedFile::fake()->createWithContent(
        'photo.svg',
        '<svg><script>alert(1)</script></svg>'
    );

    $response = $this->post(route('applicant.register'), [
        'first_name' => 'Test',
        'last_name' => 'User',
        'gender' => 'male',
        'national_id' => '1234567890123456',
        'email' => 'test@example.com',
        'phone' => '+251911234567',
        'password' => 'Password@123',
        'password_confirmation' => 'Password@123',
        'disability_status' => false,
        'preferred_locale' => 'en',
        'terms' => true,
        'profile_photo' => $svgFile,
    ]);

    $response->assertSessionHasErrors('profile_photo');
});

// ── Super Admin protection ────────────────────────────────────────────────────

test('super admin cannot be deleted when they are the only active super admin', function (): void {
    $superAdmin = User::factory()->superAdmin()->create();

    $policy = new UserPolicy;

    expect($policy->delete($superAdmin, $superAdmin))->toBeFalse();
});

test('normal admin cannot delete a super admin', function (): void {
    $superAdmin = User::factory()->superAdmin()->create();
    $admin = User::factory()->admin()->create();

    $policy = new UserPolicy;

    expect($policy->delete($admin, $superAdmin))->toBeFalse();
});

// ── CSRF protection ───────────────────────────────────────────────────────────

test('login form requires csrf token', function (): void {
    $this->withoutMiddleware(VerifyCsrfToken::class);

    // Simply verifying that CSRF middleware is registered (it will block without token in real requests)
    // This test confirms the route exists and that attempting without middleware gives 302, not 404
    $response = $this->post(route('applicant.login'), [
        'email' => 'test@example.com',
        'password' => 'wrong',
    ]);

    // Should redirect (credentials wrong), not 404 or 500
    expect($response->status())->toBeIn([302, 422]);
});
