<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class EnforceSessionTimeout
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $timeoutMinutes = $this->timeoutMinutes();
        $lastActivity = (int) $request->session()->get('auth.last_activity_at', now()->timestamp);

        if ((now()->timestamp - $lastActivity) > ($timeoutMinutes * 60)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('warning', __('auth.session_expired'));
        }

        $request->session()->put('auth.last_activity_at', now()->timestamp);

        return $next($request);
    }

    private function timeoutMinutes(): int
    {
        try {
            $minutes = (int) Setting::get('security.session_timeout', 120);
        } catch (Throwable) {
            $minutes = 120;
        }

        return min(max($minutes, 5), 1440);
    }
}
