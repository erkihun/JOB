<?php

declare(strict_types=1);

use App\Enums\EducationLevel;
use App\Enums\Gender;
use App\Models\Applicant;
use App\Models\ApplicantProfileDocument;
use App\Models\PasswordResetOtp;
use App\Models\User;
use App\Notifications\PasswordResetOtpNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/*
 * End-to-end check that the registration form saves every field it collects,
 * exactly as the browser submits it (local phone format, spaced national ID, …).
 */

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
    Notification::fake();
    $this->travelTo('2026-10-01 12:00:00');
    openRecruitmentWindow();
});

/** Every input of resources/views/applicant/auth/register.blade.php. */
function fullRegistrationForm(array $overrides = []): array
{
    return array_merge([
        // Step 1 – personal
        'first_name' => 'Selamawit', 'middle_name' => 'Kebede', 'last_name' => 'Haile',
        'gender' => 'female', 'date_of_birth' => '1997-03-21', 'nationality' => 'Ethiopian',
        'national_id' => '1234 5678 9012 3456',
        'disability_status' => '1', 'disability_type' => 'Low vision',
        // Step 2 – education
        'university_name' => 'Addis Ababa University', 'field_of_study' => 'Accounting',
        'graduation_year' => '2019', 'gpa' => '3.45', 'education_level' => 'degree',
        // Step 3 – work experience
        'work_experience_years' => '4', 'work_experience_months' => '6',
        'current_employer' => 'Ethio Telecom', 'current_position' => 'Finance Officer',
        'work_experience_summary' => 'Prepared monthly financial statements.',
        // Step 4 – contact & account
        'phone' => '0911223344', 'alternative_phone' => '0922334455', 'email' => 'Selam.Haile@example.com',
        'password' => 'Str0ng!Passw0rd', 'password_confirmation' => 'Str0ng!Passw0rd',
        'preferred_locale' => 'am',
        // Step 5 – documents
        'documents' => UploadedFile::fake()->create('my-documents.pdf', 300, 'application/pdf'),
    ], $overrides);
}

test('registration saves every submitted field on the user and applicant records', function (): void {
    $this->post(route('applicant.register'), fullRegistrationForm())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('applicant.verify-email'));

    // Account
    $user = User::where('email', 'Selam.Haile@example.com')->sole();
    expect($user->name)->toBe('Selamawit Kebede Haile')
        ->and($user->phone)->toBe('+251911223344')
        ->and($user->preferred_locale)->toBe('am')
        ->and($user->hasRole('applicant'))->toBeTrue()
        ->and($user->isActive())->toBeTrue()
        ->and(Hash::check('Str0ng!Passw0rd', $user->password))->toBeTrue()
        ->and($user->password)->not->toBe('Str0ng!Passw0rd');
    $this->assertAuthenticatedAs($user);

    // Applicant profile
    $applicant = Applicant::where('user_id', $user->id)->sole();
    expect($applicant->applicant_code)->not->toBeEmpty()
        ->and($applicant->first_name)->toBe('Selamawit')
        ->and($applicant->middle_name)->toBe('Kebede')
        ->and($applicant->last_name)->toBe('Haile')
        ->and($applicant->full_name)->toBe('Selamawit Kebede Haile')
        ->and($applicant->gender)->toBe(Gender::Female)
        ->and($applicant->date_of_birth->toDateString())->toBe('1997-03-21')
        ->and($applicant->nationality)->toBe('Ethiopian')
        ->and($applicant->national_id)->toBe('1234567890123456')
        ->and($applicant->disability_status)->toBeTrue()
        ->and($applicant->disability_type)->toBe('Low vision')
        ->and($applicant->university_name)->toBe('Addis Ababa University')
        ->and($applicant->field_of_study)->toBe('Accounting')
        ->and((int) $applicant->graduation_year)->toBe(2019)
        ->and((string) $applicant->gpa)->toBe('3.45')
        ->and($applicant->education_level)->toBe(EducationLevel::from('degree'))
        ->and((int) $applicant->work_experience_years)->toBe(4)
        ->and((int) $applicant->work_experience_months)->toBe(6)
        ->and($applicant->current_employer)->toBe('Ethio Telecom')
        ->and($applicant->current_position)->toBe('Finance Officer')
        ->and($applicant->work_experience_summary)->toBe('Prepared monthly financial statements.')
        ->and($applicant->phone)->toBe('+251911223344')
        ->and($applicant->alternative_phone)->toBe('+251922334455')
        ->and($applicant->email)->toBe('Selam.Haile@example.com')
        ->and($applicant->preferred_locale)->toBe('am');

    // Combined documents PDF on the private disk
    $document = ApplicantProfileDocument::where('applicant_id', $applicant->id)->sole();
    expect($document->document_type)->toBe('documents')
        ->and($document->original_name)->toBe('my-documents.pdf')
        ->and($document->file_path)->toStartWith('applicant-documents/'.$applicant->id.'/')
        ->and(Storage::disk('local')->exists($document->file_path))->toBeTrue();

    // E-mail verification code issued and sent
    expect(PasswordResetOtp::where('email', $user->email)->exists())->toBeTrue();
    Notification::assertSentTo($user, PasswordResetOtpNotification::class);
});

test('optional fields can be left empty', function (): void {
    $this->post(route('applicant.register'), fullRegistrationForm([
        'nationality' => '', 'disability_status' => '0', 'disability_type' => '',
        'university_name' => '', 'field_of_study' => '', 'graduation_year' => '', 'gpa' => '', 'education_level' => '',
        'work_experience_years' => '', 'work_experience_months' => '', 'current_employer' => '',
        'current_position' => '', 'work_experience_summary' => '', 'alternative_phone' => '',
    ]))->assertSessionHasNoErrors()->assertRedirect(route('applicant.verify-email'));

    $applicant = Applicant::where('email', 'Selam.Haile@example.com')->sole();
    expect($applicant->nationality)->toBeNull()
        ->and($applicant->disability_status)->toBeFalse()
        ->and($applicant->education_level)->toBeNull()
        ->and((int) $applicant->work_experience_years)->toBe(0);
});

test('a failed registration stores nothing', function (): void {
    $this->post(route('applicant.register'), fullRegistrationForm(['password_confirmation' => 'different']))
        ->assertSessionHasErrors('password');

    expect(User::where('email', 'Selam.Haile@example.com')->exists())->toBeFalse()
        ->and(Applicant::count())->toBe(0)
        ->and(ApplicantProfileDocument::count())->toBe(0);
    $this->assertGuest();
});

test('the same e-mail, phone or national ID cannot register twice', function (string $field): void {
    $this->post(route('applicant.register'), fullRegistrationForm())->assertSessionHasNoErrors();
    auth()->logout();

    $second = fullRegistrationForm([
        'email' => 'other@example.com', 'phone' => '0933445566', 'alternative_phone' => '',
        'national_id' => '9999888877776666',
    ]);
    $second[$field] = fullRegistrationForm()[$field];

    $this->post(route('applicant.register'), $second)->assertSessionHasErrors($field);
    expect(Applicant::count())->toBe(1);
})->with(['email', 'phone', 'national_id']);

test('ethnicity is no longer collected or stored', function (): void {
    expect(Schema::hasColumn('applicants', 'ethnicity'))->toBeFalse();

    $this->post(route('applicant.register'), fullRegistrationForm(['ethnicity' => 'should be ignored']))
        ->assertSessionHasNoErrors();

    expect(Applicant::where('email', 'Selam.Haile@example.com')->sole()->getAttributes())->not->toHaveKey('ethnicity');

    auth()->logout();
    app()->setLocale('am');
    $this->withSession(['locale' => 'am'])->get(route('applicant.register'))->assertOk()
        ->assertDontSee('ብሔር')
        ->assertSee('ዜግነት');
});
