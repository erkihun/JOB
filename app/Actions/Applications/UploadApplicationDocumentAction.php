<?php

declare(strict_types=1);

namespace App\Actions\Applications;

use App\Enums\DocumentVerificationStatus;
use App\Models\Application;
use App\Models\ApplicationDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadApplicationDocumentAction
{
    public function handle(
        Application $application,
        string $vacancyDocumentId,
        UploadedFile $file,
    ): ApplicationDocument {
        $extension = strtolower($file->getClientOriginalExtension());
        $fileName = Str::orderedUuid().'.'.$extension;
        $directory = 'applications/'.$application->id.'/documents';
        $filePath = $directory.'/'.$fileName;

        Storage::disk('local')->putFileAs($directory, $file, $fileName);

        return ApplicationDocument::create([
            'application_id' => $application->id,
            'vacancy_document_id' => $vacancyDocumentId,
            'file_name' => $fileName,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_type' => $extension,
            'file_size' => $file->getSize(),
            'verification_status' => DocumentVerificationStatus::Pending,
        ]);
    }
}
