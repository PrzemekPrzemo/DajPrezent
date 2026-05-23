<?php

declare(strict_types=1);

use App\Domain\Wishlist\Import\OpenGraphScraper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Cache::flush();
    $this->scraper = app(OpenGraphScraper::class);
});

it('admits allowlisted hosts (with and without subdomains)', function (string $url): void {
    expect($this->scraper->isAllowed($url))->toBeTrue();
})->with([
    'https://allegro.pl/oferta/123',
    'https://www.allegro.pl/oferta/123',
    'https://www.empik.com/ksiazka/4321',
    'https://x-kom.pl/p/abc',
    'https://www.zalando.pl/buty-123',
]);

it('refuses hosts outside the allowlist', function (string $url): void {
    expect($this->scraper->isAllowed($url))->toBeFalse();
})->with([
    'https://random-shop.example/x',
    'http://internal',
    'ftp://allegro.pl/x',
    'not a url at all',
]);

it('refuses to scrape outside-allowlist URLs', function (): void {
    $this->scraper->preview('https://evil.example/');
})->throws(RuntimeException::class, 'nie jest jeszcze obsługiwany');

it('parses og:title / og:image / product:price:amount from a stub response', function (): void {
    Http::fake([
        'allegro.pl/*' => Http::response(<<<'HTML'
            <html><head>
                <title>Allegro - Aparat Fujifilm Instax Mini 11</title>
                <meta property="og:title" content="Aparat Fujifilm Instax Mini 11">
                <meta property="og:image" content="/img/instax.jpg">
                <meta property="og:description" content="Natychmiastowy aparat fotograficzny.">
                <meta property="product:price:amount" content="299,99">
            </head><body></body></html>
            HTML, 200),
    ]);

    $preview = $this->scraper->preview('https://allegro.pl/oferta/instax-mini');

    expect($preview->title)->toBe('Aparat Fujifilm Instax Mini 11')
        ->and($preview->pricePlnGr)->toBe(29999)
        ->and($preview->imageUrl)->toBe('https://allegro.pl/img/instax.jpg')
        ->and($preview->source)->toBe('allegro.pl')
        ->and($preview->description)->toContain('aparat');
});

it('falls back to <title> when og:title is missing', function (): void {
    Http::fake([
        'empik.com/*' => Http::response('<html><head><title>Książka — Empik</title></head></html>', 200),
    ]);

    $preview = $this->scraper->preview('https://empik.com/ksiazka/123');

    expect($preview->title)->toBe('Książka — Empik')
        ->and($preview->pricePlnGr)->toBeNull();
});

it('caches the preview for repeat URLs', function (): void {
    Http::fake([
        'zalando.pl/*' => Http::response('<html><head><meta property="og:title" content="A"></head></html>', 200),
    ]);

    $url = 'https://zalando.pl/buty-1';
    $this->scraper->preview($url);
    $this->scraper->preview($url);

    Http::assertSentCount(1);
});

it('rejects malformed URLs upstream', function (): void {
    $this->scraper->preview('javascript:alert(1)');
})->throws(RuntimeException::class);
