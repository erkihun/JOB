<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('reports.view');
    }

    public function export(User $user): bool
    {
        return $user->hasPermissionTo('reports.export');
    }

    public function viewApplicants(User $user): bool
    {
        return $user->hasPermissionTo('reports.applicants');
    }

    public function viewVacancies(User $user): bool
    {
        return $user->hasPermissionTo('reports.vacancies');
    }

    public function viewScreening(User $user): bool
    {
        return $user->hasPermissionTo('reports.screening');
    }

    public function viewExamInterview(User $user): bool
    {
        return $user->hasPermissionTo('reports.exam-interview');
    }

    public function viewAudit(User $user): bool
    {
        return $user->hasPermissionTo('reports.audit');
    }
}
