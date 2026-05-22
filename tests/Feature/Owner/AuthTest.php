<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('shows the login form', function (): void {
    $this->get('/login')->assertOk()->assertSee('Zaloguj');
});

it('logs in a user with correct credentials', function (): void {
    User::factory()->create([
        'email' => 'owner@example.com',
        'password' => Hash::make('correct-horse-battery-staple'),
    ]);

    $this->post('/login', [
        'email' => 'owner@example.com',
        'password' => 'correct-horse-battery-staple',
    ])->assertRedirect('/panel');

    expect(auth()->check())->toBeTrue();
});

it('rejects bad credentials', function (): void {
    User::factory()->create([
        'email' => 'owner@example.com',
        'password' => Hash::make('right'),
    ]);

    $this->from('/login')->post('/login', [
        'email' => 'owner@example.com',
        'password' => 'wrong',
    ])->assertRedirect('/login')->assertSessionHasErrors('email');

    expect(auth()->check())->toBeFalse();
});

it('redirects guests to login when accessing /panel', function (): void {
    $this->get('/panel')->assertRedirect('/login');
});

it('logs out and clears the session', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/logout')
        ->assertRedirect('/');

    expect(auth()->check())->toBeFalse();
});
