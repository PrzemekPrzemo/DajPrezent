<?php

declare(strict_types=1);

use App\Domain\Billing\Models\Package;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wishlist\Models\Gift;
use App\Models\User;

beforeEach(function (): void {
    $this->owner = User::factory()->create();
    $this->tenant = Tenant::factory()->create(['owner_user_id' => $this->owner->id]);
    $this->stranger = User::factory()->create();

    Subscription::factory()->create([
        'tenant_id' => $this->tenant->id,
        'package_id' => Package::factory()->create(['gift_limit' => 200])->id,
        'status' => 'active',
        'paid_at' => now(),
        'expires_at' => now()->addMonths(9),
    ]);
});

it('renders the bookmarklet landing page with a javascript: snippet', function (): void {
    $this->actingAs($this->owner)
        ->get('/panel/bookmarklet')
        ->assertOk()
        ->assertSee('Bookmarklet', false)
        ->assertSee('javascript:(function()', false)
        ->assertSee('Dodaj do DajPrezent.pl', false);
});

it('redirects guests away from the import page', function (): void {
    $this->get('/panel/bookmarklet/import?url=https://example.com/&title=Test')
        ->assertRedirect('/login');
});

it('prefills the import form from OpenGraph query string', function (): void {
    $this->actingAs($this->owner)
        ->get('/panel/bookmarklet/import?url=https%3A%2F%2Fshop.example%2Fphone&title=Smartfon+ABC&price=999%2C99')
        ->assertOk()
        ->assertSee('Smartfon ABC', false)
        ->assertSee('https://shop.example/phone', false)
        ->assertSee('value="999.99"', false);
});

it('creates a gift via the bookmarklet form on an owned tenant', function (): void {
    $this->actingAs($this->owner)
        ->from('/panel/bookmarklet/import')
        ->post('/panel/bookmarklet/import', [
            'tenant_id' => $this->tenant->id,
            'title' => 'Słuchawki bezprzewodowe',
            'url' => 'https://shop.example/headphones',
            'price_pln' => '349.50',
            'priority' => 2,
        ])
        ->assertRedirect(route('owner.gifts.index', $this->tenant));

    $gift = Gift::query()->where('tenant_id', $this->tenant->id)->firstOrFail();
    expect($gift->title)->toBe('Słuchawki bezprzewodowe')
        ->and($gift->url)->toBe('https://shop.example/headphones')
        ->and($gift->price_pln_gr)->toBe(34950);
});

it('refuses to add a gift to a stranger tenant', function (): void {
    $foreign = Tenant::factory()->create(['owner_user_id' => $this->stranger->id]);

    $this->actingAs($this->owner)
        ->post('/panel/bookmarklet/import', [
            'tenant_id' => $foreign->id,
            'title' => 'Hack',
            'priority' => 2,
        ])
        ->assertForbidden();

    expect(Gift::query()->where('tenant_id', $foreign->id)->count())->toBe(0);
});

it('normalizes Polish currency formatting in prefilled price', function (string $raw, string $expected): void {
    $this->actingAs($this->owner)
        ->get('/panel/bookmarklet/import?price='.urlencode($raw))
        ->assertOk()
        ->assertSee('value="'.$expected.'"', false);
})->with([
    ['199,99 zł', '199.99'],
    ['1 999,00 PLN', '1999.00'],
    ['$19.99', '19.99'],
    ['$1,234.50', '1234.50'],
]);
