<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\RegisterApplicantAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ApplicantRegisterRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ApplicantAuthController extends Controller
{
    public function showRegister(): View
    {
        abort_unless((bool) Setting::get('recruitment.allow_registration', true), 403);

        return view('applicant.auth.register');
    }

    public function register(ApplicantRegisterRequest $request, RegisterApplicantAction $action): RedirectResponse
    {
        abort_unless((bool) Setting::get('recruitment.allow_registration', true), 403);

        $user = $action->handle($request->validated());

        Auth::login($user);

        return redirect()->route('applicant.dashboard')
            ->with('success', __('auth.registered_successfully'));
    }

    public function showLogin(): View
    {
        return view('applicant.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => __('auth.failed'),
            ])->onlyInput('email');
        }

        $user = Auth::user();

        // Block admin users from applicant area
        if (! $user->hasRole('applicant')) {
            Auth::logout();

            return back()->withErrors([
                'email' => __('auth.not_an_applicant'),
            ]);
        }

        if (! $user->isActive()) {
            Auth::logout();

            return back()->withErrors([
                'email' => __('auth.account_inactive'),
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('applicant.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
