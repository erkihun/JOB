<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Effective (display) stage of a recruitment announcement, derived from its
 * stored RecruitmentStatus *and* its opening/closing dates. A "published"
 * announcement whose opening date is still ahead is Upcoming; once the closing
 * day has ended it is effectively Closed even before the stored status catches up.
 */
enum RecruitmentStage: string
{
    case Draft = 'draft';
    case Upcoming = 'upcoming';
    case Open = 'open';
    case Closed = 'closed';
    case Screening = 'screening';
    case Exam = 'exam';
    case Interview = 'interview';
    case Finalized = 'finalized';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('recruitment.stage.'.$this->value);
    }

    /** Badge tone used by <x-admin.status>. */
    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Upcoming => 'info',
            self::Open => 'success',
            self::Closed => 'danger',
            self::Screening, self::Exam, self::Interview => 'warning',
            self::Finalized => 'success',
            self::Cancelled => 'gray',
        };
    }
}
