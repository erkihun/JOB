<?php

declare(strict_types=1);

namespace App\Http\Controllers\Applicant;

use App\Actions\Applications\ReplaceApplicationDocumentAction;
use App\Actions\Applications\SubmitApplicationAction;
use App\Actions\Applications\UpdateApplicationAction;
use App\Enums\VacancyStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Application\ReplaceDocumentRequest;
use App\Http\Requests\Application\StoreApplicationRequest;
use App\Http\Requests\Application\UpdateApplicationRequest;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Vacancy;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function index(): View
    {
        $applications = auth()->user()->applicant
            ->applications()
            ->with(['vacancy'])
            ->latest('submitted_at')
            ->paginate(15)
            ->withQueryString();

        return view('applicant.applications.index', compact('applications'));
    }

    public function create(Vacancy $vacancy): View|RedirectResponse
    {
        abort_unless($vacancy->status === VacancyStatus::Open, 404);

        if (! $vacancy->canAcceptApplications()) {
            return redirect()->route('vacancies.show', $vacancy)
                ->with('error', __('vacancies.deadline_passed'));
        }

        $applicant = auth()->user()->applicant;

        if ($applicant->hasAppliedTo($vacancy)) {
            return redirect()->route('applicant.applications.index')
                ->with('error', __('applications.duplicate_application'));
        }

        $requiredDocuments = $vacancy->requiredDocuments;

        return view('applicant.applications.create', compact('vacancy', 'requiredDocuments'));
    }

    public function store(
        StoreApplicationRequest $request,
        Vacancy $vacancy,
        SubmitApplicationAction $action,
    ): RedirectResponse {
        abort_unless($vacancy->status === VacancyStatus::Open, 422);

        if (! $vacancy->canAcceptApplications()) {
            return redirect()->route('vacancies.show', $vacancy)
                ->with('error', __('vacancies.deadline_passed'));
        }

        $applicant = auth()->user()->applicant;

        if ($applicant->hasAppliedTo($vacancy)) {
            return redirect()->route('applicant.applications.index')
                ->with('error', __('applications.duplicate_application'));
        }

        $application = $action->handle(
            $applicant,
            $vacancy,
            $request->safe()->except('documents'),
            $request->file('documents', []),
        );

        return redirect()->route('applicant.applications.show', $application)
            ->with('success', __('applications.application_submitted'));
    }

    public function show(Application $application): View
    {
        $this->authorize('view', $application);

        $application->load(['vacancy', 'vacancy.requiredDocuments', 'documents.vacancyDocument']);

        return view('applicant.applications.show', compact('application'));
    }

    public function edit(Application $application): View|RedirectResponse
    {
        $this->authorize('update', $application);

        if (! $application->isEditable()) {
            return redirect()->route('applicant.applications.show', $application)
                ->with('error', __('applications.deadline_locked'));
        }

        $application->load(['vacancy', 'vacancy.requiredDocuments', 'documents.vacancyDocument']);

        return view('applicant.applications.edit', compact('application'));
    }

    public function update(
        UpdateApplicationRequest $request,
        Application $application,
        UpdateApplicationAction $action,
    ): RedirectResponse {
        $action->handle($application, $request->validated());

        return redirect()->route('applicant.applications.show', $application)
            ->with('success', __('applications.application_updated'));
    }

    public function replaceDocument(
        ReplaceDocumentRequest $request,
        Application $application,
        ApplicationDocument $document,
        ReplaceApplicationDocumentAction $action,
    ): RedirectResponse {
        abort_unless($document->application_id === $application->id, 404);

        $action->handle($document, $request->file('file'));

        return redirect()->route('applicant.applications.edit', $application)
            ->with('success', __('applications.document_replaced'));
    }
}
