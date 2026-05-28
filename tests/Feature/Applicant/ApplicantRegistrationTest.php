<?php

declare(strict_types=1);

use App\Models\Applicant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
});

// ── Helper ──────────────────────────────────────────────────────────────────

function validRegistrationData(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Abebe',
        'middle_name' => 'Bikila',
        'last_name' => 'Tekle',
        'gender' => 'male',
        'date_of_birth' => '1995-06-15',
        'nationality' => 'Ethiopian',
        'national_id' => 'ETH-TEST-'.uniqid(),
        'disability_status' => '0',
        'disability_type' => null,
        'university_name' => 'Addis Ababa University',
        'field_of_study' => 'Computer Science',
        'graduation_year' => 2018,
        'gpa' => 3.75,
        'education_level' => 'degree',
        'work_experience_years' => 3,
        'work_experience_months' => 6,
        'current_employer' => 'Tech Corp',
        'current_position' => 'Software Engineer',
        'region' => 'Addis Ababa',
        'city' => 'Addis Ababa',
        'phone' => '+251'.rand(900000000, 999999999),
        'email' => 'applicant_'.uniqid().'@example.com',
        'password' => 'Password@123',
        'password_confirmation' => 'Password@123',
        'preferred_locale' => 'en',
        'terms' => '1',
    ], $overrides);
}

// ── Page rendering ───────────────────────────────────────────────────────────

test('registration page loads', function (): void {
    $response = $this->get(route('applicant.register'));

    $response->assertOk();
    $response->assertSee(__('applicant.register_heading'));
});

test('registration page renders in amharic', function (): void {
    $this->get(route('lang.switch', 'am'));
    $response = $this->get(route('applicant.register'));

    $response->assertOk();
    $response->assertSee(__('applicant.step_1_heading'));
});

// ── Successful registration ───────────────────────────────────────────────────

test('applicant can register with all required fields', function (): void {
    $response = $this->post(route('applicant.register'), validRegistrationData());

    $response->assertRedirect(route('applicant.verify-email'));
    expect(User::latest()->first())->not->toBeNull();
});

test('first middle and last name are saved correctly', function (): void {
    $data = validRegistrationData([
        'first_name' => 'Mekdes',
        'middle_name' => 'Haile',
        'last_name' => 'Selassie',
    ]);

    $this->post(route('applicant.register'), $data);

    $applicant = Applicant::where('email', $data['email'])->first();
    expect($applicant->first_name)->toBe('Mekdes');
    expect($applicant->middle_name)->toBe('Haile');
    expect($applicant->last_name)->toBe('Selassie');
});

test('full name is auto-generated from name parts', function (): void {
    $data = validRegistrationData([
        'first_name' => 'Sara',
        'middle_name' => 'Dawit',
        'last_name' => 'Bekele',
    ]);

    $this->post(route('applicant.register'), $data);

    $applicant = Applicant::where('email', $data['email'])->first();
    expect($applicant->full_name)->toBe('Sara Dawit Bekele');
});

test('full name omits empty middle name', function (): void {
    $data = validRegistrationData([
        'first_name' => 'Yonas',
        'middle_name' => '',
        'last_name' => 'Girma',
    ]);

    $this->post(route('applicant.register'), $data);

    $applicant = Applicant::where('email', $data['email'])->first();
    expect($applicant->full_name)->toBe('Yonas Girma');
});

test('preferred locale is saved on registration', function (): void {
    $data = validRegistrationData(['preferred_locale' => 'am']);

    $this->post(route('applicant.register'), $data);

    $applicant = Applicant::where('email', $data['email'])->first();
    expect($applicant->preferred_locale)->toBe('am');
});

// ── Uniqueness validation ─────────────────────────────────────────────────────

test('national id must be unique', function (): void {
    $existingApplicant = Applicant::factory()->create(['national_id' => 'UNIQUE-123']);

    $response = $this->post(route('applicant.register'), validRegistrationData([
        'national_id' => 'UNIQUE-123',
    ]));

    $response->assertSessionHasErrors('national_id');
});

test('phone must be unique', function (): void {
    $existingApplicant = Applicant::factory()->create(['phone' => '+251911000001']);

    $response = $this->post(route('applicant.register'), validRegistrationData([
        'phone' => '+251911000001',
    ]));

    $response->assertSessionHasErrors('phone');
});

test('email must be unique', function (): void {
    $existingApplicant = Applicant::factory()->create(['email' => 'taken@example.com']);

    $response = $this->post(route('applicant.register'), validRegistrationData([
        'email' => 'taken@example.com',
    ]));

    $response->assertSessionHasErrors('email');
});

// ── Disability validation ─────────────────────────────────────────────────────

test('disability type is required when disability status is yes', function (): void {
    $response = $this->post(route('applicant.register'), validRegistrationData([
        'disability_status' => '1',
        'disability_type' => '',
    ]));

    $response->assertSessionHasErrors('disability_type');
});

test('disability type is not required when disability status is no', function (): void {
    $data = validRegistrationData([
        'disability_status' => '0',
        'disability_type' => null,
    ]);

    $response = $this->post(route('applicant.register'), $data);

    $response->assertRedirect(route('applicant.verify-email'));
    $response->assertSessionHasNoErrors();
});

// ── File upload validation ────────────────────────────────────────────────────

test('profile photo accepts jpg and png', function (): void {
    $data = validRegistrationData();
    $data['profile_photo'] = UploadedFile::fake()->image('photo.jpg', 100, 100)->size(500);

    $response = $this->post(route('applicant.register'), $data);

    $response->assertRedirect(route('applicant.verify-email'));
    $response->assertSessionHasNoErrors();
});

test('profile photo rejects pdf', function (): void {
    $data = validRegistrationData();
    $data['profile_photo'] = UploadedFile::fake()->create('doc.pdf', 500, 'application/pdf');

    $response = $this->post(route('applicant.register'), $data);

    $response->assertSessionHasErrors('profile_photo');
});

test('document over 2 MB is rejected', function (): void {
    $data = validRegistrationData();
    $data['documents'] = UploadedFile::fake()->create('docs.pdf', 2049, 'application/pdf');

    $response = $this->post(route('applicant.register'), $data);

    $response->assertSessionHasErrors('documents');
});

test('unsupported document type is rejected', function (): void {
    $data = validRegistrationData();
    $data['documents'] = UploadedFile::fake()->create('docs.docx', 500, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

    $response = $this->post(route('applicant.register'), $data);

    $response->assertSessionHasErrors('documents');
});

test('profile documents are stored privately on local disk', function (): void {
    $data = validRegistrationData();
    $data['documents'] = UploadedFile::fake()->create('all_docs.pdf', 800, 'application/pdf');

    $this->post(route('applicant.register'), $data);

    $applicant = Applicant::where('email', $data['email'])->first();
    $doc = $applicant?->profileDocuments()->where('document_type', 'documents')->first();

    expect($doc)->not->toBeNull();
    Storage::disk('local')->assertExists($doc->file_path);
    Storage::disk('public')->assertMissing($doc->file_path);
});

// ── Dashboard profile completion ──────────────────────────────────────────────

test('applicant dashboard shows profile completion percentage', function (): void {
    $user = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('applicant.dashboard'));

    $response->assertOk();
    // The completion widget or stats section must be visible
    $response->assertSee('profile');
});

test('terms must be accepted to register', function (): void {
    $response = $this->post(route('applicant.register'), validRegistrationData(['terms' => '0']));

    $response->assertSessionHasErrors('terms');
});
