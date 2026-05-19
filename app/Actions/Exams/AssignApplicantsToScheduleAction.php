<?php

declare(strict_types=1);

namespace App\Actions\Exams;

use App\Actions\Audit\LogAuditAction;
use App\Actions\Notifications\SendApplicantNotificationAction;
use App\Enums\ApplicationStatus;
use App\Enums\ExamInterviewType;
use App\Enums\NotificationType;
use App\Models\Application;
use App\Models\ExamInterviewApplicant;
use App\Models\ExamInterviewSchedule;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class AssignApplicantsToScheduleAction
{
    /** @var array<string, ApplicationStatus> */
    private const ELIGIBLE_STATUSES = [
        ApplicationStatus::PassedScreening,
        ApplicationStatus::ShortlistedExam,
        ApplicationStatus::ShortlistedInterview,
        ApplicationStatus::ExamCompleted,
        ApplicationStatus::InterviewCompleted,
    ];

    public function __construct(
        private readonly SendApplicantNotificationAction $notifications,
        private readonly LogAuditAction $auditLogger,
    ) {}

    /**
     * @param  Collection<int, Application>|array<int, Application>  $applications
     * @return Collection<int, ExamInterviewApplicant>
     */
    public function handle(
        ExamInterviewSchedule $schedule,
        Collection|array $applications,
    ): Collection {
        $assigned = collect();

        foreach ($applications as $application) {
            if (! $this->isEligible($application)) {
                throw new InvalidArgumentException(
                    "Application [{$application->id}] is not eligible for assignment. "
                    ."Current status: [{$application->status?->value}]."
                );
            }

            $record = ExamInterviewApplicant::firstOrCreate(
                [
                    'schedule_id' => $schedule->id,
                    'application_id' => $application->id,
                ],
                ['status' => 'invited'],
            );

            $newStatus = $schedule->type === ExamInterviewType::Exam
                ? ApplicationStatus::ShortlistedExam
                : ApplicationStatus::ShortlistedInterview;

            if ($application->status !== $newStatus) {
                $application->update(['status' => $newStatus]);
            }

            $this->notifications->handle(
                applicant: $application->applicant,
                type: $schedule->type === ExamInterviewType::Exam
                    ? NotificationType::ExamInvitation
                    : NotificationType::InterviewInvitation,
                placeholders: [
                    'date' => $schedule->date?->format('Y-m-d') ?? '',
                    'time' => $schedule->start_time,
                    'venue' => $schedule->venue,
                    'instructions' => $schedule->instruction ?? '',
                ],
                application: $application,
                channel: $application->applicant?->email ? 'email' : 'in_system',
            );

            $this->auditLogger->handle(
                action: $schedule->type === ExamInterviewType::Exam ? 'exam_applicant_assigned' : 'interview_applicant_assigned',
                module: 'exam_interview',
                recordId: $record->id,
                newValues: [
                    'schedule_id' => $schedule->id,
                    'application_id' => $application->id,
                    'status' => $newStatus->value,
                ],
            );

            $assigned->push($record);
        }

        return $assigned;
    }

    private function isEligible(Application $application): bool
    {
        return in_array($application->status, self::ELIGIBLE_STATUSES, true);
    }
}
