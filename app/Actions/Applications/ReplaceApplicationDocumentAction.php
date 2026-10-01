<?php

declare(strict_types=1);

namespace App\Actions\Applications;

use App\Actions\Audit\LogAuditAction;
use App\Enums\DocumentVerificationStatus;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Services\Recruitment\RecruitmentTimelineService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ReplaceApplicationDocumentAction
{
    public function __construct(
        private readonly RecruitmentTimelineService $timeline,
        private readonly LogAuditAction $auditLogger,
    ) {}

    public function handle(ApplicationDocument $document, UploadedFile $file): ApplicationDocument
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $fileName = Str::orderedUuid().'.'.$extension;
        $directory = 'applications/'.$document->application_id.'/documents';
        $filePath = $directory.'/'.$fileName;

        // Store the new file first so a failed upload never leaves the record without one.
        Storage::disk('local')->putFileAs($directory, $file, $fileName);

        try {
            $oldPath = DB::transaction(function () use ($document, $file, $fileName, $filePath, $extension): string {
                // Re-check the deadline against row-locked data: after closing,
                // documents can no longer be replaced.
                $application = Application::whereKey($document->application_id)->lockForUpdate()->firstOrFail();
                $application->load('vacancy');
                $application->vacancy->setRelation('announcement', $application->vacancy->announcement()->lockForUpdate()->first());
                $this->timeline->assertCanEditApplication($application, 'file');

                $oldPath = $document->file_path;
                $document->update([
                    'file_name' => $fileName,
                    'original_name' => $file->getClientOriginalName(),
                    'file_path' => $filePath,
                    'file_type' => $extension,
                    'file_size' => $file->getSize(),
                    'verification_status' => DocumentVerificationStatus::Pending,
                    'verification_remark' => null,
                    'verified_by' => null,
                    'verified_at' => null,
                ]);

                $this->auditLogger->handle(
                    action: 'application_document_replaced',
                    module: 'applications',
                    recordId: $application->id,
                    oldValues: ['document_id' => $document->id],
                    newValues: ['document_id' => $document->id, 'original_name' => $file->getClientOriginalName()],
                );

                return $oldPath;
            });
        } catch (Throwable $e) {
            Storage::disk('local')->delete($filePath);

            throw $e;
        }

        if ($oldPath !== $filePath && Storage::disk('local')->exists($oldPath)) {
            Storage::disk('local')->delete($oldPath);
        }

        return $document->fresh();
    }
}
