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

    Subscription::factory()->create([
        'tenant_id' => $this->tenant->id,
        'package_id' => Package::factory()->create(['gift_limit' => 200])->id,
        'status' => 'active',
        'paid_at' => now(),
        'expires_at' => now()->addMonths(9),
    ]);

    $this->a = Gift::factory()->create(['tenant_id' => $this->tenant->id, 'position' => 1, 'title' => 'A']);
    $this->b = Gift::factory()->create(['tenant_id' => $this->tenant->id, 'position' => 2, 'title' => 'B']);
    $this->c = Gift::factory()->create(['tenant_id' => $this->tenant->id, 'position' => 3, 'title' => 'C']);
});

it('rewrites positions in the order received from the front-end', function (): void {
    $this->actingAs($this->owner)
        ->postJson("/panel/lists/{$this->tenant->id}/gifts/reorder", [
            'ids' => [$this->c->id, $this->a->id, $this->b->id],
        ])
        ->assertOk()
        ->assertJson(['ok' => true, 'count' => 3]);

    expect($this->c->fresh()->position)->toBe(1)
        ->and($this->a->fresh()->position)->toBe(2)
        ->and($this->b->fresh()->position)->toBe(3);
});

it('silently drops ids belonging to another tenant (anti-tamper)', function (): void {
    $stranger = User::factory()->create();
    $foreignTenant = Tenant::factory()->create(['owner_user_id' => $stranger->id]);
    $foreignGift = Gift::factory()->create(['tenant_id' => $foreignTenant->id, 'position' => 99]);

    $this->actingAs($this->owner)
        ->postJson("/panel/lists/{$this->tenant->id}/gifts/reorder", [
            'ids' => [$foreignGift->id, $this->b->id, $this->a->id, $this->c->id],
        ])
        ->assertOk();

    // Foreign gift untouched.
    expect($foreignGift->fresh()->position)->toBe(99)
        ->and($this->b->fresh()->position)->toBe(1)
        ->and($this->a->fresh()->position)->toBe(2)
        ->and($this->c->fresh()->position)->toBe(3);
});

it('refuses reorder for a stranger tenant', function (): void {
    $stranger = User::factory()->create();
    $foreign = Tenant::factory()->create(['owner_user_id' => $stranger->id]);

    $this->actingAs($this->owner)
        ->postJson("/panel/lists/{$foreign->id}/gifts/reorder", ['ids' => [1]])
        ->assertForbidden();
});

it('rejects an empty payload', function (): void {
    $this->actingAs($this->owner)
        ->postJson("/panel/lists/{$this->tenant->id}/gifts/reorder", ['ids' => []])
        ->assertStatus(422);
});
