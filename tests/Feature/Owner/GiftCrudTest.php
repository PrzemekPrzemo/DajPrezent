<?php

declare(strict_types=1);

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wishlist\Models\Gift;
use App\Domain\Wishlist\Models\GiftReservation;
use App\Models\User;

beforeEach(function (): void {
    $this->owner = User::factory()->create();
    $this->tenant = Tenant::factory()->create(['owner_user_id' => $this->owner->id]);
    $this->stranger = User::factory()->create();
});

it('shows the dashboard with the user lists', function (): void {
    $this->actingAs($this->owner)
        ->get('/panel')
        ->assertOk()
        ->assertSee($this->tenant->name)
        ->assertSee('dajprezent.pl/'.$this->tenant->slug);
});

it('shows the gifts page for an owned tenant', function (): void {
    Gift::factory()->create(['tenant_id' => $this->tenant->id, 'title' => 'Aparat']);

    $this->actingAs($this->owner)
        ->get("/panel/lists/{$this->tenant->id}/gifts")
        ->assertOk()
        ->assertSee('Aparat');
});

it('forbids access to a stranger tenant', function (): void {
    $foreign = Tenant::factory()->create(['owner_user_id' => $this->stranger->id]);

    $this->actingAs($this->owner)
        ->get("/panel/lists/{$foreign->id}/gifts")
        ->assertForbidden();
});

it('creates a gift via the form', function (): void {
    $this->actingAs($this->owner)
        ->from("/panel/lists/{$this->tenant->id}/gifts")
        ->post("/panel/lists/{$this->tenant->id}/gifts", [
            'title' => 'Suszarka do włosów',
            'url' => 'https://example.com/p/123',
            'price_pln' => '249.99',
            'priority' => 2,
            'description' => 'Najlepiej w kolorze różowym.',
        ])
        ->assertRedirect("/panel/lists/{$this->tenant->id}/gifts");

    $gift = Gift::query()->where('tenant_id', $this->tenant->id)->firstOrFail();
    expect($gift->title)->toBe('Suszarka do włosów')
        ->and($gift->price_pln_gr)->toBe(24999)
        ->and($gift->priority)->toBe(2);
});

it('updates a gift', function (): void {
    $gift = Gift::factory()->create(['tenant_id' => $this->tenant->id, 'title' => 'Stara nazwa']);

    $this->actingAs($this->owner)
        ->patch("/panel/lists/{$this->tenant->id}/gifts/{$gift->id}", [
            'title' => 'Nowa nazwa',
            'priority' => 1,
        ])
        ->assertRedirect();

    expect($gift->fresh()->title)->toBe('Nowa nazwa')
        ->and($gift->fresh()->priority)->toBe(1);
});

it('deletes a gift', function (): void {
    $gift = Gift::factory()->create(['tenant_id' => $this->tenant->id]);

    $this->actingAs($this->owner)
        ->delete("/panel/lists/{$this->tenant->id}/gifts/{$gift->id}")
        ->assertRedirect();

    expect(Gift::query()->find($gift->id))->toBeNull();
});

it('marks a reserved gift as received', function (): void {
    $gift = Gift::factory()->create([
        'tenant_id' => $this->tenant->id,
        'status' => Gift::STATUS_RESERVED,
    ]);

    $this->actingAs($this->owner)
        ->post("/panel/lists/{$this->tenant->id}/gifts/{$gift->id}/received")
        ->assertRedirect();

    expect($gift->fresh()->status)->toBe(Gift::STATUS_RECEIVED);
});

it('cannot tamper with a gift belonging to another tenant via URL', function (): void {
    $foreign = Tenant::factory()->create(['owner_user_id' => $this->stranger->id]);
    $foreignGift = Gift::factory()->create(['tenant_id' => $foreign->id, 'title' => 'Cudzy prezent']);

    // Owner tries to PATCH a foreign gift through their own tenant URL: should 404 (gift not in current tenant).
    $this->actingAs($this->owner)
        ->patch("/panel/lists/{$this->tenant->id}/gifts/{$foreignGift->id}", [
            'title' => 'Zhakowany',
            'priority' => 1,
        ])
        ->assertNotFound();

    expect($foreignGift->fresh()->title)->toBe('Cudzy prezent');
});

it('never exposes guest e-mail of reservations to the owner', function (): void {
    $gift = Gift::factory()->create(['tenant_id' => $this->tenant->id, 'status' => Gift::STATUS_RESERVED]);
    GiftReservation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'gift_id' => $gift->id,
        'guest_email' => 'gosc-od-cioci@example.com',
        'guest_name' => 'Mariola',
        'status' => GiftReservation::STATUS_ACTIVE,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($this->owner)
        ->get("/panel/lists/{$this->tenant->id}/gifts")
        ->assertOk()
        ->assertSee('zarezerwowany')
        ->assertDontSee('gosc-od-cioci@example.com')
        ->assertDontSee('Mariola');
});
