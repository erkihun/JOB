<?php

declare(strict_types=1);

namespace App\Actions\Screening;

use App\Actions\Audit\LogAuditAction;
use App\Actions\Notifications\SendApplicantNotificationAction;
use App\Enums\ApplicationStatus;
use App\Enums\NotificationType;
use App\Enums\ScreeningDecision;
use App\Models\Application;
use App\Models\ScreeningReview;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReviewApplicationAction
{
    public function __construct(
        private readonly LogAuditAction $auditLogger,
        private readonly SendApplicantNotificationAction $notifications,
    ) {}

    public function handle(
        Application $application,
        User $reviewer,
        ScreeningDecision $decision,
        ?string $remark = null,
    ): ScreeningReview {
        $previousStatus = $application->status?->value;
        $newStatus = $this->mapDecisionToStatus($decision);

        $review = DB::transaction(function () use ($application, $reviewer, $decision, $remark, $previousStatus, $newStatus): ScreeningReview {
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
        });

        // Tell the applicant about the outcome — only once the decision is committed,
        // and only when the status actually changed, to avoid duplicate messages.
        $notificationType = $this->notificationTypeFor($decision);
        $applicant = $application->applicant;

        if ($notificationType !== null && $applicant !== null && $previousStatus !== $newStatus->value) {
            $this->notifications->handle(
                applicant: $applicant,
                type: $notificationType,
                placeholders: ['remark' => $remark ?? ''],
                application: $application,
                channel: $applicant->email ? 'email' : 'in_system',
            );
        }

        return $review;
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

    private function notificationTypeFor(ScreeningDecision $decision): ?NotificationType
    {
        return match ($decision) {
            ScreeningDecision::Passed => NotificationType::ScreeningPassed,
            ScreeningDecision::Failed => NotificationType::ScreeningFailed,
            ScreeningDecision::CorrectionRequired => NotificationType::CorrectionRequired,
            ScreeningDecision::Pending => null,
        };
    }
}
