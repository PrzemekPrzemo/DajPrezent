<?php

declare(strict_types=1);

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wedding\Models\WeddingEvent;
use App\Models\User;

beforeEach(function (): void {
    $this->owner = User::factory()->create();
});

it('renders the wedding editor for a wedding tenant and bootstraps the event row', function (): void {
    $tenant = Tenant::factory()->create([
        'owner_user_id' => $this->owner->id,
        'kind' => 'wedding_premium',
    ]);

    $this->actingAs($this->owner)
        ->get("/panel/lists/{$tenant->id}/wedding")
        ->assertOk()
        ->assertSee('Strona ślubna', false)
        ->assertSee('Para młoda', false);

    expect(WeddingEvent::query()->where('tenant_id', $tenant->id)->exists())->toBeTrue();
});

it('returns 404 for a non-wedding tenant', function (): void {
    $tenant = Tenant::factory()->create([
        'owner_user_id' => $this->owner->id,
        'kind' => 'wishlist',
    ]);

    $this->actingAs($this->owner)
        ->get("/panel/lists/{$tenant->id}/wedding")
        ->assertNotFound();
});

it('forbids a stranger from editing someone else\'s wedding', function (): void {
    $stranger = User::factory()->create();
    $tenant = Tenant::factory()->create([
        'owner_user_id' => $stranger->id,
        'kind' => 'wedding_basic',
    ]);

    $this->actingAs($this->owner)
        ->get("/panel/lists/{$tenant->id}/wedding")
        ->assertForbidden();
});

it('persists ceremony details on PATCH', function (): void {
    $tenant = Tenant::factory()->create([
        'owner_user_id' => $this->owner->id,
        'kind' => 'wedding_basic',
    ]);

    $this->actingAs($this->owner)
        ->patch("/panel/lists/{$tenant->id}/wedding", [
            'couple_names' => 'Anna & Tomek',
            'hashtag' => '#AT2026',
            'ceremony_at' => '2026-09-12T16:00',
            'venue_name' => 'Pałac w Łazienkach',
            'venue_address' => 'Agrykola 1, 00-460 Warszawa',
            'dress_code' => 'cocktail attire',
            'story_text' => 'Poznaliśmy się w 2020 r.',
            'schedule_text' => "16:00 ceremonia\n18:30 wesele",
            'rsvp_deadline' => '2026-08-01',
            'theme' => 'garden',
        ])
        ->assertRedirect(route('owner.wedding.edit', $tenant));

    $event = WeddingEvent::query()->where('tenant_id', $tenant->id)->firstOrFail();
    expect($event->couple_names)->toBe('Anna & Tomek')
        ->and($event->hashtag)->toBe('#AT2026')
        ->and($event->theme)->toBe('garden')
        ->and($event->ceremony_at->format('Y-m-d H:i'))->toBe('2026-09-12 16:00')
        ->and($event->rsvp_deadline->format('Y-m-d'))->toBe('2026-08-01');
});

it('rejects an unknown theme', function (): void {
    $tenant = Tenant::factory()->create([
        'owner_user_id' => $this->owner->id,
        'kind' => 'wedding_basic',
    ]);

    $this->actingAs($this->owner)
        ->from("/panel/lists/{$tenant->id}/wedding")
        ->patch("/panel/lists/{$tenant->id}/wedding", [
            'theme' => 'maximalist-pink',
        ])
        ->assertSessionHasErrors('theme');
});

it('shows the "Strona ślubna" CTA on the dashboard ONLY for wedding tenants', function (): void {
    $wishlist = Tenant::factory()->create(['owner_user_id' => $this->owner->id, 'kind' => 'wishlist']);
    $wedding = Tenant::factory()->create(['owner_user_id' => $this->owner->id, 'kind' => 'wedding_basic']);

    $body = (string) $this->actingAs($this->owner)->get('/panel')->assertOk()->getContent();

    expect($body)->toContain(route('owner.wedding.edit', $wedding))
        ->not->toContain(route('owner.wedding.edit', $wishlist));
});
