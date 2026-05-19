<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\VacancyStatus;
use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\ExamInterviewSchedule;
use App\Models\Vacancy;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();

        $canViewSensitive = $user->hasPermissionTo('applications.view-sensitive');
        $canViewAudit = $user->hasPermissionTo('audit.view');

        $stats = [
            'open_vacancies' => Vacancy::where('status', VacancyStatus::Open)->count(),
            'total_applications' => Application::count(),
            'pending_screening' => Application::whereIn('status', [
                ApplicationStatus::Submitted,
                ApplicationStatus::UnderReview,
                ApplicationStatus::CorrectionRequired,
            ])->count(),
            'total_applicants' => Applicant::count(),
        ];

        $recentApplications = Application::with(['applicant', 'vacancy'])
            ->latest()
            ->limit(8)
            ->get();

        $openVacancies = Vacancy::where('status', VacancyStatus::Open)
            ->withCount('applications')
            ->orderBy('closing_date')
            ->limit(6)
            ->get();

        $upcomingSchedules = ExamInterviewSchedule::with('vacancy')
            ->where('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit(5)
            ->get();

        $recentActivity = $canViewAudit
            ? AuditLog::with('user')->latest()->limit(8)->get()
            : collect();

        return view('admin.dashboard.index', compact(
            'stats',
            'recentApplications',
            'openVacancies',
            'upcomingSchedules',
            'recentActivity',
            'canViewSensitive',
            'canViewAudit',
            'user',
        ));
    }
}
