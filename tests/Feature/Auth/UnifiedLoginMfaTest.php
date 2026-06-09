<?php

declare(strict_types=1);

use App\Http\Middleware\RequireTwoFactorSetup;
use App\Models\Applicant;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Session;

beforeEach(function (): void {
    RequireTwoFactorSetup::$bypassInTests = false;
    $this->seed(RolesAndPermissionsSeeder::class);
});

afterEach(function (): void {
    RequireTwoFactorSetup::$bypassInTests = true;
});

function setMfaPolicy(array $settings): void
{
    foreach ($settings as $key => $value) {
        $type = is_bool($value) ? 'boolean' : (is_int($value) ? 'integer' : (is_array($value) ? 'json' : 'string'));
        Setting::set("security.{$key}", $value, $type, 'security');
    }
}

test('one login page loads', function (): void {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee(__('auth.unified_login'));
});

test('admin can login from unified login page and redirects to admin', function (): void {
    setMfaPolicy(['mfa_enabled' => false]);
    $admin = User::factory()->admin()->create();

    $this->post(route('login'), ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($admin);
});

test('applicant can login from unified login page and redirects to applicant dashboard', function (): void {
    setMfaPolicy(['mfa_enabled' => false]);
    $user = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $user->id]);

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('applicant.dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('user with admin and applicant roles redirects to admin by rule', function (): void {
    setMfaPolicy(['mfa_enabled' => false]);
    $user = User::factory()->admin()->create();
    $user->assignRole('applicant');

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('admin.dashboard'));
});

test('inactive and suspended users cannot login', function (string $status): void {
    $user = User::factory()->create(['status' => $status]);

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
})->with(['inactive', 'suspended']);

test('old login routes redirect or remain secure', function (): void {
    $this->get(route('applicant.login'))->assertRedirect(route('login'));
    $this->get(route('admin.login'))->assertRedirect(route('login'));
});

test('mfa required for admin when enabled', function (): void {
    setMfaPolicy([
        'mfa_enabled' => true,
        'mfa_required_for_admins' => true,
    ]);
    $admin = User::factory()->admin()->create();

    $this->post(route('login'), ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(route('mfa.show'));
});

test('mfa not required for applicant when disabled', function (): void {
    setMfaPolicy([
        'mfa_enabled' => true,
        'mfa_required_for_applicants' => false,
    ]);
    $user = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $user->id]);

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('applicant.dashboard'));
});

test('mfa required for applicant when enabled', function (): void {
    setMfaPolicy([
        'mfa_enabled' => true,
        'mfa_required_for_applicants' => true,
    ]);
    $user = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $user->id]);

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('mfa.show'));
});

// ── Per-role MFA enforcement ─────────────────────────────────────────────────

test('mfa is required for a role listed in mfa_required_roles', function (): void {
    setMfaPolicy([
        'mfa_enabled' => true,
        'mfa_required_roles' => ['hr_manager'],
    ]);
    $user = User::factory()->create();
    $user->assignRole('hr_manager');

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('mfa.show'));
});

test('mfa is not required for a role absent from mfa_required_roles', function (): void {
    setMfaPolicy([
        'mfa_enabled' => true,
        'mfa_required_roles' => ['hr_manager'],
    ]);
    // screening_officer is NOT in the required list.
    $user = User::factory()->screeningOfficer()->create();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('admin.dashboard'));
});

test('per-role list overrides the legacy admins toggle', function (): void {
    // Admins toggle is OFF, but admin role is explicitly required via per-role list.
    setMfaPolicy([
        'mfa_enabled' => true,
        'mfa_required_for_admins' => false,
        'mfa_required_roles' => ['admin'],
    ]);
    $admin = User::factory()->admin()->create();

    $this->post(route('login'), ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(route('mfa.show'));
});

test('empty per-role list falls back to legacy admin toggle', function (): void {
    setMfaPolicy([
        'mfa_enabled' => true,
        'mfa_required_for_admins' => true,
        'mfa_required_roles' => [],
    ]);
    $admin = User::factory()->admin()->create();

    $this->post(route('login'), ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(route('mfa.show'));
});

test('mfa_required_roles can be saved through the settings form', function (): void {
    $superAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($superAdmin)
        ->put(route('admin.settings.update'), [
            'security' => [
                'mfa_enabled' => '1',
                'mfa_required_roles' => ['', 'hr_manager', 'exam_officer'],
            ],
        ])
        ->assertSessionHasNoErrors();

    expect(Setting::get('security.mfa_required_roles'))
        ->toEqualCanonicalizing(['hr_manager', 'exam_officer']);
});

test('mfa setting update requires settings security and manage permissions', function (): void {
    $screeningOfficer = User::factory()->screeningOfficer()->create();

    $this->actingAs($screeningOfficer)
        ->put(route('admin.settings.update'), [
            'security' => ['mfa_enabled' => '1'],
        ])
        ->assertForbidden();
});

test('unified login is throttled', function (): void {
    $user = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password-'.$attempt,
        ]);
    }

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertTooManyRequests();
});

test('session regenerates after unified login', function (): void {
    setMfaPolicy(['mfa_enabled' => false]);
    $user = User::factory()->asApplicant()->create();
    Applicant::factory()->create(['user_id' => $user->id]);
    $this->get(route('login'));
    $oldSessionId = Session::getId();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password']);

    expect(Session::getId())->not->toBe($oldSessionId);
});

test('logout invalidates authenticated session', function (): void {
    $user = User::factory()->asApplicant()->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('home'));

    $this->assertGuest();
});
