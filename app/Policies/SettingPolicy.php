<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;

class SettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('settings.view');
    }

    public function view(User $user, Setting $setting): bool
    {
        return $user->hasPermissionTo('settings.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('settings.manage');
    }

    public function update(User $user, Setting $setting): bool
    {
        return $user->hasPermissionTo('settings.manage');
    }

    public function delete(User $user, Setting $setting): bool
    {
        return $user->isSuperAdmin();
    }
}
