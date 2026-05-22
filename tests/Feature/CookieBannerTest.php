<?php

declare(strict_types=1);
use App\Models\User;

it('renders the cookie banner on public pages', function (): void {
    $this->get('/')->assertOk()->assertSee('cookie-banner', false)->assertSee('Polityka prywatności');
});

it('renders the cookie banner on the panel layout', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/panel')->assertOk()
        ->assertSee('cookie-banner', false)
        ->assertSee('Rozumiem');
});
