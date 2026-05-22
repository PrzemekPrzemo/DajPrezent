<?php

declare(strict_types=1);

namespace App\Domain\Tenancy\Scopes;

use App\Domain\Tenancy\CurrentTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Constrains tenantful models to the current tenant when one is set.
 *
 * When no tenant is bound (master admin context, queues, console commands)
 * the scope is a no-op so jobs and admin panel can see all rows.
 * Code that needs cross-tenant access in a tenant-bound request should
 * call ->withoutGlobalScope(TenantScope::class) explicitly.
 */
final class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        /** @var CurrentTenant $current */
        $current = app(CurrentTenant::class);

        if ($current->id() !== null) {
            $builder->where($model->qualifyColumn('tenant_id'), $current->id());
        }
    }
}
