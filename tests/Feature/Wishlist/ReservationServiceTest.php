<?php

declare(strict_types=1);

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wishlist\Mail\GiftReservationVerifyMail;
use App\Domain\Wishlist\Models\Gift;
use App\Domain\Wishlist\Models\GiftReservation;
use App\Domain\Wishlist\Reservations\ReservationException;
use App\Domain\Wishlist\Reservations\ReservationService;
use Illuminate\Support\Facades\Mail;

beforeEach(function (): void {
    Mail::fake();
    $this->tenant = Tenant::factory()->create();
    $this->gift = Gift::factory()->create([
        'tenant_id' => $this->tenant->id,
        'status' => Gift::STATUS_AVAILABLE,
    ]);
    $this->service = app(ReservationService::class);
});

it('puts a fresh reservation into pending and sends a verification mail', function (): void {
    $reservation = $this->service->request($this->gift, 'gosc@example.com', 'Anna', 'reserve', '127.0.0.1');

    expect($reservation->status)->toBe(GiftReservation::STATUS_PENDING)
        ->and($reservation->email_verified_at)->toBeNull()
        ->and($reservation->expires_at)->not->toBeNull()
        ->and($this->gift->fresh()->status)->toBe(Gift::STATUS_AVAILABLE);

    Mail::assertQueued(GiftReservationVerifyMail::class, function (GiftReservationVerifyMail $mail) use ($reservation): bool {
        return $mail->reservation->id === $reservation->id;
    });
});

it('flips the gift to reserved only after email verification', function (): void {
    $reservation = $this->service->request($this->gift, 'gosc@example.com', null, 'reserve');

    expect($this->gift->fresh()->status)->toBe(Gift::STATUS_AVAILABLE);

    $verified = $this->service->verify($reservation->verification_token);

    expect($verified->status)->toBe(GiftReservation::STATUS_ACTIVE)
        ->and($verified->email_verified_at)->not->toBeNull()
        ->and($this->gift->fresh()->status)->toBe(Gift::STATUS_RESERVED);
});

it('rejects requests on an already-reserved gift', function (): void {
    $this->gift->update(['status' => Gift::STATUS_RESERVED]);

    expect(fn () => $this->service->request($this->gift, 'gosc@example.com', null, 'reserve'))
        ->toThrow(ReservationException::class);
});

it('fails verification with an unknown token', function (): void {
    expect(fn () => $this->service->verify('bogus'))->toThrow(ReservationException::class);
});

it('expires verification when token TTL passed', function (): void {
    $reservation = $this->service->request($this->gift, 'gosc@example.com', null, 'reserve');
    $reservation->update(['expires_at' => now()->subMinute()]);

    expect(fn () => $this->service->verify($reservation->verification_token))->toThrow(ReservationException::class);

    expect($reservation->fresh()->status)->toBe(GiftReservation::STATUS_EXPIRED)
        ->and($this->gift->fresh()->status)->toBe(Gift::STATUS_AVAILABLE);
});

it('is idempotent on repeated verification clicks', function (): void {
    $reservation = $this->service->request($this->gift, 'gosc@example.com', null, 'reserve');

    $this->service->verify($reservation->verification_token);
    $again = $this->service->verify($reservation->verification_token);

    expect($again->status)->toBe(GiftReservation::STATUS_ACTIVE)
        ->and($this->gift->fresh()->status)->toBe(Gift::STATUS_RESERVED);
});

it('cancels an active reservation and frees the gift', function (): void {
    $reservation = $this->service->request($this->gift, 'gosc@example.com', null, 'reserve');
    $this->service->verify($reservation->verification_token);

    $cancelled = $this->service->cancel($reservation->verification_token);

    expect($cancelled->status)->toBe(GiftReservation::STATUS_CANCELLED)
        ->and($cancelled->cancelled_at)->not->toBeNull()
        ->and($this->gift->fresh()->status)->toBe(Gift::STATUS_AVAILABLE);
});

it('cancelling a pending reservation does not need a freed gift (it was never reserved)', function (): void {
    $reservation = $this->service->request($this->gift, 'gosc@example.com', null, 'reserve');

    $cancelled = $this->service->cancel($reservation->verification_token);

    expect($cancelled->status)->toBe(GiftReservation::STATUS_CANCELLED)
        ->and($this->gift->fresh()->status)->toBe(Gift::STATUS_AVAILABLE);
});

it('handles a race: second verifier finds the gift already taken', function (): void {
    $other = GiftReservation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'gift_id' => $this->gift->id,
    ]);
    $reservation = $this->service->request($this->gift, 'gosc@example.com', null, 'reserve');

    // Simulate the first guest winning the race.
    $this->gift->update(['status' => Gift::STATUS_RESERVED]);
    $other->update(['status' => GiftReservation::STATUS_ACTIVE]);

    expect(fn () => $this->service->verify($reservation->verification_token))->toThrow(ReservationException::class);

    expect($reservation->fresh()->status)->toBe(GiftReservation::STATUS_EXPIRED);
});

it('releases expired pending reservations via the scheduled cleanup', function (): void {
    $fresh = $this->service->request($this->gift, 'fresh@example.com', null, 'reserve');
    $stale = $this->service->request(
        Gift::factory()->create(['tenant_id' => $this->tenant->id]),
        'stale@example.com',
        null,
        'reserve',
    );
    $stale->update(['expires_at' => now()->subMinutes(10)]);

    $count = $this->service->releaseExpired();

    expect($count)->toBe(1)
        ->and($fresh->fresh()->status)->toBe(GiftReservation::STATUS_PENDING)
        ->and($stale->fresh()->status)->toBe(GiftReservation::STATUS_EXPIRED);
});
