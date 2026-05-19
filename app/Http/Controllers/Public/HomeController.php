<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\VacancyStatus;
use App\Http\Controllers\Controller;
use App\Models\HeroSlider;
use App\Models\Vacancy;
use App\Models\VacancyAnnouncement;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $sliders = HeroSlider::active()->get();

        $vacancies = Vacancy::query()
            ->where('status', VacancyStatus::Open)
            ->where('closing_date', '>=', now()->toDateString())
            ->latest('published_at')
            ->limit(6)
            ->get();

        $announcements = VacancyAnnouncement::query()
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('public.home', compact('sliders', 'vacancies', 'announcements'));
    }
}
