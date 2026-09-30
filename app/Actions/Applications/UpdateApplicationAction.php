<?php

declare(strict_types=1);

namespace App\Actions\Applications;

use App\Actions\Audit\LogAuditAction;
use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Vacancy;
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

    public function __construct(private readonly LogAuditAction $auditLogger) {}

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
                $application->refresh()->load('vacancy');
                $vacancy = $application->vacancy;
                $vacancy->setRelation('announcement', $vacancy->announcement()->lockForUpdate()->first());
                if (! $application->isEditable()) {
                    throw ValidationException::withMessages([
                        'application' => __('applications.deadline_locked'),
                    ]);
                }
                if (isset($attributes['vacancy_id'])) {
                    $target = Vacancy::find($attributes['vacancy_id']);
                    if ($target) {
                        $target->setRelation('announcement', $target->announcement()->lockForUpdate()->first());
                    }
                    if (! $target?->canAcceptApplications()) {
                        throw ValidationException::withMessages([
                            'vacancy_id' => __('vacancies.not_accepting_applications'),
                        ]);
                    }
                }
                $application->update($attributes);

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

        return $application->fresh();
    }
}
