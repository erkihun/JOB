<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vacancy;

class VacancyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('vacancies.view');
    }

    public function view(User $user, Vacancy $vacancy): bool
    {
        return $user->hasPermissionTo('vacancies.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('vacancies.create');
    }

    public function update(User $user, Vacancy $vacancy): bool
    {
        return $user->hasPermissionTo('vacancies.update');
    }

    public function delete(User $user, Vacancy $vacancy): bool
    {
        if (! $user->hasPermissionTo('vacancies.delete')) {
            return false;
        }

        // Cannot delete a vacancy that has applications
        return $vacancy->applications()->count() === 0;
    }

    public function publish(User $user, Vacancy $vacancy): bool
    {
        return $user->hasPermissionTo('vacancies.publish');
    }

    public function close(User $user, Vacancy $vacancy): bool
    {
        return $user->hasPermissionTo('vacancies.close');
    }

    public function cancel(User $user, Vacancy $vacancy): bool
    {
        return $user->hasPermissionTo('vacancies.cancel');
    }
}
