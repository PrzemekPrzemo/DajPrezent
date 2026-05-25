<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Conservative HTTP security headers applied to every response.
 *
 * Notes:
 *   - CSP allows our own scripts inline + Alpine.js CDN (used by the
 *     wishlist modal) and the small inline `<script>` blocks in our
 *     own views. Tightening to a nonce-based policy is a follow-up
 *     once we move scripts to /resources/js/.
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

        // CSP — Alpine.js (used by both Filament admin and the public
        // landing) compiles attribute expressions via `new Function`,
        // which requires `'unsafe-eval'` in script-src. Without it
        // every interactive widget (modals, dropdowns, password reveal,
        // count-up) silently breaks with a console CSP violation.
        //
        // Filament v3 also fetches Inter from fonts.bunny.net (privacy-
        // friendly Google Fonts mirror) — whitelist style-src + font-src
        // for that origin only.
        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "img-src 'self' data: https:",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
            "style-src-elem 'self' 'unsafe-inline' https://fonts.bunny.net",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
            "font-src 'self' data: https://fonts.bunny.net",
            "connect-src 'self'",
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
