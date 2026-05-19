<?php

declare(strict_types=1);

namespace App\Enums;

enum VacancyStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Closed = 'closed';
    case Screening = 'screening';
    case ExamStage = 'exam_stage';
    case InterviewStage = 'interview_stage';
    case Finalized = 'finalized';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return __('statuses.vacancy.'.$this->value);
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    public function getColor(): string|array|null
    {
        return $this->color();
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Open => 'success',
            self::Closed => 'danger',
            self::Screening => 'warning',
            self::ExamStage,
            self::InterviewStage => 'info',
            self::Finalized => 'success',
            self::Cancelled => 'danger',
        };
    }

    public function isAcceptingApplications(): bool
    {
        return $this === self::Open;
    }
}
