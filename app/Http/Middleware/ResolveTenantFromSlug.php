<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Tenancy\CurrentTenant;
use App\Domain\Tenancy\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Resolves a tenant by its slug route parameter and pins it on the
 * CurrentTenant singleton so the BelongsToTenant global scope filters
 * subsequent queries.
 *
 * Status code policy:
 *   - 404 if no tenant with that slug exists
 *   - 410 (Gone) if the subscription has expired (intentional, allows
 *     SEO-friendly tombstones explaining the list is no longer active)
 *   - 423 (Locked) if the tenant is password-protected and not yet
 *     unlocked in session — controllers decide how to render
 */
final class ResolveTenantFromSlug
{
    public function __construct(private readonly CurrentTenant $current) {}

    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('slug');

        if (! is_string($slug)) {
            throw new NotFoundHttpException;
        }

        $tenant = Tenant::query()->where('slug', $slug)->first();

        if ($tenant === null) {
            throw new NotFoundHttpException;
        }

        if ($tenant->isExpired()) {
            throw new HttpException(410, 'Lista wygasła.');
        }

        if (! $tenant->is_public) {
            throw new NotFoundHttpException;
        }

        $this->current->set($tenant);
        $request->attributes->set('tenant', $tenant);

        // Public pages render in the tenant's locale — overrides session.
        if (in_array($tenant->locale, SetLocale::SUPPORTED, true)) {
            app()->setLocale($tenant->locale);
        }

        return $next($request);
    }
}
