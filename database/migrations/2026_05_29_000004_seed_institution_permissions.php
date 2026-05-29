<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'institutions.view',
            'institutions.create',
            'institutions.update',
            'institutions.delete',
            'institutions.activate',
            'institutions.deactivate',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Grant all institution permissions to super_admin and admin
        foreach (['super_admin', 'admin'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->givePermissionTo($permissions);
            }
        }

        // Grant view + activate/deactivate to hr_manager
        $hrManager = Role::where('name', 'hr_manager')->where('guard_name', 'web')->first();
        if ($hrManager) {
            $hrManager->givePermissionTo(['institutions.view']);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::whereIn('name', [
            'institutions.view', 'institutions.create', 'institutions.update',
            'institutions.delete', 'institutions.activate', 'institutions.deactivate',
        ])->delete();
    }
};
