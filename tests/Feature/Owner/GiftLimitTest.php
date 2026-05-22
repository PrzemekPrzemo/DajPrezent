<?php

declare(strict_types=1);

use App\Domain\Billing\Models\Package;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wishlist\Models\Gift;
use App\Models\User;

beforeEach(function (): void {
    $this->owner = User::factory()->create();
    $this->tenant = Tenant::factory()->create(['owner_user_id' => $this->owner->id]);
});

function makeActiveSub(int $tenantId, int $limit): Subscription
{
    $package = Package::factory()->create([
        'gift_limit' => $limit,
        'features' => [],
    ]);

    return Subscription::factory()->create([
        'tenant_id' => $tenantId,
        'package_id' => $package->id,
        'status' => 'active',
        'paid_at' => now(),
        'expires_at' => now()->addMonths(9),
        'amount_pln_gr' => $package->price_pln_gr,
    ]);
}

it('enforces gift_limit from the active package', function (): void {
    makeActiveSub($this->tenant->id, limit: 2);

    // First two creates ok.
    foreach (range(1, 2) as $i) {
        $this->actingAs($this->owner)
            ->from("/panel/lists/{$this->tenant->id}/gifts")
            ->post("/panel/lists/{$this->tenant->id}/gifts", [
                'title' => "Prezent {$i}",
                'priority' => 2,
            ])
            ->assertRedirect();
    }

    // Third hits the limit.
    $this->actingAs($this->owner)
        ->from("/panel/lists/{$this->tenant->id}/gifts")
        ->post("/panel/lists/{$this->tenant->id}/gifts", [
            'title' => 'Trzeci',
            'priority' => 2,
        ])
        ->assertSessionHasErrors('limit');

    expect(Gift::query()->where('tenant_id', $this->tenant->id)->count())->toBe(2);
});

it('treats null gift_limit as unlimited (Wedding tier)', function (): void {
    $package = Package::factory()->create(['gift_limit' => null, 'features' => []]);
    Subscription::factory()->create([
        'tenant_id' => $this->tenant->id,
        'package_id' => $package->id,
        'status' => 'active',
        'paid_at' => now(),
        'expires_at' => now()->addYear(),
        'amount_pln_gr' => $package->price_pln_gr,
    ]);

    foreach (range(1, 20) as $i) {
        $this->actingAs($this->owner)
            ->post("/panel/lists/{$this->tenant->id}/gifts", [
                'title' => "Prezent {$i}",
                'priority' => 2,
            ])
            ->assertRedirect();
    }

    expect(Gift::query()->where('tenant_id', $this->tenant->id)->count())->toBe(20);
});

it('refuses to add gifts when there is no active subscription', function (): void {
    $this->actingAs($this->owner)
        ->from("/panel/lists/{$this->tenant->id}/gifts")
        ->post("/panel/lists/{$this->tenant->id}/gifts", [
            'title' => 'Prezent',
            'priority' => 2,
        ])
        ->assertSessionHasErrors('limit');

    expect(Gift::query()->count())->toBe(0);
});

it('refuses to add when the active subscription has expired', function (): void {
    $package = Package::factory()->create(['gift_limit' => 10, 'features' => []]);
    Subscription::factory()->create([
        'tenant_id' => $this->tenant->id,
        'package_id' => $package->id,
        'status' => 'active', // active but expires_at in the past
        'expires_at' => now()->subDay(),
        'amount_pln_gr' => $package->price_pln_gr,
    ]);

    $this->actingAs($this->owner)
        ->from("/panel/lists/{$this->tenant->id}/gifts")
        ->post("/panel/lists/{$this->tenant->id}/gifts", [
            'title' => 'Prezent',
            'priority' => 2,
        ])
        ->assertSessionHasErrors('limit');
});

it('counts only this tenant\'s gifts against the limit', function (): void {
    makeActiveSub($this->tenant->id, limit: 3);

    // 3 unrelated gifts on a different tenant.
    $other = Tenant::factory()->create();
    Gift::factory()->count(3)->create(['tenant_id' => $other->id]);

    $this->actingAs($this->owner)
        ->post("/panel/lists/{$this->tenant->id}/gifts", [
            'title' => 'Prezent', 'priority' => 2,
        ])->assertRedirect();

    expect(Gift::query()->where('tenant_id', $this->tenant->id)->count())->toBe(1);
});

it('also enforces the limit through the bookmarklet path', function (): void {
    makeActiveSub($this->tenant->id, limit: 1);
    Gift::factory()->create(['tenant_id' => $this->tenant->id]);

    $this->actingAs($this->owner)
        ->from('/panel/bookmarklet/import')
        ->post('/panel/bookmarklet/import', [
            'tenant_id' => $this->tenant->id,
            'title' => 'Ponad limit',
            'priority' => 2,
        ])
        ->assertSessionHasErrors('limit');

    expect(Gift::query()->where('tenant_id', $this->tenant->id)->count())->toBe(1);
});
