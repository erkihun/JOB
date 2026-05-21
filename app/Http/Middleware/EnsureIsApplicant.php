<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsApplicant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('applicant.login');
        }

        if (! auth()->user()->hasRole('applicant')) {
            abort(403, 'Access denied. This area is for applicants only.');
        }

        if (! auth()->user()->isActive()) {
            auth()->logout();

            return redirect()->route('applicant.login')
                ->withErrors(['account' => __('auth.account_inactive')]);
        }

        if (auth()->user()->email_verified_at === null) {
            return redirect()->route('applicant.verify-email');
        }

        return $next($request);
    }
}
