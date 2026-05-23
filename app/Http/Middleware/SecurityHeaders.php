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

        // CSP — permissive enough for current inline Blade styles +
        // Alpine.js CDN. Stricter policy when assets move to Vite.
        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "img-src 'self' data: https:",
            "style-src 'self' 'unsafe-inline'",
            "script-src 'self' 'unsafe-inline'",
            "font-src 'self' data:",
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
