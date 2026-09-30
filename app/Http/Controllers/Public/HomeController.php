<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\HeroSlider;
use App\Models\RecruitmentAnnouncement;
use App\Models\Vacancy;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $sliders = HeroSlider::active()->get();

        $openVacancies = Vacancy::query()
            ->acceptingApplications();

        $vacancies = (clone $openVacancies)
            ->with(['institution', 'announcement'])
            ->latest('published_at')
            ->limit(6)
            ->get();

        // Deadlines first: the most useful thing to see before scrolling.
        $closingSoon = (clone $openVacancies)
            ->with(['institution', 'announcement'])
            ->orderBy(RecruitmentAnnouncement::select('closing_date')->whereColumn('recruitment_announcements.id', 'vacancies.announcement_id'))
            ->limit(3)
            ->get();

        $departments = (clone $openVacancies)
            ->whereNotNull('department')->where('department', '!=', '')
            ->selectRaw('department, count(*) as vacancies_count, coalesce(sum(number_of_positions), 0) as positions_count')
            ->groupBy('department')
            ->orderByDesc('vacancies_count')->orderBy('department')
            ->limit(6)
            ->get();

        $stats = [
            'vacancies' => (clone $openVacancies)->count(),
            'positions' => (int) (clone $openVacancies)->sum('number_of_positions'),
            'institutions' => (clone $openVacancies)->whereNotNull('institution_id')->distinct()->count('institution_id'),
        ];

        $announcements = RecruitmentAnnouncement::query()
            ->where('status', 'published')->where('published_at', '<=', now())
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('public.home', compact('sliders', 'vacancies', 'closingSoon', 'departments', 'announcements', 'stats'));
    }
}
