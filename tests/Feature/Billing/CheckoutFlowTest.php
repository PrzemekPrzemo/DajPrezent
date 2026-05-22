<?php

declare(strict_types=1);

use App\Domain\Billing\Models\Subscription;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Database\Seeders\PackageSeeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Config::set('services.payu.md5_key', 'k');
    $this->seed(PackageSeeder::class);
});

it('lists active packages on /pakiety', function (): void {
    $this->get('/pakiety')
        ->assertOk()
        ->assertSee('Mini')
        ->assertSee('Pro')
        ->assertSee('Wedding Premium')
        ->assertSee('Wybieram');
});

it('redirects guest from /buy/{code} to register with the package in query', function (): void {
    $this->get('/buy/mini')
        ->assertRedirect(route('register', ['package' => 'mini']));
});

it('returns 404 for an unknown package code', function (): void {
    $this->actingAs(User::factory()->create())
        ->get('/buy/nope')
        ->assertNotFound();
});

it('logs in the buyer after registration and routes to the buy form', function (): void {
    $this->post('/register', [
        'package' => 'mini',
        'name' => 'Anna',
        'email' => 'anna@example.com',
        'password' => 'super-secret-pass',
        'password_confirmation' => 'super-secret-pass',
        'terms' => '1',
    ])->assertRedirect(route('public.checkout.buy', ['code' => 'mini']));

    expect(auth()->check())->toBeTrue()
        ->and(User::query()->where('email', 'anna@example.com')->exists())->toBeTrue();
});

it('creates a tenant + pending subscription and redirects the user to PayU', function (): void {
    Http::fake([
        'secure.snd.payu.com/pl/standard/user/oauth/authorize' => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
        'secure.snd.payu.com/api/v2_1/orders' => Http::response([
            'status' => ['statusCode' => 'SUCCESS'],
            'redirectUri' => 'https://secure.snd.payu.com/?orderId=PU-ORD-1',
            'orderId' => 'PU-ORD-1',
            'extOrderId' => 'dajprezent-sub-1',
        ], 302),
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/buy/standard', [
        'slug' => 'urodziny30',
        'name' => 'Lista Ani na 30',
        'locale' => 'pl',
        'terms' => '1',
    ]);

    $response->assertRedirect('https://secure.snd.payu.com/?orderId=PU-ORD-1');

    $tenant = Tenant::query()->where('slug', 'urodziny30')->firstOrFail();
    expect($tenant->is_public)->toBeFalse()
        ->and($tenant->owner_user_id)->toBe($user->id);

    $sub = Subscription::query()->where('tenant_id', $tenant->id)->firstOrFail();
    expect($sub->status)->toBe('pending')
        ->and($sub->payu_order_id)->toBe('PU-ORD-1');
});

it('activates a free plan without calling PayU', function (): void {
    Http::fake();
    $user = User::factory()->create();

    $this->actingAs($user)->post('/buy/free', [
        'slug' => 'wolne-zycie',
        'name' => 'Test',
        'locale' => 'pl',
        'terms' => '1',
    ])->assertRedirect();

    $tenant = Tenant::query()->where('slug', 'wolne-zycie')->firstOrFail();
    expect($tenant->is_public)->toBeTrue()
        ->and($tenant->expires_at)->not->toBeNull();

    $sub = Subscription::query()->where('tenant_id', $tenant->id)->firstOrFail();
    expect($sub->status)->toBe('active');

    Http::assertNothingSent();
});

it('rejects a reserved or invalid slug at checkout', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/buy/free')
        ->post('/buy/free', [
            'slug' => 'admin',
            'name' => 'Test',
            'locale' => 'pl',
            'terms' => '1',
        ])
        ->assertSessionHasErrors('slug');

    expect(Tenant::query()->where('slug', 'admin')->exists())->toBeFalse();
});

it('rejects a slug already in use by another tenant', function (): void {
    Tenant::factory()->create(['slug' => 'zajete']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/buy/free')
        ->post('/buy/free', [
            'slug' => 'zajete',
            'name' => 'Test',
            'locale' => 'pl',
            'terms' => '1',
        ])
        ->assertSessionHasErrors('slug');
});

it('shows a thank-you page on PayU return', function (): void {
    $this->get('/buy/return')->assertOk()->assertSee('Dziękujemy');
});

it('shows a cancellation message when PayU returns with ?error', function (): void {
    $this->get('/buy/return?error=CANCELED')->assertOk()->assertSee('anulowana');
});

it('rolls the tenant into wedding kind for wedding_* packages', function (): void {
    Http::fake([
        'secure.snd.payu.com/pl/standard/user/oauth/authorize' => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
        'secure.snd.payu.com/api/v2_1/orders' => Http::response([
            'status' => ['statusCode' => 'SUCCESS'],
            'redirectUri' => 'https://secure.snd.payu.com/?x=1',
            'orderId' => 'PU-W-1',
        ], 302),
    ]);

    $user = User::factory()->create();
    $this->actingAs($user)->post('/buy/wedding_premium', [
        'slug' => 'anna-i-tomek',
        'name' => 'Anna i Tomek 2026',
        'locale' => 'pl',
        'terms' => '1',
    ])->assertRedirect();

    expect(Tenant::query()->where('slug', 'anna-i-tomek')->first()->kind)->toBe('wedding_premium');
});
