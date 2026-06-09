<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Security\MfaSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class UnifiedAuthController extends Controller
{
    public function show(): View|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user !== null) {
            return redirect()->to($this->destinationFor($user));
        }

        return view('auth.login');
    }

    public function login(Request $request, MfaSettings $mfaSettings): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => __('auth.failed')])->onlyInput('email');
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $user->isActive()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => __('auth.account_inactive')])->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->put('auth.intended_destination', $this->destinationFor($user));
        $request->session()->put('auth.last_activity_at', now()->timestamp);

        AuditLog::record('login', 'auth', (string) $user->id);

        if ($mfaSettings->shouldChallenge($user) && ! $user->hasRememberedMfaDevice($request)) {
            if (! $user->hasTwoFactorEnabled()) {
                return redirect()->route('mfa.show')
                    ->with('warning', __('auth.mfa_setup_required'));
            }

            return redirect()->route('mfa.challenge');
        }

        return redirect()->intended($this->destinationFor($user));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function destinationFor(User $user): string
    {
        if ($user->canAccessAdminArea()) {
            return route('admin.dashboard');
        }

        if ($user->hasRole('applicant')) {
            return route('applicant.dashboard');
        }

        return route('home');
    }
}
