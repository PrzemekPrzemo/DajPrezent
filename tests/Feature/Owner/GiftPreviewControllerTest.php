<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Cache::flush();
    $this->user = User::factory()->create();
});

it('returns the OG preview as JSON for an allowlisted shop', function (): void {
    Http::fake([
        'allegro.pl/*' => Http::response(<<<'HTML'
            <html><head>
                <meta property="og:title" content="Aparat Instax">
                <meta property="og:image" content="https://allegro.pl/img/instax.jpg">
                <meta property="product:price:amount" content="299,00">
            </head></html>
            HTML, 200),
    ]);

    $response = $this->actingAs($this->user)
        ->postJson('/panel/api/gift-preview', ['url' => 'https://allegro.pl/oferta/instax']);

    $response->assertOk()
        ->assertJson([
            'ok' => true,
            'preview' => [
                'title' => 'Aparat Instax',
                'price_pln_gr' => 29900,
                'source' => 'allegro.pl',
            ],
        ]);
});

it('returns fallback=true (not 4xx) for non-allowlisted hosts so the UI can degrade gracefully', function (): void {
    $response = $this->actingAs($this->user)
        ->postJson('/panel/api/gift-preview', ['url' => 'https://obscure-shop.example/p/1']);

    $response->assertOk()
        ->assertJson(['ok' => false, 'fallback' => true]);
});

it('rejects unauthenticated requests', function (): void {
    $this->postJson('/panel/api/gift-preview', ['url' => 'https://allegro.pl/x'])
        ->assertStatus(401);
});

it('rejects invalid URL inputs (422)', function (): void {
    $this->actingAs($this->user)
        ->postJson('/panel/api/gift-preview', ['url' => 'not-a-url'])
        ->assertStatus(422);
});
