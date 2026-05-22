<?php

declare(strict_types=1);

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wishlist\Mail\GiftReservationVerifyMail;
use App\Domain\Wishlist\Models\Gift;
use App\Domain\Wishlist\Models\GiftReservation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

it('returns 404 for an unknown slug', function (): void {
    $this->get('/no-such-slug')->assertNotFound();
});

it('returns 404 for a private (not-yet-public) tenant', function (): void {
    $tenant = Tenant::factory()->create(['is_public' => false, 'slug' => 'prywatna']);

    $this->get('/'.$tenant->slug)->assertNotFound();
});

it('returns 410 Gone for an expired tenant', function (): void {
    $tenant = Tenant::factory()->create([
        'is_public' => true,
        'expires_at' => now()->subDay(),
        'slug' => 'wygasla',
    ]);

    $this->get('/'.$tenant->slug)->assertStatus(410);
});

it('redirects a password-protected tenant to the unlock form before unlock', function (): void {
    $tenant = Tenant::factory()->create([
        'is_public' => true,
        'password_hash' => Hash::make('sekret'),
        'slug' => 'chroniona',
    ]);

    $this->get('/'.$tenant->slug)->assertRedirect('/'.$tenant->slug.'/unlock');
});

it('renders the public list with gift titles', function (): void {
    $tenant = Tenant::factory()->create(['is_public' => true, 'slug' => 'anna-i-tomek', 'name' => 'Anna i Tomek']);
    $gift = Gift::factory()->create([
        'tenant_id' => $tenant->id,
        'title' => 'Aparat fotograficzny',
        'status' => Gift::STATUS_AVAILABLE,
    ]);

    $this->get('/'.$tenant->slug)
        ->assertOk()
        ->assertSee('Anna i Tomek')
        ->assertSee($gift->title);
});

it('shows reserved status without leaking guest information', function (): void {
    $tenant = Tenant::factory()->create(['is_public' => true, 'slug' => 'tajemnica']);
    $gift = Gift::factory()->create([
        'tenant_id' => $tenant->id,
        'title' => 'Lampa nocna',
        'status' => Gift::STATUS_RESERVED,
    ]);
    GiftReservation::factory()->create([
        'tenant_id' => $tenant->id,
        'gift_id' => $gift->id,
        'guest_email' => 'wujek-staszek@example.com',
        'guest_name' => 'Stanisław',
        'status' => GiftReservation::STATUS_ACTIVE,
        'email_verified_at' => now(),
    ]);

    $response = $this->get('/'.$tenant->slug);

    $response->assertOk()
        ->assertSee($gift->title)
        ->assertSee('zarezerwowany')
        ->assertDontSee('wujek-staszek@example.com')
        ->assertDontSee('Stanisław');
});

it('creates a pending reservation via the public form', function (): void {
    Mail::fake();
    $tenant = Tenant::factory()->create(['is_public' => true, 'slug' => 'wesele-2030']);
    $gift = Gift::factory()->create([
        'tenant_id' => $tenant->id,
        'status' => Gift::STATUS_AVAILABLE,
    ]);

    $response = $this->from('/'.$tenant->slug)->post(
        "/{$tenant->slug}/gifts/{$gift->id}/reserve",
        [
            'email' => 'gosc@example.com',
            'name' => 'Anna',
            'intent' => 'reserve',
        ],
    );

    $response->assertRedirect('/'.$tenant->slug)->assertSessionHas('status');

    expect(GiftReservation::query()->where('gift_id', $gift->id)->count())->toBe(1);
    Mail::assertQueued(GiftReservationVerifyMail::class);
});

it('validates email on the reservation form', function (): void {
    $tenant = Tenant::factory()->create(['is_public' => true, 'slug' => 'bad-email']);
    $gift = Gift::factory()->create(['tenant_id' => $tenant->id]);

    $this->from('/'.$tenant->slug)
        ->post("/{$tenant->slug}/gifts/{$gift->id}/reserve", [
            'email' => 'not-an-email',
            'intent' => 'reserve',
        ])
        ->assertSessionHasErrors('email');
});

it('does not let a guest reserve a gift from another tenant', function (): void {
    $tenantA = Tenant::factory()->create(['is_public' => true, 'slug' => 'lista-a']);
    $tenantB = Tenant::factory()->create(['is_public' => true, 'slug' => 'lista-b']);
    $giftOfB = Gift::factory()->create(['tenant_id' => $tenantB->id]);

    $this->from('/'.$tenantA->slug)
        ->post("/{$tenantA->slug}/gifts/{$giftOfB->id}/reserve", [
            'email' => 'attacker@example.com',
            'intent' => 'reserve',
        ])
        ->assertNotFound();
});
