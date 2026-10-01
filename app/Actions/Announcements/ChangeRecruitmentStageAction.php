<?php

declare(strict_types=1);

namespace App\Actions\Announcements;

use App\Enums\ExamInterviewType;
use App\Enums\RecruitmentStatus;
use App\Exceptions\RecruitmentRuleException;
use App\Models\ExamInterviewApplicant;
use App\Models\RecruitmentAnnouncement;
use App\Models\User;
use App\Services\Recruitment\RecruitmentStateMachine;
use App\Services\Recruitment\RecruitmentTimelineService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Manual lifecycle moves triggered from the announcement page ("Start screening",
 * "Finalize recruitment", …). Each target needs its own permission on top of the
 * state-machine rules, so hiding a button is never the only protection.
 */
final class ChangeRecruitmentStageAction
{
    /** @var array<string, string> */
    public const PERMISSIONS = [
        'published' => 'recruitment-announcements.publish',
        'draft' => 'recruitment-announcements.publish',
        'closed' => 'recruitment-announcements.close',
        'screening' => 'screening.start',
        'exam' => 'recruitment.advance-stage',
        'interview' => 'recruitment.advance-stage',
        'finalized' => 'recruitment.finalize',
        'cancelled' => 'recruitment-announcements.cancel',
    ];

    public function __construct(
        private readonly RecruitmentStateMachine $stateMachine,
        private readonly RecruitmentTimelineService $timeline,
    ) {}

    public function handle(RecruitmentAnnouncement $announcement, RecruitmentStatus $target, User $user, ?string $reason = null): RecruitmentAnnouncement
    {
        if (! $this->isAuthorized($user, $target)) {
            throw new AuthorizationException(__('recruitment.errors.stage_not_allowed'));
        }

        if ($target === RecruitmentStatus::Cancelled && blank($reason)) {
            throw RecruitmentRuleException::withMessages(['reason' => [__('validation.required', ['attribute' => __('recruitment.reason')])]]);
        }

        if ($key = $this->violation($announcement, $target)) {
            throw RecruitmentRuleException::because($key, 'status');
        }

        return DB::transaction(function () use ($announcement, $target, $reason): RecruitmentAnnouncement {
            // Published but past its closing day: record the closure on the way.
            if ($this->closesOnTheWay($announcement, $target)) {
                $this->stateMachine->transitionAnnouncement($announcement, RecruitmentStatus::Closed, context: 'system');
            }

            return $this->stateMachine->transitionAnnouncement($announcement, $target, filled($reason) ? trim((string) $reason) : null);
        });
    }

    public function isAuthorized(User $user, RecruitmentStatus $target): bool
    {
        return $user->can(self::PERMISSIONS[$target->value]);
    }

    /** State-machine rules plus the extra checks for manual stage moves. */
    public function violation(RecruitmentAnnouncement $announcement, RecruitmentStatus $target): ?string
    {
        if ($this->closesOnTheWay($announcement, $target)) {
            return null;
        }

        if ($key = $this->stateMachine->announcementTransitionViolation($announcement, $target)) {
            return $key;
        }

        $vacancyIds = $announcement->vacancies()->withTrashed()->select('id');

        return match ($target) {
            // Every application must have a screening decision first.
            RecruitmentStatus::Exam => $announcement->vacancies()->get()
                ->contains(fn ($vacancy) => ! $this->timeline->isScreeningCompleteFor($vacancy))
                    ? 'recruitment.errors.screening_incomplete' : null,
            // Every exam invitation must have a recorded result first.
            RecruitmentStatus::Interview => $announcement->exam_required && ExamInterviewApplicant::query()
                ->where('status', 'invited')
                ->whereHas('schedule', fn ($q) => $q->whereIn('vacancy_id', $vacancyIds)->where('type', ExamInterviewType::Exam->value))
                ->exists() ? 'recruitment.errors.interview_before_previous_stage' : null,
            default => null,
        };
    }

    /** "Start screening" on a Published announcement whose period has ended goes via Closed. */
    private function closesOnTheWay(RecruitmentAnnouncement $announcement, RecruitmentStatus $target): bool
    {
        return $target === RecruitmentStatus::Screening
            && $announcement->lifecycleStatus() === RecruitmentStatus::Published
            && $this->timeline->hasApplicationPeriodEnded($announcement);
    }

    /**
     * The forward moves offered on the announcement page, in lifecycle order.
     *
     * @return list<RecruitmentStatus>
     */
    public function candidateTargets(RecruitmentAnnouncement $announcement): array
    {
        return match ($announcement->lifecycleStatus()) {
            // Both are shown; before the closing date they are disabled with the reason.
            RecruitmentStatus::Published => [RecruitmentStatus::Closed, RecruitmentStatus::Screening],
            RecruitmentStatus::Closed => [RecruitmentStatus::Screening],
            RecruitmentStatus::Screening => $announcement->exam_required
                ? [RecruitmentStatus::Exam, RecruitmentStatus::Finalized]
                : [RecruitmentStatus::Exam, RecruitmentStatus::Interview, RecruitmentStatus::Finalized],
            RecruitmentStatus::Exam => [RecruitmentStatus::Interview, RecruitmentStatus::Finalized],
            RecruitmentStatus::Interview => [RecruitmentStatus::Finalized],
            default => [],
        };
    }
}
