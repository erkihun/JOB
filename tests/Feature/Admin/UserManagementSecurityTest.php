<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

/**
 * Build a valid user-update payload, overriding only what each test needs.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function userPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Test User',
        'email' => 'new-user-'.uniqid().'@example.com',
        'status' => UserStatus::Active->value,
        'role' => 'screening_officer',
        'password' => 'StrongAdminPass@123',
        'password_confirmation' => 'StrongAdminPass@123',
    ], $overrides);
}

// ── Privilege escalation ─────────────────────────────────────────────────────

test('a normal admin cannot create a super admin (privilege escalation)', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.store'), userPayload(['role' => 'super_admin']))
        ->assertSessionHasErrors('role');

    expect(User::role('super_admin')->where('email', 'like', 'new-user-%')->exists())->toBeFalse();
});

test('a normal admin cannot promote an existing user to super admin', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->screeningOfficer()->create();

    $this->actingAs($admin)
        ->put(route('admin.users.update', $target), userPayload([
            'email' => $target->email,
            'role' => 'super_admin',
            'password' => '',
            'password_confirmation' => '',
        ]))
        ->assertSessionHasErrors('role');

    expect($target->fresh()->isSuperAdmin())->toBeFalse();
});

test('a super admin CAN create another super admin', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($superAdmin)
        ->post(route('admin.users.store'), userPayload([
            'email' => 'second-super@example.com',
            'role' => 'super_admin',
        ]))
        ->assertSessionHasNoErrors();

    expect(User::where('email', 'second-super@example.com')->firstOrFail()->isSuperAdmin())->toBeTrue();
});

// ── Super Admin protection ───────────────────────────────────────────────────

test('a normal admin cannot edit a super admin account', function () {
    $admin = User::factory()->admin()->create();
    $superAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->put(route('admin.users.update', $superAdmin), userPayload([
            'email' => $superAdmin->email,
            'role' => 'admin',
            'password' => '',
            'password_confirmation' => '',
        ]))
        ->assertForbidden();

    expect($superAdmin->fresh()->isSuperAdmin())->toBeTrue();
});

test('a normal admin cannot delete any user (lacks users.delete permission)', function () {
    $admin = User::factory()->admin()->create();
    $victim = User::factory()->screeningOfficer()->create();

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $victim))
        ->assertForbidden();

    expect(User::find($victim->id))->not->toBeNull();
});

test('a super admin cannot delete the last active super admin', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    // Only one super admin exists. The model-level `User::deleting` guard halts
    // the deletion. (A super admin bypasses the policy gate, so the model guard
    // is the enforcing layer here — defense in depth.) The account must survive.
    $this->actingAs($superAdmin)
        ->delete(route('admin.users.destroy', $superAdmin));

    expect(User::find($superAdmin->id))->not->toBeNull();
});

test('a super admin can delete another super admin when more than one remains', function () {
    $actor = User::factory()->superAdmin()->create();
    $other = User::factory()->superAdmin()->create();

    $this->actingAs($actor)
        ->delete(route('admin.users.destroy', $other))
        ->assertRedirect(route('admin.users.index'));

    expect(User::find($other->id))->toBeNull();
});

// ── Input validation ─────────────────────────────────────────────────────────

test('user status is constrained to the UserStatus enum', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($superAdmin)
        ->post(route('admin.users.store'), userPayload(['status' => 'god-mode']))
        ->assertSessionHasErrors('status');
});

// ── Audit logging ────────────────────────────────────────────────────────────

test('deleting a user writes an audit log entry', function () {
    $actor = User::factory()->superAdmin()->create();
    $other = User::factory()->superAdmin()->create();

    $this->actingAs($actor)->delete(route('admin.users.destroy', $other));

    expect(AuditLog::where('action', 'user_deleted')->where('record_id', $other->id)->exists())->toBeTrue();
});

test('creating a user writes an audit log entry', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($superAdmin)->post(route('admin.users.store'), userPayload([
        'email' => 'audited-user@example.com',
    ]));

    $created = User::where('email', 'audited-user@example.com')->firstOrFail();

    expect(AuditLog::where('action', 'user_created')->where('record_id', $created->id)->exists())->toBeTrue();
});
