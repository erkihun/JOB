<?php

declare(strict_types=1);

namespace App\Actions\Screening;

use App\Actions\Audit\LogAuditAction;
use App\Enums\ApplicationStatus;
use App\Enums\ScreeningDecision;
use App\Models\Application;
use App\Models\ScreeningReview;
use App\Models\User;

class ReviewApplicationAction
{
    public function __construct(private readonly LogAuditAction $auditLogger) {}

    public function handle(
        Application $application,
        User $reviewer,
        ScreeningDecision $decision,
        ?string $remark = null,
    ): ScreeningReview {
        $previousStatus = $application->status?->value;
        $newStatus = $this->mapDecisionToStatus($decision);

        $application->update([
            'status' => $newStatus,
            'screening_status' => $decision,
            'screening_remark' => $remark,
            'screened_by' => $reviewer->id,
            'screened_at' => now(),
        ]);

        $this->auditLogger->handle(
            action: 'screening_status_changed',
            module: 'screening',
            recordId: $application->id,
            oldValues: ['status' => $previousStatus],
            newValues: [
                'status' => $newStatus->value,
                'screening_status' => $decision->value,
                'reviewer_id' => $reviewer->id,
            ],
        );

        return ScreeningReview::create([
            'application_id' => $application->id,
            'reviewer_id' => $reviewer->id,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus->value,
            'decision' => $decision,
            'remark' => $remark,
            'reviewed_at' => now(),
        ]);
    }

    private function mapDecisionToStatus(ScreeningDecision $decision): ApplicationStatus
    {
        return match ($decision) {
            ScreeningDecision::Passed => ApplicationStatus::PassedScreening,
            ScreeningDecision::Failed => ApplicationStatus::FailedScreening,
            ScreeningDecision::CorrectionRequired => ApplicationStatus::CorrectionRequired,
            ScreeningDecision::Pending => ApplicationStatus::UnderReview,
        };
    }
}
