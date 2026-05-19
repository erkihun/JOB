<?php

namespace App\Policies;

use App\Models\ApplicationDocument;
use App\Models\User;

class ApplicationDocumentPolicy
{
    public function view(User $user, ApplicationDocument $document): bool
    {
        // Admin/staff with screening permissions can view
        if ($user->hasAnyPermission(['screening.verify-documents', 'applications.view'])) {
            return true;
        }

        // Applicant can view their own documents
        return $user->applicant?->id === $document->application->applicant_id;
    }

    public function upload(User $user, ApplicationDocument $document): bool
    {
        if (! $user->hasPermissionTo('applicant.documents.upload')) {
            return false;
        }

        return $user->applicant?->id === $document->application->applicant_id;
    }

    public function replace(User $user, ApplicationDocument $document): bool
    {
        if (! $user->hasPermissionTo('applicant.documents.replace')) {
            return false;
        }

        if ($user->applicant?->id !== $document->application->applicant_id) {
            return false;
        }

        // Cannot replace after deadline
        return $document->application->isEditable();
    }

    public function verify(User $user, ApplicationDocument $document): bool
    {
        return $user->hasPermissionTo('screening.verify-documents');
    }
}
