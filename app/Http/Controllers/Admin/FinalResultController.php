<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\FinalResult;
use App\Models\Setting;
use App\Models\Vacancy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinalResultController extends Controller
{
    public function index(Request $request): View
    {
        $vacancyId = $request->query('vacancy_id', '');

        $vacancies = Vacancy::orderBy('created_at', 'desc')->get();

        $eligibleStatuses = [
            ApplicationStatus::ShortlistedExam,
            ApplicationStatus::ExamCompleted,
            ApplicationStatus::ShortlistedInterview,
            ApplicationStatus::InterviewCompleted,
            ApplicationStatus::Selected,
            ApplicationStatus::Waitlisted,
            ApplicationStatus::NotSelected,
        ];

        $applications = Application::query()
            ->with(['applicant', 'vacancy', 'finalResult'])
            ->whereIn('status', $eligibleStatuses)
            ->when($vacancyId !== '', fn ($q) => $q->where('vacancy_id', $vacancyId))
            ->latest('submitted_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.final-results.index', compact('applications', 'vacancies', 'vacancyId'));
    }

    public function create(Application $application): View
    {
        abort_unless(
            ! in_array($application->status, [
                ApplicationStatus::Submitted,
                ApplicationStatus::UnderReview,
                ApplicationStatus::CorrectionRequired,
                ApplicationStatus::FailedScreening,
                ApplicationStatus::Withdrawn,
            ], true),
            403,
            'Application has not passed screening.'
        );

        $examWeight = (float) Setting::get('results.exam_weight', 60);
        $interviewWeight = (float) Setting::get('results.interview_weight', 40);

        $result = $application->finalResult;

        return view('admin.final-results.create', compact('application', 'examWeight', 'interviewWeight', 'result'));
    }

    public function store(Request $request, Application $application): RedirectResponse
    {
        abort_unless(
            ! in_array($application->status, [
                ApplicationStatus::Submitted,
                ApplicationStatus::UnderReview,
                ApplicationStatus::CorrectionRequired,
                ApplicationStatus::FailedScreening,
                ApplicationStatus::Withdrawn,
            ], true),
            403
        );

        $data = $request->validate([
            'exam_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'interview_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'exam_weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'interview_weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'decision' => ['required', 'string', 'in:selected,waitlisted,not_selected'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['final_score'] = FinalResult::computeFinalScore(
            isset($data['exam_score']) ? (float) $data['exam_score'] : null,
            isset($data['interview_score']) ? (float) $data['interview_score'] : null,
            (float) $data['exam_weight'],
            (float) $data['interview_weight'],
        );

        $data['recorded_by'] = auth()->id();

        $application->finalResult()->updateOrCreate(
            ['application_id' => $application->id],
            $data
        );

        // Update application status to match decision
        $statusMap = [
            'selected' => ApplicationStatus::Selected,
            'waitlisted' => ApplicationStatus::Waitlisted,
            'not_selected' => ApplicationStatus::NotSelected,
        ];

        $application->update(['status' => $statusMap[$data['decision']]]);

        return redirect()
            ->route('admin.final-results.index')
            ->with('success', __('messages.result_saved'));
    }

    public function edit(Application $application): View
    {
        $result = $application->finalResult;

        abort_if($result === null, 404);

        $examWeight = (float) ($result->exam_weight ?? Setting::get('results.exam_weight', 60));
        $interviewWeight = (float) ($result->interview_weight ?? Setting::get('results.interview_weight', 40));

        return view('admin.final-results.create', compact('application', 'examWeight', 'interviewWeight', 'result'));
    }

    public function update(Request $request, Application $application): RedirectResponse
    {
        $result = $application->finalResult;
        abort_if($result === null, 404);

        $data = $request->validate([
            'exam_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'interview_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'exam_weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'interview_weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'decision' => ['required', 'string', 'in:selected,waitlisted,not_selected'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['final_score'] = FinalResult::computeFinalScore(
            isset($data['exam_score']) ? (float) $data['exam_score'] : null,
            isset($data['interview_score']) ? (float) $data['interview_score'] : null,
            (float) $data['exam_weight'],
            (float) $data['interview_weight'],
        );

        $data['recorded_by'] = auth()->id();

        $result->update($data);

        $statusMap = [
            'selected' => ApplicationStatus::Selected,
            'waitlisted' => ApplicationStatus::Waitlisted,
            'not_selected' => ApplicationStatus::NotSelected,
        ];

        $application->update(['status' => $statusMap[$data['decision']]]);

        return redirect()
            ->route('admin.final-results.index')
            ->with('success', __('messages.result_saved'));
    }
}
