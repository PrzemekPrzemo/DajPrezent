<?php

declare(strict_types=1);

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wishlist\Models\Gift;
use Database\Seeders\PackageSeeder;

it('emits OpenGraph meta on a public list page', function (): void {
    $tenant = Tenant::factory()->create([
        'is_public' => true,
        'slug' => 'seo-test',
        'name' => 'Anna i Tomek',
    ]);

    $this->get('/'.$tenant->slug)
        ->assertOk()
        ->assertSee('<meta property="og:site_name" content="DajPrezent.pl">', false)
        ->assertSee('<meta property="og:type" content="website">', false)
        ->assertSee('Anna i Tomek — lista prezentów', false)
        ->assertSee('canonical', false);
});

it('defaults tenant lists to noindex', function (): void {
    $tenant = Tenant::factory()->create(['is_public' => true, 'slug' => 'private-list']);

    $this->get('/'.$tenant->slug)
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex,follow">', false);
});

it('uses the first gift image as og:image when available', function (): void {
    $tenant = Tenant::factory()->create(['is_public' => true, 'slug' => 'cover-list']);
    Gift::factory()->create([
        'tenant_id' => $tenant->id,
        'image_path' => 'gifts/'.$tenant->id.'/cover.jpg',
    ]);

    $this->get('/'.$tenant->slug)
        ->assertOk()
        ->assertSee('og:image', false)
        ->assertSee('twitter:card" content="summary_large_image"', false)
        ->assertSee('gifts/'.$tenant->id.'/cover.jpg', false);
});

it('exposes a sitemap.xml with indexable URLs', function (): void {
    $res = $this->get('/sitemap.xml')->assertOk();
    expect($res->headers->get('Content-Type'))->toContain('application/xml');
    expect((string) $res->getContent())
        ->toContain('<urlset')
        ->toContain('/pakiety')
        ->toContain('/regulamin')
        ->toContain('/polityka-prywatnosci')
        ->toContain('/faq');
});

it('serves robots.txt with disallow for private paths', function (): void {
    $content = file_get_contents(public_path('robots.txt')) ?: '';
    expect($content)
        ->toContain('Disallow: /panel/')
        ->toContain('Disallow: /admin/')
        ->toContain('Disallow: /webhooks/')
        ->toContain('Sitemap: https://dajprezent.pl/sitemap.xml');
});

it('renders a branded 404 page for unknown URLs', function (): void {
    $this->get('/this-slug-does-not-exist')
        ->assertNotFound()
        ->assertSee('404 — nic tu nie ma')
        ->assertSee('noindex', false);
});

it('renders the branded 410 page when a list is expired', function (): void {
    $tenant = Tenant::factory()->create([
        'is_public' => true,
        'expires_at' => now()->subDay(),
        'slug' => 'wygasla-lista',
    ]);

    $this->get('/'.$tenant->slug)
        ->assertStatus(410)
        ->assertSee('Ta lista już nie istnieje');
});

it('emits Organization + FAQPage JSON-LD on landing', function (): void {
    $body = (string) $this->get('/')->assertOk()->getContent();
    expect($body)
        ->toContain('application/ld+json')
        ->toContain('"@type": "Organization"')
        ->toContain('"@type": "FAQPage"')
        ->toContain('Sendormeco Holding');
});

it('emits ItemList of Offers + BreadcrumbList on /pakiety', function (): void {
    $this->seed(PackageSeeder::class);

    $body = (string) $this->get('/pakiety')->assertOk()->getContent();
    expect($body)
        ->toContain('"@type": "ItemList"')
        ->toContain('"@type": "Offer"')
        ->toContain('"@type": "BreadcrumbList"')
        ->toContain('"priceCurrency": "PLN"');
});

it('emits FAQPage JSON-LD on /faq', function (): void {
    $body = (string) $this->get('/faq')->assertOk()->getContent();
    expect($body)
        ->toContain('"@type": "FAQPage"')
        ->toContain('"@type": "Question"');
});

it('serves the default OG PNG with correct headers and magic bytes', function (): void {
    $res = $this->get('/og.png')->assertOk();
    expect($res->headers->get('Content-Type'))->toBe('image/png');
    expect($res->headers->get('Cache-Control'))->toContain('public');
    // PNG magic: 89 50 4E 47 0D 0A 1A 0A
    expect(substr((string) $res->getContent(), 0, 8))->toBe("\x89PNG\r\n\x1a\n");
});

it('serves a per-tenant OG PNG by slug', function (): void {
    $tenant = Tenant::factory()->create(['slug' => 'og-foo', 'name' => 'Anna i Tomek']);
    $res = $this->get('/og/list/'.$tenant->slug)->assertOk();
    expect($res->headers->get('Content-Type'))->toBe('image/png');
    expect(substr((string) $res->getContent(), 0, 4))->toBe("\x89PNG");
});

it('renders landing variants with branded H1 + correct CTA', function (): void {
    foreach ([
        '/lista-prezentow-na-urodziny' => ['Lista prezentów na urodziny', 'urodziny'],
        '/lista-prezentow-slubnych' => ['Lista prezentów ślubnych', 'wesele'],
        '/prezent-na-rocznice' => ['Prezent na rocznicę', 'rocznica'],
    ] as $url => [$h1, $chip]) {
        $body = (string) $this->get($url)->assertOk()->getContent();
        expect($body)
            ->toContain($h1)
            ->toContain($chip)
            ->toContain('"@type": "BreadcrumbList"');
    }
});

it('includes landing variants + lastmod/changefreq in sitemap', function (): void {
    $body = (string) $this->get('/sitemap.xml')->assertOk()->getContent();
    expect($body)
        ->toContain('/lista-prezentow-na-urodziny')
        ->toContain('/lista-prezentow-slubnych')
        ->toContain('/prezent-na-rocznice')
        ->toContain('<changefreq>weekly</changefreq>')
        ->toContain('<lastmod>');
});
