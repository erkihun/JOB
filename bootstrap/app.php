<?php

use App\Http\Middleware\AdminAuthenticate;
use App\Http\Middleware\EnforceSessionTimeout;
use App\Http\Middleware\EnsureIsApplicant;
use App\Http\Middleware\RequireTwoFactorSetup;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;
use PragmaRX\Google2FALaravel\Middleware as TwoFactorMiddleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SecurityHeaders::class,
            SetLocale::class,
        ]);

        $middleware->alias([
            'admin' => AdminAuthenticate::class,
            'applicant' => EnsureIsApplicant::class,
            'session.timeout' => EnforceSessionTimeout::class,
            '2fa' => TwoFactorMiddleware::class,
            'require2fa' => RequireTwoFactorSetup::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // When a session expires the CSRF token becomes stale and the next form
        // submission throws TokenMismatchException (HTTP 419). Redirect the user
        // to the appropriate login page with a friendly message instead of showing
        // an error page.
        $exceptions->render(function (TokenMismatchException $e, $request) {
            $redirect = $request->is('admin/*')
                ? redirect()->route('login')
                : redirect()->route('login');

            return $redirect->with('warning', 'Your session expired. Please log in again.');
        });
    })->create();
