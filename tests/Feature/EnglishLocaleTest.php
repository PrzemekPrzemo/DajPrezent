<?php

declare(strict_types=1);

use App\Domain\Billing\Models\Package;

beforeEach(function (): void {
    // Set EN as the session locale so subsequent requests render English.
    $this->withSession(['locale' => 'en']);
});

it('renders the landing in English when locale=en', function (): void {
    $this->withSession(['locale' => 'en'])
        ->get('/')
        ->assertOk()
        ->assertSee('Gifts from the heart', false)
        ->assertSee('Choose a plan', false)
        ->assertSee('How does it work?', false)
        ->assertSee('Will the guest who reserves a gift be visible', false)
        ->assertDontSee('Wybierz pakiet', false)
        ->assertDontSee('Jak to działa', false);
});

it('renders the pricing page in English when locale=en', function (): void {
    Package::factory()->create([
        'code' => 'plus', 'name' => 'Plus', 'price_pln_gr' => 6900,
        'valid_days' => 270, 'gift_limit' => 75,
        'features' => ['custom_slug' => true, 'password_protect' => true, 'multiple_lists' => 3],
        'is_active' => true,
    ]);

    $this->withSession(['locale' => 'en'])
        ->get('/pakiety')
        ->assertOk()
        ->assertSee('Choose a plan', false)
        ->assertSee('Most popular', false)
        ->assertSee('Up to 75 gifts', false)
        ->assertSee('Multiple lists (3)', false)
        ->assertSee('Custom list address', false)
        ->assertDontSee('Wybieram', false);
});

it('exposes a PL/EN locale switcher in the public nav', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSee('locale/pl', false)
        ->assertSee('locale/en', false);
});

it('falls back to Polish when no locale is set', function (): void {
    $this->withSession(['locale' => null])
        ->get('/')
        ->assertOk()
        ->assertSee('Prezenty', false)
        ->assertSee('od serca', false);
});

it('returns FAQ_ITEMS_EN when locale=en on landing', function (): void {
    $body = (string) $this->withSession(['locale' => 'en'])->get('/')->assertOk()->getContent();
    expect($body)
        ->toContain('Will the guest who reserves a gift be visible')
        ->toContain('What happens when my plan expires')
        ->not->toContain('Co się dzieje po wygaśnięciu');
});

it('switches locale via POST /locale/{locale} and persists in session', function (): void {
    $this->post('/locale/en')->assertRedirect();
    expect(session('locale'))->toBe('en');

    $this->get('/')->assertSee('Gifts from the heart', false);
});
