<?php

declare(strict_types=1);

use App\Models\Applicant;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
});

function passwordPolicyAdminPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Policy Admin',
        'email' => fake()->unique()->safeEmail(),
        'status' => 'active',
        'role' => 'admin',
        'password' => 'StrongAdmin@1234',
        'password_confirmation' => 'StrongAdmin@1234',
    ], $overrides);
}

function passwordPolicyApplicantPayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Policy',
        'middle_name' => 'Test',
        'last_name' => 'Applicant',
        'email' => fake()->unique()->safeEmail(),
        'phone' => fake()->unique()->numerify('+2519########'),
        'gender' => 'male',
        'date_of_birth' => '1995-01-01',
        'nationality' => 'Ethiopian',
        'national_id' => fake()->unique()->numerify('################'),
        'disability_status' => '0',
        'university_name' => 'Addis Ababa University',
        'field_of_study' => 'Computer Science',
        'graduation_year' => 2018,
        'gpa' => 3.5,
        'education_level' => 'degree',
        'work_experience_years' => 0,
        'work_experience_months' => 0,
        'region' => 'Addis Ababa',
        'city' => 'Addis Ababa',
        'password' => 'Applicant123',
        'password_confirmation' => 'Applicant123',
        'preferred_locale' => 'en',
        'documents' => UploadedFile::fake()->create('documents.pdf', 500, 'application/pdf'),
    ], $overrides);
}

function setPasswordPolicy(string $scope, array $settings): void
{
    foreach ($settings as $key => $value) {
        $type = is_bool($value) ? 'boolean' : (is_int($value) ? 'integer' : 'string');

        Setting::set("security.{$scope}_password_{$key}", $value, $type, 'security');
    }
}

test('settings manage user can update admin and applicant password policy', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.settings.update'), [
            'security' => [
                'admin_password_min_length' => 14,
                'admin_password_require_uppercase' => '1',
                'admin_password_require_lowercase' => '1',
                'admin_password_require_number' => '1',
                'admin_password_require_symbol' => '1',
                'admin_password_prevent_common_passwords' => '1',
                'admin_password_expiry_days' => 90,
                'admin_password_history_count' => 5,
                'applicant_password_min_length' => 10,
                'applicant_password_require_uppercase' => '1',
                'applicant_password_require_lowercase' => '1',
                'applicant_password_require_number' => '1',
                'applicant_password_require_symbol' => '0',
                'applicant_password_prevent_common_passwords' => '1',
                'applicant_password_expiry_days' => 180,
                'applicant_password_history_count' => 3,
            ],
        ])
        ->assertRedirect();

    expect(Setting::get('security.admin_password_min_length'))->toBe(14)
        ->and(Setting::get('security.admin_password_require_symbol'))->toBeTrue()
        ->and(Setting::get('security.admin_password_expiry_days'))->toBe('90')
        ->and(Setting::get('security.applicant_password_min_length'))->toBe(10)
        ->and(Setting::get('security.applicant_password_require_symbol'))->toBeFalse()
        ->and(Setting::get('security.applicant_password_history_count'))->toBe('3');
});

test('unauthorized user cannot update password policy settings', function (): void {
    $screeningOfficer = User::factory()->screeningOfficer()->create();

    $this->actingAs($screeningOfficer)
        ->put(route('admin.settings.update'), [
            'security' => ['admin_password_min_length' => 14],
        ])
        ->assertForbidden();
});

test('admin password min length is enforced', function (): void {
    setPasswordPolicy('admin', ['min_length' => 16]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.store'), passwordPolicyAdminPayload([
            'password' => 'Admin@123',
            'password_confirmation' => 'Admin@123',
        ]))
        ->assertSessionHasErrors('password');
});

test('admin uppercase number and symbol requirements are enforced', function (string $password): void {
    setPasswordPolicy('admin', [
        'min_length' => 12,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_number' => true,
        'require_symbol' => true,
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.store'), passwordPolicyAdminPayload([
            'password' => $password,
            'password_confirmation' => $password,
        ]))
        ->assertSessionHasErrors('password');
})->with([
    'missing uppercase' => ['strongadmin@1234'],
    'missing number' => ['StrongAdmin@abcd'],
    'missing symbol' => ['StrongAdmin1234'],
]);

test('applicant password min length and uppercase number requirements are enforced', function (string $password): void {
    setPasswordPolicy('applicant', [
        'min_length' => 12,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_number' => true,
        'require_symbol' => false,
    ]);

    $this->post(route('applicant.register'), passwordPolicyApplicantPayload([
        'password' => $password,
        'password_confirmation' => $password,
    ]))
        ->assertSessionHasErrors('password');
})->with([
    'too short' => ['App123'],
    'missing uppercase' => ['applicant1234'],
    'missing number' => ['ApplicantPass'],
]);

test('applicant symbol requirement can be disabled and enabled by settings', function (): void {
    setPasswordPolicy('applicant', ['require_symbol' => false]);

    $this->post(route('applicant.register'), passwordPolicyApplicantPayload([
        'password' => 'Applicant123',
        'password_confirmation' => 'Applicant123',
    ]))
        ->assertRedirect(route('applicant.verify-email'));

    auth()->logout();
    session()->invalidate();
    session()->regenerateToken();

    setPasswordPolicy('applicant', ['require_symbol' => true]);

    $this->post(route('applicant.register'), passwordPolicyApplicantPayload([
        'email' => 'second-policy-applicant@example.test',
        'phone' => '+251911222333',
        'national_id' => '9999888877776666',
        'password' => 'Applicant123',
        'password_confirmation' => 'Applicant123',
    ]))
        ->assertSessionHasErrors('password');
});

test('admin user creation uses admin password policy', function (): void {
    setPasswordPolicy('admin', ['require_symbol' => true]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.store'), passwordPolicyAdminPayload([
            'password' => 'StrongAdmin1234',
            'password_confirmation' => 'StrongAdmin1234',
        ]))
        ->assertSessionHasErrors('password');
});

test('password reset uses correct policy for admin and applicant', function (): void {
    setPasswordPolicy('admin', ['require_symbol' => true]);
    setPasswordPolicy('applicant', ['require_symbol' => false]);

    $admin = User::factory()->admin()->create(['email' => 'admin-reset@example.test']);
    $applicant = User::factory()->asApplicant()->create(['email' => 'applicant-reset@example.test']);
    Applicant::factory()->create(['user_id' => $applicant->id]);

    $this->withSession([
        'admin_password_reset_email' => $admin->email,
        'admin_password_reset_token' => 'admin-token',
    ])->post(route('admin.password.reset'), [
        'token' => 'admin-token',
        'password' => 'StrongAdmin1234',
        'password_confirmation' => 'StrongAdmin1234',
    ])->assertSessionHasErrors('password');

    $this->withSession([
        'applicant_password_reset_email' => $applicant->email,
        'applicant_password_reset_token' => 'applicant-token',
    ])->post(route('applicant.password.reset'), [
        'token' => 'applicant-token',
        'password' => 'Applicant123',
        'password_confirmation' => 'Applicant123',
    ])->assertRedirect(route('login'));

    expect(Hash::check('Applicant123', $applicant->fresh()->password))->toBeTrue();
});

test('amharic validation message appears when locale is am', function (): void {
    app()->setLocale('am');
    setPasswordPolicy('applicant', [
        'min_length' => 8,
        'require_uppercase' => false,
        'require_lowercase' => false,
        'require_number' => false,
        'require_symbol' => false,
        'prevent_common_passwords' => true,
    ]);

    $this->post(route('applicant.register'), passwordPolicyApplicantPayload([
        'preferred_locale' => 'am',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]))
        ->assertSessionHasErrors([
            'password' => __('validation.password_policy'),
        ]);
});
