<?php

declare(strict_types=1);

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wishlist\Models\Gift;
use App\Domain\Wishlist\Models\GiftReservation;

it('expires pending reservations past their TTL', function (): void {
    $tenant = Tenant::factory()->create();
    $gift = Gift::factory()->create(['tenant_id' => $tenant->id]);

    $stale = GiftReservation::factory()->create([
        'tenant_id' => $tenant->id,
        'gift_id' => $gift->id,
        'status' => GiftReservation::STATUS_PENDING,
        'expires_at' => now()->subMinutes(5),
    ]);
    $fresh = GiftReservation::factory()->create([
        'tenant_id' => $tenant->id,
        'gift_id' => $gift->id,
        'status' => GiftReservation::STATUS_PENDING,
        'expires_at' => now()->addMinutes(30),
    ]);

    $this->artisan('reservations:release-expired')
        ->expectsOutputToContain('Released 1 expired reservation')
        ->assertSuccessful();

    expect($stale->fresh()->status)->toBe(GiftReservation::STATUS_EXPIRED)
        ->and($fresh->fresh()->status)->toBe(GiftReservation::STATUS_PENDING);
});
