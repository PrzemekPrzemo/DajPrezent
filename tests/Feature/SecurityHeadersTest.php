<?php

declare(strict_types=1);

it('emits conservative security headers on every response', function (): void {
    $response = $this->get('/');

    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($response->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN')
        ->and($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin');

    $permissions = (string) $response->headers->get('Permissions-Policy');
    expect($permissions)->toContain('camera=()')
        ->and($permissions)->toContain('microphone=()');
});

it('emits a Content-Security-Policy that permits our own inline scripts', function (): void {
    $csp = (string) $this->get('/')->headers->get('Content-Security-Policy');

    expect($csp)
        ->toContain("default-src 'self'")
        ->toContain("script-src 'self' 'unsafe-inline' https://unpkg.com")
        ->toContain("form-action 'self' https://secure.snd.payu.com");
});

it('does not emit HSTS on plain HTTP in non-production', function (): void {
    $response = $this->get('/');

    expect($response->headers->get('Strict-Transport-Security'))->toBeNull();
});
