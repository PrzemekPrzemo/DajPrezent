<?php

declare(strict_types=1);

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wishlist\Models\Gift;

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
