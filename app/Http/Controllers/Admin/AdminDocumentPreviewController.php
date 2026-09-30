<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApplicationDocument;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams an application document inline (images and PDFs only) so screeners can
 * read it directly on the review page. Same rules as the profile-document preview.
 */
class AdminDocumentPreviewController extends Controller
{
    public function __invoke(ApplicationDocument $document): StreamedResponse
    {
        $this->authorize('view', $document);

        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        $mimeType = Storage::disk('local')->mimeType($document->file_path)
            ?: ($document->file_type ?? 'application/octet-stream');

        abort_unless(str_starts_with($mimeType, 'image/') || $mimeType === 'application/pdf', 415);

        $asciiName = preg_replace('/[^\x20-\x7E]/', '_', (string) $document->original_name) ?: 'document';

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
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="'.addslashes($asciiName).'"',
                'Content-Length' => Storage::disk('local')->size($document->file_path),
                'X-Frame-Options' => 'SAMEORIGIN',
                'Cache-Control' => 'private, max-age=300',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }
}
