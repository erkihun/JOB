<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Vacancies\SyncVacancyRequirementsAction;
use App\Enums\EducationLevel;
use App\Enums\EmploymentType;
use App\Enums\VacancyStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveVacancyRequest;
use App\Models\Institution;
use App\Models\RecruitmentAnnouncement;
use App\Models\Vacancy;
use App\Services\CodeGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VacancyController extends Controller
{
    public function __construct(
        private readonly CodeGeneratorService $codes,
        private readonly SyncVacancyRequirementsAction $syncRequirements,
    ) {}

    public function index(Request $request): View
    {
        $query = Vacancy::with('institution')->withCount('applications')->latest();

        if ($search = $request->query('search')) {
            $query->where(fn ($q) => $q->where('title->en', 'like', "%$search%")
                ->orWhere('code', 'like', "%$search%")
                ->orWhere('department', 'like', "%$search%"));
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($institutionId = $request->query('institution_id')) {
            $query->where('institution_id', $institutionId);
        }

        return view('admin.vacancies.index', [
            'vacancies' => $query->paginate(20)->withQueryString(),
            'statuses' => VacancyStatus::cases(),
            'institutions' => Institution::orderBy('name')->get(['id', 'name', 'short_name']),
        ]);
    }

    public function create(Request $request): View
    {
        $autoCode = $this->codes->vacancyAutoGenerate();

        return view('admin.vacancies.create', [
            'vacancy' => new Vacancy(['announcement_id' => $request->integer('announcement_id') ?: null]),
            'announcements' => RecruitmentAnnouncement::whereNotNull('opening_date')->whereNotNull('closing_date')->with('institutions')->latest()->get(),
            'statuses' => VacancyStatus::cases(),
            'educationLevels' => EducationLevel::cases(),
            'employmentTypes' => EmploymentType::cases(),
            'institutions' => Institution::where('status', 'active')->orderBy('name')->get(['id', 'name', 'short_name']),
            'autoCode' => $autoCode,
            'codePreview' => $autoCode ? $this->codes->forVacancy() : null,
        ]);
    }

    public function store(SaveVacancyRequest $request): RedirectResponse
    {
        $autoCode = $this->codes->vacancyAutoGenerate();
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        if ($autoCode) {
            $data['code'] = $this->codes->forVacancy();
        }

        $data = $this->stampPublishedAt($data, null);

        DB::transaction(function () use ($data): void {
            $vacancy = Vacancy::create(Arr::except($data, 'requirement_groups'));
            $this->syncRequirements->handle($vacancy, $data['requirement_groups'] ?? []);
        });

        return redirect()->route('admin.vacancies.index')
            ->with('success', __('messages.vacancy_created'));
    }

    public function show(Vacancy $vacancy): View
    {
        $vacancy->load(['applications.applicant', 'requirementGroups.requirements', 'announcement', 'institution']);

        return view('admin.vacancies.show', compact('vacancy'));
    }

    public function edit(Vacancy $vacancy): View
    {
        $autoCode = $this->codes->vacancyAutoGenerate();

        $vacancy->load('requirementGroups.requirements');

        return view('admin.vacancies.edit', [
            'vacancy' => $vacancy,
            'announcements' => RecruitmentAnnouncement::whereNotNull('opening_date')->whereNotNull('closing_date')->with('institutions')->latest()->get(),
            'statuses' => VacancyStatus::cases(),
            'educationLevels' => EducationLevel::cases(),
            'employmentTypes' => EmploymentType::cases(),
            'institutions' => Institution::where('status', 'active')->orderBy('name')->get(['id', 'name', 'short_name']),
            'autoCode' => $autoCode,
            'codePreview' => null,
        ]);
    }

    public function update(SaveVacancyRequest $request, Vacancy $vacancy): RedirectResponse
    {
        $data = $request->validated();
        $data = $this->stampPublishedAt($data, $vacancy);

        DB::transaction(function () use ($vacancy, $data, $request): void {
            $vacancy->update(Arr::except($data, 'requirement_groups'));

            // Only touch requirement options when the form sent them, so older
            // clients/integrations that don't know about options keep them intact.
            if ($request->has('requirement_groups_submitted')) {
                $this->syncRequirements->handle($vacancy, $data['requirement_groups'] ?? []);
            }
        });

        return redirect()->route('admin.vacancies.index')
            ->with('success', __('messages.vacancy_updated'));
    }

    public function destroy(Vacancy $vacancy): RedirectResponse
    {
        $vacancy->delete();

        return redirect()->route('admin.vacancies.index')
            ->with('success', __('messages.vacancy_deleted'));
    }

    /**
     * Record when a vacancy is first opened, so public listings ("latest") order it correctly.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function stampPublishedAt(array $data, ?Vacancy $vacancy): array
    {
        if (($data['status'] ?? null) === VacancyStatus::Open->value && $vacancy?->published_at === null) {
            $data['published_at'] = now();
        }

        return $data;
    }
}
