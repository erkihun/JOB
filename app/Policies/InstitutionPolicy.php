<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Institution;
use App\Models\User;

class InstitutionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('institutions.view');
    }

    public function view(User $user, Institution $institution): bool
    {
        return $user->hasPermissionTo('institutions.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('institutions.create');
    }

    public function update(User $user, Institution $institution): bool
    {
        return $user->hasPermissionTo('institutions.update');
    }

    public function delete(User $user, Institution $institution): bool
    {
        if (! $user->hasPermissionTo('institutions.delete')) {
            return false;
        }

        // Cannot delete institution that has vacancies
        return $institution->vacancies()->count() === 0;
    }

    public function activate(User $user, Institution $institution): bool
    {
        return $user->hasPermissionTo('institutions.activate');
    }

    public function deactivate(User $user, Institution $institution): bool
    {
        return $user->hasPermissionTo('institutions.deactivate');
    }
}
