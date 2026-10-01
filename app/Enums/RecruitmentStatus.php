<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Stored lifecycle status of a RecruitmentAnnouncement.
 *
 * Draft → Published → Closed → Screening → Exam → Interview → Finalized, with
 * Cancelled reachable from any non-terminal status. Valid moves are defined in
 * App\Services\Recruitment\RecruitmentStateMachine — never assign this column
 * directly from request input.
 *
 * "Published" is the stored value for an announcement that has been released;
 * whether it is actually *open* (accepting applications) is derived from its
 * dates by RecruitmentTimelineService (see RecruitmentStage).
 */
enum RecruitmentStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Closed = 'closed';
    case Screening = 'screening';
    case Exam = 'exam';
    case Interview = 'interview';
    case Finalized = 'finalized';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('recruitment.status.'.$this->value);
    }

    /** Position in the forward lifecycle (Cancelled sits outside it). */
    public function rank(): int
    {
        return match ($this) {
            self::Draft => 0,
            self::Published => 1,
            self::Closed => 2,
            self::Screening => 3,
            self::Exam => 4,
            self::Interview => 5,
            self::Finalized => 6,
            self::Cancelled => 99,
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::Finalized || $this === self::Cancelled;
    }

    /** Once released, an announcement stays publicly visible for transparency. */
    public function isPubliclyVisible(): bool
    {
        return ! in_array($this, [self::Draft, self::Cancelled], true);
    }

    /** Screening (or a later assessment stage) has begun. */
    public function hasStartedAssessment(): bool
    {
        return in_array($this, [self::Screening, self::Exam, self::Interview, self::Finalized], true);
    }

    /** @return list<string> */
    public static function publicValues(): array
    {
        return array_values(array_map(
            fn (self $s) => $s->value,
            array_filter(self::cases(), fn (self $s) => $s->isPubliclyVisible()),
        ));
    }
}
