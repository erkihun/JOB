<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /** Kept in sync with RolesAndPermissionsSeeder for existing installations. */
    private const PERMISSIONS = [
        'backups.view', 'backups.settings.manage', 'backups.run',
        'backups.download', 'backups.restore', 'backups.delete',
    ];

    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // Restoring or deleting backups stays with super_admin only.
        Role::where('name', 'super_admin')->where('guard_name', 'web')->first()?->givePermissionTo(self::PERMISSIONS);
        Role::where('name', 'admin')->where('guard_name', 'web')->first()
            ?->givePermissionTo(['backups.view', 'backups.settings.manage', 'backups.run', 'backups.download']);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Permission::whereIn('name', self::PERMISSIONS)->delete();
    }
};
