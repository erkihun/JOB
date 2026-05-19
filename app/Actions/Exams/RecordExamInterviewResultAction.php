<?php

declare(strict_types=1);

namespace App\Actions\Exams;

use App\Enums\ApplicationStatus;
use App\Enums\ExamInterviewType;
use App\Models\ExamInterviewApplicant;

class RecordExamInterviewResultAction
{
    public function handle(
        ExamInterviewApplicant $applicantRecord,
        string $status,
        ?float $score = null,
        ?string $remark = null,
    ): ExamInterviewApplicant {
        $applicantRecord->update([
            'status' => $status,
            'score' => $score,
            'remark' => $remark,
        ]);

        // Update the application status based on the result
        $application = $applicantRecord->application;
        $scheduleType = $applicantRecord->schedule->type;

        $newAppStatus = match (true) {
            $scheduleType === ExamInterviewType::Exam && $status === 'passed' => ApplicationStatus::ShortlistedInterview,
            $scheduleType === ExamInterviewType::Exam && $status === 'failed' => ApplicationStatus::ExamCompleted,
            $scheduleType === ExamInterviewType::Exam && $status === 'attended' => ApplicationStatus::ExamCompleted,
            $scheduleType === ExamInterviewType::Interview && $status === 'passed' => ApplicationStatus::Selected,
            $scheduleType === ExamInterviewType::Interview && $status === 'failed' => ApplicationStatus::NotSelected,
            $scheduleType === ExamInterviewType::Interview && $status === 'attended' => ApplicationStatus::InterviewCompleted,
            default => $application->status,
        };

        if ($newAppStatus !== $application->status) {
            $application->update(['status' => $newAppStatus]);
        }

        return $applicantRecord->refresh();
    }
}
