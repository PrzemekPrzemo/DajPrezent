<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\PolishResetPasswordNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

it('shows the forgot-password form', function (): void {
    $this->get('/password/forgot')
        ->assertOk()
        ->assertSee('Reset hasła');
});

it('sends a Polish-branded reset link to a known email', function (): void {
    Notification::fake();
    $user = User::factory()->create(['email' => 'owner@example.com']);

    $this->post('/password/forgot', ['email' => 'owner@example.com'])
        ->assertRedirect()
        ->assertSessionHas('status');

    Notification::assertSentTo($user, PolishResetPasswordNotification::class);
});

it('does not leak whether an email is registered', function (): void {
    Notification::fake();

    $this->post('/password/forgot', ['email' => 'nobody@example.com'])
        ->assertRedirect()
        ->assertSessionHas('status');

    Notification::assertNothingSent();
});

it('renders the reset form with token + email', function (): void {
    $this->get('/password/reset/abc123?email=test%40example.com')
        ->assertOk()
        ->assertSee('test@example.com', false)
        ->assertSee('abc123', false);
});

it('resets the password with a valid token', function (): void {
    $user = User::factory()->create([
        'email' => 'owner@example.com',
        'password' => Hash::make('old-password-1'),
    ]);

    // Generate a real token via the broker.
    $token = Password::createToken($user);

    $this->post('/password/reset', [
        'token' => $token,
        'email' => 'owner@example.com',
        'password' => 'new-secure-password',
        'password_confirmation' => 'new-secure-password',
    ])->assertRedirect(route('login'));

    $user->refresh();
    expect(Hash::check('new-secure-password', $user->password))->toBeTrue()
        ->and(Hash::check('old-password-1', $user->password))->toBeFalse();
});

it('rejects an invalid token', function (): void {
    $user = User::factory()->create(['email' => 'owner@example.com']);

    $this->from('/password/reset/bogus?email=owner%40example.com')
        ->post('/password/reset', [
            'token' => 'bogus-token',
            'email' => 'owner@example.com',
            'password' => 'whatever-secure-pass',
            'password_confirmation' => 'whatever-secure-pass',
        ])
        ->assertSessionHasErrors('email');
});

it('requires the password confirmation to match', function (): void {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $this->post('/password/reset', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'one-password-1',
        'password_confirmation' => 'mismatched-pass-2',
    ])->assertSessionHasErrors('password');
});
