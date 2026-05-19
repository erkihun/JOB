<?php

declare(strict_types=1);

namespace App\Http\Controllers\Applicant;

use App\Actions\Applicants\UpdateApplicantProfileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Applicant\UpdateApplicantProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ApplicantProfileController extends Controller
{
    public function show(): View
    {
        $applicant = auth()->user()->applicant;

        return view('applicant.profile.show', compact('applicant'));
    }

    public function edit(): View
    {
        $applicant = auth()->user()->applicant;

        return view('applicant.profile.edit', compact('applicant'));
    }

    public function update(
        UpdateApplicantProfileRequest $request,
        UpdateApplicantProfileAction $action,
    ): RedirectResponse {
        $action->handle(auth()->user()->applicant, $request->validated());

        return redirect()->route('applicant.profile.show')
            ->with('success', __('messages.profile_updated'));
    }
}
