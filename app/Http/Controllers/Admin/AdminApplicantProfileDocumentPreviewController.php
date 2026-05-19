<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApplicantProfileDocument;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class AdminApplicantProfileDocumentPreviewController extends Controller
{
    public function __invoke(ApplicantProfileDocument $document): Response
    {
        $this->authorize('view', $document);

        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        $mimeType = Storage::disk('local')->mimeType($document->file_path) ?? $document->file_type ?? 'application/octet-stream';

        abort_unless(
            str_starts_with($mimeType, 'image/') || $mimeType === 'application/pdf',
            415,
        );

        return response(Storage::disk('local')->get($document->file_path), 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.$document->original_name.'"',
            'X-Frame-Options' => 'SAMEORIGIN',
        ]);
    }
}
