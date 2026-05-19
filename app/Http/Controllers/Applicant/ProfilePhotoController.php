<?php

declare(strict_types=1);

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ProfilePhotoController extends Controller
{
    public function __invoke(): Response
    {
        $applicant = auth()->user()?->applicant;

        abort_unless($applicant && $applicant->profile_photo_path, 404);
        abort_unless(Storage::disk('local')->exists($applicant->profile_photo_path), 404);

        $content = Storage::disk('local')->get($applicant->profile_photo_path);
        $mimeType = Storage::disk('local')->mimeType($applicant->profile_photo_path) ?: 'image/jpeg';

        return response($content, 200)->header('Content-Type', $mimeType);
    }
}
