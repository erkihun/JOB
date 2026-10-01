<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /** Kept in sync with RolesAndPermissionsSeeder for existing installations. */
    private const GRANTS = [
        'super_admin' => [
            'recruitment-announcements.publish', 'recruitment-announcements.close',
            'recruitment-announcements.cancel', 'recruitment-announcements.extend-deadline',
            'screening.start', 'recruitment.advance-stage', 'recruitment.finalize',
        ],
        'admin' => [
            'recruitment-announcements.publish', 'recruitment-announcements.close',
            'recruitment-announcements.cancel', 'recruitment-announcements.extend-deadline',
            'screening.start', 'recruitment.advance-stage', 'recruitment.finalize', 'applications.unlock',
        ],
        'hr_manager' => [
            'recruitment-announcements.publish', 'recruitment-announcements.close',
            'recruitment-announcements.cancel', 'recruitment-announcements.extend-deadline',
            'screening.start', 'recruitment.advance-stage', 'recruitment.finalize',
            'applications.lock', 'applications.unlock',
        ],
        'hr_officer' => [
            'recruitment-announcements.publish', 'recruitment-announcements.close', 'screening.start',
        ],
    ];

    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (array_unique(array_merge(...array_values(self::GRANTS))) as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach (self::GRANTS as $roleName => $permissions) {
            Role::where('name', $roleName)->where('guard_name', 'web')->first()?->givePermissionTo($permissions);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::whereIn('name', [
            'recruitment-announcements.publish', 'recruitment-announcements.close',
            'recruitment-announcements.cancel', 'recruitment-announcements.extend-deadline',
            'screening.start', 'recruitment.advance-stage', 'recruitment.finalize',
        ])->delete();
    }
};
