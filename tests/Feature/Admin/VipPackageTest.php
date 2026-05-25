<?php

declare(strict_types=1);

use App\Domain\Billing\Models\Package;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wishlist\GiftLimitGuard;
use App\Models\User;
use Database\Seeders\PackageSeeder;

beforeEach(function (): void {
    $this->seed(PackageSeeder::class);
});

it('seeds the VIP package as is_active=false (hidden from /pakiety)', function (): void {
    $vip = Package::query()->where('code', 'vip')->firstOrFail();
    expect((bool) $vip->is_active)->toBeFalse()
        ->and($vip->price_pln_gr)->toBe(0)
        ->and($vip->valid_days)->toBeGreaterThanOrEqual(36500)
        ->and($vip->gift_limit)->toBeNull();
});

it('VIP package never shows up in /pakiety', function (): void {
    $body = (string) $this->get('/pakiety')->assertOk()->getContent();
    expect($body)->not->toContain('VIP — Full Forever');
});

it('grants a VIP package to a tenant via direct service call (Filament action sim)', function (): void {
    $owner = User::factory()->create();
    $tenant = Tenant::factory()->create(['owner_user_id' => $owner->id, 'is_public' => false]);
    $vip = Package::query()->where('code', 'vip')->firstOrFail();

    // Same logic as the Filament Action does.
    $sub = Subscription::create([
        'tenant_id' => $tenant->id,
        'package_id' => $vip->id,
        'status' => 'active',
        'amount_pln_gr' => 0,
        'paid_at' => now(),
        'expires_at' => now()->addDays($vip->valid_days),
        'buyer_name' => 'VIP — partner medialny',
    ]);
    $tenant->update([
        'expires_at' => now()->addDays($vip->valid_days),
        'parent_subscription_id' => $sub->id,
        'is_public' => true,
    ]);

    expect(app(GiftLimitGuard::class)->canAdd($tenant->fresh()))->toBeTrue();
    expect(app(GiftLimitGuard::class)->activePackage($tenant->fresh())?->code)->toBe('vip');
    // Bez limitu prezentów
    expect(app(GiftLimitGuard::class)->activePackage($tenant->fresh())?->gift_limit)->toBeNull();
});
