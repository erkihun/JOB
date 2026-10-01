<?php

declare(strict_types=1);

namespace App\Actions\Exams;

use App\Actions\Audit\LogAuditAction;
use App\Enums\ApplicationStatus;
use App\Enums\ExamInterviewType;
use App\Enums\RecruitmentStatus;
use App\Exceptions\RecruitmentRuleException;
use App\Models\ExamInterviewApplicant;
use App\Services\Recruitment\RecruitmentStateMachine;
use Illuminate\Support\Facades\DB;

class RecordExamInterviewResultAction
{
    /**
     * Application statuses that a result for each stage is allowed to change.
     * Anything outside these (final decisions, withdrawn, a later stage) is never
     * overwritten, so a late or corrected result can't move an application backwards.
     */
    private const STAGE_STATUSES = [
        'exam' => [
            ApplicationStatus::PassedScreening,
            ApplicationStatus::ShortlistedExam,
            ApplicationStatus::ExamCompleted,
            ApplicationStatus::ShortlistedInterview,
            ApplicationStatus::NotSelected,
        ],
        'interview' => [
            ApplicationStatus::ShortlistedInterview,
            ApplicationStatus::InterviewCompleted,
            ApplicationStatus::NotSelected,
        ],
        'practical' => [
            ApplicationStatus::PassedScreening,
            ApplicationStatus::ShortlistedExam,
            ApplicationStatus::ExamCompleted,
            ApplicationStatus::ShortlistedInterview,
            ApplicationStatus::InterviewCompleted,
            ApplicationStatus::NotSelected,
        ],
    ];

    public function __construct(
        private readonly LogAuditAction $auditLogger,
        private readonly RecruitmentStateMachine $stateMachine,
    ) {}

    public function handle(
        ExamInterviewApplicant $applicantRecord,
        string $status,
        ?float $score = null,
        ?string $remark = null,
    ): ExamInterviewApplicant {
        return DB::transaction(function () use ($applicantRecord, $status, $score, $remark): ExamInterviewApplicant {
            $previousRecordStatus = $applicantRecord->status;

            $announcementStatus = $applicantRecord->schedule?->vacancy?->announcement?->lifecycleStatus();
            if (in_array($announcementStatus, [RecruitmentStatus::Finalized, RecruitmentStatus::Cancelled], true)) {
                throw RecruitmentRuleException::because('recruitment.errors.stage_not_allowed', 'status');
            }

            $applicantRecord->update([
                'status' => $status,
                'score' => $score,
                'remark' => $remark,
            ]);

            $application = $applicantRecord->application;
            $scheduleType = $applicantRecord->schedule->type;
            $previousAppStatus = $application->status;

            // Passing or attending moves the applicant to the next stage; failing ends
            // their candidacy. The final "selected / waitlisted" decision is made only
            // on the Final Results page, so an interview never sets Selected directly.
            $newAppStatus = match (true) {
                $scheduleType === ExamInterviewType::Exam && $status === 'passed' => ApplicationStatus::ShortlistedInterview,
                $scheduleType === ExamInterviewType::Exam && $status === 'attended' => ApplicationStatus::ExamCompleted,
                $scheduleType === ExamInterviewType::Exam && $status === 'failed' => ApplicationStatus::NotSelected,
                $scheduleType === ExamInterviewType::Interview && in_array($status, ['passed', 'attended'], true) => ApplicationStatus::InterviewCompleted,
                $scheduleType === ExamInterviewType::Interview && $status === 'failed' => ApplicationStatus::NotSelected,
                // Practical test: failing ends the candidacy; passing keeps the current stage.
                $scheduleType === ExamInterviewType::Practical && $status === 'failed' => ApplicationStatus::NotSelected,
                default => $previousAppStatus,
            };

            $stageKey = $scheduleType->value;
            $canChangeStatus = in_array($previousAppStatus, self::STAGE_STATUSES[$stageKey], true)
                && $application->finalResult()->doesntExist()
                && $this->stateMachine->canTransitionApplication($application, $newAppStatus);

            if ($canChangeStatus && $newAppStatus !== $previousAppStatus) {
                $application->update(['status' => $newAppStatus]);
            }

            $this->auditLogger->handle(
                action: $stageKey.'_result_recorded',
                module: 'exam_interview',
                recordId: $applicantRecord->id,
                oldValues: [
                    'result_status' => $previousRecordStatus,
                    'application_status' => $previousAppStatus?->value,
                ],
                newValues: [
                    'result_status' => $status,
                    'score' => $score,
                    'application_id' => $application->id,
                    'application_status' => $application->status?->value,
                ],
            );

            return $applicantRecord->refresh();
        });
    }
}
