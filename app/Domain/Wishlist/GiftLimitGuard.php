<?php

declare(strict_types=1);

namespace App\Domain\Wishlist;

use App\Domain\Billing\Models\Package;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Scopes\TenantScope;
use App\Domain\Wishlist\Models\Gift;
use Illuminate\Database\Eloquent\Builder;

/**
 * Single source of truth for "can this tenant add one more gift?"
 *
 * Called by GiftController::store and BookmarkletController::store
 * so both paths enforce the same package gift_limit.
 */
final class GiftLimitGuard
{
    public function canAdd(Tenant $tenant): bool
    {
        $package = $this->activePackage($tenant);
        if ($package === null) {
            return false;
        }

        $limit = $package->gift_limit;
        if ($limit === null) {
            return true;
        }

        // Count without tenant scope so we don't depend on caller having
        // set CurrentTenant correctly first.
        $current = Gift::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->id)
            ->count();

        return $current < $limit;
    }

    public function errorFor(Tenant $tenant): string
    {
        $package = $this->activePackage($tenant);
        if ($package === null) {
            return 'Nie masz aktywnej subskrypcji — przedłuż pakiet, aby dodać prezenty.';
        }

        return sprintf(
            'Osiągnięto limit prezentów (%d) dla pakietu %s. Przejdź na wyższy pakiet, aby dodać więcej.',
            $package->gift_limit ?? 0,
            $package->name,
        );
    }

    public function activePackage(Tenant $tenant): ?Package
    {
        $sub = $tenant->subscriptions()
            ->where('status', 'active')
            ->where(function (Builder $q): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->with('package')
            ->orderByDesc('paid_at')
            ->first();

        return $sub?->package;
    }
}
