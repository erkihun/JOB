<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;
use PragmaRX\Google2FALaravel\Support\Authenticator;

class AdminAuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check() && $this->canAccessAdmin(Auth::user())) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => __('auth.failed')])->onlyInput('email');
        }

        $user = Auth::user();

        if (! $this->canAccessAdmin($user)) {
            Auth::logout();
            $request->session()->invalidate();

            return back()->withErrors(['email' => __('auth.not_authorized')])->onlyInput('email');
        }

        $request->session()->regenerate();

        // If 2FA is configured, redirect to the login-step challenge before entering the panel
        if ($user->hasTwoFactorEnabled()) {
            return redirect()->route('admin.login.two-factor');
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    public function showTwoFactorChallenge(Request $request): View|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user || ! $user->hasTwoFactorEnabled()) {
            return redirect()->route('admin.dashboard');
        }

        if (session(config('google2fa.session_var').'.auth_passed')) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return view('admin.two-factor.challenge');
    }

    public function verifyTwoFactorChallenge(Request $request): RedirectResponse
    {
        $request->validate(['one_time_password' => ['required', 'digits:6']]);

        /** @var User|null $user */
        $user = Auth::user();

        if (! $user || ! $user->hasTwoFactorEnabled()) {
            return redirect()->route('admin.login');
        }

        $valid = app(Google2FA::class)->verifyKey(
            (string) $user->google2fa_secret,
            $request->input('one_time_password')
        );

        if (! $valid) {
            return back()
                ->withErrors(['one_time_password' => 'Invalid verification code. Please try again.'])
                ->withInput();
        }

        app(Authenticator::class)->boot($request)->login();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    private function canAccessAdmin(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->hasRole('applicant')) {
            return false;
        }

        return $user->status === UserStatus::Active && $user->roles->isNotEmpty();
    }
}
