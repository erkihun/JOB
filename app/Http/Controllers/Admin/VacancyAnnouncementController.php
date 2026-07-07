<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVacancyAnnouncementRequest;
use App\Http\Requests\Admin\UpdateVacancyAnnouncementRequest;
use App\Models\VacancyAnnouncement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VacancyAnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $query = VacancyAnnouncement::with('author')->latest();

        if ($search = $request->query('search')) {
            $query->where('subject', 'like', "%$search%");
        }

        $announcements = $query->paginate(20)->withQueryString();

        return view('admin.announcements.index', compact('announcements'));
    }

    public function create(): View
    {
        return view('admin.announcements.create');
    }

    public function store(StoreVacancyAnnouncementRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $status = $data['status'];

        // Auto-set published_at to now when publishing for the first time
        // and no explicit date was provided.
        $publishedAt = $data['published_at'] ?? null;
        if ($status === 'published' && $publishedAt === null) {
            $publishedAt = now();
        }

        VacancyAnnouncement::create([
            'subject'      => $data['subject'],
            'content'      => $data['content'],
            'status'       => $status,
            'published_at' => $publishedAt,
            'created_by'   => auth()->id(),
        ]);

        return redirect()->route('admin.announcements.index')
            ->with('success', __('messages.announcement_created'));
    }

    public function show(VacancyAnnouncement $announcement): View
    {
        return view('admin.announcements.show', compact('announcement'));
    }

    public function edit(VacancyAnnouncement $announcement): View
    {
        return view('admin.announcements.edit', compact('announcement'));
    }

    public function update(UpdateVacancyAnnouncementRequest $request, VacancyAnnouncement $announcement): RedirectResponse
    {
        $data = $request->validated();
        $status = $data['status'];

        // Preserve the original published_at when re-saving a published announcement
        // without an explicit date. Set it now when publishing for the first time.
        $publishedAt = $data['published_at'] ?? null;
        if ($status === 'published' && $publishedAt === null) {
            $publishedAt = $announcement->published_at ?? now();
        }
        if ($status === 'draft') {
            $publishedAt = null;
        }

        $announcement->update([
            'subject'      => $data['subject'],
            'content'      => $data['content'],
            'status'       => $status,
            'published_at' => $publishedAt,
        ]);

        return redirect()->route('admin.announcements.index')
            ->with('success', __('messages.announcement_updated'));
    }

    public function destroy(VacancyAnnouncement $announcement): RedirectResponse
    {
        $announcement->delete();

        return redirect()->route('admin.announcements.index')
            ->with('success', __('messages.announcement_deleted'));
    }
}
