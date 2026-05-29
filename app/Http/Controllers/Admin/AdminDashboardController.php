<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\ExamInterviewType;
use App\Enums\VacancyStatus;
use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\ExamInterviewApplicant;
use App\Models\ExamInterviewSchedule;
use App\Models\FinalResult;
use App\Models\Vacancy;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        $canViewAudit = $user->hasPermissionTo('audit.view');
        $canViewSensitive = $user->hasPermissionTo('applications.view-sensitive');

        $stats = $this->remember('dashboard.stats', fn (): array => [
            'total_applicants' => Applicant::count(),
            'open_vacancies' => Vacancy::where('status', VacancyStatus::Open)->count(),
            'total_applications' => Application::count(),
            'pending_screening' => Application::whereIn('status', [
                ApplicationStatus::Submitted,
                ApplicationStatus::UnderReview,
                ApplicationStatus::CorrectionRequired,
            ])->count(),
            'passed_screening' => Application::where('status', ApplicationStatus::PassedScreening)->count(),
            'selected' => Application::where('status', ApplicationStatus::Selected)->count(),
            'total_vacancies' => Vacancy::count(),
            'closed_vacancies' => Vacancy::whereIn('status', [VacancyStatus::Closed, VacancyStatus::Finalized, VacancyStatus::Cancelled])->count(),
        ]);

        $pipeline = $this->remember('dashboard.pipeline', fn (): array => Application::selectRaw('status, count(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray());

        $total = max(array_sum($pipeline), 1);

        $pipelineStages = collect([
            ['key' => 'submitted', 'label' => 'Submitted', 'color' => 'bg-blue-500'],
            ['key' => 'under_review', 'label' => 'Under Review', 'color' => 'bg-indigo-500'],
            ['key' => 'correction_required', 'label' => 'Correction Required', 'color' => 'bg-amber-500'],
            ['key' => 'passed_screening', 'label' => 'Passed Screening', 'color' => 'bg-teal-500'],
            ['key' => 'failed_screening', 'label' => 'Failed Screening', 'color' => 'bg-red-500'],
            ['key' => 'shortlisted_exam', 'label' => 'Shortlisted (Exam)', 'color' => 'bg-violet-500'],
            ['key' => 'exam_completed', 'label' => 'Exam Completed', 'color' => 'bg-purple-500'],
            ['key' => 'shortlisted_interview', 'label' => 'Shortlisted (Interview)', 'color' => 'bg-cyan-500'],
            ['key' => 'interview_completed', 'label' => 'Interview Completed', 'color' => 'bg-sky-500'],
            ['key' => 'selected', 'label' => 'Selected', 'color' => 'bg-green-500'],
            ['key' => 'waitlisted', 'label' => 'Waitlisted', 'color' => 'bg-yellow-500'],
            ['key' => 'not_selected', 'label' => 'Not Selected', 'color' => 'bg-rose-500'],
            ['key' => 'withdrawn', 'label' => 'Withdrawn', 'color' => 'bg-gray-400'],
        ])->map(fn (array $stage): array => array_merge($stage, [
            'count' => $pipeline[$stage['key']] ?? 0,
            'pct' => round((($pipeline[$stage['key']] ?? 0) / $total) * 100, 1),
        ]))->filter(fn (array $stage): bool => $stage['count'] > 0)->values();

        $genderDist = $this->remember('dashboard.gender_dist', fn (): array => Applicant::selectRaw("COALESCE(gender,'unknown') as gender, count(*) as cnt")
            ->groupBy('gender')
            ->pluck('cnt', 'gender')
            ->toArray());
        $genderTotal = max(array_sum($genderDist), 1);

        $genderPassed = $this->remember('dashboard.gender_passed', fn (): array => Application::where('status', ApplicationStatus::PassedScreening)
            ->join('applicants', 'applications.applicant_id', '=', 'applicants.id')
            ->selectRaw("COALESCE(applicants.gender,'unknown') as gender, count(*) as cnt")
            ->groupBy('applicants.gender')
            ->pluck('cnt', 'gender')
            ->toArray());

        $genderSelected = $this->remember('dashboard.gender_selected', fn (): array => Application::where('status', ApplicationStatus::Selected)
            ->join('applicants', 'applications.applicant_id', '=', 'applicants.id')
            ->selectRaw("COALESCE(applicants.gender,'unknown') as gender, count(*) as cnt")
            ->groupBy('applicants.gender')
            ->pluck('cnt', 'gender')
            ->toArray());

        $disabilityDist = $this->remember('dashboard.disability_dist', fn (): array => [
            'with' => Applicant::where('disability_status', true)->count(),
            'without' => Applicant::where('disability_status', false)->orWhereNull('disability_status')->count(),
        ]);

        $ageGroups = ['Under 25' => [0, 25], '25-30' => [25, 30], '31-35' => [31, 35], '36-40' => [36, 40], 'Over 40' => [41, 999]];
        $ageDist = $this->remember('dashboard.age_dist', function () use ($ageGroups): array {
            $ageDist = [];

            foreach ($ageGroups as $label => [$min, $max]) {
                $youngestDate = now()->subYears($min)->toDateString();
                $oldestDate = now()->subYears($max)->toDateString();

                $ageDist[$label] = Applicant::whereNotNull('date_of_birth')
                    ->whereDate('date_of_birth', '<=', $youngestDate)
                    ->whereDate('date_of_birth', '>', $oldestDate)
                    ->count();
            }

            return $ageDist;
        });
        $ageTotal = max(array_sum($ageDist), 1);

        $examTopScorers = $this->remember('dashboard.exam_top_scorers', fn () => ExamInterviewApplicant::with(['application.applicant', 'schedule.vacancy'])
            ->whereHas('schedule', fn ($query) => $query->where('type', ExamInterviewType::Exam->value))
            ->whereNotNull('score')
            ->orderByDesc('score')
            ->limit(10)
            ->get());

        $interviewTopScorers = $this->remember('dashboard.interview_top_scorers', fn () => ExamInterviewApplicant::with(['application.applicant', 'schedule.vacancy'])
            ->whereHas('schedule', fn ($query) => $query->where('type', ExamInterviewType::Interview->value))
            ->whereNotNull('score')
            ->orderByDesc('score')
            ->limit(10)
            ->get());

        $examPassByGender = $this->remember('dashboard.exam_pass_by_gender', fn () => ExamInterviewApplicant::join('applications', 'exam_interview_applicants.application_id', '=', 'applications.id')
            ->join('applicants', 'applications.applicant_id', '=', 'applicants.id')
            ->join('exam_interview_schedules', 'exam_interview_applicants.schedule_id', '=', 'exam_interview_schedules.id')
            ->where('exam_interview_schedules.type', ExamInterviewType::Exam->value)
            ->whereNotNull('exam_interview_applicants.score')
            ->selectRaw("COALESCE(applicants.gender,'unknown') as gender, count(*) as total, round(avg(exam_interview_applicants.score),1) as avg_score, max(exam_interview_applicants.score) as max_score")
            ->groupBy('applicants.gender')
            ->get()
            ->keyBy('gender'));

        $finalResultStats = $this->remember('dashboard.final_result_stats', fn (): array => [
            'total' => FinalResult::count(),
            'avg_exam' => round((float) FinalResult::avg('exam_score'), 1),
            'avg_int' => round((float) FinalResult::avg('interview_score'), 1),
            'avg_fin' => round((float) FinalResult::avg('final_score'), 1),
        ]);

        $vacancyLoad = $this->remember('dashboard.vacancy_load', fn () => Vacancy::where('status', VacancyStatus::Open)
            ->withCount('applications')
            ->orderByDesc('applications_count')
            ->limit(8)
            ->get());

        $upcomingSchedules = $this->remember('dashboard.upcoming_schedules', fn () => ExamInterviewSchedule::with('vacancy')
            ->where('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit(5)
            ->get());

        $recentApplications = $this->remember('dashboard.recent_applications', fn () => Application::with(['applicant', 'vacancy'])
            ->latest()
            ->limit(6)
            ->get());

        $recentActivity = $canViewAudit
            ? $this->remember('dashboard.recent_activity', fn () => AuditLog::with('user')->latest()->limit(6)->get())
            : collect();

        return view('admin.dashboard.index', compact(
            'stats', 'pipelineStages', 'total',
            'genderDist', 'genderTotal', 'genderPassed', 'genderSelected',
            'disabilityDist',
            'ageDist', 'ageTotal',
            'examTopScorers', 'interviewTopScorers', 'examPassByGender',
            'finalResultStats',
            'vacancyLoad',
            'upcomingSchedules', 'recentApplications', 'recentActivity',
            'canViewSensitive', 'canViewAudit', 'user',
        ));
    }

    private function remember(string $key, callable $callback): mixed
    {
        if (app()->environment('testing')) {
            return $callback();
        }

        return Cache::remember($key, now()->addSeconds(60), $callback);
    }
}
