<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Announcements\ExtendRecruitmentDeadlineAction;
use App\Actions\Audit\LogAuditAction;
use App\Actions\Notifications\SendApplicantNotificationAction;
use App\Enums\ApplicationStatus;
use App\Enums\NotificationType;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\RecruitmentDeadlineExtension;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Tells every applicant of an announcement that its deadline moved. Runs on the
 * queue so a large recruitment does not slow down the admin request; each
 * message is itself queued and audited by SendApplicantNotificationAction.
 */
class NotifyDeadlineExtensionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly int $extensionId) {}

    public function handle(
        SendApplicantNotificationAction $notifications,
        ExtendRecruitmentDeadlineAction $extendAction,
        LogAuditAction $auditLogger,
    ): void {
        $extension = RecruitmentDeadlineExtension::with('announcement')->find($this->extensionId);
        $announcement = $extension?->announcement;

        if ($announcement === null) {
            return;
        }

        $sent = 0;
        $applicantIds = $extendAction->applicantIdsFor($announcement);

        Applicant::query()->whereIn('id', $applicantIds)->chunkById(200, function ($applicants) use ($notifications, $extension, $announcement, &$sent): void {
            foreach ($applicants as $applicant) {
                // Link the message to one of the applicant's applications under this announcement.
                $application = Application::query()
                    ->where('applicant_id', $applicant->id)
                    ->whereIn('vacancy_id', $announcement->vacancies()->select('id'))
                    ->where('status', '!=', ApplicationStatus::Withdrawn->value)
                    ->oldest('submitted_at')
                    ->first();

                $notifications->handle(
                    applicant: $applicant,
                    type: NotificationType::DeadlineExtended,
                    placeholders: [
                        'announcement' => trim($announcement->subject.($announcement->code ? ' ('.$announcement->code.')' : '')),
                        'old_date' => $extension->old_closing_date->toDateString(),
                        'new_date' => $extension->new_closing_date->toDateString(),
                        'reason' => $extension->reason,
                    ],
                    application: $application,
                    channel: $applicant->email ? 'email' : 'in_system',
                );
                $sent++;
            }
        });

        $auditLogger->handle(
            action: 'deadline_extension_notifications_dispatched',
            module: 'notifications',
            recordId: (string) $announcement->id,
            newValues: ['extension_id' => $extension->id, 'notified' => $sent],
        );
    }
}
