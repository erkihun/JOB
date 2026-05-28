<?php

declare(strict_types=1);

namespace App\Http\Controllers\Applicant;

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
            ->where('status', VacancyStatus::Open)
            ->where('closing_date', '>=', now()->toDateString());

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->whereRaw("JSON_EXTRACT(title, '$.en') LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("JSON_EXTRACT(title, '$.am') LIKE ?", ["%{$search}%"]);
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
            $query->where('opening_date', '>=', $openingDate);
        }

        if ($closingDate = $request->input('closing_date')) {
            $query->where('closing_date', '<=', $closingDate);
        }

        $vacancies = $query->with('institution')->latest('published_at')->paginate(12)->withQueryString();

        $departments = Vacancy::where('status', VacancyStatus::Open)
            ->distinct()->pluck('department')->sort()->values();

        $employmentTypes = collect(EmploymentType::cases())->mapWithKeys(
            fn (EmploymentType $e) => [$e->value => $e->label()]
        );

        return view('applicant.vacancies.index', compact('vacancies', 'departments', 'employmentTypes'));
    }

    public function show(Vacancy $vacancy): View
    {
        abort_unless($vacancy->status === VacancyStatus::Open, 404);

        $canApply = $vacancy->canAcceptApplications();
        $alreadyApplied = auth()->user()->applicant?->hasAppliedTo($vacancy) ?? false;

        return view('applicant.vacancies.show', compact('vacancy', 'canApply', 'alreadyApplied'));
    }
}
