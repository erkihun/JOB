<?php

declare(strict_types=1);

namespace App\Actions\Exams;

use App\Actions\Audit\LogAuditAction;
use App\Actions\Notifications\SendApplicantNotificationAction;
use App\Enums\ApplicationStatus;
use App\Enums\ExamInterviewType;
use App\Enums\NotificationType;
use App\Exceptions\RecruitmentRuleException;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\ExamInterviewApplicant;
use App\Models\ExamInterviewSchedule;
use App\Services\Recruitment\RecruitmentStateMachine;
use App\Services\Recruitment\RecruitmentTimelineService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AssignApplicantsToScheduleAction
{
    public function __construct(
        private readonly SendApplicantNotificationAction $notifications,
        private readonly LogAuditAction $auditLogger,
        private readonly RecruitmentTimelineService $timeline,
        private readonly RecruitmentStateMachine $stateMachine,
    ) {}

    /**
     * Invite applications to a session — all or nothing. Every application is
     * checked for stage eligibility, duplicates and overlapping sessions while
     * its applicant row is locked, so concurrent requests cannot double-book.
     *
     * @param  Collection<int, Application>|array<int, Application>  $applications
     * @return Collection<int, ExamInterviewApplicant>
     *
     * @throws RecruitmentRuleException when any application may not be assigned
     */
    public function handle(
        ExamInterviewSchedule $schedule,
        Collection|array $applications,
    ): Collection {
        $schedule->loadMissing('vacancy');

        try {
            $assigned = DB::transaction(function () use ($schedule, $applications): Collection {
                $assigned = collect();

                foreach ($applications as $application) {
                    Applicant::whereKey($application->applicant_id)->lockForUpdate()->first();
                    $application = Application::with('vacancy')->whereKey($application->id)->lockForUpdate()->firstOrFail();

                    $this->timeline->assertCanAssign($schedule, $application);

                    // A practical test has no stage status of its own: the application keeps its status.
                    $newStatus = match ($schedule->type) {
                        ExamInterviewType::Exam => ApplicationStatus::ShortlistedExam,
                        ExamInterviewType::Interview => ApplicationStatus::ShortlistedInterview,
                        ExamInterviewType::Practical => $application->status,
                    };
                    $this->stateMachine->assertApplicationTransition($application, $newStatus, 'application_ids');

                    $record = ExamInterviewApplicant::create([
                        'schedule_id' => $schedule->id,
                        'application_id' => $application->id,
                        'status' => 'invited',
                    ]);

                    $previousStatus = $application->status;
                    if ($previousStatus !== $newStatus) {
                        $application->update(['status' => $newStatus]);
                    }

                    $this->auditLogger->handle(
                        action: $schedule->type->value.'_applicant_assigned',
                        module: 'exam_interview',
                        recordId: $record->id,
                        oldValues: ['application_status' => $previousStatus?->value],
                        newValues: [
                            'schedule_id' => $schedule->id,
                            'application_id' => $application->id,
                            'status' => $newStatus->value,
                        ],
                    );

                    $assigned->push($record->setRelation('application', $application));
                }

                return $assigned;
            });
        } catch (UniqueConstraintViolationException) {
            // Lost a race with a concurrent assignment of the same application.
            throw RecruitmentRuleException::because('recruitment.errors.duplicate_assignment', 'application_ids');
        }

        // Invitations go out only once every assignment is committed.
        foreach ($assigned as $record) {
            $application = $record->application;

            $this->notifications->handle(
                applicant: $application->applicant,
                type: $schedule->type->isExamLike()
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
        }

        return $assigned;
    }
}
