<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\PolishVerifyEmailNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

it('shows the account edit page to logged-in user', function (): void {
    $user = User::factory()->create(['email' => 'me@example.com', 'name' => 'Anna']);

    $this->actingAs($user)
        ->get('/panel/konto')
        ->assertOk()
        ->assertSee('me@example.com')
        ->assertSee('Anna');
});

it('updates name without changing the email', function (): void {
    Notification::fake();
    $user = User::factory()->create(['email' => 'me@example.com', 'name' => 'Stara']);

    $this->actingAs($user)
        ->patch('/panel/konto/profil', [
            'name' => 'Nowa',
            'email' => 'me@example.com',
        ])
        ->assertRedirect(route('owner.account.edit'));

    expect($user->fresh()->name)->toBe('Nowa');
    Notification::assertNothingSent();
});

it('changes email + clears verification + sends a fresh verification mail', function (): void {
    Notification::fake();
    $user = User::factory()->create([
        'email' => 'old@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->patch('/panel/konto/profil', [
            'name' => $user->name,
            'email' => 'new@example.com',
        ])
        ->assertRedirect();

    $user->refresh();
    expect($user->email)->toBe('new@example.com')
        ->and($user->email_verified_at)->toBeNull();

    Notification::assertSentTo($user, PolishVerifyEmailNotification::class);
});

it('rejects taken emails', function (): void {
    User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/panel/konto')
        ->patch('/panel/konto/profil', [
            'name' => $user->name,
            'email' => 'taken@example.com',
        ])
        ->assertSessionHasErrors('email');
});

it('changes password when current password is correct', function (): void {
    $user = User::factory()->create(['password' => Hash::make('old-secret-pass')]);

    $this->actingAs($user)
        ->patch('/panel/konto/haslo', [
            'current_password' => 'old-secret-pass',
            'password' => 'new-secret-pass',
            'password_confirmation' => 'new-secret-pass',
        ])
        ->assertRedirect();

    expect(Hash::check('new-secret-pass', $user->fresh()->password))->toBeTrue();
});

it('rejects password change when current password is wrong', function (): void {
    $user = User::factory()->create(['password' => Hash::make('right')]);

    $this->actingAs($user)
        ->from('/panel/konto')
        ->patch('/panel/konto/haslo', [
            'current_password' => 'wrong',
            'password' => 'new-secret-pass',
            'password_confirmation' => 'new-secret-pass',
        ])
        ->assertSessionHasErrors('current_password');

    expect(Hash::check('right', $user->fresh()->password))->toBeTrue();
});

it('rejects too-short new passwords', function (): void {
    $user = User::factory()->create(['password' => Hash::make('right-pass')]);

    $this->actingAs($user)
        ->from('/panel/konto')
        ->patch('/panel/konto/haslo', [
            'current_password' => 'right-pass',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])
        ->assertSessionHasErrors('password');
});

it('redirects guests away from /panel/konto', function (): void {
    $this->get('/panel/konto')->assertRedirect('/login');
});
