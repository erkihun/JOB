<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->status !== UserStatus::Active || ! $user->canAccessAdminArea()) {
            Auth::logout();

            return redirect()->route('login')->withErrors(['email' => __('auth.not_authorized')]);
        }

        return $next($request);
    }
}
