<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Exams\AssignApplicantsToScheduleAction;
use App\Actions\Exams\RecordExamInterviewResultAction;
use App\Enums\ApplicationStatus;
use App\Enums\ExamInterviewType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExamInterview\RecordResultRequest;
use App\Models\Application;
use App\Models\ExamInterviewApplicant;
use App\Models\ExamInterviewSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class ExamInterviewResultController extends Controller
{
    public function schedules(): View
    {
        $schedules = ExamInterviewSchedule::with('vacancy')
            ->withCount('assignedApplicants')
            ->latest('date')
            ->paginate(20);

        return view('admin.schedules.result-index', compact('schedules'));
    }

    public function index(ExamInterviewSchedule $schedule): View
    {
        $schedule->load([
            'vacancy',
            'assignedApplicants.application.applicant',
        ]);

        $eligibleApplications = Application::query()
            ->with('applicant')
            ->where('vacancy_id', $schedule->vacancy_id)
            ->whereIn('status', $this->eligibleStatusesFor($schedule))
            ->whereDoesntHave('examInterviewApplicants', function ($query) use ($schedule): void {
                $query->where('schedule_id', $schedule->id);
            })
            ->latest('submitted_at')
            ->get();

        return view('admin.schedules.results', compact('schedule', 'eligibleApplications'));
    }

    public function assignApplicants(
        Request $request,
        ExamInterviewSchedule $schedule,
        AssignApplicantsToScheduleAction $action,
    ): RedirectResponse {
        $data = $request->validate([
            'application_ids' => ['required', 'array', 'min:1'],
            'application_ids.*' => ['required', 'uuid', 'exists:applications,id'],
        ]);

        $applications = Application::query()
            ->where('vacancy_id', $schedule->vacancy_id)
            ->whereIn('status', $this->eligibleStatusesFor($schedule))
            ->whereIn('id', $data['application_ids'])
            ->get();

        if ($applications->count() !== count($data['application_ids'])) {
            return redirect()
                ->route('admin.schedules.results', $schedule)
                ->with('error', __('messages.invalid_applicant_selection'));
        }

        try {
            $action->handle($schedule, $applications);
        } catch (InvalidArgumentException) {
            return redirect()
                ->route('admin.schedules.results', $schedule)
                ->with('error', __('messages.invalid_applicant_selection'));
        }

        return redirect()
            ->route('admin.schedules.results', $schedule)
            ->with('success', __('messages.applicants_assigned'));
    }

    public function store(
        RecordResultRequest $request,
        ExamInterviewSchedule $schedule,
        ExamInterviewApplicant $applicantRecord,
        RecordExamInterviewResultAction $action,
    ): RedirectResponse {
        $action->handle(
            applicantRecord: $applicantRecord,
            status: $request->string('status')->toString(),
            score: $request->filled('score') ? (float) $request->input('score') : null,
            remark: $request->input('remark'),
        );

        return redirect()
            ->route('admin.schedules.results', $schedule)
            ->with('success', __('messages.result_saved'));
    }

    /**
     * @return array<int, ApplicationStatus>
     */
    private function eligibleStatusesFor(ExamInterviewSchedule $schedule): array
    {
        return match ($schedule->type) {
            ExamInterviewType::Exam => [
                ApplicationStatus::PassedScreening,
                ApplicationStatus::ShortlistedExam,
            ],
            ExamInterviewType::Interview => [
                ApplicationStatus::ExamCompleted,
                ApplicationStatus::ShortlistedInterview,
            ],
        };
    }
}
