<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\EthiopianPhone;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->admin()->create([
        'name' => 'Super Admin',
        'email' => 'boss@jobs.local',
        'phone' => '+251900000001',
        'password' => Hash::make('Old-Passw0rd!2026'),
    ]);
});

function profilePayload(array $overrides = []): array
{
    return $overrides + ['name' => 'Super Admin', 'email' => 'boss@jobs.local', 'phone' => '900000001'];
}

test('profile page renders details, password and two-factor sections', function (): void {
    $this->actingAs($this->admin)->get(route('admin.profile.edit'))
        ->assertOk()
        ->assertSee(__('messages.profile_personal'))
        ->assertSee(__('messages.profile_password'))
        ->assertSee(__('messages.profile_2fa'))
        ->assertSee('value="900000001"', false);
});

test('saving with the existing 9-digit phone works (was rejected before)', function (string $typed): void {
    $this->actingAs($this->admin)->put(route('admin.profile.update'), profilePayload(['phone' => $typed, 'name' => 'Renamed']))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.profile.edit'));

    expect($this->admin->refresh()->phone)->toBe('+251911234567')
        ->and($this->admin->name)->toBe('Renamed');
})->with(['911234567', '0911234567', '0911 234 567', '+251911234567', '251911234567']);

test('changing the sign-in email requires the current password', function (): void {
    $this->actingAs($this->admin)->put(route('admin.profile.update'), profilePayload(['email' => 'new@jobs.local']))
        ->assertSessionHasErrorsIn('profile', 'current_password');
    expect($this->admin->refresh()->email)->toBe('boss@jobs.local');

    $this->actingAs($this->admin)->put(route('admin.profile.update'), profilePayload(['email' => 'new@jobs.local', 'current_password' => 'Old-Passw0rd!2026']))
        ->assertSessionHasNoErrors();
    expect($this->admin->refresh()->email)->toBe('new@jobs.local');
});

test('password change requires the current password and the admin policy', function (): void {
    $this->actingAs($this->admin)->put(route('admin.profile.password'), [
        'current_password' => 'wrong', 'new_password' => 'New-Passw0rd!2026', 'new_password_confirmation' => 'New-Passw0rd!2026',
    ])->assertSessionHasErrorsIn('password', 'current_password');

    $this->actingAs($this->admin)->put(route('admin.profile.password'), [
        'current_password' => 'Old-Passw0rd!2026', 'new_password' => 'short', 'new_password_confirmation' => 'short',
    ])->assertSessionHasErrorsIn('password', 'new_password');

    $this->actingAs($this->admin)->put(route('admin.profile.password'), [
        'current_password' => 'Old-Passw0rd!2026', 'new_password' => 'New-Passw0rd!2026', 'new_password_confirmation' => 'New-Passw0rd!2026',
    ])->assertSessionHasNoErrors();

    expect(Hash::check('New-Passw0rd!2026', $this->admin->refresh()->password))->toBeTrue();
});

test('phone helper normalises common formats', function (): void {
    expect(EthiopianPhone::normalize('0911 234 567'))->toBe('+251911234567')
        ->and(EthiopianPhone::normalize(''))->toBeNull()
        ->and(EthiopianPhone::local('+251911234567'))->toBe('911234567')
        ->and(preg_match(EthiopianPhone::PATTERN, '+2510911234567'))->toBe(0);
});
