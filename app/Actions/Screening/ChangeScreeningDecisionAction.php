<?php

declare(strict_types=1);

namespace App\Actions\Screening;

use App\Actions\Audit\LogAuditAction;
use App\Enums\ScreeningDecision;
use App\Models\Application;
use App\Models\ScreeningReview;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Changes an already-recorded screening decision (pass ⇄ fail). Only users holding
 * `screening.reverse-decision` may do this, a reason is mandatory, and it is only
 * possible while the application is still at the screening stage.
 */
class ChangeScreeningDecisionAction
{
    public function __construct(
        private readonly ReviewApplicationAction $reviewApplication,
        private readonly LogAuditAction $auditLogger,
    ) {}

    public function handle(
        Application $application,
        User $user,
        ScreeningDecision $decision,
        string $reason,
    ): ScreeningReview {
        if (! $user->hasPermissionTo('screening.reverse-decision')) {
            throw new AuthorizationException(__('messages.decision_change_forbidden'));
        }

        if (! $application->screeningDecisionChangeable()) {
            throw ValidationException::withMessages(['decision' => __('messages.decision_change_stage_locked')]);
        }

        if ($application->screening_status === $decision) {
            throw ValidationException::withMessages(['decision' => __('messages.decision_change_same')]);
        }

        $previousDecision = $application->screening_status?->value;

        return DB::transaction(function () use ($application, $user, $decision, $reason, $previousDecision): ScreeningReview {
            // Records the new decision + history entry, and notifies the applicant.
            $review = $this->reviewApplication->handle($application, $user, $decision, $reason);

            $this->auditLogger->handle(
                action: 'screening_decision_changed',
                module: 'screening',
                recordId: $application->id,
                oldValues: ['screening_status' => $previousDecision],
                newValues: ['screening_status' => $decision->value, 'reason' => $reason, 'changed_by' => $user->id],
            );

            return $review;
        });
    }
}
