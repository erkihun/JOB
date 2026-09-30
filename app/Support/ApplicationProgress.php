<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\ApplicationStatus;
use App\Models\Application;

/**
 * Turns an application's status into the five-step tracker shown to applicants
 * (Submitted → Screening → Exam → Interview → Result) plus a plain-language
 * "what happens next" line. Used by the dashboard, list and detail pages.
 */
final class ApplicationProgress
{
    public const STEPS = ['submitted', 'screening', 'exam', 'interview', 'result'];

    /**
     * Index of the step the application is at, and whether that step ended the process.
     *
     * @return array{0: int, 1: 'current'|'failed'|'done'}
     */
    public static function position(ApplicationStatus $status): array
    {
        return match ($status) {
            ApplicationStatus::Submitted, ApplicationStatus::CorrectionRequired => [0, 'current'],
            ApplicationStatus::UnderReview => [1, 'current'],
            ApplicationStatus::FailedScreening => [1, 'failed'],
            ApplicationStatus::PassedScreening, ApplicationStatus::ShortlistedExam => [2, 'current'],
            ApplicationStatus::ExamCompleted, ApplicationStatus::ShortlistedInterview => [3, 'current'],
            ApplicationStatus::InterviewCompleted, ApplicationStatus::Waitlisted => [4, 'current'],
            ApplicationStatus::Selected => [4, 'done'],
            ApplicationStatus::NotSelected, ApplicationStatus::Withdrawn => [4, 'failed'],
        };
    }

    /**
     * Each step with its state, a specific label ("Screening passed", "Written exam")
     * and the date it happened or is scheduled, when known.
     *
     * @return list<array{key: string, label: string, state: 'done'|'current'|'failed'|'pending', date: ?\Carbon\CarbonInterface}>
     */
    public static function steps(Application $application): array
    {
        [$at, $mode] = self::position($application->status);

        // Sessions (exam / practical / interview) are used only when already loaded,
        // so list pages don't trigger extra queries unless they eager-load them.
        $sessions = $application->relationLoaded('examInterviewApplicants')
            ? $application->examInterviewApplicants->filter(fn ($r) => $r->relationLoaded('schedule') && $r->schedule)
            : collect();
        $exam = $sessions->first(fn ($r) => $r->schedule->type->isExamLike());
        $interview = $sessions->first(fn ($r) => ! $r->schedule->type->isExamLike());

        return array_map(function (string $key, int $i) use ($at, $mode, $application, $exam, $interview): array {
            $state = match (true) {
                $i < $at => 'done',
                $i === $at => $mode,
                default => 'pending',
            };

            $label = match (true) {
                $key === 'screening' && $state === 'done' => __('applicant.track_screening_passed'),
                $key === 'screening' && $state === 'failed' => __('applicant.track_screening_failed'),
                $key === 'exam' && $exam !== null => $exam->schedule->type->getLabel(),
                $key === 'result' && $state === 'done' => __('applicant.track_selected'),
                default => __('applicant.track_'.$key),
            };

            $date = match ($key) {
                'submitted' => $application->submitted_at ?? $application->created_at,
                'screening' => $state !== 'pending' && $state !== 'current' ? $application->screened_at : null,
                'exam' => $exam?->schedule->date,
                'interview' => $interview?->schedule->date,
                'result' => $state === 'done' || $state === 'failed' ? $application->updated_at : null,
            };

            return ['key' => $key, 'label' => $label, 'state' => $state, 'date' => $date];
        }, self::STEPS, array_keys(self::STEPS));
    }

    /** One sentence telling the applicant what happens next for this status. */
    public static function nextText(Application $application): string
    {
        return __('applicant.next_'.$application->status->value);
    }

    /** Status badge tone used across the portal. */
    public static function tone(ApplicationStatus $status): string
    {
        return match ($status) {
            ApplicationStatus::Selected, ApplicationStatus::PassedScreening => 'success',
            ApplicationStatus::FailedScreening, ApplicationStatus::NotSelected, ApplicationStatus::Withdrawn => 'danger',
            ApplicationStatus::CorrectionRequired, ApplicationStatus::ShortlistedExam, ApplicationStatus::ShortlistedInterview, ApplicationStatus::Waitlisted => 'warning',
            default => 'info',
        };
    }
}
