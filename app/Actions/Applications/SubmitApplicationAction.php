<?php

declare(strict_types=1);

namespace App\Actions\Applications;

use App\Actions\Audit\LogAuditAction;
use App\Enums\ApplicationStatus;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\Vacancy;
use App\Services\Recruitment\ApplicationSnapshotService;
use App\Services\Recruitment\RecruitmentTimelineService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitApplicationAction
{
    public function __construct(
        private readonly UploadApplicationDocumentAction $uploadAction,
        private readonly RecruitmentTimelineService $timeline,
        private readonly ApplicationSnapshotService $snapshots,
        private readonly LogAuditAction $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, UploadedFile>  $files  keyed by vacancy_document_id
     *
     * @throws ValidationException on duplicate application (race condition) or closed vacancy
     */
    public function handle(
        Applicant $applicant,
        Vacancy $vacancy,
        array $data,
        array $files = [],
    ): Application {
        return DB::transaction(function () use ($applicant, $vacancy, $data, $files): Application {
            $vacancy->refresh();
            $vacancy->setRelation('announcement', $vacancy->announcement()->lockForUpdate()->first());
            // Re-verify the window inside the transaction, against the row-locked parent
            // announcement, so a request that started before the deadline but commits
            // after it (or races a status change) is still rejected.
            $this->timeline->assertCanSubmitApplication($vacancy);

            try {
                $application = Application::create([
                    'applicant_id' => $applicant->id,
                    'vacancy_id' => $vacancy->id,
                    'field_of_study' => $data['field_of_study'],
                    'graduation_date' => $data['graduation_date'],
                    'cgpa' => $data['cgpa'] ?? null,
                    'status' => ApplicationStatus::Submitted,
                    'submitted_at' => now(),
                ]);
            } catch (UniqueConstraintViolationException) {
                // Race condition: another concurrent request already inserted (applicant_id, vacancy_id).
                throw ValidationException::withMessages([
                    'vacancy' => [__('applications.duplicate_application')],
                ]);
            }

            // Any upload exception propagates out of the transaction, rolling back the application row.
            foreach ($files as $vacancyDocumentId => $file) {
                if ($file instanceof UploadedFile) {
                    $this->uploadAction->handle($application, (string) $vacancyDocumentId, $file);
                }
            }

            $this->snapshots->capture($application, $applicant);

            $this->auditLogger->handle(
                action: 'application_submitted',
                module: 'applications',
                recordId: $application->id,
                newValues: [
                    'vacancy_id' => $vacancy->id,
                    'announcement_id' => $vacancy->announcement_id,
                    'reference_number' => $application->reference_number,
                ],
            );

            return $application;
        });
    }
}
