<?php

declare(strict_types=1);

namespace App\Actions\Applications;

use App\Actions\Audit\LogAuditAction;
use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Vacancy;
use App\Services\Recruitment\ApplicationSnapshotService;
use App\Services\Recruitment\RecruitmentTimelineService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateApplicationAction
{
    /**
     * Statuses produced by a screening decision. An edit made after one of these
     * decisions invalidates it, so the application goes back into the screening queue.
     */
    private const RESCREEN_STATUSES = [
        ApplicationStatus::CorrectionRequired,
        ApplicationStatus::PassedScreening,
        ApplicationStatus::FailedScreening,
    ];

    /**
     * Statuses in which the application may still be moved to another vacancy.
     * Past these, it is attached to that vacancy's exam/interview/results.
     */
    private const SWITCHABLE_STATUSES = [
        ApplicationStatus::Submitted,
        ApplicationStatus::UnderReview,
        ApplicationStatus::CorrectionRequired,
        ApplicationStatus::PassedScreening,
        ApplicationStatus::FailedScreening,
    ];

    public function __construct(
        private readonly LogAuditAction $auditLogger,
        private readonly RecruitmentTimelineService $timeline,
        private readonly ApplicationSnapshotService $snapshots,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Application $application, array $data): Application
    {
        $previousStatus = $application->status;

        $attributes = [
            'field_of_study' => $data['field_of_study'],
            'graduation_date' => $data['graduation_date'],
            'cgpa' => $data['cgpa'] ?? null,
            'last_updated_at' => now(),
        ];

        // Optional position switch: move the application to a different open vacancy.
        if (! empty($data['vacancy_id']) && $data['vacancy_id'] !== $application->vacancy_id) {
            if (! in_array($previousStatus, self::SWITCHABLE_STATUSES, true)) {
                throw ValidationException::withMessages([
                    'vacancy_id' => [__('applications.cannot_switch_after_shortlist')],
                ]);
            }

            $attributes['vacancy_id'] = $data['vacancy_id'];
        }

        $needsRescreen = in_array($previousStatus, self::RESCREEN_STATUSES, true);

        if ($needsRescreen) {
            $attributes['status'] = ApplicationStatus::UnderReview;
        }

        try {
            DB::transaction(function () use ($application, $attributes, $needsRescreen, $previousStatus): void {
                $application = Application::whereKey($application->id)->lockForUpdate()->firstOrFail();
                $application->load('vacancy');
                $vacancy = $application->vacancy;
                $vacancy->setRelation('announcement', $vacancy->announcement()->lockForUpdate()->first());
                // Deadline, admin lock and stage are re-checked against locked rows.
                $this->timeline->assertCanEditApplication($application);
                if (isset($attributes['vacancy_id'])) {
                    $target = Vacancy::find($attributes['vacancy_id']);
                    if ($target === null) {
                        throw ValidationException::withMessages([
                            'vacancy_id' => __('vacancies.not_accepting_applications'),
                        ]);
                    }
                    $target->setRelation('announcement', $target->announcement()->lockForUpdate()->first());
                    $this->timeline->assertCanSubmitApplication($target, 'vacancy_id');
                }
                $oldValues = $application->only(['vacancy_id', 'field_of_study', 'cgpa']) + [
                    'graduation_date' => $application->graduation_date?->toDateString(),
                ];
                $application->update($attributes);
                $this->snapshots->capture($application->refresh());

                $this->auditLogger->handle(
                    action: 'application_edited',
                    module: 'applications',
                    recordId: $application->id,
                    oldValues: $oldValues,
                    newValues: $application->only(['vacancy_id', 'field_of_study', 'cgpa']) + [
                        'graduation_date' => $application->graduation_date?->toDateString(),
                    ],
                );

                if ($needsRescreen) {
                    $this->auditLogger->handle(
                        action: 'application_resubmitted_for_screening',
                        module: 'applications',
                        recordId: $application->id,
                        oldValues: ['status' => $previousStatus?->value],
                        newValues: [
                            'status' => ApplicationStatus::UnderReview->value,
                            'vacancy_id' => $application->vacancy_id,
                        ],
                    );
                }
            });
        } catch (UniqueConstraintViolationException) {
            // Race: another application for (applicant, target vacancy) already exists.
            throw ValidationException::withMessages([
                'vacancy_id' => [__('applications.duplicate_application')],
            ]);
        }

        return Application::findOrFail($application->id);
    }
}
