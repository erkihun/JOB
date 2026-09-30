<?php

declare(strict_types=1);

namespace App\Http\Controllers\Applicant;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\ExamInterviewApplicant;
use App\Models\Vacancy;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicantDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $applicant = $request->user()->applicant;

        $applications = $applicant
            ? $applicant->applications()->with(['vacancy', 'examInterviewApplicants.schedule'])->latest()->limit(5)->get()
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

        // Next exam / practical / interview the applicant is invited to.
        $upcoming = $applicant
            ? ExamInterviewApplicant::with(['schedule.vacancy', 'application'])
                ->whereHas('application', fn ($q) => $q->where('applicant_id', $applicant->id))
                ->whereHas('schedule', fn ($q) => $q->whereDate('date', '>=', today()))
                ->get()
                ->sortBy(fn ($r) => $r->schedule->date->format('Y-m-d').' '.$r->schedule->start_time)
                ->first()
            : null;

        // An application sent back for correction always comes first.
        $needsCorrection = $applicant
            ? $applicant->applications()->with('vacancy')->where('status', ApplicationStatus::CorrectionRequired)->latest()->first()
            : null;

        $messages = $applicant ? $applicant->notifications()->latest()->limit(4)->get() : collect();
        $unreadMessages = $applicant ? $applicant->notifications()->whereNull('read_at')->count() : 0;

        // A few open vacancies the applicant has not applied to yet.
        $appliedIds = $applicant ? $applicant->applications()->pluck('vacancy_id') : collect();
        $suggested = Vacancy::acceptingApplications()
            ->with('announcement')
            ->whereNotIn('id', $appliedIds)
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('applicant.dashboard', compact(
            'applicant',
            'applications',
            'applicationStats',
            'completionPct',
            'completionMissing',
            'upcoming',
            'needsCorrection',
            'messages',
            'unreadMessages',
            'suggested',
        ));
    }
}
