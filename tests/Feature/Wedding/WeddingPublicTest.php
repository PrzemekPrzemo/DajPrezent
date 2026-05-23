<?php

declare(strict_types=1);

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wedding\Models\Rsvp;
use App\Domain\Wedding\Models\WeddingEvent;
use App\Domain\Wishlist\Models\Gift;

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create([
        'is_public' => true,
        'kind' => 'wedding_premium',
        'slug' => 'anna-i-tomek',
        'name' => 'Anna i Tomek',
    ]);
    $this->event = WeddingEvent::factory()->create([
        'tenant_id' => $this->tenant->id,
        'couple_names' => 'Anna & Tomek',
        'hashtag' => '#AT2026',
        'venue_name' => 'Pałac w Łazienkach',
        'venue_address' => 'Agrykola 1, Warszawa',
        'ceremony_at' => now()->addMonths(6),
        'story_text' => 'Poznaliśmy się na koncercie.',
        'schedule_text' => "16:00 ceremonia\n18:30 wesele",
        'rsvp_deadline' => now()->addMonths(5)->toDateString(),
        'theme' => 'garden',
    ]);
});

it('renders the wedding public page for a wedding tenant', function (): void {
    $body = (string) $this->get('/'.$this->tenant->slug)->assertOk()->getContent();

    expect($body)
        ->toContain('Anna &amp; Tomek')        // couple_names with & escaped
        ->toContain('#AT2026')                  // hashtag
        ->toContain('Pałac w Łazienkach')       // venue
        ->toContain('Poznaliśmy się')           // story
        ->toContain('Potwierdź obecność')       // RSVP heading
        ->toContain('Wyślij RSVP')              // submit button
        ->toContain('google.com/maps');         // map link
});

it('shows dietary + transport fields only for wedding_premium', function (): void {
    $basic = Tenant::factory()->create(['kind' => 'wedding_basic', 'is_public' => true, 'slug' => 'basic-w']);
    WeddingEvent::factory()->create(['tenant_id' => $basic->id, 'couple_names' => 'B & C']);

    $premiumBody = (string) $this->get('/'.$this->tenant->slug)->getContent();
    $basicBody = (string) $this->get('/'.$basic->slug)->getContent();

    expect($premiumBody)->toContain('Preferencje dietetyczne')->toContain('Potrzebuję transportu');
    expect($basicBody)->not->toContain('Preferencje dietetyczne')->not->toContain('Potrzebuję transportu');
});

it('renders gifts at the bottom of the wedding page', function (): void {
    Gift::factory()->create(['tenant_id' => $this->tenant->id, 'title' => 'Robot kuchenny']);

    $this->get('/'.$this->tenant->slug)
        ->assertOk()
        ->assertSee('Lista prezentów')
        ->assertSee('Robot kuchenny');
});

it('stores an RSVP from a public guest', function (): void {
    $this->from('/'.$this->tenant->slug)
        ->post('/'.$this->tenant->slug.'/rsvp', [
            'guest_name' => 'Marek Kowalski',
            'guest_email' => 'marek@example.com',
            'attending' => '1',
            'plus_one' => '1',
            'plus_one_name' => 'Anna Kowalska',
            'dietary' => 'wegetariańska',
            'transport_needed' => '1',
            'message' => 'Wszystkiego najlepszego!',
        ])
        ->assertRedirect()
        ->assertSessionHas('rsvp_status');

    $rsvp = Rsvp::query()->where('tenant_id', $this->tenant->id)->firstOrFail();
    expect($rsvp->guest_name)->toBe('Marek Kowalski')
        ->and($rsvp->attending)->toBeTrue()
        ->and($rsvp->plus_one)->toBeTrue()
        ->and($rsvp->plus_one_name)->toBe('Anna Kowalska')
        ->and($rsvp->dietary)->toBe('wegetariańska')
        ->and($rsvp->headCount())->toBe(2);
});

it('refuses RSVP after rsvp_deadline', function (): void {
    $this->event->update(['rsvp_deadline' => now()->subDay()]);

    $this->from('/'.$this->tenant->slug)
        ->post('/'.$this->tenant->slug.'/rsvp', [
            'guest_name' => 'X', 'attending' => '1',
        ])
        ->assertSessionHasErrors('rsvp');

    expect(Rsvp::query()->count())->toBe(0);
});

it('returns 404 for RSVP on a non-wedding tenant', function (): void {
    $wishlist = Tenant::factory()->create(['kind' => 'wishlist', 'is_public' => true, 'slug' => 'normalna']);

    $this->from('/'.$wishlist->slug)
        ->post('/'.$wishlist->slug.'/rsvp', ['guest_name' => 'X', 'attending' => '1'])
        ->assertNotFound();
});

it('validates required guest_name on RSVP', function (): void {
    $this->from('/'.$this->tenant->slug)
        ->post('/'.$this->tenant->slug.'/rsvp', ['attending' => '1'])
        ->assertSessionHasErrors('guest_name');
});

it('hides guest_email in toArray on RSVP model (PII)', function (): void {
    $rsvp = Rsvp::factory()->create([
        'tenant_id' => $this->tenant->id,
        'guest_email' => 'secret@example.com',
        'ip' => '203.0.113.1',
    ]);

    $array = $rsvp->toArray();
    expect($array)->not->toHaveKey('guest_email')->not->toHaveKey('ip');
});

it('still renders the plain wishlist for non-wedding tenants', function (): void {
    $wishlist = Tenant::factory()->create(['kind' => 'wishlist', 'is_public' => true, 'slug' => 'lista-zwykla', 'name' => 'Lista Zwykła']);
    Gift::factory()->create(['tenant_id' => $wishlist->id, 'title' => 'Aparat']);

    $this->get('/'.$wishlist->slug)
        ->assertOk()
        ->assertSee('Lista Zwykła')
        ->assertSee('Aparat')
        ->assertDontSee('Potwierdź obecność')
        ->assertDontSee('Wyślij RSVP');
});
