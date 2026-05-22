<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Per-request locale resolution.
 *
 * Order of preference:
 *   1. Tenant's own locale, set by ResolveTenantFromSlug into the
 *      request attributes — public list pages always render in the
 *      tenant's chosen language.
 *   2. User's session preference (set via /locale POST).
 *   3. config('app.locale') default ('pl').
 *
 * The list is exhaustive; we never read Accept-Language to avoid
 * confusing users whose browsers send headers they didn't pick.
 */
final class SetLocale
{
    /** @var list<string> */
    public const SUPPORTED = ['pl', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->attributes->get('tenant');
        if ($tenant !== null && in_array($tenant->locale, self::SUPPORTED, true)) {
            app()->setLocale($tenant->locale);

            return $next($request);
        }

        $session = (string) $request->session()->get('locale', '');
        if (in_array($session, self::SUPPORTED, true)) {
            app()->setLocale($session);
        }

        return $next($request);
    }
}
