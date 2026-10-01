<?php

declare(strict_types=1);

namespace App\Services\Recruitment;

use App\Actions\Audit\LogAuditAction;
use App\Enums\ApplicationStatus;
use App\Enums\RecruitmentStatus;
use App\Exceptions\RecruitmentRuleException;
use App\Models\Application;
use App\Models\RecruitmentAnnouncement;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The only place recruitment statuses are allowed to change.
 *
 * Announcement lifecycle:
 *   draft → published → closed → screening → exam → interview → finalized
 *   (+ cancelled from any non-terminal status; see ANNOUNCEMENT_TRANSITIONS for
 *   the guarded exceptions such as un-publishing and deadline-extension reopening).
 *
 * Application stages: see APPLICATION_TRANSITIONS. Status values coming from
 * the browser are never trusted — callers ask for a target and this class
 * decides whether the move is legal.
 */
final class RecruitmentStateMachine
{
    /** @var array<string, list<string>> */
    private const ANNOUNCEMENT_TRANSITIONS = [
        'draft' => ['published', 'cancelled'],
        'published' => ['draft', 'closed', 'cancelled'],
        'closed' => ['published', 'screening', 'cancelled'],
        'screening' => ['exam', 'interview', 'finalized', 'cancelled'],
        'exam' => ['interview', 'finalized', 'cancelled'],
        'interview' => ['finalized', 'cancelled'],
        'finalized' => [],
        'cancelled' => [],
    ];

    /** Audit action recorded for each target status. */
    private const ANNOUNCEMENT_AUDIT_ACTIONS = [
        'draft' => 'announcement_unpublished',
        'published' => 'announcement_published',
        'closed' => 'announcement_closed',
        'screening' => 'screening_started',
        'exam' => 'exam_stage_started',
        'interview' => 'interview_stage_started',
        'finalized' => 'recruitment_finalized',
        'cancelled' => 'announcement_cancelled',
    ];

    /** @var array<string, list<string>> */
    private const APPLICATION_TRANSITIONS = [
        'submitted' => ['under_review', 'correction_required', 'passed_screening', 'failed_screening', 'withdrawn'],
        'under_review' => ['correction_required', 'passed_screening', 'failed_screening', 'withdrawn'],
        'correction_required' => ['under_review', 'passed_screening', 'failed_screening', 'withdrawn'],
        // under_review: an applicant edit (open window / reopened) sends a decision back to review.
        'passed_screening' => ['under_review', 'failed_screening', 'shortlisted_exam', 'shortlisted_interview', 'not_selected', 'withdrawn'],
        'failed_screening' => ['under_review', 'passed_screening', 'withdrawn'],
        'shortlisted_exam' => ['exam_completed', 'shortlisted_interview', 'not_selected', 'withdrawn'],
        'exam_completed' => ['shortlisted_interview', 'not_selected', 'selected', 'waitlisted', 'withdrawn'],
        'shortlisted_interview' => ['exam_completed', 'interview_completed', 'not_selected', 'selected', 'waitlisted', 'withdrawn'],
        'interview_completed' => ['not_selected', 'selected', 'waitlisted', 'withdrawn'],
        'selected' => ['waitlisted', 'not_selected'],
        'waitlisted' => ['selected', 'not_selected'],
        // Corrections of a recorded exam / interview result before a final decision exists.
        'not_selected' => ['selected', 'waitlisted', 'exam_completed', 'shortlisted_interview', 'interview_completed'],
        'withdrawn' => [],
    ];

    public function __construct(
        private readonly RecruitmentTimelineService $timeline,
        private readonly LogAuditAction $auditLogger,
    ) {}

    // ── Announcements ────────────────────────────────────────────────────────

    /**
     * @param  string  $context  'manual' | 'system' | 'deadline_extension'
     */
    public function announcementTransitionViolation(
        RecruitmentAnnouncement $announcement,
        RecruitmentStatus $to,
        string $context = 'manual',
    ): ?string {
        $from = $announcement->lifecycleStatus();

        if (! in_array($to->value, self::ANNOUNCEMENT_TRANSITIONS[$from->value], true)) {
            return 'recruitment.errors.invalid_transition';
        }

        return match (true) {
            $to === RecruitmentStatus::Published && $from === RecruitmentStatus::Draft => $announcement->opening_date === null || $announcement->closing_date === null
                || $announcement->closing_date->lte($announcement->opening_date) ? 'recruitment.errors.dates_required' : null,
            // Un-publishing is only possible before anyone has applied.
            $to === RecruitmentStatus::Draft => $announcement->applications()->exists() ? 'recruitment.errors.unpublish_has_applications' : null,
            $to === RecruitmentStatus::Closed => $this->timeline->hasApplicationPeriodEnded($announcement) ? null : 'recruitment.errors.close_before_deadline',
            // Closed → Published only happens through an approved deadline extension.
            $to === RecruitmentStatus::Published => $context === 'deadline_extension' ? null : 'recruitment.errors.invalid_transition',
            $to === RecruitmentStatus::Screening => $this->timeline->hasApplicationPeriodEnded($announcement) ? null : 'recruitment.errors.screening_before_close',
            $to === RecruitmentStatus::Interview && $from === RecruitmentStatus::Screening => $announcement->exam_required ? 'recruitment.errors.interview_before_previous_stage' : null,
            $to === RecruitmentStatus::Finalized => $this->timeline->finalizeViolation($announcement),
            default => null,
        };
    }

    public function canTransitionAnnouncement(RecruitmentAnnouncement $announcement, RecruitmentStatus $to, string $context = 'manual'): bool
    {
        return $this->announcementTransitionViolation($announcement, $to, $context) === null;
    }

    /**
     * Apply one validated transition (row-locked, audited).
     *
     * @param  array<string, mixed>  $extra  extra audit values
     */
    public function transitionAnnouncement(
        RecruitmentAnnouncement $announcement,
        RecruitmentStatus $to,
        ?string $reason = null,
        string $context = 'manual',
        array $extra = [],
    ): RecruitmentAnnouncement {
        return DB::transaction(function () use ($announcement, $to, $reason, $context, $extra): RecruitmentAnnouncement {
            $locked = RecruitmentAnnouncement::withTrashed()->whereKey($announcement->getKey())->lockForUpdate()->firstOrFail();
            $from = $locked->lifecycleStatus();

            if ($key = $this->announcementTransitionViolation($locked, $to, $context)) {
                throw RecruitmentRuleException::because($key, 'status');
            }

            $now = $this->timeline->now();
            $locked->status = $to->value;
            match ($to) {
                RecruitmentStatus::Published => $locked->published_at ??= $now,
                RecruitmentStatus::Draft => $locked->published_at = null,
                RecruitmentStatus::Closed => $locked->closed_at = $now,
                RecruitmentStatus::Finalized => $locked->finalized_at = $now,
                RecruitmentStatus::Cancelled => $locked->cancelled_at = $now,
                default => null,
            };
            $locked->save();

            $this->auditLogger->handle(
                action: $from === RecruitmentStatus::Closed && $to === RecruitmentStatus::Published
                    ? 'announcement_reopened' : self::ANNOUNCEMENT_AUDIT_ACTIONS[$to->value],
                module: 'recruitment',
                recordId: (string) $locked->getKey(),
                oldValues: ['status' => $from->value],
                newValues: array_filter(['status' => $to->value, 'reason' => $reason, 'context' => $context, ...$extra], fn ($v) => $v !== null),
            );

            Cache::forget('dashboard.stats');
            $announcement->setRawAttributes($locked->getAttributes(), true);

            return $announcement;
        });
    }

    /**
     * Move an announcement forward to $target through every intermediate status
     * (each one validated and audited). Does nothing when it is already there or
     * further along — so repeated calls never create duplicate transitions.
     */
    public function advanceTo(RecruitmentAnnouncement $announcement, RecruitmentStatus $target, string $context = 'system'): void
    {
        $path = [RecruitmentStatus::Closed, RecruitmentStatus::Screening, RecruitmentStatus::Exam, RecruitmentStatus::Interview];

        foreach ($path as $step) {
            $current = $announcement->lifecycleStatus();

            if ($current->isTerminal() || $current === RecruitmentStatus::Draft || $current->rank() >= $target->rank()) {
                return;
            }

            if ($current->rank() >= $step->rank()) {
                continue;
            }

            // Screening → Interview directly when the recruitment has no exam stage.
            if ($step === RecruitmentStatus::Exam && $target === RecruitmentStatus::Interview && ! $announcement->exam_required) {
                continue;
            }

            $this->transitionAnnouncement($announcement, $step, context: $context);

            if ($step->rank() >= $target->rank()) {
                return;
            }
        }
    }

    // ── Applications ─────────────────────────────────────────────────────────

    public function applicationTransitionViolation(Application $application, ApplicationStatus $to): ?string
    {
        $from = $application->status;

        if ($from === $to) {
            return null;
        }

        if ($from === null || ! in_array($to->value, self::APPLICATION_TRANSITIONS[$from->value] ?? [], true)) {
            return 'recruitment.errors.invalid_application_transition';
        }

        // Skipping the exam is only possible when the recruitment has no exam stage.
        if ($from === ApplicationStatus::PassedScreening && $to === ApplicationStatus::ShortlistedInterview
            && ($application->vacancy?->announcement?->exam_required ?? true)) {
            return 'recruitment.errors.applicant_not_eligible';
        }

        return null;
    }

    public function canTransitionApplication(Application $application, ApplicationStatus $to): bool
    {
        return $this->applicationTransitionViolation($application, $to) === null;
    }

    public function assertApplicationTransition(Application $application, ApplicationStatus $to, string $field = 'status'): void
    {
        if ($key = $this->applicationTransitionViolation($application, $to)) {
            throw RecruitmentRuleException::because($key, $field);
        }
    }
}
