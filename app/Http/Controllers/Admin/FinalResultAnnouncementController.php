<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Notifications\SendApplicantNotificationAction;
use App\Enums\ApplicationStatus;
use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\FinalResult\AnnounceFinalResultRequest;
use App\Models\Application;
use App\Models\Vacancy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinalResultAnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $vacancies = Vacancy::query()
            ->whereHas('applications', fn ($query) => $query->whereIn('status', $this->finalStatuses()))
            ->orderBy('title->en')
            ->get(['id', 'title', 'code']);

        $selectedVacancyId = $request->string('vacancy_id')->toString();

        $applications = collect();

        if ($selectedVacancyId !== '') {
            $applications = Application::query()
                ->with(['applicant', 'vacancy'])
                ->where('vacancy_id', $selectedVacancyId)
                ->whereIn('status', $this->finalStatuses())
                ->latest('updated_at')
                ->get();
        }

        return view('admin.final-results.index', compact('vacancies', 'selectedVacancyId', 'applications'));
    }

    public function store(
        AnnounceFinalResultRequest $request,
        SendApplicantNotificationAction $notifications,
    ): RedirectResponse {
        $applications = Application::query()
            ->with(['applicant.user', 'vacancy'])
            ->whereIn('id', $request->input('application_ids', []))
            ->whereIn('status', $this->finalStatuses())
            ->get();

        foreach ($applications as $application) {
            if ($application->applicant === null) {
                continue;
            }

            $notifications->handle(
                applicant: $application->applicant,
                type: $this->notificationTypeFor($application->status),
                placeholders: [
                    'message' => $request->input('message', ''),
                ],
                application: $application,
                channel: $request->string('channel')->toString(),
            );
        }

        return redirect()
            ->back()
            ->with('success', __('messages.final_results_announced', ['count' => $applications->count()]));
    }

    /**
     * @return array<int, ApplicationStatus>
     */
    private function finalStatuses(): array
    {
        return [
            ApplicationStatus::Selected,
            ApplicationStatus::Waitlisted,
            ApplicationStatus::NotSelected,
        ];
    }

    private function notificationTypeFor(ApplicationStatus $status): NotificationType
    {
        return match ($status) {
            ApplicationStatus::Selected => NotificationType::Selected,
            ApplicationStatus::Waitlisted => NotificationType::Waitlisted,
            ApplicationStatus::NotSelected => NotificationType::NotSelected,
            default => NotificationType::General,
        };
    }
}
