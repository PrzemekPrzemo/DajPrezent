<?php

declare(strict_types=1);

use App\Domain\Billing\Models\Package;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wedding\Models\Rsvp;
use App\Domain\Wishlist\Models\Gift;
use App\Models\User;

beforeEach(function (): void {
    $this->owner = User::factory()->create();
    $this->tenant = Tenant::factory()->create(['owner_user_id' => $this->owner->id]);

    Subscription::factory()->create([
        'tenant_id' => $this->tenant->id,
        'package_id' => Package::factory()->create(['gift_limit' => 200])->id,
        'status' => 'active',
        'paid_at' => now(),
        'expires_at' => now()->addMonths(9),
    ]);
});

it('fires confetti reason after the first gift is added (and not on the second)', function (): void {
    $this->actingAs($this->owner)
        ->post("/panel/lists/{$this->tenant->id}/gifts", [
            'title' => 'Pierwszy prezent',
            'priority' => 2,
        ])
        ->assertRedirect()
        ->assertSessionHas('dp_confetti', 'first-gift');

    $this->actingAs($this->owner)
        ->post("/panel/lists/{$this->tenant->id}/gifts", [
            'title' => 'Drugi prezent',
            'priority' => 2,
        ])
        ->assertRedirect()
        ->assertSessionMissing('dp_confetti');
});

it('flashes a heart pulse marker when a gift is marked received', function (): void {
    $gift = Gift::factory()->create([
        'tenant_id' => $this->tenant->id,
        'status' => Gift::STATUS_RESERVED,
    ]);

    $this->actingAs($this->owner)
        ->post("/panel/lists/{$this->tenant->id}/gifts/{$gift->id}/received")
        ->assertRedirect()
        ->assertSessionHas('dp_heart', $gift->id);
});

it('fires confetti reason after the first RSVP (but not subsequent ones)', function (): void {
    $tenant = Tenant::factory()->create([
        'owner_user_id' => $this->owner->id,
        'kind' => 'wedding_basic',
        'slug' => 'ania-tomek-rsvp',
        'is_public' => true,
    ]);

    $payload = [
        'guest_name' => 'Babcia Hania',
        'attending' => '1',
    ];

    $this->post("/{$tenant->slug}/rsvp", $payload)
        ->assertRedirect()
        ->assertSessionHas('dp_confetti', 'first-rsvp');

    $this->post("/{$tenant->slug}/rsvp", $payload + ['guest_name' => 'Dziadek'])
        ->assertRedirect()
        ->assertSessionMissing('dp_confetti');

    expect(Rsvp::query()->where('tenant_id', $tenant->id)->count())->toBe(2);
});
