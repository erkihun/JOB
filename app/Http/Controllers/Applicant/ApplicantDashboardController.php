<?php

declare(strict_types=1);

namespace App\Http\Controllers\Applicant;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicantDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $applicant = $request->user()->applicant;

        $applications = $applicant
            ? $applicant->applications()->with('vacancy')->latest()->limit(5)->get()
            : collect();

        $applicationStats = [
            'total' => 0,
            'active' => 0,
            'positive' => 0,
            'rejected' => 0,
        ];

        if ($applicant) {
            $statusCounts = $applicant->applications()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $applicationStats = [
                'total' => (int) $statusCounts->sum(),
                'active' => (int) $statusCounts->only([
                    ApplicationStatus::Submitted->value,
                    ApplicationStatus::UnderReview->value,
                    ApplicationStatus::CorrectionRequired->value,
                ])->sum(),
                'positive' => (int) $statusCounts->only([
                    ApplicationStatus::PassedScreening->value,
                    ApplicationStatus::ShortlistedExam->value,
                    ApplicationStatus::ShortlistedInterview->value,
                    ApplicationStatus::Selected->value,
                    ApplicationStatus::ExamCompleted->value,
                    ApplicationStatus::InterviewCompleted->value,
                    ApplicationStatus::Waitlisted->value,
                ])->sum(),
                'rejected' => (int) $statusCounts->only([
                    ApplicationStatus::FailedScreening->value,
                    ApplicationStatus::NotSelected->value,
                    ApplicationStatus::Withdrawn->value,
                ])->sum(),
            ];
        }

        $completionPct = $applicant ? $applicant->profileCompletionPercentage() : 0;
        $completionMissing = ($applicant && $completionPct < 100)
            ? $applicant->profileMissingFields()
            : [];

        return view('applicant.dashboard', compact(
            'applicant',
            'applications',
            'applicationStats',
            'completionPct',
            'completionMissing',
        ));
    }
}
