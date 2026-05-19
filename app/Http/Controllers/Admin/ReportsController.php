<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Vacancy;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function index(Request $request): View
    {
        $vacancies = Vacancy::orderBy('title->en')->get(['id', 'title', 'code']);

        $query = Application::with(['applicant', 'vacancy'])->latest();

        if ($vacancyId = $request->get('vacancy_id')) {
            $query->where('vacancy_id', $vacancyId);
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($from = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($until = $request->get('date_until')) {
            $query->whereDate('created_at', '<=', $until);
        }

        $applications = $query->paginate(50)->withQueryString();
        $statuses = ApplicationStatus::cases();

        $summary = [
            'total' => $query->toBase()->count(),
            'passed_screening' => (clone $query)->where('status', ApplicationStatus::PassedScreening)->count(),
            'failed_screening' => (clone $query)->where('status', ApplicationStatus::FailedScreening)->count(),
        ];

        return view('admin.reports.index', compact('applications', 'vacancies', 'statuses', 'summary'));
    }
}
