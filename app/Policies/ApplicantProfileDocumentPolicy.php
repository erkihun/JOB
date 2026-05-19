<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ApplicantProfileDocument;
use App\Models\User;

class ApplicantProfileDocumentPolicy
{
    public function view(User $user, ApplicantProfileDocument $document): bool
    {
        // Admin/staff with screening perms can view
        if ($user->hasAnyPermission(['screening.verify-documents', 'applications.view'])) {
            return true;
        }

        // Applicant can only view their own documents
        return $user->applicant?->id === $document->applicant_id;
    }

    public function delete(User $user, ApplicantProfileDocument $document): bool
    {
        return $user->applicant?->id === $document->applicant_id;
    }
}
