<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\AuditLog;
use App\Models\User;

class ApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('applications.view');
    }

    public function view(User $user, Application $application): bool
    {
        // Admin/staff with permission can view any
        if ($user->hasPermissionTo('applications.view')) {
            return true;
        }

        // Applicant can view only their own
        return $user->applicant?->id === $application->applicant_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('applicant.applications.create');
    }

    public function update(User $user, Application $application): bool
    {
        // Staff with admin update permission
        if ($user->hasPermissionTo('applications.update')) {
            return true;
        }

        // Applicant can update only before deadline and not locked
        if ($user->applicant?->id !== $application->applicant_id) {
            return false;
        }

        return $application->isEditable();
    }

    public function delete(User $user, Application $application): bool
    {
        return $user->hasPermissionTo('applications.delete');
    }

    public function viewSensitive(User $user, Application $application): bool
    {
        $allowed = $user->hasPermissionTo('applications.view-sensitive');

        if ($allowed) {
            AuditLog::record(
                'sensitive_applicant_data_viewed',
                'applications',
                $application->id,
                null,
                ['applicant_id' => $application->applicant_id],
            );
        }

        return $allowed;
    }

    public function screen(User $user, Application $application): bool
    {
        if (! $user->hasPermissionTo('screening.review')) {
            return false;
        }

        // If the user has broader permission (e.g. admin/hr_manager), allow any
        if ($user->hasAnyPermission(['applications.view-sensitive', 'applications.assign-reviewer'])) {
            return true;
        }

        // Screening officers may only review assigned applications
        if ($application->assigned_reviewer_id !== null) {
            return $application->assigned_reviewer_id === $user->id;
        }

        // If no reviewer assigned, fall back to general permission
        return true;
    }

    public function reverseDecision(User $user, Application $application): bool
    {
        return $user->hasPermissionTo('screening.reverse-decision');
    }

    public function assignReviewer(User $user): bool
    {
        return $user->hasPermissionTo('applications.assign-reviewer');
    }

    public function viewHistory(User $user, Application $application): bool
    {
        return $user->hasPermissionTo('screening.view-history');
    }
}
