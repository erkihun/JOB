<?php

namespace App\Policies;

use App\Enums\UserStatus;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('users.view');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasPermissionTo('users.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('users.create');
    }

    public function update(User $user, User $model): bool
    {
        if (! $user->hasPermissionTo('users.update')) {
            return false;
        }

        // Cannot update a Super Admin unless you are a Super Admin
        if ($model->isSuperAdmin() && ! $user->isSuperAdmin()) {
            return false;
        }

        return true;
    }

    public function delete(User $user, User $model): bool
    {
        if (! $user->hasPermissionTo('users.delete')) {
            return false;
        }

        if ($model->isSuperAdmin()) {
            return $user->isSuperAdmin()
                && User::role('super_admin')
                    ->where('status', UserStatus::Active->value)
                    ->whereKeyNot($model->id)
                    ->exists();
        }

        // Cannot delete yourself
        if ($user->id === $model->id) {
            return false;
        }

        return true;
    }

    public function assignRole(User $user, User $model): bool
    {
        return $user->hasPermissionTo('users.assign-role');
    }
}
