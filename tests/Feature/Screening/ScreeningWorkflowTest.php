<?php

declare(strict_types=1);

use App\Actions\Screening\AssignApplicationReviewerAction;
use App\Actions\Screening\ReverseScreeningDecisionAction;
use App\Actions\Screening\ReviewApplicationAction;
use App\Actions\Screening\VerifyApplicationDocumentAction;
use App\Enums\ApplicationStatus;
use App\Enums\DocumentVerificationStatus;
use App\Enums\ScreeningDecision;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\ScreeningReview;
use App\Models\User;
use App\Models\Vacancy;
use App\Models\VacancyDocument;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
});

// ── Helper: create a submitted application ───────────────────────────────────

function makeApplication(?User $reviewer = null): Application
{
    $applicantUser = User::factory()->asApplicant()->create();
    $applicant = Applicant::factory()->create(['user_id' => $applicantUser->id]);
    $vacancy = Vacancy::factory()->open()->create();

    return Application::create([
        'applicant_id' => $applicant->id,
        'vacancy_id' => $vacancy->id,
        'field_of_study' => 'Computer Science',
        'graduation_date' => now()->subYears(2),
        'status' => ApplicationStatus::Submitted,
        'submitted_at' => now(),
        'assigned_reviewer_id' => $reviewer?->id,
    ]);
}

// ── Authorization ────────────────────────────────────────────────────────────

test('authorized screening officer can view assigned application', function () {
    $officer = User::factory()->screeningOfficer()->create();
    $application = makeApplication($officer);

    $this->assertTrue($officer->can('screen', $application));
});

test('screening officer cannot view unassigned application when another is assigned', function () {
    $officer1 = User::factory()->screeningOfficer()->create();
    $officer2 = User::factory()->screeningOfficer()->create();
    $application = makeApplication($officer1); // assigned to officer1

    // officer2 cannot screen an application assigned to officer1
    $this->assertFalse($officer2->can('screen', $application));
});

test('unauthorized user cannot access screening dashboard', function () {
    $applicantUser = User::factory()->asApplicant()->create();

    $response = $this->actingAs($applicantUser)->get('/admin/screening');

    // Filament will redirect or 403 — not 200
    expect($response->status())->not->toBe(200);
});

test('applicant cannot access screening routes', function () {
    $applicantUser = User::factory()->asApplicant()->create();
    $application = makeApplication();

    $response = $this->actingAs($applicantUser)->get("/admin/screening/{$application->id}/review");

    expect($response->status())->not->toBe(200);
});

// ── Document Verification ────────────────────────────────────────────────────

test('document verifier can verify document', function () {
    $verifier = User::factory()->create();
    $verifier->givePermissionTo('screening.verify-documents');
    $application = makeApplication();
    $vacDoc = VacancyDocument::factory()->create(['vacancy_id' => $application->vacancy_id]);
    $document = ApplicationDocument::create([
        'application_id' => $application->id,
        'vacancy_document_id' => $vacDoc->id,
        'file_name' => 'secure_name.pdf',
        'original_name' => 'cv.pdf',
        'file_path' => 'documents/secure_name.pdf',
        'file_type' => 'pdf',
        'file_size' => 512000,
        'verification_status' => DocumentVerificationStatus::Pending,
    ]);

    app(VerifyApplicationDocumentAction::class)->handle(
        $document,
        $verifier,
        DocumentVerificationStatus::Verified,
        null,
    );

    $document->refresh();

    expect($document->verification_status)->toBe(DocumentVerificationStatus::Verified);
    expect($document->verified_by)->toBe($verifier->id);
    expect($document->verified_at)->not->toBeNull();
});

test('rejected document requires remark', function () {
    $verifier = User::factory()->create();
    $verifier->givePermissionTo('screening.verify-documents');
    $application = makeApplication();
    $vacDoc = VacancyDocument::factory()->create(['vacancy_id' => $application->vacancy_id]);
    $document = ApplicationDocument::create([
        'application_id' => $application->id,
        'vacancy_document_id' => $vacDoc->id,
        'file_name' => 'doc.pdf',
        'original_name' => 'doc.pdf',
        'file_path' => 'documents/doc.pdf',
        'file_type' => 'pdf',
        'file_size' => 204800,
        'verification_status' => DocumentVerificationStatus::Pending,
    ]);

    // Verify that the action DOES store the remark when provided
    app(VerifyApplicationDocumentAction::class)->handle(
        $document,
        $verifier,
        DocumentVerificationStatus::Rejected,
        'Missing signature on the document.',
    );

    $document->refresh();
    expect($document->verification_status)->toBe(DocumentVerificationStatus::Rejected);
    expect($document->verification_remark)->toBe('Missing signature on the document.');
});

// ── Screening Decision ───────────────────────────────────────────────────────

test('screening officer can mark application passed', function () {
    $officer = User::factory()->screeningOfficer()->create();
    $application = makeApplication($officer);

    app(ReviewApplicationAction::class)->handle(
        $application,
        $officer,
        ScreeningDecision::Passed,
        null,
    );

    $application->refresh();
    expect($application->status)->toBe(ApplicationStatus::PassedScreening);
    expect($application->screening_status)->toBe(ScreeningDecision::Passed);
    expect($application->screened_by)->toBe($officer->id);
    expect($application->screened_at)->not->toBeNull();
});

test('screening officer can mark application failed with remark', function () {
    $officer = User::factory()->screeningOfficer()->create();
    $application = makeApplication($officer);

    app(ReviewApplicationAction::class)->handle(
        $application,
        $officer,
        ScreeningDecision::Failed,
        'CGPA below minimum threshold.',
    );

    $application->refresh();
    expect($application->status)->toBe(ApplicationStatus::FailedScreening);
    expect($application->screening_remark)->toBe('CGPA below minimum threshold.');
});

test('failed decision requires remark — history record remark is stored', function () {
    $officer = User::factory()->screeningOfficer()->create();
    $application = makeApplication($officer);

    $review = app(ReviewApplicationAction::class)->handle(
        $application,
        $officer,
        ScreeningDecision::Failed,
        'Incomplete documentation.',
    );

    expect($review->remark)->toBe('Incomplete documentation.');
    expect($review->decision)->toBe(ScreeningDecision::Failed);
});

test('correction required decision requires remark — history record remark is stored', function () {
    $officer = User::factory()->screeningOfficer()->create();
    $application = makeApplication($officer);

    $review = app(ReviewApplicationAction::class)->handle(
        $application,
        $officer,
        ScreeningDecision::CorrectionRequired,
        'Upload a clearer copy of your ID.',
    );

    expect($review->remark)->toBe('Upload a clearer copy of your ID.');
    expect($review->decision)->toBe(ScreeningDecision::CorrectionRequired);
});

test('screening decision creates history record', function () {
    $officer = User::factory()->screeningOfficer()->create();
    $application = makeApplication($officer);

    $this->assertDatabaseCount('screening_reviews', 0);

    app(ReviewApplicationAction::class)->handle(
        $application,
        $officer,
        ScreeningDecision::Passed,
        null,
    );

    $this->assertDatabaseCount('screening_reviews', 1);
    $this->assertDatabaseHas('screening_reviews', [
        'application_id' => $application->id,
        'reviewer_id' => $officer->id,
        'decision' => ScreeningDecision::Passed->value,
        'new_status' => ApplicationStatus::PassedScreening->value,
    ]);
});

// ── Status List Filtering ────────────────────────────────────────────────────

test('passed applicants appear in passed list', function () {
    $officer = User::factory()->screeningOfficer()->create();
    $application = makeApplication($officer);

    app(ReviewApplicationAction::class)->handle($application, $officer, ScreeningDecision::Passed, null);

    $passed = Application::where('status', ApplicationStatus::PassedScreening)->get();
    expect($passed)->toHaveCount(1);
    expect($passed->first()->id)->toBe($application->id);
});

test('failed applicants appear in failed list', function () {
    $officer = User::factory()->screeningOfficer()->create();
    $application = makeApplication($officer);

    app(ReviewApplicationAction::class)->handle($application, $officer, ScreeningDecision::Failed, 'Did not meet qualifications.');

    $failed = Application::where('status', ApplicationStatus::FailedScreening)->get();
    expect($failed)->toHaveCount(1);
    expect($failed->first()->id)->toBe($application->id);
});

test('correction required applicants remain visible in screening queue', function () {
    $officer = User::factory()->screeningOfficer()->create();
    $application = makeApplication($officer);

    app(ReviewApplicationAction::class)->handle(
        $application,
        $officer,
        ScreeningDecision::CorrectionRequired,
        'Please upload a clearer supporting document.',
    );

    $response = $this->actingAs($officer)->get('/admin/screening');

    $response->assertOk()
        ->assertSeeText($application->applicant->full_name)
        ->assertSeeText(ApplicationStatus::CorrectionRequired->getLabel());
});

// ── Sensitive Fields ─────────────────────────────────────────────────────────

test('sensitive applicant fields require applications.view-sensitive permission', function () {
    $officer = User::factory()->screeningOfficer()->create();
    $application = makeApplication();

    // Officer does NOT have view-sensitive
    $this->assertFalse($officer->can('viewSensitive', $application));

    // Grant it
    $officer->givePermissionTo('applications.view-sensitive');
    $officer->refresh();
    $officer->unsetRelation('permissions');

    $this->assertTrue($officer->can('viewSensitive', $application));
});

// ── Reverse Decision ─────────────────────────────────────────────────────────

test('reverse screening decision requires screening.reverse-decision permission', function () {
    $officer = User::factory()->screeningOfficer()->create();
    $application = makeApplication($officer);

    // Officer does NOT have reverse-decision permission
    app(ReviewApplicationAction::class)->handle($application, $officer, ScreeningDecision::Passed, null);

    $this->expectException(AuthorizationException::class);

    app(ReverseScreeningDecisionAction::class)->handle($application, $officer, 'Reversing for correction.');
});

test('user with reverse-decision permission can reverse a screening decision', function () {
    $admin = User::factory()->admin()->create();
    $application = makeApplication();

    // First make a decision
    app(ReviewApplicationAction::class)->handle($application, $admin, ScreeningDecision::Passed, null);
    expect($application->refresh()->status)->toBe(ApplicationStatus::PassedScreening);

    // Now reverse it
    app(ReverseScreeningDecisionAction::class)->handle($application, $admin, 'Applicant appealed the decision.');

    $application->refresh();
    expect($application->status)->toBe(ApplicationStatus::UnderReview);
    expect($application->screening_status)->toBe(ScreeningDecision::Pending);

    // Two history records should exist
    expect(ScreeningReview::where('application_id', $application->id)->count())->toBe(2);
});

// ── Reviewer Assignment ──────────────────────────────────────────────────────

test('admin can assign reviewer to application', function () {
    $admin = User::factory()->admin()->create();
    $officer = User::factory()->screeningOfficer()->create();
    $application = makeApplication();

    $this->actingAs($admin);

    app(AssignApplicationReviewerAction::class)->handle($application, $officer);

    $application->refresh();
    expect($application->assigned_reviewer_id)->toBe($officer->id);
});

test('admin can unassign reviewer from application', function () {
    $admin = User::factory()->admin()->create();
    $officer = User::factory()->screeningOfficer()->create();
    $application = makeApplication($officer);

    expect($application->assigned_reviewer_id)->toBe($officer->id);

    app(AssignApplicationReviewerAction::class)->handle($application, null);

    $application->refresh();
    expect($application->assigned_reviewer_id)->toBeNull();
});

// ── submitReview enforces the screen policy at the HTTP layer ─────────────────

test('screening officer cannot submit a decision for an application assigned to another officer', function () {
    $officer1 = User::factory()->screeningOfficer()->create();
    $officer2 = User::factory()->screeningOfficer()->create();
    $application = makeApplication($officer1); // assigned to officer1

    $this->actingAs($officer2)
        ->post("/admin/screening/{$application->id}", [
            'decision' => ScreeningDecision::Passed->value,
        ])
        ->assertForbidden();

    expect($application->fresh()->status)->toBe(ApplicationStatus::Submitted);
});

test('screening officer can submit a decision for their own assigned application', function () {
    $officer = User::factory()->screeningOfficer()->create();
    $application = makeApplication($officer);

    $this->actingAs($officer)
        ->post("/admin/screening/{$application->id}", [
            'decision' => ScreeningDecision::Passed->value,
        ])
        ->assertRedirect(route('admin.screening.index'));

    expect($application->fresh()->status)->toBe(ApplicationStatus::PassedScreening);
});
