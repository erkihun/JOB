<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    RateLimiter::clear('settings-throttle@example.test|127.0.0.1');
});

test('admin settings page renders integrated security and appearance controls', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.settings.index'))
        ->assertOk()
        ->assertSee(__('settings.session_timeout'))
        ->assertSee(__('settings.login_attempts'))
        ->assertSee(__('settings.mfa_management'))
        ->assertSee(__('settings.appearance_presets'))
        ->assertSee(__('settings.appearance_reset_defaults'));
});

test('settings update remains protected by strict settings permissions', function (): void {
    $screeningOfficer = User::factory()->screeningOfficer()->create();

    $this->actingAs($screeningOfficer)
        ->put(route('admin.settings.update'), [
            'appearance' => ['primary_color' => '#059669'],
        ])
        ->assertForbidden();
});

test('theme colors reject unsafe css input', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.settings.update'), [
            'appearance' => [
                'primary_color' => 'red;background:url(javascript:alert(1))',
                'sidebar_color' => '#1E3A8A',
                'accent_color' => '#FF6B2B',
                'logo_size' => 36,
            ],
        ])
        ->assertSessionHasErrors('appearance.primary_color');
});

test('appearance settings persist and render sanitized admin theme variables', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.settings.update'), [
            'appearance' => [
                'primary_color' => '#059669',
                'sidebar_color' => '#064E3B',
                'accent_color' => '#F59E0B',
                'logo_size' => 48,
            ],
        ])
        ->assertRedirect();

    expect(Setting::get('appearance.primary_color'))->toBe('#059669')
        ->and(Setting::get('appearance.sidebar_color'))->toBe('#064E3B')
        ->and(Setting::get('appearance.accent_color'))->toBe('#F59E0B')
        ->and((int) Setting::get('appearance.logo_size'))->toBe(48);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('--color-brand: #059669;', false)
        ->assertSee('--color-navy: #064E3B;', false)
        ->assertSee('--color-accent: #F59E0B;', false)
        ->assertSee('width: 48px; height: 48px;', false);
});

test('admin theme output falls back when stored color is corrupted', function (): void {
    $admin = User::factory()->admin()->create();
    Setting::set('appearance.primary_color', 'red;position:fixed', 'string', 'appearance');
    Setting::set('appearance.sidebar_color', '#064E3B', 'string', 'appearance');
    Setting::set('appearance.accent_color', '#F59E0B', 'string', 'appearance');

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('red;position:fixed', false)
        ->assertSee('--color-brand: #1A56DB;', false)
        ->assertSee('--color-navy: #064E3B;', false);
});

test('admin mfa challenge renders saved admin theme variables', function (): void {
    Setting::set('security.mfa_enabled', true, 'boolean', 'security');
    Setting::set('security.mfa_required_for_admins', true, 'boolean', 'security');
    Setting::set('appearance.primary_color', '#7C3AED', 'string', 'appearance');

    $admin = User::factory()->admin()->create([
        'google2fa_secret' => 'JBSWY3DPEHPK3PXP',
    ]);

    $this->actingAs($admin)
        ->get(route('mfa.challenge'))
        ->assertOk()
        ->assertSee('--color-brand: #7C3AED;', false)
        ->assertSee('admin-auth-shell', false);
});

test('settings updates write non-security audit log without uploaded file payloads', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.settings.update'), [
            'org' => [
                'name' => 'Audit Checked Organization',
            ],
            'appearance' => [
                'primary_color' => '#059669',
            ],
        ])
        ->assertRedirect();

    $log = AuditLog::query()
        ->where('action', 'settings_updated')
        ->latest('created_at')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->new_values['org.name'] ?? null)->toBe('Audit Checked Organization')
        ->and($log->new_values['appearance.primary_color'] ?? null)->toBe('#059669');
});

test('configured login attempts change unified login throttling', function (): void {
    Setting::set('security.login_attempts', 3, 'integer', 'security');
    $email = 'settings-throttle@example.test';

    for ($i = 0; $i < 3; $i++) {
        $this->post(route('login'), [
            'email' => $email,
            'password' => 'wrong-password-'.$i,
        ])->assertStatus(302);
    }

    $this->post(route('login'), [
        'email' => $email,
        'password' => 'wrong-password',
    ])->assertStatus(429);
});

test('session timeout logs out idle authenticated users and keeps active users', function (): void {
    Setting::set('security.session_timeout', 5, 'integer', 'security');
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->withSession(['auth.last_activity_at' => now()->subMinutes(6)->timestamp])
        ->get(route('admin.dashboard'))
        ->assertRedirect(route('login'));

    $this->assertGuest();

    $freshAdmin = User::factory()->admin()->create([
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($freshAdmin)
        ->withSession(['auth.last_activity_at' => now()->subMinutes(4)->timestamp])
        ->get(route('admin.dashboard'))
        ->assertOk();

    $this->assertAuthenticatedAs($freshAdmin);
});

test('settings cache reflects fresh writes during tests', function (): void {
    Setting::set('appearance.primary_color', '#059669', 'string', 'appearance');
    expect(Setting::get('appearance.primary_color'))->toBe('#059669');

    Setting::set('appearance.primary_color', '#E11D48', 'string', 'appearance');
    expect(Setting::get('appearance.primary_color'))->toBe('#E11D48');
});
