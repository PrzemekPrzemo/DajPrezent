<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\PolishVerifyEmailNotification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

it('sends a verification email on registration', function (): void {
    Notification::fake();

    $this->post('/register', [
        'name' => 'Anna',
        'email' => 'anna@example.com',
        'password' => 'super-secret-123',
        'password_confirmation' => 'super-secret-123',
        'terms' => '1',
    ])->assertRedirect();

    $user = User::query()->where('email', 'anna@example.com')->firstOrFail();
    Notification::assertSentTo($user, PolishVerifyEmailNotification::class);
});

it('redirects unverified users to the verify-email notice page', function (): void {
    $user = User::factory()->create(['email_verified_at' => null]);

    $this->actingAs($user)
        ->get('/email/verify')
        ->assertOk()
        ->assertSee('Potwierdź swój adres e-mail');
});

it('marks the user as verified when the signed URL is hit', function (): void {
    Event::fake();
    $user = User::factory()->create(['email_verified_at' => null]);

    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $this->actingAs($user)
        ->get($url)
        ->assertRedirect(route('owner.dashboard'));

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertDispatched(Verified::class);
});

it('rejects a tampered verification URL', function (): void {
    $user = User::factory()->create(['email_verified_at' => null]);

    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong@email.com')],
    );

    $this->actingAs($user)
        ->get($url)
        ->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('can resend a verification email', function (): void {
    Notification::fake();
    $user = User::factory()->create(['email_verified_at' => null]);

    $this->actingAs($user)
        ->post('/email/verification-notification')
        ->assertRedirect();

    Notification::assertSentTo($user, PolishVerifyEmailNotification::class);
});

it('redirects already-verified users away from the notice page', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->get('/email/verify')
        ->assertRedirect(route('owner.dashboard'));
});
