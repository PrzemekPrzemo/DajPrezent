<?php

declare(strict_types=1);

/*
 * Smoke tests against config/seller.php. Acts as a regression catch
 * if anyone accidentally drops a field that the regulamin / privacy /
 * KSeF integration depends on.
 */

it('exposes the full KRS-confirmed seller identity in config', function (): void {
    expect(config('seller.legal_name'))->toBe('Sendormeco Holding sp. z o.o.')
        ->and(config('seller.nip'))->toBe('5252866457')
        ->and(config('seller.regon'))->toBe('389194801')
        ->and(config('seller.krs'))->toBe('0000906110');
});

it('exposes a complete registered address', function (): void {
    expect(config('seller.address.street'))->toBe('ul. Złota 75A/7')
        ->and(config('seller.address.postal_code'))->toBe('00-819')
        ->and(config('seller.address.city'))->toBe('Warszawa')
        ->and(config('seller.address.country'))->toBe('PL');
});

it('exposes share capital and registry court (KSH wymogi obrotu)', function (): void {
    expect(config('seller.share_capital_pln'))->toBe(5000)
        ->and((string) config('seller.registry_court'))->toContain('Sąd Rejonowy');
});

it('puts the full company name + KRS + NIP + REGON in the public footer', function (): void {
    $response = $this->get('/')->assertOk();

    expect((string) $response->getContent())
        ->toContain('Sendormeco Holding sp. z o.o.')
        ->toContain('KRS 0000906110')
        ->toContain('NIP 5252866457')
        ->toContain('REGON 389194801')
        ->toContain('Złota 75A/7');
});

it('shows the full identity in regulamin', function (): void {
    $this->get('/regulamin')
        ->assertOk()
        ->assertSee('Sendormeco Holding sp. z o.o.')
        ->assertSee('0000906110')
        ->assertSee('Złota 75A/7')
        ->assertSee('Sąd Rejonowy dla m.st. Warszawy');
});

it('shows the full identity in polityka prywatności', function (): void {
    $this->get('/polityka-prywatnosci')
        ->assertOk()
        ->assertSee('Sendormeco Holding sp. z o.o.')
        ->assertSee('0000906110')
        ->assertSee('389194801');
});
