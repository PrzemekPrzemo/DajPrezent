<?php

declare(strict_types=1);

use App\Domain\Tenancy\CurrentTenant;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Scopes\TenantScope;
use App\Domain\Wishlist\Models\Gift;

it('filters tenantful models to the current tenant', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    Gift::factory()->count(3)->create(['tenant_id' => $tenantA->id]);
    Gift::factory()->count(2)->create(['tenant_id' => $tenantB->id]);

    app(CurrentTenant::class)->set($tenantA);
    expect(Gift::query()->count())->toBe(3);

    app(CurrentTenant::class)->set($tenantB);
    expect(Gift::query()->count())->toBe(2);

    app(CurrentTenant::class)->forget();
});

it('exposes all rows when no tenant is bound (admin context)', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    Gift::factory()->count(3)->create(['tenant_id' => $tenantA->id]);
    Gift::factory()->count(2)->create(['tenant_id' => $tenantB->id]);

    app(CurrentTenant::class)->forget();

    expect(Gift::query()->count())->toBe(5);
});

it('auto-assigns tenant_id from CurrentTenant on creating', function (): void {
    $tenant = Tenant::factory()->create();

    app(CurrentTenant::class)->set($tenant);
    $gift = Gift::create(['title' => 'Książka', 'priority' => 2, 'status' => Gift::STATUS_AVAILABLE]);
    app(CurrentTenant::class)->forget();

    expect($gift->tenant_id)->toBe($tenant->id);
});

it('can bypass scope explicitly when needed', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    Gift::factory()->count(3)->create(['tenant_id' => $tenantA->id]);
    Gift::factory()->count(2)->create(['tenant_id' => $tenantB->id]);

    app(CurrentTenant::class)->set($tenantA);

    expect(Gift::query()->withoutGlobalScope(TenantScope::class)->count())->toBe(5);

    app(CurrentTenant::class)->forget();
});
