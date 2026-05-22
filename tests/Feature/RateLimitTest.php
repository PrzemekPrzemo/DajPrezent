<?php

declare(strict_types=1);

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wishlist\Models\Gift;
use App\Models\User;

it('throttles login attempts after 10/minute from the same IP', function (): void {
    User::factory()->create(['email' => 'me@example.com']);

    // 10 wrong attempts allowed.
    for ($i = 0; $i < 10; $i++) {
        $this->from('/login')->post('/login', ['email' => 'me@example.com', 'password' => 'wrong'])
            ->assertSessionHasErrors('email');
    }

    // 11th hits the throttle.
    $this->from('/login')->post('/login', ['email' => 'me@example.com', 'password' => 'wrong'])
        ->assertStatus(429);
});

it('throttles signups after 10/hour', function (): void {
    for ($i = 0; $i < 10; $i++) {
        $this->post('/register', [
            'name' => 'X', 'email' => "u{$i}@example.com",
            'password' => 'p-a-s-s-1', 'password_confirmation' => 'p-a-s-s-1',
            'terms' => '1',
        ])->assertRedirect();
    }

    $this->post('/register', [
        'name' => 'X', 'email' => 'eleventh@example.com',
        'password' => 'p-a-s-s-1', 'password_confirmation' => 'p-a-s-s-1',
        'terms' => '1',
    ])->assertStatus(429);
});

it('throttles reservation attempts per (IP, slug) at 5/minute', function (): void {
    $tenant = Tenant::factory()->create(['is_public' => true, 'slug' => 'flood']);
    $gift = Gift::factory()->create(['tenant_id' => $tenant->id]);

    for ($i = 0; $i < 5; $i++) {
        $this->from('/'.$tenant->slug)
            ->post("/{$tenant->slug}/gifts/{$gift->id}/reserve", [
                'email' => "g{$i}@example.com", 'intent' => 'reserve',
            ])
            ->assertRedirect();
    }

    $this->from('/'.$tenant->slug)
        ->post("/{$tenant->slug}/gifts/{$gift->id}/reserve", [
            'email' => 'g6@example.com', 'intent' => 'reserve',
        ])
        ->assertStatus(429);
});

it('reservation throttle is per-slug — flooding one list does NOT block another', function (): void {
    $a = Tenant::factory()->create(['is_public' => true, 'slug' => 'lista-a']);
    $b = Tenant::factory()->create(['is_public' => true, 'slug' => 'lista-b']);
    $giftA = Gift::factory()->create(['tenant_id' => $a->id]);
    $giftB = Gift::factory()->create(['tenant_id' => $b->id]);

    for ($i = 0; $i < 5; $i++) {
        $this->from('/lista-a')->post("/lista-a/gifts/{$giftA->id}/reserve", [
            'email' => "x{$i}@example.com", 'intent' => 'reserve',
        ])->assertRedirect();
    }
    // 6th to lista-a → throttled.
    $this->from('/lista-a')->post("/lista-a/gifts/{$giftA->id}/reserve", [
        'email' => 'over@example.com', 'intent' => 'reserve',
    ])->assertStatus(429);

    // But lista-b is unaffected.
    $this->from('/lista-b')->post("/lista-b/gifts/{$giftB->id}/reserve", [
        'email' => 'ok@example.com', 'intent' => 'reserve',
    ])->assertRedirect();
});
