<?php

declare(strict_types=1);

use App\Domain\Billing\Models\Package;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wishlist\GiftLimitGuard;
use App\Models\User;

beforeEach(function (): void {
    $this->owner = User::factory()->create();

    $this->plusPkg = Package::factory()->create([
        'code' => 'plus',
        'name' => 'Plus',
        'gift_limit' => 75,
        'features' => ['multiple_lists' => 3, 'custom_slug' => true],
    ]);

    $this->primaryTenant = Tenant::factory()->create([
        'owner_user_id' => $this->owner->id,
        'slug' => 'ania-glowna',
        'name' => 'Ania — lista główna',
    ]);

    $this->sub = Subscription::factory()->create([
        'tenant_id' => $this->primaryTenant->id,
        'package_id' => $this->plusPkg->id,
        'status' => 'active',
        'paid_at' => now()->subDay(),
        'expires_at' => now()->addMonths(9),
    ]);

    // The primary tenant points back at its own sub — counted toward the 3-list slot.
    $this->primaryTenant->update(['parent_subscription_id' => $this->sub->id]);
});

it('shows the "add list" CTA on dashboard when slots are free', function (): void {
    $this->actingAs($this->owner)
        ->get('/panel')
        ->assertOk()
        ->assertSee('Dodaj kolejną listę', false)
        ->assertSee('2/3 wolne', false);
});

it('creates a sibling tenant inheriting expires_at + kind, private by default', function (): void {
    $this->actingAs($this->owner)
        ->post('/panel/lists', [
            'name' => 'Ania — święta',
            'slug' => 'ania-swieta',
        ])
        ->assertRedirect();

    $sibling = Tenant::query()->where('slug', 'ania-swieta')->firstOrFail();
    expect($sibling->parent_subscription_id)->toBe($this->sub->id)
        ->and($sibling->kind)->toBe('wishlist')
        ->and($sibling->expires_at?->toDateString())->toBe($this->sub->expires_at->toDateString())
        // Privacy default — matches CheckoutService primary tenants.
        // Owner publishes via /panel/lists/{tenant}/settings.
        ->and((bool) $sibling->is_public)->toBeFalse();
});

it('rejects a 4th list when Plus limit (3) is exhausted', function (): void {
    // Fill the 3 slots — primary + 2 siblings.
    Tenant::factory()->count(2)->create([
        'owner_user_id' => $this->owner->id,
        'parent_subscription_id' => $this->sub->id,
    ]);

    $this->actingAs($this->owner)
        ->get('/panel')
        ->assertOk()
        ->assertDontSee('Dodaj kolejną listę', false);

    $this->actingAs($this->owner)
        ->post('/panel/lists', [
            'name' => 'Czwarta',
            'slug' => 'ania-czwarta',
        ])
        ->assertSessionHasErrors('limit');

    expect(Tenant::query()->where('slug', 'ania-czwarta')->exists())->toBeFalse();
});

it('rejects a reserved/blacklisted slug for the sibling list', function (): void {
    $this->actingAs($this->owner)
        ->post('/panel/lists', [
            'name' => 'Admin lista',
            'slug' => 'admin',
        ])
        ->assertSessionHasErrors('slug');
});

it('does not offer multi-list CTA when only a Mini (single-list) sub exists', function (): void {
    $mini = Package::factory()->create([
        'code' => 'mini',
        'features' => ['multiple_lists' => false],
        'gift_limit' => 10,
    ]);
    $miniOwner = User::factory()->create();
    $miniTenant = Tenant::factory()->create(['owner_user_id' => $miniOwner->id]);
    $miniSub = Subscription::factory()->create([
        'tenant_id' => $miniTenant->id,
        'package_id' => $mini->id,
        'status' => 'active',
        'paid_at' => now(),
        'expires_at' => now()->addMonths(9),
    ]);
    $miniTenant->update(['parent_subscription_id' => $miniSub->id]);

    $this->actingAs($miniOwner)
        ->get('/panel')
        ->assertOk()
        ->assertDontSee('Dodaj kolejną listę', false);
});

it('a sibling tenant inherits the parent package\'s gift_limit (per-tenant, not shared)', function (): void {
    $sibling = Tenant::factory()->create([
        'owner_user_id' => $this->owner->id,
        'parent_subscription_id' => $this->sub->id,
    ]);

    $guard = app(GiftLimitGuard::class);
    expect($guard->activePackage($sibling)?->gift_limit)->toBe(75);
});
