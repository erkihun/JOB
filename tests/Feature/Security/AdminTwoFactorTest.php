<?php

declare(strict_types=1);

use App\Http\Middleware\RequireTwoFactorSetup;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use PragmaRX\Google2FA\Google2FA;

beforeEach(function (): void {
    RequireTwoFactorSetup::$bypassInTests = false; // enable enforcement for these tests
    $this->seed(RolesAndPermissionsSeeder::class);
    Setting::set('security.mfa_enabled', true, 'boolean', 'security');
    Setting::set('security.mfa_required_for_admins', false, 'boolean', 'security');
});

afterEach(function (): void {
    RequireTwoFactorSetup::$bypassInTests = true; // reset so other test files are unaffected
});

// ── Login-step 2FA flow ───────────────────────────────────────────────────────

test('login with 2fa enabled redirects to the challenge page instead of the dashboard', function (): void {
    $google2fa = app(Google2FA::class);
    $secret = $google2fa->generateSecretKey();
    $admin = User::factory()->admin()->create(['google2fa_secret' => $secret]);

    $this->post(route('admin.login'), ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(route('mfa.challenge'));
});

test('login without 2fa completes normally and redirects to dashboard', function (): void {
    $admin = User::factory()->admin()->create();

    $this->post(route('admin.login'), ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(route('admin.dashboard'));
});

test('login challenge verifies a valid otp and grants access', function (): void {
    $google2fa = app(Google2FA::class);
    $secret = $google2fa->generateSecretKey();
    $admin = User::factory()->admin()->create(['google2fa_secret' => $secret]);
    $otp = $google2fa->getCurrentOtp($secret);

    $this->actingAs($admin)
        ->post(route('admin.login.two-factor'), ['one_time_password' => $otp])
        ->assertRedirect(route('admin.dashboard'));
});

test('login challenge rejects an invalid otp', function (): void {
    $google2fa = app(Google2FA::class);
    $secret = $google2fa->generateSecretKey();
    $admin = User::factory()->admin()->create(['google2fa_secret' => $secret]);

    $this->actingAs($admin)
        ->post(route('admin.login.two-factor'), ['one_time_password' => '000000'])
        ->assertSessionHasErrors('one_time_password');
});

test('challenge page skips to dashboard when session is already verified', function (): void {
    $google2fa = app(Google2FA::class);
    $secret = $google2fa->generateSecretKey();
    $admin = User::factory()->admin()->create(['google2fa_secret' => $secret]);

    $this->actingAs($admin)
        ->withSession([config('google2fa.session_var') => ['auth_passed' => true]])
        ->get(route('admin.login.two-factor'))
        ->assertRedirect(route('admin.dashboard'));
});

// ── Admin without 2FA — must set up 2FA before accessing admin panel ─────────

test('admin without 2fa is redirected to 2fa setup page', function (): void {
    Setting::set('security.mfa_required_for_admins', true, 'boolean', 'security');
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertRedirect(route('mfa.show'));
});

test('admin without 2fa sees setup prompt on two-factor page', function (): void {
    Setting::set('security.mfa_required_for_admins', true, 'boolean', 'security');
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.two-factor.show'))
        ->assertOk()
        ->assertSee('Multi-Factor Authentication')
        ->assertSee('Enable Multi-Factor Authentication');
});

// ── Admin with 2FA — require2fa redirects to login challenge ──────────────────

test('admin with 2fa and unverified session is redirected to login challenge', function (): void {
    $admin = User::factory()->admin()->create();
    $admin->google2fa_secret = app(Google2FA::class)->generateSecretKey();
    $admin->save();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertRedirect(route('mfa.challenge'));
});

test('admin with 2fa and verified session can access the dashboard', function (): void {
    $admin = User::factory()->admin()->create();
    $admin->google2fa_secret = app(Google2FA::class)->generateSecretKey();
    $admin->save();

    $this->actingAs($admin)
        ->withSession([config('google2fa.session_var') => ['auth_passed' => true]])
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('Verification Required');
});

// ── Enable flow ───────────────────────────────────────────────────────────────

test('admin can enable 2fa with a valid otp', function (): void {
    $admin = User::factory()->admin()->create();
    $google2fa = app(Google2FA::class);
    $secret = $google2fa->generateSecretKey();
    $otp = $google2fa->getCurrentOtp($secret);

    $this->actingAs($admin)
        ->withSession(['2fa_setup_secret' => $secret])
        ->post(route('admin.two-factor.enable'), ['one_time_password' => $otp])
        ->assertRedirect(route('mfa.show'));

    $admin->refresh();
    expect($admin->hasTwoFactorEnabled())->toBeTrue();
});

test('enable 2fa rejects an incorrect otp', function (): void {
    $admin = User::factory()->admin()->create();
    $secret = app(Google2FA::class)->generateSecretKey();

    $this->actingAs($admin)
        ->withSession(['2fa_setup_secret' => $secret])
        ->post(route('admin.two-factor.enable'), ['one_time_password' => '000000'])
        ->assertSessionHasErrors('one_time_password');

    $admin->refresh();
    expect($admin->hasTwoFactorEnabled())->toBeFalse();
});

// ── Disable flow ──────────────────────────────────────────────────────────────

test('admin can disable 2fa with current password confirmation', function (): void {
    $google2fa = app(Google2FA::class);
    $secret = $google2fa->generateSecretKey();
    $admin = User::factory()->admin()->create(['google2fa_secret' => $secret]);

    $this->actingAs($admin)
        ->withSession([config('google2fa.session_var') => ['auth_passed' => true]])
        ->post(route('admin.two-factor.disable'), ['current_password' => 'password'])
        ->assertRedirect(route('mfa.show'));

    $admin->refresh();
    expect($admin->hasTwoFactorEnabled())->toBeFalse();
});

test('disable 2fa rejects an incorrect current password', function (): void {
    $google2fa = app(Google2FA::class);
    $secret = $google2fa->generateSecretKey();
    $admin = User::factory()->admin()->create(['google2fa_secret' => $secret]);

    $this->actingAs($admin)
        ->withSession([config('google2fa.session_var') => ['auth_passed' => true]])
        ->post(route('admin.two-factor.disable'), ['current_password' => 'wrong-password'])
        ->assertSessionHasErrors('current_password');

    $admin->refresh();
    expect($admin->hasTwoFactorEnabled())->toBeTrue();
});

// ── 2FA setup page not accessible by applicants ───────────────────────────────

test('applicant cannot access admin two-factor setup page', function (): void {
    $user = User::factory()->asApplicant()->create();

    $this->actingAs($user)
        ->get(route('admin.two-factor.show'))
        ->assertRedirect();
});
