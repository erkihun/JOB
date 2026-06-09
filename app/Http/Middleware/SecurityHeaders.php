<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('X-DNS-Prefetch-Control', 'off');

        $this->applyContentSecurityPolicy($response);
        $this->applyStrictTransportSecurity($request, $response);

        return $response;
    }

    /**
     * Content-Security-Policy.
     *
     * The application relies on inline <script>/<style> blocks (theme toggles,
     * TinyMCE/Google-Maps init, Alpine-style handlers) and a small set of
     * trusted third-party origins (TinyMCE CDN, Google Maps). A nonce-only
     * policy would require rewriting dozens of templates, so 'unsafe-inline'
     * is retained for scripts/styles while every other directive is locked
     * down — object-src/base-uri/form-action/frame-ancestors meaningfully
     * reduce injection and clickjacking surface.
     *
     * Defaults to **report-only** so the policy can be validated in production
     * before being enforced. Set CSP_ENFORCE=true (and optionally
     * CSP_REPORT_URI) once verified.
     */
    private function applyContentSecurityPolicy(Response $response): void
    {
        $scriptSrc = "'self' 'unsafe-inline' https://cdn.jsdelivr.net https://maps.googleapis.com https://maps.gstatic.com";
        $styleSrc = "'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com";
        $imgSrc = "'self' data: blob: https://maps.googleapis.com https://maps.gstatic.com https://*.googleapis.com https://*.gstatic.com https://*.google.com";
        $fontSrc = "'self' data: https://fonts.gstatic.com https://cdn.jsdelivr.net";
        $connectSrc = "'self' https://maps.googleapis.com https://cdn.jsdelivr.net";
        $frameSrc = "'self' https://www.google.com https://maps.google.com";

        $directives = [
            "default-src 'self'",
            "script-src {$scriptSrc}",
            "style-src {$styleSrc}",
            "img-src {$imgSrc}",
            "font-src {$fontSrc}",
            "connect-src {$connectSrc}",
            "frame-src {$frameSrc}",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ];

        $reportUri = env('CSP_REPORT_URI');
        if (is_string($reportUri) && $reportUri !== '') {
            $directives[] = 'report-uri '.$reportUri;
        }

        $policy = implode('; ', $directives);

        $header = filter_var(env('CSP_ENFORCE', false), FILTER_VALIDATE_BOOLEAN)
            ? 'Content-Security-Policy'
            : 'Content-Security-Policy-Report-Only';

        $response->headers->set($header, $policy);
    }

    /**
     * HSTS — only over a secure connection in production, so it is never sent
     * during local HTTP development (which would otherwise pin the browser to
     * HTTPS for localhost).
     */
    private function applyStrictTransportSecurity(Request $request, Response $response): void
    {
        if (app()->environment('production') && $request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload',
            );
        }
    }
}
