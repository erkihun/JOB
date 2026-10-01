<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Applications\ChangeApplicationLockAction;
use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Institution;
use App\Models\Vacancy;
use App\Services\Recruitment\RecruitmentTimelineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    /** Stage tabs on the list: tab key => statuses it covers. */
    private const STAGES = [
        'awaiting' => ['submitted', 'under_review', 'correction_required'],
        'passed' => ['passed_screening'],
        'failed' => ['failed_screening'],
        'assessment' => ['shortlisted_exam', 'exam_completed', 'shortlisted_interview', 'interview_completed'],
        'selected' => ['selected', 'waitlisted'],
    ];

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
        if ($institutionId = $request->get('institution_id')) {
            $query->whereHas('vacancy', fn ($q) => $q->where('institution_id', $institutionId));
        }

        // Stage tabs: counted over the other filters so each tab shows what it would list.
        $stages = self::STAGES;
        $statusCounts = (clone $query)->reorder()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $stageCounts = collect($stages)->map(fn (array $statuses) => (int) collect($statuses)->sum(fn ($s) => $statusCounts[$s] ?? 0));
        $stageCounts->prepend((int) $statusCounts->sum(), 'all');

        $stage = $request->get('stage');
        if (isset($stages[$stage])) {
            $query->whereIn('status', $stages[$stage]);
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $applications = $query->paginate(20)->withQueryString();
        $vacancies = Vacancy::orderBy('title->en')->get(['id', 'title', 'code']);
        $institutions = Institution::orderBy('name')->get(['id', 'name', 'short_name']);
        $statuses = ApplicationStatus::cases();

        return view('admin.applications.index', compact('applications', 'vacancies', 'institutions', 'statuses', 'canViewSensitive', 'stageCounts'));
    }

    public function show(Application $application): View
    {
        $canViewSensitive = auth()->user()?->hasPermissionTo('applications.view-sensitive') ?? false;

        $application->load(['applicant.profileDocuments', 'vacancy.announcement', 'vacancy.institution', 'documents.vacancyDocument', 'screeningReviews.reviewer', 'screener']);

        $timeline = app(RecruitmentTimelineService::class);
        $announcement = $application->vacancy?->announcement;
        // Reopening is offered only after the deadline, before a screening decision.
        $canReopen = $announcement !== null && ! $announcement->lifecycleStatus()->isTerminal()
            && $timeline->hasApplicationPeriodEnded($announcement)
            && in_array($application->status, RecruitmentTimelineService::AWAITING_SCREENING_STATUSES, true);

        return view('admin.applications.show', compact('application', 'canViewSensitive', 'canReopen'));
    }

    public function lock(Request $request, Application $application, ChangeApplicationLockAction $action): RedirectResponse
    {
        $data = $request->validate(['lock_reason' => ['required', 'string', 'min:5', 'max:1000']]);

        $action->lock($application, $request->user(), $data['lock_reason']);

        return redirect()->route('admin.applications.show', $application)->with('success', __('recruitment.application.locked'));
    }

    public function reopen(Request $request, Application $application, ChangeApplicationLockAction $action): RedirectResponse
    {
        $data = $request->validate([
            'reopened_until' => ['required', 'date_format:Y-m-d'],
            'reopen_reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $application = $action->reopen($application, $request->user(), $data['reopened_until'], $data['reopen_reason']);

        return redirect()->route('admin.applications.show', $application)
            ->with('success', __('recruitment.application.reopened', ['date' => et_date($application->reopened_until, 'M d, Y')]));
    }
}
