<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Screening\ReviewApplicationAction;
use App\Enums\ApplicationStatus;
use App\Enums\ScreeningDecision;
use App\Exports\FailedScreeningReportExport;
use App\Exports\PassedScreeningReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Screening\StoreScreeningReviewRequest;
use App\Models\Application;
use App\Models\User;
use App\Models\Vacancy;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ScreeningController extends Controller
{
    public function index(Request $request): View
    {
        return $this->renderList(
            request: $request,
            allowedStatuses: [
                ApplicationStatus::Submitted,
                ApplicationStatus::UnderReview,
                ApplicationStatus::CorrectionRequired,
            ],
            pageTitle: __('menus.screening'),
            emptyText: __('messages.no_records'),
            resetRoute: route('admin.screening.index'),
        );
    }

    public function passed(Request $request): View
    {
        return $this->renderList(
            request: $request,
            allowedStatuses: [
                ApplicationStatus::PassedScreening,
                ApplicationStatus::ShortlistedExam,
                ApplicationStatus::ExamCompleted,
                ApplicationStatus::ShortlistedInterview,
                ApplicationStatus::InterviewCompleted,
                ApplicationStatus::Selected,
                ApplicationStatus::Waitlisted,
                ApplicationStatus::NotSelected,
            ],
            pageTitle: __('menus.passed_applicants'),
            emptyText: __('messages.no_records'),
            resetRoute: route('admin.screening.passed'),
        );
    }

    public function failed(Request $request): View
    {
        return $this->renderList(
            request: $request,
            allowedStatuses: [ApplicationStatus::FailedScreening],
            pageTitle: __('menus.failed_applicants'),
            emptyText: __('messages.no_records'),
            resetRoute: route('admin.screening.failed'),
        );
    }

    public function exportPassed(Request $request): BinaryFileResponse|Response
    {
        return $this->doExport($request, ApplicationStatus::PassedScreening, __('menus.passed_applicants'));
    }

    public function exportFailed(Request $request): BinaryFileResponse|Response
    {
        return $this->doExport($request, ApplicationStatus::FailedScreening, __('menus.failed_applicants'));
    }

    public function review(Application $application): View
    {
        $canViewSensitive = auth()->user()?->hasPermissionTo('applications.view-sensitive') ?? false;

        $application->load([
            'applicant.profileDocuments',
            'vacancy',
            'documents',
            'screeningReviews.reviewer',
        ]);

        $reviewers = User::role(['admin', 'screening_officer'])->where('status', 'active')->get(['id', 'name']);

        return view('admin.screening.review', compact('application', 'reviewers', 'canViewSensitive'));
    }

    public function submitReview(
        StoreScreeningReviewRequest $request,
        Application $application,
        ReviewApplicationAction $reviewApplicationAction,
    ): RedirectResponse {
        // A screening officer may only submit a decision for an application
        // that is assigned to them (or that has no assigned reviewer). Users
        // with broader screening authority may review any application.
        $this->authorize('screen', $application);

        $data = $request->validated();

        $reviewApplicationAction->handle(
            $application,
            $request->user(),
            ScreeningDecision::from($data['decision']),
            $data['remark'] ?? null,
        );

        return redirect()->route('admin.screening.index')
            ->with('success', __('messages.screening_submitted'));
    }

    private function doExport(
        Request $request,
        ApplicationStatus $status,
        string $title,
    ): BinaryFileResponse|Response {
        $filters = array_filter([
            'vacancy_id' => $request->query('vacancy_id'),
        ]);

        $filename = str_replace(' ', '_', strtolower($title)).'_'.now()->format('Ymd');

        if ($request->query('format') === 'pdf') {
            $vacancyFilter = null;
            if (! empty($filters['vacancy_id'])) {
                $v = Vacancy::find($filters['vacancy_id']);
                $vacancyFilter = $v ? ($v->code.' — '.$v->title) : null;
            }

            // Hard cap at 500 rows per PDF to prevent memory exhaustion.
            // For larger exports use the Excel format which streams via chunks.
            $applications = Application::with(['applicant', 'vacancy', 'screener'])
                ->where('status', $status->value)
                ->when(! empty($filters['vacancy_id']), fn ($q) => $q->where('vacancy_id', $filters['vacancy_id']))
                ->latest()
                ->limit(500)
                ->get();

            $pdf = Pdf::loadView('admin.screening.export-pdf', compact('applications', 'title', 'vacancyFilter'))
                ->setPaper('a4', 'landscape');

            return $pdf->download($filename.'.pdf');
        }

        $export = $status === ApplicationStatus::PassedScreening
            ? new PassedScreeningReportExport($filters)
            : new FailedScreeningReportExport($filters);

        return Excel::download($export, $filename.'.xlsx');
    }

    /**
     * @param  array<int, ApplicationStatus>  $allowedStatuses
     */
    private function renderList(
        Request $request,
        array $allowedStatuses,
        string $pageTitle,
        string $emptyText,
        string $resetRoute,
    ): View {
        $canViewSensitive = auth()->user()?->hasPermissionTo('applications.view-sensitive') ?? false;

        $query = Application::with(['applicant', 'vacancy'])
            ->whereIn('status', array_map(static fn (ApplicationStatus $status): string => $status->value, $allowedStatuses))
            ->latest();

        if ($search = $request->get('search')) {
            $query->whereHas('applicant', fn ($q) => $q
                ->where('first_name', 'like', "%$search%")
                ->orWhere('last_name', 'like', "%$search%")
                ->orWhere('full_name', 'like', "%$search%")
            );
        }
        if ($vacancyId = $request->get('vacancy_id')) {
            $query->where('vacancy_id', $vacancyId);
        }

        $applications = $query->paginate(20)->withQueryString();
        $vacancies = Vacancy::orderBy('title->en')->get(['id', 'title', 'code']);

        return view('admin.screening.index', compact(
            'applications',
            'vacancies',
            'canViewSensitive',
            'pageTitle',
            'emptyText',
            'resetRoute',
        ));
    }
}
