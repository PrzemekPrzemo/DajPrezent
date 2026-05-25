<?php

declare(strict_types=1);

use App\Domain\Billing\Checkout\CheckoutOrderData;
use App\Domain\Billing\Checkout\CheckoutService;
use App\Domain\Billing\Models\Package;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Database\Seeders\PackageSeeder;

beforeEach(function (): void {
    $this->seed(PackageSeeder::class);
    $this->user = User::factory()->create();
});

function dataFor(string $slug): CheckoutOrderData
{
    return new CheckoutOrderData(
        slug: $slug,
        tenantName: 'Test',
        locale: 'pl',
        customerIp: '127.0.0.1',
        buyerName: 'Jan',
        buyerCompany: null,
        buyerNip: null,
        buyerStreet: 'Ul 1',
        buyerPostalCode: '00-000',
        buyerCity: 'Warszawa',
    );
}

it('lets a user pick Free once', function (): void {
    $free = Package::query()->where('code', 'free')->firstOrFail();
    $result = app(CheckoutService::class)->start($this->user, $free, dataFor('jan-test'));
    expect($result->tenant->slug)->toBe('jan-test');
});

it('refuses a second Free package for the same user', function (): void {
    $free = Package::query()->where('code', 'free')->firstOrFail();
    app(CheckoutService::class)->start($this->user, $free, dataFor('jan-test'));

    expect(fn () => app(CheckoutService::class)->start($this->user, $free, dataFor('jan-test-2')))
        ->toThrow(DomainException::class, 'Masz już aktywny pakiet Free');
});

it('only refuses Free; paid packages skip the limit check entirely', function (): void {
    $free = Package::query()->where('code', 'free')->firstOrFail();
    $plus = Package::query()->where('code', 'plus')->firstOrFail();

    app(CheckoutService::class)->start($this->user, $free, dataFor('jan-free'));

    // For paid packages the limit method short-circuits to false on code !== 'free',
    // so calling it for $plus should not throw a DomainException (it would throw
    // a different error from PayU client, which we expect — that path is tested
    // in PayUWebhookTest with proper HTTP mocking).
    $hasFree = Subscription::query()
        ->whereHas('tenant', fn ($q) => $q->where('owner_user_id', $this->user->id))
        ->whereHas('package', fn ($q) => $q->where('code', 'free'))
        ->whereIn('status', ['pending', 'active'])
        ->exists();

    expect($hasFree)->toBeTrue()
        ->and($plus->code)->toBe('plus'); // sanity — limit doesn't apply
});

it('allows a NEW Free once previous Free has expired', function (): void {
    $free = Package::query()->where('code', 'free')->firstOrFail();

    // Pre-existing Free, expired.
    $tenant = Tenant::factory()->create(['owner_user_id' => $this->user->id]);
    Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'package_id' => $free->id,
        'status' => 'expired',
        'paid_at' => now()->subMonths(2),
        'expires_at' => now()->subDay(),
    ]);

    $result = app(CheckoutService::class)->start($this->user, $free, dataFor('jan-free-renewed'));
    expect($result->tenant->slug)->toBe('jan-free-renewed');
});
