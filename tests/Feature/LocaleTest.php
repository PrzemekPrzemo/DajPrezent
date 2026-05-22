<?php

declare(strict_types=1);

use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;

it('defaults to Polish when no preference is set', function (): void {
    $this->actingAs(User::factory()->create())
        ->get('/panel')
        ->assertOk()
        ->assertSee('lang="pl"', false)
        ->assertSee('Wyloguj');
});

it('persists locale preference in the session', function (): void {
    $this->from('/login')->post('/locale/en')->assertRedirect('/login');

    expect(session('locale'))->toBe('en');
});

it('renders English copy after switching', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['locale' => 'en'])
        ->get('/panel')
        ->assertOk()
        ->assertSee('lang="en"', false)
        ->assertSee('Log out');
});

it('falls back to default when given an unsupported locale', function (): void {
    $this->from('/login')->post('/locale/de')->assertRedirect('/login');

    expect(session('locale'))->toBeNull();
});

it('honours the tenant locale on public list pages regardless of session', function (): void {
    $tenant = Tenant::factory()->create([
        'is_public' => true,
        'slug' => 'tenant-en',
        'locale' => 'en',
    ]);

    $this->withSession(['locale' => 'pl'])
        ->get('/'.$tenant->slug)
        ->assertOk()
        ->assertSee('lang="en"', false);
});
