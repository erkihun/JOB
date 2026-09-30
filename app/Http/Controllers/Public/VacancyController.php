<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\EmploymentType;
use App\Enums\VacancyStatus;
use App\Http\Controllers\Controller;
use App\Models\Vacancy;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VacancyController extends Controller
{
    public function index(Request $request): View
    {
        $query = Vacancy::query()
            ->acceptingApplications();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->whereRaw("JSON_EXTRACT(title, '$.en') LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("JSON_EXTRACT(title, '$.am') LIKE ?", ["%{$search}%"])
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%")
                    ->orWhere('field_of_study', 'like', "%{$search}%");
            });
        }

        if ($department = $request->input('department')) {
            $query->where('department', $department);
        }

        if ($fieldOfStudy = $request->input('field_of_study')) {
            $query->where('field_of_study', 'like', "%{$fieldOfStudy}%");
        }

        if ($employmentType = $request->input('employment_type')) {
            $query->where('employment_type', $employmentType);
        }

        if ($location = $request->input('location')) {
            $query->where(function ($q) use ($location): void {
                $q->whereRaw("JSON_EXTRACT(location, '$.en') LIKE ?", ["%{$location}%"])
                    ->orWhereRaw("JSON_EXTRACT(location, '$.am') LIKE ?", ["%{$location}%"]);
            });
        }

        if ($openingDate = $request->input('opening_date')) {
            $query->whereHas('announcement', fn ($q) => $q->whereDate('opening_date', '>=', $openingDate));
        }

        if ($closingDate = $request->input('closing_date')) {
            $query->whereHas('announcement', fn ($q) => $q->whereDate('closing_date', '<=', $closingDate));
        }

        $vacancies = $query->with('institution')->latest('published_at')->paginate(12)->withQueryString();

        $departments = Vacancy::acceptingApplications()
            ->whereNotNull('department')
            ->distinct()
            ->pluck('department')
            ->sort()
            ->values();

        $employmentTypes = collect(EmploymentType::cases())->mapWithKeys(
            fn (EmploymentType $e) => [$e->value => $e->label()]
        );

        return view('public.vacancies.index', compact('vacancies', 'departments', 'employmentTypes'));
    }

    public function show(Vacancy $vacancy): View
    {
        abort_unless($vacancy->status === VacancyStatus::Open && $vacancy->announcement?->isPublished(), 404);

        $vacancy->load([
            'institution',
            'requiredDocuments',
            'requirementGroups' => fn ($q) => $q->where('is_active', true)->with('requirements'),
        ]);
        $canApply = $vacancy->canAcceptApplications();

        $alreadyApplied = false;
        if (auth()->check() && auth()->user()->hasRole('applicant')) {
            $applicant = auth()->user()->applicant;
            $alreadyApplied = $applicant?->hasAppliedTo($vacancy) ?? false;
        }

        return view('public.vacancies.show', compact('vacancy', 'canApply', 'alreadyApplied'));
    }
}
