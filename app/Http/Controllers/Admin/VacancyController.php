<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\EducationLevel;
use App\Enums\EmploymentType;
use App\Enums\VacancyStatus;
use App\Http\Controllers\Controller;
use App\Models\Vacancy;
use App\Services\CodeGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VacancyController extends Controller
{
    public function __construct(private readonly CodeGeneratorService $codes) {}

    public function index(Request $request): View
    {
        $query = Vacancy::withCount('applications')->latest();

        if ($search = $request->query('search')) {
            $query->where(fn ($q) => $q->where('title->en', 'like', "%$search%")
                ->orWhere('code', 'like', "%$search%")
                ->orWhere('department', 'like', "%$search%"));
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return view('admin.vacancies.index', [
            'vacancies' => $query->paginate(20)->withQueryString(),
            'statuses' => VacancyStatus::cases(),
        ]);
    }

    public function create(): View
    {
        $autoCode = $this->codes->vacancyAutoGenerate();

        return view('admin.vacancies.create', [
            'vacancy' => new Vacancy,
            'statuses' => VacancyStatus::cases(),
            'educationLevels' => EducationLevel::cases(),
            'employmentTypes' => EmploymentType::cases(),
            'autoCode' => $autoCode,
            'codePreview' => $autoCode ? $this->codes->forVacancy() : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $autoCode = $this->codes->vacancyAutoGenerate();
        $data = $request->validate($this->rules(autoCode: $autoCode));
        $data['created_by'] = $request->user()->id;

        if ($autoCode) {
            $data['code'] = $this->codes->forVacancy();
        }

        Vacancy::create($data);

        return redirect()->route('admin.vacancies.index')
            ->with('success', __('messages.vacancy_created'));
    }

    public function show(Vacancy $vacancy): View
    {
        $vacancy->load('applications.applicant');

        return view('admin.vacancies.show', compact('vacancy'));
    }

    public function edit(Vacancy $vacancy): View
    {
        $autoCode = $this->codes->vacancyAutoGenerate();

        return view('admin.vacancies.edit', [
            'vacancy' => $vacancy,
            'statuses' => VacancyStatus::cases(),
            'educationLevels' => EducationLevel::cases(),
            'employmentTypes' => EmploymentType::cases(),
            'autoCode' => $autoCode,
            'codePreview' => null,
        ]);
    }

    public function update(Request $request, Vacancy $vacancy): RedirectResponse
    {
        $autoCode = $this->codes->vacancyAutoGenerate();
        $data = $request->validate($this->rules(ignoreId: $vacancy->id, autoCode: $autoCode));

        $vacancy->update($data);

        return redirect()->route('admin.vacancies.index')
            ->with('success', __('messages.vacancy_updated'));
    }

    public function destroy(Vacancy $vacancy): RedirectResponse
    {
        $vacancy->delete();

        return redirect()->route('admin.vacancies.index')
            ->with('success', __('messages.vacancy_deleted'));
    }

    private function rules(?string $ignoreId = null, bool $autoCode = false): array
    {
        $codeRule = $autoCode
            ? ['nullable', 'string', 'max:50']
            : ['required', 'string', 'max:50', Rule::unique('vacancies', 'code')->ignore($ignoreId)];

        return [
            'code' => $codeRule,
            'title' => ['required', 'array'],
            'title.en' => ['required', 'string', 'max:255'],
            'title.am' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string'],
            'employment_type' => ['nullable', 'string'],
            'number_of_positions' => ['required', 'integer', 'min:1'],
            'salary_grade' => ['nullable', 'string', 'max:100'],
            'field_of_study' => ['nullable', 'string', 'max:255'],
            'education_level' => ['nullable', 'string'],
            'minimum_experience' => ['nullable', 'integer', 'min:0'],
            'location' => ['required', 'array'],
            'location.en' => ['required', 'string', 'max:255'],
            'location.am' => ['nullable', 'string', 'max:255'],
            'opening_date' => ['required', 'date'],
            'closing_date' => ['required', 'date', 'after_or_equal:opening_date'],
            'description' => ['nullable', 'array'],
            'description.en' => ['nullable', 'string'],
            'description.am' => ['nullable', 'string'],
            'qualification_requirements' => ['nullable', 'array'],
            'qualification_requirements.en' => ['nullable', 'string'],
            'qualification_requirements.am' => ['nullable', 'string'],
        ];
    }
}
