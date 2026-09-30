<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Announcements\SaveRecruitmentAnnouncementAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVacancyAnnouncementRequest;
use App\Http\Requests\Admin\UpdateVacancyAnnouncementRequest;
use App\Models\Institution;
use App\Models\RecruitmentAnnouncement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VacancyAnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $query = RecruitmentAnnouncement::with('author')->latest();

        if ($search = $request->query('search')) {
            $query->where('subject', 'like', "%$search%");
        }

        $announcements = $query->paginate(20)->withQueryString();

        return view('admin.announcements.index', compact('announcements'));
    }

    public function create(): View
    {
        return view('admin.announcements.create', ['institutions' => Institution::where('status', 'active')->orderBy('name')->get()]);
    }

    public function store(StoreVacancyAnnouncementRequest $request, SaveRecruitmentAnnouncementAction $action): RedirectResponse
    {
        $action->handle(new RecruitmentAnnouncement, $request->validated(), $request->user()->id);

        return redirect()->route('admin.announcements.index')
            ->with('success', __('messages.announcement_created'));
    }

    public function show(RecruitmentAnnouncement $announcement): View
    {
        $announcement->load(['institutions', 'vacancies.institution']);

        return view('admin.announcements.show', compact('announcement'));
    }

    public function edit(RecruitmentAnnouncement $announcement): View
    {
        $announcement->load(['institutions', 'vacancies.institution']);

        return view('admin.announcements.edit', [
            'announcement' => $announcement,
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
