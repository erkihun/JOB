<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationType: string
{
    case ExamInvitation = 'exam_invitation';
    case InterviewInvitation = 'interview_invitation';
    case ScreeningPassed = 'screening_passed';
    case ScreeningFailed = 'screening_failed';
    case CorrectionRequired = 'correction_required';
    case ApplicationSubmitted = 'application_submitted';
    case Selected = 'selected';
    case Waitlisted = 'waitlisted';
    case NotSelected = 'not_selected';
    case General = 'general';

    public function getLabel(): string
    {
        return __('statuses.notification_type.'.$this->value);
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
