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
});

it('renders the drawer trigger button on the gifts index', function (): void {
    $this->actingAs($this->owner)
        ->get("/panel/lists/{$this->tenant->id}/gifts")
        ->assertOk()
        ->assertSee('Dodaj prezent', false)
        ->assertSee('open-gift-drawer', false);
});

it('renders the drawer markup with the gift-preview endpoint wired', function (): void {
    $this->actingAs($this->owner)
        ->get("/panel/lists/{$this->tenant->id}/gifts")
        ->assertOk()
        ->assertSee('dpGiftDrawer', false)
        ->assertSee('gift-preview', false)
        ->assertSee('Wklej link do produktu', false);
});

it('shows the empty-state CTA when no gifts are present', function (): void {
    $this->actingAs($this->owner)
        ->get("/panel/lists/{$this->tenant->id}/gifts")
        ->assertOk()
        ->assertSee('Stwórz swoją pierwszą listę', false)
        ->assertSee('Dodaj pierwszy prezent', false);
});

it('renders the gifts table once gifts exist (no empty-state)', function (): void {
    Gift::factory()->count(2)->create(['tenant_id' => $this->tenant->id]);

    $response = $this->actingAs($this->owner)->get("/panel/lists/{$this->tenant->id}/gifts");

    $response->assertOk()
        ->assertSee('Prezenty (2)', false)
        ->assertDontSee('Stwórz swoją pierwszą listę', false);
});

it('emits a CSRF meta tag in the panel head', function (): void {
    $this->actingAs($this->owner)
        ->get("/panel/lists/{$this->tenant->id}/gifts")
        ->assertOk()
        ->assertSee('name="csrf-token"', false);
});
