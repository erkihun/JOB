<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApplicantProfileDocument;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminApplicantProfileDocumentPreviewController extends Controller
{
    public function __invoke(ApplicantProfileDocument $document): StreamedResponse
    {
        $this->authorize('view', $document);

        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        $mimeType = Storage::disk('local')->mimeType($document->file_path)
            ?: ($document->file_type ?? 'application/octet-stream');

        abort_unless(
            str_starts_with($mimeType, 'image/') || $mimeType === 'application/pdf',
            415,
        );

        $size = Storage::disk('local')->size($document->file_path);
        // Safe ASCII fallback for Content-Disposition filename
        $asciiName = preg_replace('/[^\x20-\x7E]/', '_', $document->original_name) ?? 'document.pdf';

        return response()->stream(
            function () use ($document): void {
                $stream = Storage::disk('local')->readStream($document->file_path);
                if (is_resource($stream)) {
                    while (! feof($stream)) {
                        echo fread($stream, 8192);
                        flush();
                    }
                    fclose($stream);
                }
            },
            200,
            [
                'Content-Type'        => $mimeType,
                'Content-Disposition' => 'inline; filename="' . addslashes($asciiName) . '"',
                'Content-Length'      => $size,
                'X-Frame-Options'     => 'SAMEORIGIN',
                'Cache-Control'       => 'private, max-age=300',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }
}
