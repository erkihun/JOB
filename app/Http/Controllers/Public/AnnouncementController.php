<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\VacancyAnnouncement;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        $announcements = VacancyAnnouncement::query()
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->paginate(12);

        return view('public.announcements.index', compact('announcements'));
    }

    public function show(VacancyAnnouncement $announcement): View
    {
        abort_unless($announcement->isPublished(), 404);

        return view('public.announcements.show', compact('announcement'));
    }
}
