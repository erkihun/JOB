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
    case DeadlineExtended = 'deadline_extended';
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

    /**
     * Placeholders that actually receive a value when this notification is sent
     * (see SendApplicantNotificationAction and its callers).
     *
     * @return list<string>
     */
    public function placeholders(): array
    {
        $common = ['applicant_name', 'vacancy_title', 'reference_number', 'contact_information'];

        return match ($this) {
            self::ExamInvitation, self::InterviewInvitation => [...$common, 'date', 'time', 'venue', 'instructions'],
            self::ScreeningPassed, self::ScreeningFailed, self::CorrectionRequired => [...$common, 'remark'],
            self::Selected, self::Waitlisted, self::NotSelected => [...$common, 'message'],
            self::General => ['applicant_name', 'message', 'contact_information'],
            self::ApplicationSubmitted => $common,
            self::DeadlineExtended => [...$common, 'announcement', 'old_date', 'new_date', 'reason'],
        };
    }

    /** Recruitment stage, used to group templates in the admin. */
    public function stage(): string
    {
        return match ($this) {
            self::ApplicationSubmitted, self::CorrectionRequired, self::DeadlineExtended => 'application',
            self::ScreeningPassed, self::ScreeningFailed => 'screening',
            self::ExamInvitation, self::InterviewInvitation => 'assessment',
            self::Selected, self::Waitlisted, self::NotSelected => 'final',
            self::General => 'general',
        };
    }
}
