<?php

declare(strict_types=1);

namespace App\Actions\Applications;

use App\Enums\DocumentVerificationStatus;
use App\Models\ApplicationDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReplaceApplicationDocumentAction
{
    public function handle(ApplicationDocument $document, UploadedFile $file): ApplicationDocument
    {
        // Delete old file from private disk
        if (Storage::disk('local')->exists($document->file_path)) {
            Storage::disk('local')->delete($document->file_path);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $fileName = Str::orderedUuid().'.'.$extension;
        $applicationId = $document->application_id;
        $directory = 'applications/'.$applicationId.'/documents';
        $filePath = $directory.'/'.$fileName;

        Storage::disk('local')->putFileAs($directory, $file, $fileName);

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

        return $document->fresh();
    }
}
