<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Audit\LogAuditAction;
use App\Enums\ApplicationStatus;
use App\Enums\ExamInterviewType;
use App\Exceptions\RecruitmentRuleException;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\FinalResult;
use App\Models\Setting;
use App\Models\Vacancy;
use App\Services\Recruitment\RecruitmentStateMachine;
use App\Services\Recruitment\RecruitmentTimelineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FinalResultController extends Controller
{
    /** Applications in these statuses have not (or no longer) qualified for a final result. */
    private const INELIGIBLE_STATUSES = [
        ApplicationStatus::Submitted,
        ApplicationStatus::UnderReview,
        ApplicationStatus::CorrectionRequired,
        ApplicationStatus::FailedScreening,
        ApplicationStatus::Withdrawn,
    ];

    private const DECISION_STATUS = [
        'selected' => ApplicationStatus::Selected,
        'waitlisted' => ApplicationStatus::Waitlisted,
        'not_selected' => ApplicationStatus::NotSelected,
    ];

    public function __construct(
        private readonly LogAuditAction $auditLogger,
        private readonly RecruitmentTimelineService $timeline,
        private readonly RecruitmentStateMachine $stateMachine,
    ) {}

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
        $this->ensureEligible($application);

        $examWeight = (float) Setting::get('results.exam_weight', 60);
        $interviewWeight = (float) Setting::get('results.interview_weight', 40);
        $practicalWeight = (float) Setting::get('results.practical_weight', 0);

        $result = $application->finalResult;
        [$recordedExamScore, $recordedInterviewScore, $recordedPracticalScore] = $this->recordedScores($application);

        return view('admin.final-results.create', compact(
            'application', 'examWeight', 'interviewWeight', 'practicalWeight', 'result', 'recordedExamScore', 'recordedInterviewScore', 'recordedPracticalScore',
        ));
    }

    public function store(Request $request, Application $application): RedirectResponse
    {
        $this->ensureEligible($application);

        $this->saveResult($application, $this->validated($request));

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
        $practicalWeight = (float) ($result->practical_weight ?? Setting::get('results.practical_weight', 0));
        [$recordedExamScore, $recordedInterviewScore, $recordedPracticalScore] = $this->recordedScores($application);

        return view('admin.final-results.create', compact(
            'application', 'examWeight', 'interviewWeight', 'practicalWeight', 'result', 'recordedExamScore', 'recordedInterviewScore', 'recordedPracticalScore',
        ));
    }

    public function update(Request $request, Application $application): RedirectResponse
    {
        abort_if($application->finalResult === null, 404);
        $this->ensureEligible($application);

        $this->saveResult($application, $this->validated($request));

        return redirect()
            ->route('admin.final-results.index')
            ->with('success', __('messages.result_saved'));
    }

    private function ensureEligible(Application $application): void
    {
        abort_if(
            in_array($application->status, self::INELIGIBLE_STATUSES, true),
            403,
            'Application has not passed screening.'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'exam_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'interview_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'practical_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'exam_weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'interview_weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'practical_weight' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'decision' => ['required', 'string', 'in:selected,waitlisted,not_selected'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    /**
     * Save the result and the matching application status together, so the two
     * can never disagree, and record who made the decision.
     *
     * @param  array<string, mixed>  $data
     */
    private function saveResult(Application $application, array $data): void
    {
        $data['final_score'] = FinalResult::computeFinalScore(
            isset($data['exam_score']) ? (float) $data['exam_score'] : null,
            isset($data['interview_score']) ? (float) $data['interview_score'] : null,
            (float) $data['exam_weight'],
            (float) $data['interview_weight'],
            isset($data['practical_score']) ? (float) $data['practical_score'] : null,
            (float) ($data['practical_weight'] ?? 0),
        );
        $data['practical_weight'] = (float) ($data['practical_weight'] ?? 0);
        $data['recorded_by'] = auth()->id();

        DB::transaction(function () use ($application, $data): void {
            $application->setRawAttributes(Application::whereKey($application->id)->lockForUpdate()->firstOrFail()->getAttributes(), true);
            $previous = $application->finalResult;
            $previousStatus = $application->status;

            // The recruitment must be past its application period and not yet
            // finalized, and the decision must follow the completed stages.
            if ($key = $this->timeline->finalResultViolation($application)) {
                throw RecruitmentRuleException::because($key, 'decision');
            }
            $this->stateMachine->assertApplicationTransition($application, self::DECISION_STATUS[$data['decision']], 'decision');

            $application->finalResult()->updateOrCreate(
                ['application_id' => $application->id],
                $data
            );

            $application->update(['status' => self::DECISION_STATUS[$data['decision']]]);

            $this->auditLogger->handle(
                action: $previous ? 'final_result_updated' : 'final_result_recorded',
                module: 'final_results',
                recordId: $application->id,
                oldValues: [
                    'status' => $previousStatus?->value,
                    'decision' => $previous?->decision,
                    'final_score' => $previous?->final_score,
                ],
                newValues: [
                    'status' => $application->status?->value,
                    'decision' => $data['decision'],
                    'final_score' => $data['final_score'],
                ],
            );
        });
    }

    /**
     * Latest scores already recorded on the exam / interview schedule results, used
     * to pre-fill the final result form so they don't have to be retyped.
     *
     * @return array{0: float|null, 1: float|null, 2: float|null}
     */
    private function recordedScores(Application $application): array
    {
        $records = $application->examInterviewApplicants()
            ->with('schedule')
            ->whereNotNull('score')
            ->latest('updated_at')
            ->get();

        $scoreFor = function (ExamInterviewType $type) use ($records): ?float {
            $score = $records->first(fn ($record) => $record->schedule?->type === $type)?->score;

            return $score !== null ? (float) $score : null;
        };

        return [$scoreFor(ExamInterviewType::Exam), $scoreFor(ExamInterviewType::Interview), $scoreFor(ExamInterviewType::Practical)];
    }
}
