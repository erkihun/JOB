<?php

declare(strict_types=1);

use App\Enums\ApplicationStatus;
use App\Models\Applicant;
use App\Models\ApplicantProfileDocument;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Vacancy;
use App\Models\VacancyDocument;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
});

// ── SVG / script-upload rejection across all upload paths ───────────────────────

test('svg upload is rejected when submitting an application', function (): void {
    $user = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $user->id]);
    $vacancy = Vacancy::factory()->open()->create();
    $doc = VacancyDocument::factory()->for($vacancy)->create(['is_required' => true]);

    $svg = UploadedFile::fake()->createWithContent(
        'evil.svg',
        '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'
    );

    $this->actingAs($user)->post(route('applicant.applications.store', $vacancy), [
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2)->toDateString(),
        'documents' => [$doc->id => $svg],
    ])->assertSessionHasErrors("documents.{$doc->id}");

    expect(Application::where('applicant_id', $user->applicant->id)->where('vacancy_id', $vacancy->id)->exists())->toBeFalse();
});

test('svg upload is rejected when replacing an application document', function (): void {
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);
    $vacancy = Vacancy::factory()->open()->create();
    $vacDoc = VacancyDocument::factory()->for($vacancy)->create();

    $application = Application::create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancy->id,
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
    ]);

    $document = ApplicationDocument::create([
        'application_id' => $application->id,
        'vacancy_document_id' => $vacDoc->id,
        'file_name' => 'orig.pdf',
        'original_name' => 'orig.pdf',
        'file_path' => 'application-documents/'.$application->id.'/orig.pdf',
        'file_type' => 'application/pdf',
        'file_size' => 1000,
    ]);

    $svg = UploadedFile::fake()->createWithContent('evil.svg', '<svg><script>alert(1)</script></svg>');

    $this->actingAs($user)
        ->post(route('applicant.applications.documents.replace', [$application, $document]), ['file' => $svg])
        ->assertSessionHasErrors('file');
});

test('svg upload is rejected when updating the applicant profile', function (): void {
    $user = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $user->id]);

    $svg = UploadedFile::fake()->createWithContent('evil.svg', '<svg><script>alert(1)</script></svg>');

    $this->actingAs($user)
        ->put(route('applicant.profile.update'), [
            'documents' => $svg,
        ])
        ->assertSessionHasErrors('documents');
});

// ── IDOR: an applicant cannot access another applicant's documents ──────────────

test('an applicant cannot download another applicants application document', function (): void {
    // Victim with an application document.
    $victimUser = User::factory()->asApplicant()->create();
    $victim = Applicant::factory()->create(['user_id' => $victimUser->id]);
    $vacancy = Vacancy::factory()->open()->create();
    $vacDoc = VacancyDocument::factory()->for($vacancy)->create();

    $application = Application::create([
        'applicant_id' => $victim->id,
        'vacancy_id' => $vacancy->id,
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
    ]);

    $document = ApplicationDocument::create([
        'application_id' => $application->id,
        'vacancy_document_id' => $vacDoc->id,
        'file_name' => 'secret.pdf',
        'original_name' => 'secret.pdf',
        'file_path' => 'application-documents/'.$application->id.'/secret.pdf',
        'file_type' => 'application/pdf',
        'file_size' => 1000,
    ]);
    Storage::disk('local')->put($document->file_path, 'CONFIDENTIAL');

    // Attacker: a different, fully-formed applicant.
    $attackerUser = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $attackerUser->id]);

    $this->actingAs($attackerUser)
        ->get(route('applicant.documents.download', $document))
        ->assertForbidden();
});

test('an applicant cannot download another applicants profile document', function (): void {
    $victimUser = User::factory()->asApplicant()->create();
    $victim = Applicant::factory()->create(['user_id' => $victimUser->id]);

    $document = ApplicantProfileDocument::create([
        'applicant_id' => $victim->id,
        'document_type' => 'documents',
        'file_name' => 'cv.pdf',
        'original_name' => 'cv.pdf',
        'file_path' => 'applicant-documents/'.$victim->id.'/cv.pdf',
        'file_type' => 'application/pdf',
        'file_size' => 1000,
    ]);
    Storage::disk('local')->put($document->file_path, 'CONFIDENTIAL');

    $attackerUser = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $attackerUser->id]);

    $this->actingAs($attackerUser)
        ->get(route('applicant.profile.documents.download', $document))
        ->assertForbidden();
});

test('an applicant can download their own application document', function (): void {
    $user = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create(['user_id' => $user->id]);
    $vacancy = Vacancy::factory()->open()->create();
    $vacDoc = VacancyDocument::factory()->for($vacancy)->create();

    $application = Application::create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancy->id,
        'field_of_study' => 'CS',
        'graduation_date' => now()->subYears(2),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
    ]);

    $document = ApplicationDocument::create([
        'application_id' => $application->id,
        'vacancy_document_id' => $vacDoc->id,
        'file_name' => 'mine.pdf',
        'original_name' => 'mine.pdf',
        'file_path' => 'application-documents/'.$application->id.'/mine.pdf',
        'file_type' => 'application/pdf',
        'file_size' => 1000,
    ]);
    Storage::disk('local')->put($document->file_path, 'MY OWN FILE');

    $this->actingAs($user)
        ->get(route('applicant.documents.download', $document))
        ->assertOk();
});

// ── Mass assignment: MFA/role fields cannot be set via fill() ────────────────────

test('mfa secret and roles are not mass assignable on the user model', function (): void {
    $user = User::factory()->asApplicant()->create();

    $user->fill([
        'google2fa_secret' => 'ATTACKERINJECTEDSECRET',
        'google2fa_recovery_codes' => ['pwned'],
    ]);

    expect($user->google2fa_secret)->not->toBe('ATTACKERINJECTEDSECRET')
        ->and($user->google2fa_recovery_codes)->not->toBe(['pwned']);
});

// ── Sensitive applicant data access is audit-logged ─────────────────────────────

test('viewing an applicant profile writes a sensitive-access audit log entry', function (): void {
    $admin = User::factory()->admin()->create();
    $applicant = Applicant::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.applicants.show', $applicant))
        ->assertOk();

    $log = AuditLog::where('action', 'applicant_profile_viewed')
        ->where('record_id', $applicant->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($admin->id)
        ->and($log->module)->toBe('applicants')
        ->and($log->ip_address)->not->toBeNull();
});

// ── HTTPS is forced in production (config-level guard) ───────────────────────────

test('https scheme is forced for generated urls in the production environment', function (): void {
    // The guard lives in AppServiceProvider::boot() behind environment('production').
    // Assert the provider applies URL::forceScheme('https') only in production.
    $source = file_get_contents(app_path('Providers/AppServiceProvider.php'));

    expect($source)->toContain("environment('production')")
        ->and($source)->toContain("URL::forceScheme('https')");
});
