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
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        $user             = auth()->user();
        $canViewAudit     = $user->hasPermissionTo('audit.view');
        $canViewSensitive = $user->hasPermissionTo('applications.view-sensitive');

        // ── KPI totals ────────────────────────────────────────────────
        $stats = [
            'total_applicants'   => Applicant::count(),
            'open_vacancies'     => Vacancy::where('status', VacancyStatus::Open)->count(),
            'total_applications' => Application::count(),
            'pending_screening'  => Application::whereIn('status', [
                ApplicationStatus::Submitted,
                ApplicationStatus::UnderReview,
                ApplicationStatus::CorrectionRequired,
            ])->count(),
            'passed_screening'   => Application::where('status', ApplicationStatus::PassedScreening)->count(),
            'selected'           => Application::where('status', ApplicationStatus::Selected)->count(),
            'total_vacancies'    => Vacancy::count(),
            'closed_vacancies'   => Vacancy::whereIn('status', [VacancyStatus::Closed, VacancyStatus::Finalized, VacancyStatus::Cancelled])->count(),
        ];

        // ── Application pipeline by status ───────────────────────────
        $pipeline = Application::selectRaw('status, count(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        $total = max(array_sum($pipeline), 1);

        $pipelineStages = collect([
            ['key' => 'submitted',             'label' => 'Submitted',              'color' => 'bg-blue-500'],
            ['key' => 'under_review',          'label' => 'Under Review',           'color' => 'bg-indigo-500'],
            ['key' => 'correction_required',   'label' => 'Correction Required',    'color' => 'bg-amber-500'],
            ['key' => 'passed_screening',      'label' => 'Passed Screening',       'color' => 'bg-teal-500'],
            ['key' => 'failed_screening',      'label' => 'Failed Screening',       'color' => 'bg-red-500'],
            ['key' => 'shortlisted_exam',      'label' => 'Shortlisted (Exam)',     'color' => 'bg-violet-500'],
            ['key' => 'exam_completed',        'label' => 'Exam Completed',         'color' => 'bg-purple-500'],
            ['key' => 'shortlisted_interview', 'label' => 'Shortlisted (Interview)','color' => 'bg-cyan-500'],
            ['key' => 'interview_completed',   'label' => 'Interview Completed',    'color' => 'bg-sky-500'],
            ['key' => 'selected',              'label' => 'Selected',               'color' => 'bg-green-500'],
            ['key' => 'waitlisted',            'label' => 'Waitlisted',             'color' => 'bg-yellow-500'],
            ['key' => 'not_selected',          'label' => 'Not Selected',           'color' => 'bg-rose-500'],
            ['key' => 'withdrawn',             'label' => 'Withdrawn',              'color' => 'bg-gray-400'],
        ])->map(fn ($s) => array_merge($s, [
            'count' => $pipeline[$s['key']] ?? 0,
            'pct'   => round((($pipeline[$s['key']] ?? 0) / $total) * 100, 1),
        ]))->filter(fn ($s) => $s['count'] > 0)->values();

        // ── Gender distribution ───────────────────────────────────────
        $genderDist = Applicant::selectRaw("COALESCE(gender,'unknown') as gender, count(*) as cnt")
            ->groupBy('gender')->pluck('cnt', 'gender')->toArray();
        $genderTotal = max(array_sum($genderDist), 1);

        $genderPassed = Application::where('status', ApplicationStatus::PassedScreening)
            ->join('applicants', 'applications.applicant_id', '=', 'applicants.id')
            ->selectRaw("COALESCE(applicants.gender,'unknown') as gender, count(*) as cnt")
            ->groupBy('applicants.gender')->pluck('cnt', 'gender')->toArray();

        $genderSelected = Application::where('status', ApplicationStatus::Selected)
            ->join('applicants', 'applications.applicant_id', '=', 'applicants.id')
            ->selectRaw("COALESCE(applicants.gender,'unknown') as gender, count(*) as cnt")
            ->groupBy('applicants.gender')->pluck('cnt', 'gender')->toArray();

        // ── Disability distribution ───────────────────────────────────
        $disabilityDist = [
            'with'    => Applicant::where('disability_status', true)->count(),
            'without' => Applicant::where('disability_status', false)->orWhereNull('disability_status')->count(),
        ];

        // ── Age distribution ──────────────────────────────────────────
        $ageGroups  = ['Under 25' => [0,25], '25–30' => [25,30], '31–35' => [31,35], '36–40' => [36,40], 'Over 40' => [41,999]];
        $ageDist    = [];
        foreach ($ageGroups as $label => [$min, $max]) {
            $ageDist[$label] = Applicant::whereNotNull('date_of_birth')
                ->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) >= ?', [$min])
                ->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < ?', [$max])
                ->count();
        }
        $ageTotal = max(array_sum($ageDist), 1);

        // ── Exam top scorers ──────────────────────────────────────────
        $examTopScorers = ExamInterviewApplicant::with(['application.applicant', 'schedule.vacancy'])
            ->whereHas('schedule', fn ($q) => $q->where('type', ExamInterviewType::Exam->value))
            ->whereNotNull('score')
            ->orderByDesc('score')->limit(10)->get();

        // ── Interview top scorers ─────────────────────────────────────
        $interviewTopScorers = ExamInterviewApplicant::with(['application.applicant', 'schedule.vacancy'])
            ->whereHas('schedule', fn ($q) => $q->where('type', ExamInterviewType::Interview->value))
            ->whereNotNull('score')
            ->orderByDesc('score')->limit(10)->get();

        // ── Exam pass stats by gender ─────────────────────────────────
        $examPassByGender = ExamInterviewApplicant::join('applications', 'exam_interview_applicants.application_id', '=', 'applications.id')
            ->join('applicants', 'applications.applicant_id', '=', 'applicants.id')
            ->join('exam_interview_schedules', 'exam_interview_applicants.schedule_id', '=', 'exam_interview_schedules.id')
            ->where('exam_interview_schedules.type', ExamInterviewType::Exam->value)
            ->whereNotNull('exam_interview_applicants.score')
            ->selectRaw("COALESCE(applicants.gender,'unknown') as gender, count(*) as total, round(avg(exam_interview_applicants.score),1) as avg_score, max(exam_interview_applicants.score) as max_score")
            ->groupBy('applicants.gender')->get()->keyBy('gender');

        // ── Final results overview ────────────────────────────────────
        $finalResultStats = [
            'total'    => FinalResult::count(),
            'avg_exam' => round((float) FinalResult::avg('exam_score'), 1),
            'avg_int'  => round((float) FinalResult::avg('interview_score'), 1),
            'avg_fin'  => round((float) FinalResult::avg('final_score'), 1),
        ];

        // ── Applications per vacancy (top 8 open) ────────────────────
        $vacancyLoad = Vacancy::where('status', VacancyStatus::Open)
            ->withCount('applications')
            ->orderByDesc('applications_count')->limit(8)->get();

        // ── Upcoming schedules ────────────────────────────────────────
        $upcomingSchedules = ExamInterviewSchedule::with('vacancy')
            ->where('date', '>=', now()->toDateString())
            ->orderBy('date')->orderBy('start_time')->limit(5)->get();

        // ── Recent applications ───────────────────────────────────────
        $recentApplications = Application::with(['applicant', 'vacancy'])->latest()->limit(6)->get();

        // ── Recent activity ───────────────────────────────────────────
        $recentActivity = $canViewAudit
            ? AuditLog::with('user')->latest()->limit(6)->get()
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
}
