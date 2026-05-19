<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicationTrackingController extends Controller
{
    public function show(): View
    {
        return view('public.track');
    }

    public function search(Request $request): View|RedirectResponse
    {
        $validated = $request->validate([
            'reference_number' => ['required', 'string', 'max:30'],
            'identifier' => ['required', 'string', 'max:100'],
        ]);

        $application = Application::with(['applicant', 'vacancy', 'documents'])
            ->where('reference_number', $validated['reference_number'])
            ->first();

        if (! $application) {
            return back()->withErrors(['reference_number' => __('applications.not_found')])->withInput();
        }

        $identifier = strtolower(trim($validated['identifier']));
        $email = strtolower(trim($application->applicant->email ?? ''));
        $phone = trim($application->applicant->phone ?? '');

        if ($identifier !== $email && $identifier !== $phone) {
            return back()->withErrors(['identifier' => __('applications.identifier_mismatch')])->withInput();
        }

        return view('public.track', compact('application'));
    }
}
