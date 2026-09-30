<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Screening\ChangeScreeningDecisionAction;
use App\Actions\Screening\ReviewApplicationAction;
use App\Enums\ApplicationStatus;
use App\Enums\ScreeningDecision;
use App\Exports\FailedScreeningReportExport;
use App\Exports\PassedScreeningReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Screening\ChangeScreeningDecisionRequest;
use App\Http\Requests\Screening\StoreScreeningReviewRequest;
use App\Models\Application;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\Eligibility\EligibilityProfile;
use App\Services\Eligibility\VacancyEligibilityChecker;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
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

    public function review(Request $request, Application $application, VacancyEligibilityChecker $eligibilityChecker): View
    {
        $canViewSensitive = auth()->user()?->hasPermissionTo('applications.view-sensitive') ?? false;

        $application->load([
            'applicant.profileDocuments',
            'vacancy.requirementGroups.requirements',
            'documents.vacancyDocument',
            'screeningReviews.reviewer',
            'screener',
        ]);

        $reviewers = User::role(['admin', 'screening_officer'])->where('status', 'active')->get(['id', 'name']);

        // Screening aid: does the application meet one of the vacancy's requirement options?
        $eligibility = $application->applicant
            ? $eligibilityChecker->check($application->vacancy, EligibilityProfile::fromApplication($application))
            : null;

        // Queue context: how many are still waiting and who comes next (for "Skip").
        $queueVacancyId = $request->string('vacancy_id')->toString() ?: null;
        $queueRemaining = $this->pendingQueue($request->user(), $queueVacancyId)->whereKeyNot($application->id)->count();
        $nextApplication = $this->nextInQueue($application, $request->user(), $queueVacancyId);

        $canChangeDecision = $request->user()?->can('reverseDecision', $application) ?? false;

        return view('admin.screening.review', compact(
            'canChangeDecision',
            'application',
            'reviewers',
            'canViewSensitive',
            'eligibility',
            'queueVacancyId',
            'queueRemaining',
            'nextApplication',
        ));
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

        // A decision is final once given; changing it goes through changeDecision()
        // (permission + reason required).
        if (! $application->awaitsScreeningDecision()) {
            return redirect()->route('admin.screening.review', $application)
                ->with('error', __('messages.decision_already_recorded'));
        }

        $data = $request->validated();

        $reviewApplicationAction->handle(
            $application,
            $request->user(),
            ScreeningDecision::from($data['decision']),
            $data['remark'] ?? null,
        );

        // Keep the screener in flow: open the next applicant waiting in the queue
        // (same vacancy filter, if one was applied) instead of returning to the list.
        $queueVacancyId = $request->string('queue_vacancy_id')->toString() ?: null;
        $next = $this->nextInQueue($application, $request->user(), $queueVacancyId);

        if ($next === null) {
            return redirect()->route('admin.screening.index', array_filter(['vacancy_id' => $queueVacancyId]))
                ->with('success', __('messages.screening_queue_done'));
        }

        $remaining = $this->pendingQueue($request->user(), $queueVacancyId)->count();

        return redirect()->route('admin.screening.review', array_filter([
            'application' => $next->id,
            'vacancy_id' => $queueVacancyId,
        ]))->with('success', trans_choice('messages.screening_saved_next', $remaining, ['count' => $remaining]));
    }

    public function changeDecision(
        ChangeScreeningDecisionRequest $request,
        Application $application,
        ChangeScreeningDecisionAction $changeDecision,
    ): RedirectResponse {
        $this->authorize('reverseDecision', $application);

        $data = $request->validated();

        $changeDecision->handle(
            $application,
            $request->user(),
            ScreeningDecision::from($data['decision']),
            $data['reason'],
        );

        return redirect()->route('admin.screening.review', $application)
            ->with('success', __('messages.decision_changed'));
    }

    /**
     * Applications still awaiting a screening decision that this user may screen.
     * Correction-required ones are excluded: they are waiting on the applicant.
     *
     * @return Builder<Application>
     */
    private function pendingQueue(?User $user, ?string $vacancyId): Builder
    {
        $broad = $user?->hasAnyPermission(['applications.view-sensitive', 'applications.assign-reviewer']) ?? false;

        return Application::query()
            ->whereIn('status', [ApplicationStatus::Submitted->value, ApplicationStatus::UnderReview->value])
            ->when($vacancyId, fn (Builder $q) => $q->where('vacancy_id', $vacancyId))
            ->when(! $broad && $user, fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->whereNull('assigned_reviewer_id')
                ->orWhere('assigned_reviewer_id', $user->id)));
    }

    /**
     * The applicant after $current in queue order (newest first, same as the list),
     * wrapping around to the top when $current was the last one.
     */
    private function nextInQueue(Application $current, ?User $user, ?string $vacancyId): ?Application
    {
        $after = $this->pendingQueue($user, $vacancyId)
            ->whereKeyNot($current->id)
            ->where(fn (Builder $q) => $q
                ->where('created_at', '<', $current->created_at)
                ->orWhere(fn (Builder $q) => $q->where('created_at', $current->created_at)->where('id', '<', $current->id)))
            ->orderByDesc('created_at')->orderByDesc('id')
            ->first();

        return $after ?? $this->pendingQueue($user, $vacancyId)
            ->whereKeyNot($current->id)
            ->orderByDesc('created_at')->orderByDesc('id')
            ->first();
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
