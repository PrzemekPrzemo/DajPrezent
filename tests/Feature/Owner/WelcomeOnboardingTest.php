<?php

declare(strict_types=1);

use App\Domain\Billing\Models\Package;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Billing\SubscriptionActivator;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wishlist\Models\Gift;
use App\Models\User;
use App\Notifications\WelcomeOwnerNotification;
use Illuminate\Support\Facades\Notification;

it('dispatches WelcomeOwnerNotification on first activation', function (): void {
    Notification::fake();

    $owner = User::factory()->create();
    $tenant = Tenant::factory()->create(['owner_user_id' => $owner->id]);
    $sub = Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'package_id' => Package::factory()->create(['valid_days' => 270])->id,
        'status' => 'pending',
        'amount_pln_gr' => 0, // free plan
    ]);

    app(SubscriptionActivator::class)->activate($sub);

    Notification::assertSentTo($owner, WelcomeOwnerNotification::class);
});

it('does NOT dispatch welcome a second time (idempotent IPN replays)', function (): void {
    Notification::fake();

    $owner = User::factory()->create();
    $tenant = Tenant::factory()->create(['owner_user_id' => $owner->id]);
    $sub = Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'package_id' => Package::factory()->create(['valid_days' => 270])->id,
        'status' => 'pending',
        'amount_pln_gr' => 9900,
    ]);

    app(SubscriptionActivator::class)->activate($sub);
    app(SubscriptionActivator::class)->activate($sub->fresh()); // replay

    Notification::assertSentToTimes($owner, WelcomeOwnerNotification::class, 1);
});

it('shows the onboarding tour markup only when tenant has no gifts', function (): void {
    $owner = User::factory()->create();
    $fresh = Tenant::factory()->create(['owner_user_id' => $owner->id, 'name' => 'Fresh List']);

    $body = (string) $this->actingAs($owner)->get('/panel')->assertOk()->getContent();

    expect($body)->toContain('dpTour')->toContain('dp.tour.'.$fresh->id);
});

it('hides the onboarding tour when tenant has gifts', function (): void {
    $owner = User::factory()->create();
    $tenant = Tenant::factory()->create(['owner_user_id' => $owner->id]);
    Gift::factory()->create(['tenant_id' => $tenant->id]);

    $body = (string) $this->actingAs($owner)->get('/panel')->assertOk()->getContent();

    expect($body)->not->toContain('dp.tour.'.$tenant->id);
});

it('renders the share widget with QR + WhatsApp + Messenger + email links', function (): void {
    $owner = User::factory()->create();
    $tenant = Tenant::factory()->create(['owner_user_id' => $owner->id, 'slug' => 'kasia-30']);

    $body = (string) $this->actingAs($owner)->get('/panel')->assertOk()->getContent();

    expect($body)
        ->toContain('qr.svg')
        ->toContain('wa.me/?text=')
        ->toContain('facebook.com/sharer')
        ->toContain('mailto:?subject=')
        ->toContain('Pobierz QR');
});
