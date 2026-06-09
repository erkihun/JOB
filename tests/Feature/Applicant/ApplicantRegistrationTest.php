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
        'national_id' => fake()->unique()->numerify('################'),
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
        'documents' => UploadedFile::fake()->create('documents.pdf', 500, 'application/pdf'),
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

test('date of birth uses a native gregorian input in english', function (): void {
    $this->get(route('lang.switch', 'en'));
    $response = $this->get(route('applicant.register'));

    $response->assertOk()
        // Native date input, no Ethiopian calendar Alpine component.
        ->assertSee('type="date"', false)
        ->assertSee('id="date_of_birth"', false)
        ->assertDontSee('ethiopianDatepicker(', false);
});

test('date of birth uses the ethiopian calendar picker in amharic', function (): void {
    $this->get(route('lang.switch', 'am'));
    $response = $this->get(route('applicant.register'));

    $response->assertOk()
        // The Ethiopian datepicker Alpine component is wired up for the DOB field.
        ->assertSee('ethiopianDatepicker(', false)
        ->assertSee('"date_of_birth"', false);
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

test('middle name is required', function (): void {
    $data = validRegistrationData(['middle_name' => '']);

    $this->post(route('applicant.register'), $data)
        ->assertSessionHasErrors('middle_name');

    expect(Applicant::where('email', $data['email'])->exists())->toBeFalse();
});

test('preferred locale is saved on registration', function (): void {
    $data = validRegistrationData(['preferred_locale' => 'am']);

    $this->post(route('applicant.register'), $data);

    $applicant = Applicant::where('email', $data['email'])->first();
    expect($applicant->preferred_locale)->toBe('am');
});

// ── Uniqueness validation ─────────────────────────────────────────────────────

test('national id must be unique', function (): void {
    $existingApplicant = Applicant::factory()->create(['national_id' => '1234567890123456']);

    $response = $this->post(route('applicant.register'), validRegistrationData([
        'national_id' => '1234 5678 9012 3456',
    ]));

    $response->assertSessionHasErrors('national_id');
});

test('national id is normalized and must contain sixteen digits', function (): void {
    $data = validRegistrationData([
        'national_id' => '1111 2222 3333 4444',
    ]);

    $this->post(route('applicant.register'), $data)
        ->assertRedirect(route('applicant.verify-email'));

    expect(Applicant::where('email', $data['email'])->value('national_id'))->toBe('1111222233334444');

    auth()->logout();
    session()->invalidate();
    session()->regenerateToken();

    $this->post(route('applicant.register'), validRegistrationData([
        'national_id' => '1234 5678',
    ]))->assertSessionHasErrors('national_id');
});

test('phone must be unique', function (): void {
    $existingApplicant = Applicant::factory()->create(['phone' => '+251911000001']);

    $response = $this->post(route('applicant.register'), validRegistrationData([
        'phone' => '+251911000001',
    ]));

    $response->assertSessionHasErrors('phone');
});

test('phone is normalized and must be unique', function (): void {
    $data = validRegistrationData([
        'phone' => '0911 222 333',
    ]);

    $this->post(route('applicant.register'), $data)
        ->assertRedirect(route('applicant.verify-email'));

    expect(Applicant::where('email', $data['email'])->value('phone'))->toBe('+251911222333');

    auth()->logout();
    session()->invalidate();
    session()->regenerateToken();

    $this->post(route('applicant.register'), validRegistrationData([
        'phone' => '0111 222 333',
    ]))->assertSessionHasErrors('phone');
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

test('profile photo is updated later and prohibited during registration', function (): void {
    $data = validRegistrationData();
    $data['profile_photo'] = UploadedFile::fake()->image('photo.jpg', 100, 100)->size(500);

    $response = $this->post(route('applicant.register'), $data);

    $response->assertSessionHasErrors('profile_photo');
});

test('document over 2 MB is rejected', function (): void {
    $data = validRegistrationData();
    $data['documents'] = UploadedFile::fake()->create('docs.pdf', 2049, 'application/pdf');

    $response = $this->post(route('applicant.register'), $data);

    $response->assertSessionHasErrors('documents');
});

test('document is required to register', function (): void {
    $data = validRegistrationData(['documents' => null]);

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

test('terms agreement is not required on registration review', function (): void {
    $response = $this->post(route('applicant.register'), validRegistrationData());

    $response->assertRedirect(route('applicant.verify-email'));
});

test('review step pre-populates fields from old input after a failed submission', function (): void {
    // Submit with an invalid password to trigger a server redirect back with old() values.
    $data = validRegistrationData([
        'first_name' => 'Tigist',
        'middle_name' => 'Haile',
        'last_name' => 'Bekele',
        'date_of_birth' => '1990-03-25',
        'national_id' => '1111222233334444',
        'phone' => '+251911555666',
        'email' => 'review_test_'.uniqid().'@example.com',
        'password' => 'weak',
        'password_confirmation' => 'weak',
    ]);

    $response = $this->post(route('applicant.register'), $data);

    // Should redirect back with errors (password too weak).
    $response->assertSessionHasErrors('password');

    // Follow the redirect to get the re-rendered form.
    $page = $this->get(route('applicant.register'));
    $page->assertOk();

    // The Alpine reactive properties are seeded from old() in the <script> block.
    // Assert each value is embedded so the review step renders correctly.
    $page->assertSee("firstName: 'Tigist'", false);
    $page->assertSee("middleName: 'Haile'", false);
    $page->assertSee("lastName: 'Bekele'", false);
    $page->assertSee("dateOfBirth: '1990-03-25'", false);
    $page->assertSee("nationalId: '1111222233334444'", false);
    $page->assertSee("phone: '+251911555666'", false);
    $page->assertSee($data['email'], false);
});

test('review step date of birth displays in gregorian format for english locale', function (): void {
    $this->get(route('lang.switch', 'en'));

    $data = validRegistrationData([
        'date_of_birth' => '1992-07-14',
        'password' => 'weak',
        'password_confirmation' => 'weak',
    ]);

    $this->post(route('applicant.register'), $data)->assertSessionHasErrors('password');

    $page = $this->get(route('applicant.register'));
    $page->assertOk();

    // In English locale the JS formatDobForReview returns the raw YYYY-MM-DD string.
    $page->assertSee('formatDobForReview(dateOfBirth)', false);
    // Locale baked into the blade output should be 'en'.
    $page->assertSee("locale = 'en'", false);
});

test('review step date of birth displays in ethiopian format for amharic locale', function (): void {
    $this->get(route('lang.switch', 'am'));

    $data = validRegistrationData([
        'date_of_birth' => '2000-01-07',  // ET: 1992 ጥር 29
        'preferred_locale' => 'am',
        'password' => 'weak',
        'password_confirmation' => 'weak',
    ]);

    $this->post(route('applicant.register'), $data)->assertSessionHasErrors('password');

    $page = $this->get(route('applicant.register'));
    $page->assertOk();

    // In Amharic locale the blade output bakes locale = 'am' into the JS.
    $page->assertSee("locale = 'am'", false);
    $page->assertSee("dateOfBirth: '2000-01-07'", false);
});
