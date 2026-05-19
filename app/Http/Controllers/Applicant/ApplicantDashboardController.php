<?php

declare(strict_types=1);

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicantDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $applicant = $request->user()->applicant;

        $applications = $applicant
            ? $applicant->applications()->with('vacancy')->latest()->get()
            : collect();

        $completionPct = $applicant ? $applicant->profileCompletionPercentage() : 0;
        $completionMissing = ($applicant && $completionPct < 100)
            ? $applicant->profileMissingFields()
            : [];

        return view('applicant.dashboard', compact(
            'applicant',
            'applications',
            'completionPct',
            'completionMissing',
        ));
    }
}
