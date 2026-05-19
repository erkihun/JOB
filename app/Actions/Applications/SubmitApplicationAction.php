<?php

declare(strict_types=1);

namespace App\Actions\Applications;

use App\Enums\ApplicationStatus;
use App\Models\Applicant;
use App\Models\Application;
use App\Models\Vacancy;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitApplicationAction
{
    public function __construct(
        private readonly UploadApplicationDocumentAction $uploadAction,
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
            // Re-verify deadline inside the transaction so the check and insert are atomic.
            if (! $vacancy->canAcceptApplications()) {
                throw ValidationException::withMessages([
                    'vacancy' => [__('vacancies.deadline_passed')],
                ]);
            }

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

            return $application;
        });
    }
}
