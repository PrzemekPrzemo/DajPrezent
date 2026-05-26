<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Settings\SettingsRepository;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Conservative HTTP security headers applied to every response.
 *
 * Notes:
 *   - CSP base allows own scripts inline + 'unsafe-eval' for Alpine.js
 *     (Filament + landing both rely on attribute-driven expressions).
 *   - Extra hosts (Google Analytics / Google Ads / Plausible) join the
 *     CSP only when the corresponding ID is set in master-admin
 *     /admin/settings. No tracker enabled → CSP stays strict.
 *   - HSTS is only emitted in production over HTTPS so local dev
 *     doesn't get pinned.
 */
final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), interest-cohort=()');

        // Base CSP origin lists — extended below based on Settings.
        $scriptSrc = ["'self'", "'unsafe-inline'", "'unsafe-eval'"];
        $styleSrc = ["'self'", "'unsafe-inline'", 'https://fonts.bunny.net'];
        $fontSrc = ["'self'", 'data:', 'https://fonts.bunny.net'];
        $connectSrc = ["'self'"];
        $imgSrc = ["'self'", 'data:', 'https:'];

        // GA / Ads / Plausible domains added only when admin enabled them.
        // SettingsRepository wrapped in try/catch — on fresh installs the
        // app_settings table doesn't exist yet and we don't want CSP to
        // crash the response.
        try {
            $s = app(SettingsRepository::class);
            $ga4 = trim((string) $s->get('seo.ga4_id', ''));
            $ads = trim((string) $s->get('seo.google_ads_id', ''));
            $plausible = trim((string) $s->get('seo.plausible_domain', ''));

            if ($ga4 !== '' || $ads !== '') {
                $scriptSrc[] = 'https://www.googletagmanager.com';
                $scriptSrc[] = 'https://www.google-analytics.com';
                $connectSrc[] = 'https://www.googletagmanager.com';
                $connectSrc[] = 'https://www.google-analytics.com';
                $connectSrc[] = 'https://*.analytics.google.com';
                $connectSrc[] = 'https://*.google-analytics.com';
                $imgSrc[] = 'https://www.google-analytics.com';
                if ($ads !== '') {
                    $scriptSrc[] = 'https://www.googleadservices.com';
                    $scriptSrc[] = 'https://www.google.com';
                    $imgSrc[] = 'https://www.google.com';
                    $connectSrc[] = 'https://www.google.com';
                }
            }
            if ($plausible !== '') {
                $scriptSrc[] = 'https://plausible.io';
                $connectSrc[] = 'https://plausible.io';
            }
        } catch (\Throwable) {
            // Fresh install / settings table not migrated yet — keep base CSP.
        }

        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            'img-src '.implode(' ', array_unique($imgSrc)),
            'style-src '.implode(' ', array_unique($styleSrc)),
            'style-src-elem '.implode(' ', array_unique($styleSrc)),
            'script-src '.implode(' ', array_unique($scriptSrc)),
            'font-src '.implode(' ', array_unique($fontSrc)),
            'connect-src '.implode(' ', array_unique($connectSrc)),
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self' https://secure.snd.payu.com https://secure.payu.com",
        ]));

        if ($request->isSecure() && app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
