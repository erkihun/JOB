<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminApplicantController extends Controller
{
    public function index(Request $request): View
    {
        $query = Applicant::with('user')
            ->withCount('applications')
            ->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('national_id', 'like', "%{$search}%")
                    ->orWhere('applicant_code', 'like', "%{$search}%");
            });
        }

        $applicants = $query->paginate(20)->withQueryString();

        return view('admin.applicants.index', compact('applicants', 'search'));
    }

    public function show(Applicant $applicant): View
    {
        $applicant->load(['user', 'applications.vacancy', 'profileDocuments']);

        return view('admin.applicants.show', compact('applicant'));
    }

    public function photo(Applicant $applicant): Response
    {
        abort_unless($applicant->profile_photo_path, 404);
        abort_unless(Storage::disk('local')->exists($applicant->profile_photo_path), 404);

        $content = (string) Storage::disk('local')->get($applicant->profile_photo_path);
        $mimeType = Storage::disk('local')->mimeType($applicant->profile_photo_path) ?: 'image/jpeg';

        return response($content, 200)
            ->header('Content-Type', $mimeType)
            ->header('Cache-Control', 'private, max-age=3600');
    }
}
