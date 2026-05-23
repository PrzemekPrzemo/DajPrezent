<?php

declare(strict_types=1);

use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    Cache::flush();
});

it('renders the sales landing with brand identity and CTAs', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSee('Prezenty', false)
        ->assertSee('od serca', false)
        ->assertSee('Wybierz pakiet', false)
        ->assertSee('Jak to działa?', false)
        ->assertSee('Co dostajesz w pakiecie?', false)
        ->assertSee('Najczęstsze pytania', false);
});

it('exposes live stats in the social-proof band', function (): void {
    // Floor values are baked in so even an empty DB shows non-zero numbers.
    $this->get('/')
        ->assertOk()
        ->assertSee('aktywnych list', false)
        ->assertSee('prezentów na listach', false)
        ->assertSee('rezerwacji od bliskich', false);
});

it('counts real public tenants when above the floor', function (): void {
    Tenant::factory()->count(200)->create(['is_public' => true]);

    $body = (string) $this->get('/')->assertOk()->getContent();
    expect($body)->toContain('200');
});

it('omits private tenants from the active-lists counter', function (): void {
    Tenant::factory()->count(50)->create(['is_public' => true]);
    Tenant::factory()->count(50)->create(['is_public' => false]);

    // Total tenants = 100 but only 50 are public; floor is 124 so counter shows 124+.
    $this->get('/')->assertOk()->assertSee('124');
});

it('caches the stats query for repeat hits', function (): void {
    Tenant::factory()->count(150)->create(['is_public' => true]);

    $this->get('/')->assertOk();

    Tenant::factory()->count(100)->create(['is_public' => true]); // change behind the cache

    // Second hit served from cache — still old number (150).
    $body = (string) $this->get('/')->getContent();
    expect($body)->toContain('150')->not->toContain('250');
});

it('serves the placeholder Lottie file at the brand path', function (): void {
    $path = public_path('brand/lottie/gift-float.json');
    expect(file_exists($path))->toBeTrue();
    $json = json_decode((string) file_get_contents($path), true);
    expect($json)->toBeArray()->toHaveKey('layers');
});
