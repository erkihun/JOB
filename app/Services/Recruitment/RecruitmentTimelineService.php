<?php

declare(strict_types=1);

namespace App\Services\Recruitment;

use App\Enums\ApplicationStatus;
use App\Enums\ExamInterviewType;
use App\Enums\RecruitmentStage;
use App\Enums\RecruitmentStatus;
use App\Enums\ScreeningDecision;
use App\Enums\VacancyStatus;
use App\Exceptions\RecruitmentRuleException;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\ExamInterviewApplicant;
use App\Models\ExamInterviewSchedule;
use App\Models\RecruitmentAnnouncement;
use App\Models\Vacancy;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Single source of truth for every date- and stage-dependent recruitment rule.
 *
 * Timezone: all comparisons use the application timezone (config('app.timezone')).
 * opening_date / closing_date are DATE values, so an announcement opens at
 * 00:00:00 on its opening day and closes at 23:59:59.999999 on its closing day —
 * never at the midnight that *starts* the closing day.
 *
 * Every rule has a `…Violation()` method returning a translation key (or null
 * when allowed); `can…()` and `assert…()` wrap it so controllers, policies,
 * Form Requests, Actions, views and commands all share the same logic.
 */
final class RecruitmentTimelineService
{
    /** Application statuses that still allow the applicant to change their data. */
    public const EDITABLE_APPLICATION_STATUSES = [
        ApplicationStatus::Submitted,
        ApplicationStatus::UnderReview,
        ApplicationStatus::CorrectionRequired,
        // Legacy rolling screening: an edit sends these back to review.
        ApplicationStatus::PassedScreening,
        ApplicationStatus::FailedScreening,
    ];

    /** Still waiting for a screening decision. */
    public const AWAITING_SCREENING_STATUSES = [
        ApplicationStatus::Submitted,
        ApplicationStatus::UnderReview,
        ApplicationStatus::CorrectionRequired,
    ];

    /** No further recruitment stage applies to these applications. */
    public const CONCLUDED_APPLICATION_STATUSES = [
        ApplicationStatus::FailedScreening,
        ApplicationStatus::Selected,
        ApplicationStatus::Waitlisted,
        ApplicationStatus::NotSelected,
        ApplicationStatus::Withdrawn,
    ];

    // ── Clock & dates ─────────────────────────────────────────────────────────

    public function now(): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezone());
    }

    public function timezone(): string
    {
        return (string) config('app.timezone', 'UTC');
    }

    /** First instant applications are accepted (start of the opening day). */
    public function openingAt(RecruitmentAnnouncement $announcement): ?CarbonImmutable
    {
        return $announcement->opening_date === null ? null
            : CarbonImmutable::parse($announcement->opening_date->toDateString(), $this->timezone())->startOfDay();
    }

    /** Last instant applications are accepted (end of the closing day). */
    public function closingAt(RecruitmentAnnouncement $announcement): ?CarbonImmutable
    {
        return $announcement->closing_date === null ? null
            : CarbonImmutable::parse($announcement->closing_date->toDateString(), $this->timezone())->endOfDay();
    }

    // ── Announcement state ───────────────────────────────────────────────────

    /** Released to the public (publish time reached, not archived). */
    public function isReleased(RecruitmentAnnouncement $announcement): bool
    {
        return ! $announcement->trashed()
            && $announcement->published_at !== null
            && $announcement->published_at->lte($this->now());
    }

    /** Published, but publication or the opening day is still ahead. */
    public function isUpcoming(RecruitmentAnnouncement $announcement): bool
    {
        if ($announcement->lifecycleStatus() !== RecruitmentStatus::Published || $announcement->trashed()) {
            return false;
        }

        $opening = $this->openingAt($announcement);

        return ! $this->isReleased($announcement) || $opening === null || $this->now()->lt($opening);
    }

    /** Accepting applications right now. */
    public function isOpen(RecruitmentAnnouncement $announcement): bool
    {
        $opening = $this->openingAt($announcement);
        $closing = $this->closingAt($announcement);

        return $announcement->lifecycleStatus() === RecruitmentStatus::Published
            && $this->isReleased($announcement)
            && $opening !== null && $closing !== null
            && $this->now()->betweenIncluded($opening, $closing);
    }

    /** The closing day has fully ended. */
    public function hasApplicationPeriodEnded(RecruitmentAnnouncement $announcement): bool
    {
        $closing = $this->closingAt($announcement);

        return $closing !== null && $this->now()->gt($closing);
    }

    /** Released and no longer accepting applications because the period ended. */
    public function isClosed(RecruitmentAnnouncement $announcement): bool
    {
        $status = $announcement->lifecycleStatus();

        return $status !== RecruitmentStatus::Draft && $status !== RecruitmentStatus::Cancelled
            && $this->hasApplicationPeriodEnded($announcement);
    }

    public function stage(RecruitmentAnnouncement $announcement): RecruitmentStage
    {
        return match ($announcement->lifecycleStatus()) {
            RecruitmentStatus::Draft => RecruitmentStage::Draft,
            RecruitmentStatus::Cancelled => RecruitmentStage::Cancelled,
            RecruitmentStatus::Finalized => RecruitmentStage::Finalized,
            RecruitmentStatus::Interview => RecruitmentStage::Interview,
            RecruitmentStatus::Exam => RecruitmentStage::Exam,
            RecruitmentStatus::Screening => RecruitmentStage::Screening,
            RecruitmentStatus::Closed => RecruitmentStage::Closed,
            RecruitmentStatus::Published => match (true) {
                $this->isOpen($announcement) => RecruitmentStage::Open,
                $this->hasApplicationPeriodEnded($announcement) => RecruitmentStage::Closed,
                default => RecruitmentStage::Upcoming,
            },
        };
    }

    /** Whole days left while open (0 = closes today), null otherwise. */
    public function remainingDays(RecruitmentAnnouncement $announcement): ?int
    {
        if (! $this->isOpen($announcement)) {
            return null;
        }

        return (int) $this->now()->startOfDay()->diffInDays($this->closingAt($announcement)->startOfDay());
    }

    // ── Registration (global) ────────────────────────────────────────────────

    /** Applicant accounts may be created while at least one announcement is open. */
    public function canRegisterApplicant(): bool
    {
        return RecruitmentAnnouncement::query()->acceptingApplications()->exists();
    }

    public function assertCanRegisterApplicant(): void
    {
        if (! $this->canRegisterApplicant()) {
            throw RecruitmentRuleException::because('recruitment.errors.registration_closed', 'registration');
        }
    }

    /** Soonest announcement that is published but not yet open (for "opens on" notices). */
    public function nextUpcomingAnnouncement(): ?RecruitmentAnnouncement
    {
        $today = $this->now()->toDateString();

        return RecruitmentAnnouncement::query()
            ->where('status', RecruitmentStatus::Published->value)
            ->whereNotNull('published_at')
            ->whereNotNull('opening_date')
            ->whereDate('closing_date', '>=', $today)
            ->where(fn ($q) => $q->whereDate('opening_date', '>', $today)->orWhere('published_at', '>', now()))
            ->orderBy('opening_date')
            ->first();
    }

    // ── Applications ─────────────────────────────────────────────────────────

    public function applicationSubmissionViolation(Vacancy $vacancy): ?string
    {
        $announcement = $vacancy->announcement;

        if ($announcement === null || $announcement->trashed()) {
            return 'recruitment.errors.not_accepting';
        }

        if ($this->hasApplicationPeriodEnded($announcement) || $announcement->lifecycleStatus()->rank() > RecruitmentStatus::Published->rank()) {
            return 'recruitment.errors.application_closed';
        }

        if ($announcement->lifecycleStatus() !== RecruitmentStatus::Published || $vacancy->status !== VacancyStatus::Open) {
            return 'recruitment.errors.not_accepting';
        }

        if ($this->isUpcoming($announcement)) {
            return 'recruitment.errors.application_not_open';
        }

        return $this->isOpen($announcement) ? null : 'recruitment.errors.not_accepting';
    }

    public function canSubmitApplication(Vacancy $vacancy): bool
    {
        return $this->applicationSubmissionViolation($vacancy) === null;
    }

    public function assertCanSubmitApplication(Vacancy $vacancy, string $field = 'vacancy'): void
    {
        if ($key = $this->applicationSubmissionViolation($vacancy)) {
            throw RecruitmentRuleException::because($key, $field);
        }
    }

    public function applicationEditViolation(Application $application): ?string
    {
        if ($application->locked_at !== null) {
            return 'recruitment.errors.application_locked';
        }

        if (! in_array($application->status, self::EDITABLE_APPLICATION_STATUSES, true)) {
            return 'recruitment.errors.stage_not_allowed';
        }

        $vacancy = $application->vacancy;
        $announcement = $vacancy?->announcement;

        if ($announcement === null || $announcement->lifecycleStatus()->isTerminal()) {
            return 'recruitment.errors.application_read_only';
        }

        if ($vacancy->status === VacancyStatus::Open && $this->isOpen($announcement)) {
            return null;
        }

        // Authorised, time-boxed reopening (ReopenApplicationAction) — relocks itself on expiry.
        if ($application->reopened_until !== null && $this->now()->lte($application->reopened_until)) {
            return null;
        }

        return 'recruitment.errors.application_read_only';
    }

    public function canEditApplication(Application $application): bool
    {
        return $this->applicationEditViolation($application) === null;
    }

    public function assertCanEditApplication(Application $application, string $field = 'application'): void
    {
        if ($key = $this->applicationEditViolation($application)) {
            throw RecruitmentRuleException::because($key, $field);
        }
    }

    /** Documents follow the application: replaceable only while it is editable. */
    public function canReplaceApplicationDocument(ApplicationDocument $document): bool
    {
        return $document->application !== null && $this->canEditApplication($document->application);
    }

    // ── Screening ────────────────────────────────────────────────────────────

    /** The announcement may move into the Screening stage. */
    public function screeningStartViolation(RecruitmentAnnouncement $announcement): ?string
    {
        $status = $announcement->lifecycleStatus();

        if (! in_array($status, [RecruitmentStatus::Published, RecruitmentStatus::Closed], true)) {
            return 'recruitment.errors.stage_not_allowed';
        }

        return $this->hasApplicationPeriodEnded($announcement) ? null : 'recruitment.errors.screening_before_close';
    }

    public function canStartScreening(RecruitmentAnnouncement $announcement): bool
    {
        return $this->screeningStartViolation($announcement) === null;
    }

    /**
     * Pass / fail decisions need the application period to be over. Asking the
     * applicant for a correction (or parking it as pending) is a preliminary
     * review step that is intentionally allowed while the window is still open,
     * so the applicant can fix it before the deadline.
     */
    public function screeningDecisionViolation(Application $application, ScreeningDecision $decision): ?string
    {
        $announcement = $application->vacancy?->announcement;
        $status = $announcement?->lifecycleStatus();

        if ($announcement === null || in_array($status, [RecruitmentStatus::Draft, RecruitmentStatus::Cancelled, RecruitmentStatus::Finalized], true)) {
            return 'recruitment.errors.stage_not_allowed';
        }

        if (in_array($decision, [ScreeningDecision::Passed, ScreeningDecision::Failed], true)
            && ! $this->hasApplicationPeriodEnded($announcement)) {
            return 'recruitment.errors.screening_before_close';
        }

        return null;
    }

    public function canRecordScreeningDecision(Application $application, ScreeningDecision $decision): bool
    {
        return $this->screeningDecisionViolation($application, $decision) === null;
    }

    public function assertCanRecordScreeningDecision(Application $application, ScreeningDecision $decision): void
    {
        if ($key = $this->screeningDecisionViolation($application, $decision)) {
            throw RecruitmentRuleException::because($key, 'decision');
        }
    }

    /** No application of the vacancy is still waiting for a screening decision. */
    public function isScreeningCompleteFor(Vacancy $vacancy): bool
    {
        return ! $vacancy->applications()
            ->whereIn('status', array_map(fn (ApplicationStatus $s) => $s->value, self::AWAITING_SCREENING_STATUSES))
            ->exists();
    }

    /** Screening, a schedule or any later decision exists for the announcement. */
    public function hasAssessmentStarted(RecruitmentAnnouncement $announcement): bool
    {
        if ($announcement->lifecycleStatus()->hasStartedAssessment()) {
            return true;
        }

        $vacancyIds = $announcement->vacancies()->withTrashed()->pluck('id');

        $decided = Application::query()->whereIn('vacancy_id', $vacancyIds)
            ->whereNotIn('status', array_map(fn (ApplicationStatus $s) => $s->value, [
                ...self::AWAITING_SCREENING_STATUSES, ApplicationStatus::Withdrawn,
            ]))
            ->exists();

        return $decided || ExamInterviewSchedule::query()->whereIn('vacancy_id', $vacancyIds)->exists();
    }

    // ── Deadline extension ───────────────────────────────────────────────────

    public function deadlineExtensionViolation(RecruitmentAnnouncement $announcement): ?string
    {
        if ($announcement->trashed() || $announcement->closing_date === null || $announcement->published_at === null
            || ! in_array($announcement->lifecycleStatus(), [RecruitmentStatus::Published, RecruitmentStatus::Closed], true)) {
            return 'recruitment.errors.extension_not_allowed';
        }

        return $this->hasAssessmentStarted($announcement) ? 'recruitment.errors.extension_after_assessment' : null;
    }

    public function canExtendDeadline(RecruitmentAnnouncement $announcement): bool
    {
        return $this->deadlineExtensionViolation($announcement) === null;
    }

    // ── Exams & interviews ───────────────────────────────────────────────────

    /**
     * Rules for placing (or moving) an exam / practical / interview session.
     *
     * @param  ExamInterviewSchedule|null  $existing  the schedule being edited, if any
     */
    public function scheduleViolation(
        Vacancy $vacancy,
        ExamInterviewType $type,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        ?ExamInterviewSchedule $existing = null,
    ): ?string {
        $announcement = $vacancy->announcement;
        $status = $announcement?->lifecycleStatus();
        $isInterview = $type === ExamInterviewType::Interview;

        if ($announcement === null || $announcement->trashed()
            || in_array($status, [RecruitmentStatus::Draft, RecruitmentStatus::Cancelled, RecruitmentStatus::Finalized], true)) {
            return 'recruitment.errors.stage_not_allowed';
        }

        $closing = $this->closingAt($announcement);
        if (! $this->hasApplicationPeriodEnded($announcement) || $closing === null || $startsAt->lte($closing)) {
            return $isInterview ? 'recruitment.errors.interview_before_previous_stage' : 'recruitment.errors.exam_before_close';
        }

        $startChanged = $existing === null || $existing->starts_at === null || ! $existing->starts_at->equalTo($startsAt);
        if ($startChanged && $startsAt->lt($this->now())) {
            return 'recruitment.errors.schedule_in_past';
        }

        if ($endsAt->lte($startsAt)) {
            return 'recruitment.errors.schedule_end_before_start';
        }

        if (! $this->isScreeningCompleteFor($vacancy)) {
            return $isInterview ? 'recruitment.errors.interview_before_previous_stage' : 'recruitment.errors.screening_incomplete';
        }

        $otherSchedules = $vacancy->schedules()->when($existing?->exists, fn ($q) => $q->whereKeyNot($existing->id));

        if ($type === ExamInterviewType::Exam && (clone $otherSchedules)->where('type', ExamInterviewType::Interview->value)->exists()) {
            // The interview stage already started for this position.
            return 'recruitment.errors.stage_not_allowed';
        }

        if ($isInterview && $announcement->exam_required) {
            $examEnd = (clone $otherSchedules)->where('type', ExamInterviewType::Exam->value)->max('ends_at');

            if ($examEnd === null || $startsAt->lte(CarbonImmutable::parse($examEnd, $this->timezone()))) {
                return 'recruitment.errors.interview_before_previous_stage';
            }
        }

        return null;
    }

    public function canScheduleExam(Vacancy $vacancy, CarbonInterface $startsAt, CarbonInterface $endsAt, ?ExamInterviewSchedule $existing = null): bool
    {
        return $this->scheduleViolation($vacancy, ExamInterviewType::Exam, $startsAt, $endsAt, $existing) === null;
    }

    public function canScheduleInterview(Vacancy $vacancy, CarbonInterface $startsAt, CarbonInterface $endsAt, ?ExamInterviewSchedule $existing = null): bool
    {
        return $this->scheduleViolation($vacancy, ExamInterviewType::Interview, $startsAt, $endsAt, $existing) === null;
    }

    public function assertCanSchedule(Vacancy $vacancy, ExamInterviewType $type, CarbonInterface $startsAt, CarbonInterface $endsAt, ?ExamInterviewSchedule $existing = null, string $field = 'date'): void
    {
        if ($key = $this->scheduleViolation($vacancy, $type, $startsAt, $endsAt, $existing)) {
            throw RecruitmentRuleException::because($key, $field);
        }
    }

    /**
     * Application statuses that may be invited to a session of this type.
     *
     * @return list<ApplicationStatus>
     */
    public function eligibleStatusesFor(ExamInterviewType $type, ?RecruitmentAnnouncement $announcement): array
    {
        return match ($type) {
            ExamInterviewType::Exam => [ApplicationStatus::PassedScreening, ApplicationStatus::ShortlistedExam],
            ExamInterviewType::Interview => ($announcement?->exam_required ?? true)
                ? [ApplicationStatus::ExamCompleted, ApplicationStatus::ShortlistedInterview]
                : [ApplicationStatus::PassedScreening, ApplicationStatus::ExamCompleted, ApplicationStatus::ShortlistedInterview],
            // Anyone past screening without a final decision can sit a practical test.
            ExamInterviewType::Practical => [
                ApplicationStatus::PassedScreening, ApplicationStatus::ShortlistedExam, ApplicationStatus::ExamCompleted,
                ApplicationStatus::ShortlistedInterview, ApplicationStatus::InterviewCompleted,
            ],
        };
    }

    public function assignmentViolation(ExamInterviewSchedule $schedule, Application $application): ?string
    {
        $vacancy = $schedule->vacancy;
        $announcement = $vacancy?->announcement;

        if ($announcement === null
            || in_array($announcement->lifecycleStatus(), [RecruitmentStatus::Draft, RecruitmentStatus::Cancelled, RecruitmentStatus::Finalized], true)
            || ! $this->hasApplicationPeriodEnded($announcement)) {
            return $schedule->type === ExamInterviewType::Interview
                ? 'recruitment.errors.interview_before_previous_stage'
                : 'recruitment.errors.exam_before_close';
        }

        if ($application->vacancy_id !== $schedule->vacancy_id
            || ! in_array($application->status, $this->eligibleStatusesFor($schedule->type, $announcement), true)) {
            return 'recruitment.errors.applicant_not_eligible';
        }

        if ($schedule->starts_at !== null && $schedule->starts_at->lt($this->now())) {
            return 'recruitment.errors.schedule_in_past';
        }

        $assignments = ExamInterviewApplicant::query()
            ->whereHas('application', fn ($q) => $q->where('applicant_id', $application->applicant_id));

        if ((clone $assignments)->where('schedule_id', $schedule->id)->where('application_id', $application->id)->exists()
            || (clone $assignments)->where('application_id', $application->id)->where('status', 'invited')
                ->whereHas('schedule', fn ($q) => $q->where('type', $schedule->type->value))->exists()) {
            return 'recruitment.errors.duplicate_assignment';
        }

        if ($schedule->starts_at !== null && $schedule->ends_at !== null
            && (clone $assignments)->where('schedule_id', '!=', $schedule->id)
                ->whereHas('schedule', fn ($q) => $q
                    ->where('starts_at', '<', $schedule->ends_at)
                    ->where('ends_at', '>', $schedule->starts_at))
                ->exists()) {
            return 'recruitment.errors.schedule_conflict';
        }

        return null;
    }

    public function canAssignApplicantToExam(ExamInterviewSchedule $schedule, Application $application): bool
    {
        return $schedule->type->isExamLike() && $this->assignmentViolation($schedule, $application) === null;
    }

    public function canAssignApplicantToInterview(ExamInterviewSchedule $schedule, Application $application): bool
    {
        return $schedule->type === ExamInterviewType::Interview && $this->assignmentViolation($schedule, $application) === null;
    }

    public function assertCanAssign(ExamInterviewSchedule $schedule, Application $application): void
    {
        if ($key = $this->assignmentViolation($schedule, $application)) {
            throw RecruitmentRuleException::because($key, 'application_ids');
        }
    }

    /**
     * Moving an existing session must not double-book anyone already invited.
     */
    public function rescheduleConflictViolation(ExamInterviewSchedule $schedule, CarbonInterface $startsAt, CarbonInterface $endsAt): ?string
    {
        if (! $schedule->exists) {
            return null;
        }

        $applicantIds = Application::query()
            ->whereIn('id', $schedule->assignedApplicants()->select('application_id'))
            ->pluck('applicant_id');

        if ($applicantIds->isEmpty()) {
            return null;
        }

        $conflict = ExamInterviewApplicant::query()
            ->where('schedule_id', '!=', $schedule->id)
            ->whereHas('application', fn ($q) => $q->whereIn('applicant_id', $applicantIds))
            ->whereHas('schedule', fn ($q) => $q->where('starts_at', '<', $endsAt)->where('ends_at', '>', $startsAt))
            ->exists();

        return $conflict ? 'recruitment.errors.schedule_conflict' : null;
    }

    // ── Final results & finalization ─────────────────────────────────────────

    /** A final decision (selected / waitlisted / not selected) may be recorded. */
    public function finalResultViolation(Application $application): ?string
    {
        $announcement = $application->vacancy?->announcement;

        if ($announcement === null
            || in_array($announcement->lifecycleStatus(), [RecruitmentStatus::Draft, RecruitmentStatus::Cancelled, RecruitmentStatus::Finalized], true)
            || ! $this->hasApplicationPeriodEnded($announcement)) {
            return 'recruitment.errors.stage_not_allowed';
        }

        return null;
    }

    public function finalizeViolation(RecruitmentAnnouncement $announcement): ?string
    {
        if (! in_array($announcement->lifecycleStatus(), [RecruitmentStatus::Screening, RecruitmentStatus::Exam, RecruitmentStatus::Interview], true)) {
            return 'recruitment.errors.stage_not_allowed';
        }

        $pending = Application::query()
            ->whereIn('vacancy_id', $announcement->vacancies()->withTrashed()->select('id'))
            ->whereNotIn('status', array_map(fn (ApplicationStatus $s) => $s->value, self::CONCLUDED_APPLICATION_STATUSES))
            ->exists();

        return $pending ? 'recruitment.errors.finalize_incomplete' : null;
    }

    public function canFinalizeRecruitment(RecruitmentAnnouncement $announcement): bool
    {
        return $this->finalizeViolation($announcement) === null;
    }
}
