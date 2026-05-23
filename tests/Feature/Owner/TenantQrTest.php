<?php

declare(strict_types=1);

use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;

beforeEach(function (): void {
    $this->owner = User::factory()->create();
    $this->tenant = Tenant::factory()->create(['owner_user_id' => $this->owner->id, 'slug' => 'kasia-30']);
});

it('returns the QR SVG for an owned tenant', function (): void {
    $response = $this->actingAs($this->owner)
        ->get("/panel/lists/{$this->tenant->id}/qr.svg")
        ->assertOk();

    expect($response->headers->get('Content-Type'))->toContain('image/svg+xml');
    $body = strtolower((string) $response->getContent());
    expect($body)
        ->toContain('<svg')
        ->toContain('</svg>')
        ->toContain('#4f46e5'); // brand purple in the QR foreground
});

it('refuses QR for a stranger tenant', function (): void {
    $stranger = User::factory()->create();
    $foreign = Tenant::factory()->create(['owner_user_id' => $stranger->id]);

    $this->actingAs($this->owner)
        ->get("/panel/lists/{$foreign->id}/qr.svg")
        ->assertForbidden();
});

it('redirects guests to /login', function (): void {
    $this->get("/panel/lists/{$this->tenant->id}/qr.svg")
        ->assertRedirect('/login');
});
