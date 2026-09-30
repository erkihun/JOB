<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\RecruitmentAnnouncement;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        $announcements = RecruitmentAnnouncement::query()
            ->where('status', 'published')->where('published_at', '<=', now())
            ->latest('published_at')
            ->paginate(12);

        return view('public.announcements.index', compact('announcements'));
    }

    public function show(RecruitmentAnnouncement $announcement): View
    {
        abort_unless($announcement->isPublished(), 404);

        $announcement->load(['vacancies' => fn ($q) => $q->where('status', 'open')->with('institution')]);

        return view('public.announcements.show', compact('announcement'));
    }
}
