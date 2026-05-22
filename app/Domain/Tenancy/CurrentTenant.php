<?php

declare(strict_types=1);

namespace App\Domain\Tenancy;

use App\Domain\Tenancy\Models\Tenant;

/**
 * Holder for the current tenant within a request lifecycle.
 *
 * Resolved by the slug-bound route or by ownership in the owner panel.
 * The BelongsToTenant global scope reads from here.
 */
final class CurrentTenant
{
    private ?Tenant $tenant = null;

    public function set(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function get(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): ?int
    {
        return $this->tenant?->id;
    }

    public function isSet(): bool
    {
        return $this->tenant !== null;
    }

    public function forget(): void
    {
        $this->tenant = null;
    }
}
