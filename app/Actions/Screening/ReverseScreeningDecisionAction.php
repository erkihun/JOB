<?php

declare(strict_types=1);

namespace App\Actions\Screening;

use App\Enums\ApplicationStatus;
use App\Enums\ScreeningDecision;
use App\Models\Application;
use App\Models\ScreeningReview;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class ReverseScreeningDecisionAction
{
    public function handle(
        Application $application,
        User $reviewer,
        string $remark,
    ): ScreeningReview {
        if (! $reviewer->hasPermissionTo('screening.reverse-decision')) {
            throw new AuthorizationException('You do not have permission to reverse screening decisions.');
        }

        $previousStatus = $application->status?->value;

        $application->update([
            'status' => ApplicationStatus::UnderReview,
            'screening_status' => ScreeningDecision::Pending,
            'screening_remark' => $remark,
            'screened_by' => $reviewer->id,
            'screened_at' => now(),
        ]);

        return ScreeningReview::create([
            'application_id' => $application->id,
            'reviewer_id' => $reviewer->id,
            'previous_status' => $previousStatus,
            'new_status' => ApplicationStatus::UnderReview->value,
            'decision' => ScreeningDecision::Pending,
            'remark' => 'REVERSAL: '.$remark,
            'reviewed_at' => now(),
        ]);
    }
}
