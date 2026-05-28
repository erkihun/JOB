<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Vacancy;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $canViewSensitive = auth()->user()?->hasPermissionTo('applications.view-sensitive') ?? false;

        $query = Application::with(['applicant', 'vacancy.institution'])->latest();

        if ($search = $request->get('search')) {
            $query->whereHas('applicant', fn ($q) => $q
                ->where('first_name', 'like', "%$search%")
                ->orWhere('last_name', 'like', "%$search%")
                ->orWhere('national_id', 'like', "%$search%")
                ->orWhere('phone', 'like', "%$search%")
            );
        }
        if ($vacancyId = $request->get('vacancy_id')) {
            $query->where('vacancy_id', $vacancyId);
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $applications = $query->paginate(20)->withQueryString();
        $vacancies = Vacancy::orderBy('title->en')->get(['id', 'title', 'code']);
        $statuses = ApplicationStatus::cases();

        return view('admin.applications.index', compact('applications', 'vacancies', 'statuses', 'canViewSensitive'));
    }

    public function show(Application $application): View
    {
        $canViewSensitive = auth()->user()?->hasPermissionTo('applications.view-sensitive') ?? false;

        $application->load(['applicant.profileDocuments', 'vacancy', 'documents', 'screeningReviews.reviewer']);

        return view('admin.applications.show', compact('application', 'canViewSensitive'));
    }
}
