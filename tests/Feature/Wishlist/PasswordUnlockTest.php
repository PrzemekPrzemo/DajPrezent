<?php

declare(strict_types=1);

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Wishlist\Models\Gift;
use Illuminate\Support\Facades\Hash;

it('redirects from a password-protected list to the unlock form', function (): void {
    $tenant = Tenant::factory()->create([
        'is_public' => true,
        'password_hash' => Hash::make('sekret'),
        'slug' => 'chroniona',
    ]);

    $this->get('/'.$tenant->slug)
        ->assertRedirect('/'.$tenant->slug.'/unlock');
});

it('shows the unlock form on GET /{slug}/unlock', function (): void {
    $tenant = Tenant::factory()->create([
        'is_public' => true,
        'password_hash' => Hash::make('sekret'),
        'slug' => 'chroniona',
        'name' => 'Lista Ani',
    ]);

    $this->get('/'.$tenant->slug.'/unlock')
        ->assertOk()
        ->assertSee('Lista Ani')
        ->assertSee('hasło', false);
});

it('does not bother showing the form if the tenant has no password', function (): void {
    $tenant = Tenant::factory()->create([
        'is_public' => true,
        'password_hash' => null,
        'slug' => 'otwarta',
    ]);

    $this->get('/'.$tenant->slug.'/unlock')
        ->assertRedirect('/'.$tenant->slug);
});

it('unlocks the list with the correct password and lets the user see gifts', function (): void {
    $tenant = Tenant::factory()->create([
        'is_public' => true,
        'password_hash' => Hash::make('sekret-123'),
        'slug' => 'chroniona',
    ]);
    Gift::factory()->create(['tenant_id' => $tenant->id, 'title' => 'Aparat']);

    $this->post('/'.$tenant->slug.'/unlock', ['password' => 'sekret-123'])
        ->assertRedirect('/'.$tenant->slug);

    $this->get('/'.$tenant->slug)
        ->assertOk()
        ->assertSee('Aparat');
});

it('rejects an incorrect password', function (): void {
    $tenant = Tenant::factory()->create([
        'is_public' => true,
        'password_hash' => Hash::make('right'),
        'slug' => 'chroniona',
    ]);

    $this->from('/'.$tenant->slug.'/unlock')
        ->post('/'.$tenant->slug.'/unlock', ['password' => 'wrong'])
        ->assertRedirect('/'.$tenant->slug.'/unlock')
        ->assertSessionHasErrors('password');

    // Still redirected to /unlock on the next GET because we never set the session flag.
    $this->get('/'.$tenant->slug)->assertRedirect('/'.$tenant->slug.'/unlock');
});

it('rate-limits brute-force attempts on the same IP', function (): void {
    $tenant = Tenant::factory()->create([
        'is_public' => true,
        'password_hash' => Hash::make('correct'),
        'slug' => 'chroniona',
    ]);

    for ($i = 0; $i < 5; $i++) {
        $this->from('/'.$tenant->slug.'/unlock')
            ->post('/'.$tenant->slug.'/unlock', ['password' => 'bad'])
            ->assertSessionHasErrors('password');
    }

    $this->from('/'.$tenant->slug.'/unlock')
        ->post('/'.$tenant->slug.'/unlock', ['password' => 'correct'])
        ->assertSessionHasErrors('password'); // throttled

    expect(session()->get("tenant.unlocked.{$tenant->id}"))->not->toBe(true);
});
