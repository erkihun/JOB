<?php

declare(strict_types=1);

namespace App\Actions\Screening;

use App\Models\Application;
use App\Models\User;

class AssignApplicationReviewerAction
{
    public function handle(Application $application, ?User $reviewer): Application
    {
        $application->update([
            'assigned_reviewer_id' => $reviewer?->id,
        ]);

        return $application->refresh();
    }
}
