<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        app()->setLocale($locale);
        app()->setFallbackLocale((string) $this->setting('app.fallback_locale', config('app.fallback_locale', 'en')));

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        $available = $this->availableLocales();

        if (auth()->check()) {
            $user = auth()->user();
            $userLocale = $user->preferred_locale;

            if (in_array($userLocale, $available, true)) {
                return $userLocale;
            }

            $applicantLocale = $user->applicant?->preferred_locale;
            if (in_array($applicantLocale, $available, true)) {
                return $applicantLocale;
            }
        }

        $sessionLocale = session('locale');
        if (in_array($sessionLocale, $available, true)) {
            return $sessionLocale;
        }

        $systemLocale = $this->setting('localization.default_locale', config('app.locale', 'en'));
        if (in_array($systemLocale, $available, true)) {
            return $systemLocale;
        }

        return 'en';
    }

    private function availableLocales(): array
    {
        $configured = $this->setting('app.available_locales', config('app.available_locales', ['en', 'am']));

        if (is_string($configured)) {
            $configured = explode(',', $configured);
        }

        $locales = array_values(array_intersect((array) $configured, ['en', 'am']));

        return $locales === [] ? ['en', 'am'] : $locales;
    }

    private function setting(string $key, mixed $default): mixed
    {
        try {
            return Setting::get($key, $default);
        } catch (\Throwable) {
            return $default;
        }
    }
}
