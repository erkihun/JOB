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
use PragmaRX\Google2FALaravel\Middleware as TwoFactorMiddleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trimStrings(except: ['smtp_password', 'messaging_secret']);
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
        $exceptions->dontFlash(['smtp_password', 'messaging_secret']);
        // Laravel converts TokenMismatchException to a 419 before render callbacks run.
        $exceptions->render(function (HttpException $e, $request) {
            if ($e->getStatusCode() !== 419 || $request->expectsJson()) {
                return null;
            }

            // CSRF verification runs before SetLocale in the web group.
            app(SetLocale::class)->handle($request, fn () => response()->noContent());

            if ($request->user()) {
                return back()
                    ->withInput($request->except(['_token', 'password', 'password_confirmation', 'current_password', 'one_time_password', 'recovery_code', 'otp', 'smtp_password', 'messaging_secret']))
                    ->with('warning', __('auth.page_expired'));
            }

            return redirect()->route('login')->with('warning', __('auth.session_expired'));
        });
    })->create();
