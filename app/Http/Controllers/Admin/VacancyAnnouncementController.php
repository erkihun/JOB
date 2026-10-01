<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Announcements\ChangeRecruitmentStageAction;
use App\Actions\Announcements\ExtendRecruitmentDeadlineAction;
use App\Actions\Announcements\SaveRecruitmentAnnouncementAction;
use App\Enums\RecruitmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExtendRecruitmentDeadlineRequest;
use App\Http\Requests\Admin\StoreVacancyAnnouncementRequest;
use App\Http\Requests\Admin\UpdateVacancyAnnouncementRequest;
use App\Models\Institution;
use App\Models\RecruitmentAnnouncement;
use App\Services\Recruitment\RecruitmentTimelineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VacancyAnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $query = RecruitmentAnnouncement::with('author')->withCount(['vacancies', 'applications'])->latest();

        if ($search = $request->query('search')) {
            $query->where(fn ($q) => $q->where('subject', 'like', "%$search%")->orWhere('code', 'like', "%$search%"));
        }

        $today = app(RecruitmentTimelineService::class)->now()->toDateString();
        $published = RecruitmentStatus::Published->value;
        match ($request->query('state')) {
            // Legacy filters
            'published' => $query->publiclyVisible(),
            'scheduled' => $query->where('status', $published)->where('published_at', '>', now()),
            // Effective lifecycle stages
            'draft' => $query->where('status', RecruitmentStatus::Draft->value),
            'upcoming' => $query->where('status', $published)->where(fn ($q) => $q
                ->whereNull('published_at')->orWhere('published_at', '>', now())->orWhereDate('opening_date', '>', $today)),
            'open' => $query->acceptingApplications(),
            'closed' => $query->where(fn ($q) => $q->where('status', RecruitmentStatus::Closed->value)
                ->orWhere(fn ($q) => $q->where('status', $published)->whereDate('closing_date', '<', $today))),
            'screening', 'exam', 'interview', 'finalized', 'cancelled' => $query->where('status', $request->query('state')),
            default => null,
        };

        $announcements = $query->paginate(20)->withQueryString();

        return view('admin.announcements.index', compact('announcements'));
    }

    public function create(): View
    {
        return view('admin.announcements.create', [
            'institutions' => Institution::where('status', 'active')->orderBy('name')->get(),
            'datesLocked' => false,
        ]);
    }

    public function store(StoreVacancyAnnouncementRequest $request, SaveRecruitmentAnnouncementAction $action): RedirectResponse
    {
        $action->handle(new RecruitmentAnnouncement, $request->validated(), $request->user()->id);

        return redirect()->route('admin.announcements.index')
            ->with('success', __('messages.announcement_created'));
    }

    public function show(RecruitmentAnnouncement $announcement, RecruitmentTimelineService $timeline, ChangeRecruitmentStageAction $stages): View
    {
        $announcement->load(['institutions', 'vacancies.institution', 'deadlineExtensions.extendedBy'])
            ->loadCount('applications');

        // Next lifecycle moves, each with whether it is allowed right now and why not.
        $user = request()->user();
        $nextActions = collect($stages->candidateTargets($announcement))->map(fn (RecruitmentStatus $target) => [
            'target' => $target,
            'authorized' => $stages->isAuthorized($user, $target),
            'blocked' => $stages->violation($announcement, $target),
        ]);

        return view('admin.announcements.show', [
            'announcement' => $announcement,
            'stage' => $timeline->stage($announcement),
            'remainingDays' => $timeline->remainingDays($announcement),
            'nextActions' => $nextActions,
            'canCancel' => ! $announcement->lifecycleStatus()->isTerminal() && $stages->isAuthorized($user, RecruitmentStatus::Cancelled),
            'extensionBlocked' => $timeline->deadlineExtensionViolation($announcement),
            'canExtend' => $user->can('recruitment-announcements.extend-deadline'),
        ]);
    }

    public function transition(Request $request, RecruitmentAnnouncement $announcement, ChangeRecruitmentStageAction $action): RedirectResponse
    {
        // The browser only names a target; legality and permission are decided server-side.
        $data = $request->validate([
            'target' => ['required', Rule::in(['closed', 'screening', 'exam', 'interview', 'finalized', 'cancelled'])],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $action->handle($announcement, RecruitmentStatus::from($data['target']), $request->user(), $data['reason'] ?? null);

        return redirect()->route('admin.announcements.show', $announcement)
            ->with('success', __('recruitment.transition_done', ['stage' => $announcement->stage()->label()]));
    }

    public function extendDeadline(ExtendRecruitmentDeadlineRequest $request, RecruitmentAnnouncement $announcement, ExtendRecruitmentDeadlineAction $action): RedirectResponse
    {
        $extension = $action->handle(
            $announcement,
            $request->user(),
            $request->string('new_closing_date')->toString(),
            $request->string('reason')->toString(),
            $request->input('reference'),
        );

        return redirect()->route('admin.announcements.show', $announcement)
            ->with('success', __('recruitment.extension.done', [
                'date' => et_date($extension->new_closing_date, 'M d, Y'),
                'count' => $extension->notified_count,
            ]));
    }

    public function edit(RecruitmentAnnouncement $announcement): View
    {
        $announcement->load(['institutions', 'vacancies.institution']);

        return view('admin.announcements.edit', [
            'announcement' => $announcement,
            'datesLocked' => $announcement->lifecycleStatus() !== RecruitmentStatus::Draft,
            'institutions' => Institution::where('status', 'active')->orWhereIn('id', $announcement->institutions->modelKeys())->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateVacancyAnnouncementRequest $request, RecruitmentAnnouncement $announcement, SaveRecruitmentAnnouncementAction $action): RedirectResponse
    {
        $action->handle($announcement, $request->validated(), $request->user()->id);

        return redirect()->route('admin.announcements.index')
            ->with('success', __('messages.announcement_updated'));
    }

    public function destroy(RecruitmentAnnouncement $announcement): RedirectResponse
    {
        if ($announcement->vacancies()->withTrashed()->exists()) {
            throw ValidationException::withMessages(['announcement' => __('vacancies.announcement_has_vacancies')]);
        }
        $announcement->delete();

        return redirect()->route('admin.announcements.index')
            ->with('success', __('messages.announcement_deleted'));
    }
}
