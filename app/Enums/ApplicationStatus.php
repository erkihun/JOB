<?php

declare(strict_types=1);

namespace App\Enums;

enum ApplicationStatus: string
{
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case CorrectionRequired = 'correction_required';
    case PassedScreening = 'passed_screening';
    case FailedScreening = 'failed_screening';
    case ShortlistedExam = 'shortlisted_exam';
    case ExamCompleted = 'exam_completed';
    case ShortlistedInterview = 'shortlisted_interview';
    case InterviewCompleted = 'interview_completed';
    case Selected = 'selected';
    case Waitlisted = 'waitlisted';
    case NotSelected = 'not_selected';
    case Withdrawn = 'withdrawn';

    public function getLabel(): string
    {
        return __('statuses.application.'.$this->value);
    }

    public function label(): string
    {
        return $this->getLabel();
    }

    public function labelAmharic(): string
    {
        return app()->setLocale('am') ? $this->getLabel() : $this->getLabel();
    }

    public function getColor(): string|array|null
    {
        return $this->color();
    }

    public function color(): string
    {
        return match ($this) {
            self::Submitted => 'info',
            self::UnderReview => 'warning',
            self::CorrectionRequired => 'warning',
            self::PassedScreening => 'success',
            self::FailedScreening => 'danger',
            self::ShortlistedExam,
            self::ShortlistedInterview => 'success',
            self::ExamCompleted,
            self::InterviewCompleted => 'info',
            self::Selected => 'success',
            self::Waitlisted => 'warning',
            self::NotSelected => 'danger',
            self::Withdrawn => 'gray',
        };
    }
}
