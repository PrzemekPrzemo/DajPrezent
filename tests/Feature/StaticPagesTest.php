<?php

declare(strict_types=1);

it('renders /regulamin', function (): void {
    $this->get('/regulamin')->assertOk()
        ->assertSee('Regulamin serwisu DajPrezent.pl')
        ->assertSee('5252866457')
        ->assertSee('prawo odstąpienia', false);
});

it('renders /polityka-prywatnosci', function (): void {
    $this->get('/polityka-prywatnosci')->assertOk()
        ->assertSee('Polityka prywatności')
        ->assertSee('Administrator')
        ->assertSee('RODO');
});

it('renders /faq', function (): void {
    $this->get('/faq')->assertOk()
        ->assertSee('najczęstsze pytania', false)
        ->assertSee('Czy gość, który zarezerwuje prezent');
});

it('renders /kontakt', function (): void {
    $this->get('/kontakt')->assertOk()
        ->assertSee('Kontakt')
        ->assertSee('kontakt@dajprezent.pl')
        ->assertSee('Sendormeco Holding');
});

it('exposes footer links from the welcome page', function (): void {
    $this->get('/')->assertOk()
        ->assertSee('/regulamin', false)
        ->assertSee('/polityka-prywatnosci', false)
        ->assertSee('/faq', false)
        ->assertSee('/kontakt', false);
});

it('blacklists slugs that would collide with explicit routes', function (): void {
    foreach (['pakiety', 'buy', 'regulamin', 'polityka-prywatnosci', 'email', 'password', 'locale', 'r'] as $reserved) {
        $blacklist = (array) config('packages.reserved_slugs');
        expect(in_array($reserved, $blacklist, true))
            ->toBeTrue("Expected reserved_slugs to contain '{$reserved}'.");
    }
});
