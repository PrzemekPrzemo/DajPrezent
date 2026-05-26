<?php

declare(strict_types=1);

use App\Domain\Settings\SettingsRepository;

beforeEach(function (): void {
    // Ensure clean state — each test sets its own seo.* values.
    DB::table('app_settings')->where('key', 'like', 'seo.%')->delete();
    Cache::flush();
});

it('does not include gtag when GA4 ID is empty', function (): void {
    $body = (string) $this->get('/')->assertOk()->getContent();
    expect($body)
        ->not->toContain('googletagmanager.com')
        ->not->toContain('plausible.io');
});

it('includes gtag.js when GA4 ID is set in settings', function (): void {
    app(SettingsRepository::class)->set('seo.ga4_id', 'G-MEFY74Q5GK');

    $body = (string) $this->get('/')->assertOk()->getContent();
    expect($body)
        ->toContain('googletagmanager.com/gtag/js?id=G-MEFY74Q5GK')
        ->toContain("gtag('config'")
        ->toContain('G-MEFY74Q5GK')
        ->toContain('anonymize_ip');
});

it('includes Google Ads config when ads ID is set alongside GA4', function (): void {
    $s = app(SettingsRepository::class);
    $s->set('seo.ga4_id', 'G-MEFY74Q5GK');
    $s->set('seo.google_ads_id', 'AW-123456789');

    $body = (string) $this->get('/')->assertOk()->getContent();
    expect($body)
        ->toContain('AW-123456789')
        ->toContain('G-MEFY74Q5GK');
});

it('includes Plausible script when domain is set', function (): void {
    app(SettingsRepository::class)->set('seo.plausible_domain', 'dajprezent.pl');

    $body = (string) $this->get('/')->assertOk()->getContent();
    expect($body)
        ->toContain('plausible.io/js/script.js')
        ->toContain('data-domain="dajprezent.pl"');
});

it('emits Google Search Console verification meta when set', function (): void {
    app(SettingsRepository::class)->set('seo.gsc_verification', 'uPgaT3kf4N9abcdefXYZ123');

    $this->get('/')->assertOk()
        ->assertSee('<meta name="google-site-verification" content="uPgaT3kf4N9abcdefXYZ123">', false);
});

it('CSP whitelists Google Analytics domains when GA4 is enabled', function (): void {
    app(SettingsRepository::class)->set('seo.ga4_id', 'G-XYZ');

    $resp = $this->get('/')->assertOk();
    $csp = (string) $resp->headers->get('Content-Security-Policy');

    expect($csp)
        ->toContain('https://www.googletagmanager.com')
        ->toContain('https://www.google-analytics.com');
});

it('CSP stays strict when no analytics integration is enabled', function (): void {
    $resp = $this->get('/')->assertOk();
    $csp = (string) $resp->headers->get('Content-Security-Policy');

    expect($csp)
        ->not->toContain('googletagmanager.com')
        ->not->toContain('plausible.io');
});
