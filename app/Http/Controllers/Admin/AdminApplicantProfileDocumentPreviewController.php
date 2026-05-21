<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApplicantProfileDocument;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminApplicantProfileDocumentPreviewController extends Controller
{
    public function __invoke(ApplicantProfileDocument $document): BinaryFileResponse
    {
        $this->authorize('view', $document);

        $absolutePath = Storage::disk('local')->path($document->file_path);

        abort_unless(file_exists($absolutePath), 404);

        $mimeType = Storage::disk('local')->mimeType($document->file_path)
            ?: ($document->file_type ?? 'application/octet-stream');

        abort_unless(
            str_starts_with($mimeType, 'image/') || $mimeType === 'application/pdf',
            415,
        );

        return response()->file($absolutePath, [
            'Content-Type'        => $mimeType,
            'Content-Disposition' => 'inline; filename="' . rawurlencode($document->original_name) . '"',
            'X-Frame-Options'     => 'SAMEORIGIN',
            'Cache-Control'       => 'private, max-age=300',
        ]);
    }
}
