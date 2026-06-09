<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Security\MfaSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTwoFactorSetup
{
    /**
     * Set to false in test files that specifically test enforcement behaviour.
     * All other test files leave this true so 2FA enforcement is transparent to them.
     */
    public static bool $bypassInTests = true;

    public function handle(Request $request, Closure $next): Response
    {
        if (static::$bypassInTests && app()->runningUnitTests()) {
            return $next($request);
        }

        $user = $request->user();
        $settings = app(MfaSettings::class);

        if (! $user || ! $settings->shouldChallenge($user)) {
            return $next($request);
        }

        if (! $request->routeIs('mfa.*', 'admin.two-factor.*', 'admin.login.two-factor', 'admin.logout', 'applicant.logout')) {
            if ($user->hasRememberedMfaDevice($request)) {
                session()->put(config('google2fa.session_var').'.auth_passed', true);

                return $next($request);
            }

            if (! $user->hasTwoFactorEnabled()) {
                return redirect()->route('mfa.show')
                    ->with('warning', __('auth.mfa_setup_required'));
            }

            if (! session(config('google2fa.session_var').'.auth_passed')) {
                return redirect()->route('mfa.challenge');
            }
        }

        return $next($request);
    }
}
