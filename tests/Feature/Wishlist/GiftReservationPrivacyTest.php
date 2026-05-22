<?php

declare(strict_types=1);

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wishlist\Models\Gift;
use App\Domain\Wishlist\Models\GiftReservation;

/**
 * Hard invariant: a list owner must NEVER see the guest's e-mail
 * via toArray()/toJson() when listing reservations on a gift.
 *
 * This protects anonymity of who reserved a gift — a core product
 * promise. The guest_email field is hidden on the model and only
 * accessible inside the ReservationService for verification flows.
 */
it('hides guest_email and verification_token from array output', function (): void {
    $tenant = Tenant::factory()->create();
    $gift = Gift::factory()->create(['tenant_id' => $tenant->id]);

    $reservation = GiftReservation::create([
        'tenant_id' => $tenant->id,
        'gift_id' => $gift->id,
        'guest_email' => 'gosc@example.com',
        'guest_name' => 'Anna',
        'intent' => 'reserve',
        'status' => GiftReservation::STATUS_ACTIVE,
        'verification_token' => str_repeat('a', 40),
        'email_verified_at' => now(),
    ]);

    $array = $reservation->toArray();

    expect($array)->not->toHaveKey('guest_email')
        ->and($array)->not->toHaveKey('verification_token')
        ->and($array)->not->toHaveKey('ip')
        ->and($array)->toHaveKey('status')
        ->and($array['status'])->toBe(GiftReservation::STATUS_ACTIVE);
});
